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

## Architecture Notes

### Why Deterministic Lock Ordering?
Sorting seat IDs (`sort($seatIds)`) before calling `lockForUpdate()` prevents cyclic lock wait deadlocks in MySQL when two users request the same set of seats in opposite orders.

### Expiry vs. Payment Race Condition
Both payment processing and expiration jobs acquire row locks (`lockForUpdate()`) on the `bookings` table row. The first operation to acquire the lock changes the state (to `paid` or `expired`), preventing the second operation from running against an invalid state.

### Redis Locks vs. MySQL Row Locking
MySQL pessimistic locking (`SELECT ... FOR UPDATE`) is used here as the authoritative source of truth for ACID consistency. In production under extreme loads (e.g. 100,000 requests/sec), Redis distributed locks (`Cache::lock`) can be placed in front of MySQL as an ultra-fast non-blocking filter layer.



####This is how the application works diagram.

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