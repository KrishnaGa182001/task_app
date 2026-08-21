# High-Concurrency Ticket Booking System

A Laravel 11 backend API for a high-concurrency event ticket booking system. Built to handle high-demand ticket drops safely, ensuring seats cannot be double-booked across simultaneous requests.

---

## Technical Overview

* **Pessimistic DB Locks**: Uses InnoDB `SELECT ... FOR UPDATE` inside transactions to prevent double booking.
* **Deadlock Prevention**: Seat IDs are sorted in ascending order (`sort($seatIds)`) before locking rows.
* **Payment Idempotency**: Unique database constraints on `transaction_id` prevent duplicate payments.
* **Automatic Expiry**: Pending holds expire after 10 minutes, releasing seats back to available status.
* **Audit Logging**: VIP seat tier changes create immutable records in `seat_audit_logs`.
* **Testing**: Includes unit and feature tests, plus a 100-user parallel process concurrency test.

---

## Tech Stack

* PHP 8.3+
* Laravel 11.x
* MySQL 8+ (InnoDB Engine)
* Laravel Sanctum (API Token Auth)
* jQuery & Bootstrap 5 (Interactive Demo Dashboard)

---

## Quick Setup

### 1. Clone & Install Dependencies
```bash
cd task_app
composer install
```

### 2. Environment Setup
Copy `.env.example` to `.env` and set your MySQL credentials and timezone:
```ini
APP_NAME="Ticket Booking System"
APP_ENV=local
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_booking
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync
```

Generate application key:
```bash
php artisan key:generate
```

### 3. Database Migration & Seeding
Create a MySQL database named `ticket_booking`, then run:
```bash
php artisan migrate:fresh --seed
```

This seeds:
* **Admin**: `admin@example.com` / `password`
* **Test Users**: `user1@example.com` / `password`, `user2@example.com` / `password`
* **1 Event**: "Grand World Tour Concert 2026"
* **100 Seats**: 10 VIP (`VIP-01` to `VIP-10`) and 90 Standard (`STD-001` to `STD-090`)

### 4. Run Development Server
```bash
php artisan serve
```
Open `http://127.0.0.1:8000` in your browser to view the interactive dashboard.

---

## Testing

Run full test suite:
```bash
php artisan test
```

### Concurrent Booking Test
`tests/Feature/ConcurrentReservationTest.php` spawns 100 parallel child PHP processes competing for 5 seats over MySQL to verify that exactly 1 request succeeds while 99 fail safely without double-booking.

---

## API Endpoints

All endpoints require JSON headers and a Sanctum Bearer token:
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

### 1. Reserve Seats (`POST /api/book`)
Holds seats for 10 minutes.
```json
{
  "event_id": 1,
  "seat_ids": [5, 6]
}
```

### 2. Submit Payment (`POST /api/payment`)
Confirms payment for a pending booking.
```json
{
  "booking_id": 3,
  "transaction_id": "CHG-987654"
}
```

### 3. Cancel Booking (`POST /api/cancel`)
Cancels a pending booking and releases its seats.
```json
{
  "booking_id": 3
}
```

### 4. Get User Bookings (`GET /api/bookings`)
Lists all active and past bookings for the authenticated user.

### 5. Admin Seat Upgrade (`POST /api/admin/seats/upgrade`)
Upgrades a seat to VIP and creates an audit log entry (Admin only).
```json
{
  "seat_id": 5,
  "new_tier": "vip"
}
```

---

## Architectural Reasoning & Scalability

### 1. Redis Locks vs. Database Locking
* **Database Approach (Current Implementation)**: Uses MySQL `SELECT ... FOR UPDATE` inside `DB::transaction()`. Guarantees ACID strict consistency where seat status and booking creation happen atomically in the same database engine.
* **Redis Approach (`Cache::lock()`)**: Under extreme traffic drops (e.g. 50,000 requests/sec), Redis distributed locks (`Cache::lock("seat:{$id}", 10)`) can be used as an ultra-fast non-blocking filter layer in front of MySQL to reject excess requests before they touch the database. MySQL remains the authoritative transactional source of truth.

### 2. Optimistic vs. Pessimistic Locking Tradeoffs
* **Pessimistic Locking (`lockForUpdate()`) [Chosen]**: Holds row locks during the transaction. Best suited for high-demand ticket sales where seat contention is intense. It avoids wasted application CPU cycles and repeated retry loops.
* **Optimistic Locking (`version` column)**: Updates rows using `WHERE id = ? AND version = ?`. Ideal for low-contention systems. Under heavy ticket drops, 99% of optimistic update attempts fail at commit time, triggering massive application retries and burning CPU resources.

### 3. Queue Processing for 10,000 Bookings / Minute
To handle 10,000 expirations per minute without locking up MySQL:
* **Batch Dispatching**: The scheduler (`bookings:expire`) queries expired pending booking IDs in small chunked batches (e.g. 500 IDs per chunk) to avoid long table scans.
* **Queued Worker Pool**: Dispatches lightweight `ExpireBooking($id)` jobs to a Redis queue processed by multiple worker pods (e.g. 20–50 horizon workers). Each worker locks only 1 specific booking row in <10ms, handling 10,000 expirations per minute cleanly without database lock bloat.

### 4. Database Partitioning & Seat Sharding for 100,000 Seats
For massive venues with 100,000+ seats:
* **Database Partitioning**: Range/Hash partition the `seats` table by `event_id` and index `(event_id, status)`.
* **Horizontal Seat Sharding**: Route requests for different seat sections or events to separate database shards (e.g. Section A to Shard 1, Section B to Shard 2).
* **Redis Availability Set**: Cache available seat IDs in Redis Sets/Bitmaps to serve read-heavy venue mapping queries without querying MySQL.

---

## System Flow Architecture

```text
                         HIGH CONCURRENCY
                                │
                                ▼
                     ┌────────────────────┐
                     │  POST /api/book    │
                     └─────────┬──────────┘
                               │
                          DB Transaction
                               │
                               ▼
                        lockForUpdate()
                               │
                               ▼
                     ┌────────────────────┐
                     │ Is every seat      │
                     │ available?         │
                     └──────┬───────┬─────┘
                            │       │
                           NO      YES
                            │       │
                        ROLLBACK    ▼
                                Create booking
                                     │
                                10-min hold
                                     │
                               Seats RESERVED
                                     │
                        ┌────────────┴───────────┐
                        │                        │
                     PAYMENT                  EXPIRY
                        │                        │
                   Lock booking             Lock booking
                        │                        │
                   Still pending?            Still pending?
                   Not expired?             Expired?
                        │                        │
                        ▼                        ▼
                   PAID + BOOKED          EXPIRED + AVAILABLE
                        │                        │
                        └──────────┬─────────────┘
                                   │
                                   ▼
                            CONSISTENT STATE
```