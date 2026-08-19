<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Ticket Booking Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .card {
            border: 1px solid #e3e6f0;
            border-radius: 8px;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }

        .stage-box {
            background-color: #e9ecef;
            border: 2px dashed #adb5bd;
            color: #495057;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        .seats-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            max-height: 460px;
            overflow-y: auto;
            padding: 4px;
        }

        .seat-btn {
            aspect-ratio: 1;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
        }

        .seat-btn.available {
            background-color: #e8f5e9;
            border: 1px solid #81c784;
            color: #2e7d32;
        }
        .seat-btn.available:hover {
            background-color: #c8e6c9;
            border-color: #4caf50;
        }

        .seat-btn.reserved {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            color: #f57f17;
        }

        .seat-btn.booked {
            background-color: #ffebee;
            border: 1px solid #ef9a9a;
            color: #c62828;
        }

        .seat-btn.selected {
            background-color: #0d6efd !important;
            border-color: #0a58ca !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.4);
        }

        .seat-btn.vip-seat {
            border-top: 3px solid #ab47bc !important;
        }

        .terminal-box {
            background-color: #1e1e1e;
            color: #4af626;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8rem;
            padding: 12px;
            border-radius: 6px;
            height: 200px;
            overflow-y: auto;
        }

        .log-line {
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .log-200, .log-201 { color: #4af626; }
        .log-400, .log-403, .log-409, .log-410 { color: #ff5252; }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- TOP NAVIGATION BAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">
                <i class="bi bi-ticket-perforated-fill text-warning me-2"></i>
                Ticket Booking System
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light fs-7 me-1">Active User:</span>
                <select id="userSelect" class="form-select form-select-sm" style="width: 260px;">
                    <option value="user1@example.com">User 1: John Doe (Customer)</option>
                    <option value="user2@example.com">User 2: Jane Smith (Customer)</option>
                    <option value="admin@example.com">User 3: System Admin (Admin)</option>
                </select>
                <button class="btn btn-outline-light btn-sm" id="btnRefresh">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-4">
            <!-- LEFT COLUMN: SEATING MAP -->
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Event Seating Layout (100 Seats)
                        </h6>
                        <div class="d-flex gap-3 fs-7">
                            <span><span class="legend-dot bg-success"></span> Available</span>
                            <span><span class="legend-dot bg-warning"></span> Reserved</span>
                            <span><span class="legend-dot bg-danger"></span> Booked</span>
                            <span><span class="legend-dot" style="background:#ab47bc;"></span> VIP Tier</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stage-box">
                            <i class="bi bi-music-note-beamed me-2"></i>STAGE AREA
                        </div>
                        <div class="seats-grid" id="seatsGrid">
                            <!-- Dynamic seats rendered by jQuery -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: ACTION PANEL -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-sliders me-2"></i>Actions & API Tester
                        </h6>
                        <span class="badge bg-secondary" id="selectedCount">0 selected</span>
                    </div>
                    <div class="card-body">
                        <!-- RESERVE ACTION -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Step 1: Select & Reserve Seats</label>
                            <button class="btn btn-primary w-100" id="btnBook">
                                <i class="bi bi-bookmark-check-fill me-1"></i> Reserve Selected Seats
                            </button>
                        </div>

                        <hr>

                        <!-- PAYMENT ACTION -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Step 2: Submit Payment</label>
                            <div class="form-text mb-2 text-muted">
                                <i class="bi bi-info-circle me-1"></i>Enter Booking Reference ID (e.g. 3):
                            </div>
                            <div class="mb-2">
                                <input type="number" id="payBookingId" class="form-control form-control-sm" placeholder="Booking Ref ID (e.g. 3)">
                            </div>
                            <div class="mb-2">
                                <input type="text" id="payTxId" class="form-control form-control-sm" placeholder="Transaction ID (e.g. CHG-102938)">
                            </div>
                            <button class="btn btn-success w-100 btn-sm" id="btnPay">
                                <i class="bi bi-credit-card-fill me-1"></i> Submit Payment
                            </button>
                        </div>

                        <hr>

                        <!-- CANCEL ACTION -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Cancel Booking</label>
                            <div class="form-text mb-2 text-muted">
                                <i class="bi bi-info-circle me-1"></i>Click a seat or booking below to auto-fill ID:
                            </div>
                            <div class="mb-2">
                                <input type="number" id="cancelBookingId" class="form-control form-control-sm" placeholder="Booking Ref ID (e.g. 3)">
                            </div>
                            <button class="btn btn-danger w-100 btn-sm" id="btnCancel">
                                <i class="bi bi-x-circle-fill me-1"></i> Cancel Booking
                            </button>
                        </div>

                        <!-- ADMIN SECTION -->
                        <div id="adminSection" class="p-3 bg-light rounded border border-warning" style="display: none;">
                            <label class="form-label fw-bold text-dark fs-7">
                                <i class="bi bi-shield-lock-fill text-warning me-1"></i>Admin: Upgrade Seat Tier
                            </label>
                            <div class="mb-2">
                                <input type="number" id="adminSeatId" class="form-control form-control-sm" placeholder="Seat ID (click seat on map)">
                            </div>
                            <button class="btn btn-warning w-100 btn-sm fw-bold" id="btnUpgrade">
                                Upgrade Seat to VIP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECOND ROW: USER BOOKINGS & LIVE LOGS -->
        <div class="row g-4 mt-1 mb-4">
            <!-- USER BOOKINGS -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-task me-2"></i>My Bookings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="bookingsList" style="max-height: 220px; overflow-y: auto;">
                            <div class="text-muted fs-7">Loading bookings...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIVE LOG CONSOLE -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-terminal-fill me-2"></i>Activity Log Console
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary py-0" id="btnClearLogs">Clear</button>
                    </div>
                    <div class="card-body">
                        <div class="terminal-box" id="terminalLog">
                            <div class="log-line">[System] Activity log initialized. Click any seat to auto-populate inputs.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery AJAX Logic -->
    <script>
        var currentToken = '';
        var currentUser = null;
        var currentEventId = null;
        var selectedSeatIds = [];
        var seatsData = [];
        var targetSeatId = null;

        $(document).ready(function() {
            switchUser();

            $('#userSelect').on('change', function() {
                switchUser();
            });

            $('#btnRefresh').on('click', function() {
                refreshData();
            });

            $('#btnBook').on('click', function() {
                bookSelectedSeats();
            });

            $('#btnPay').on('click', function() {
                processPayment();
            });

            $('#btnCancel').on('click', function() {
                cancelBooking();
            });

            $('#btnUpgrade').on('click', function() {
                upgradeSeat();
            });

            $('#btnClearLogs').on('click', function() {
                $('#terminalLog').empty();
            });
        });

        function switchUser() {
            var email = $('#userSelect').val();
            logTerminal('[Auth] Logging in as ' + email + '...');

            // Clear input fields and selected seats when switching users
            $('#payBookingId').val('');
            $('#cancelBookingId').val('');
            $('#payTxId').val('');
            $('#adminSeatId').val('');
            selectedSeatIds = [];
            targetSeatId = null;
            $('#selectedCount').text('0 selected');

            $.ajax({
                url: '/api/demo-token',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email }),
                success: function(response) {
                    currentToken = response.token;
                    currentUser = response.user;

                    logTerminal('[Auth Success] Authenticated as ' + currentUser.name + ' (' + (currentUser.is_admin ? 'Admin' : 'Customer') + ')', 200);

                    if (currentUser.is_admin) {
                        $('#adminSection').show();
                    } else {
                        $('#adminSection').hide();
                    }

                    refreshData();
                },
                error: function(xhr) {
                    var errMessage = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Auth Failed';
                    logTerminal('[Auth Error] ' + errMessage, xhr.status);
                    alert('Auth Error (' + xhr.status + '): ' + errMessage);
                }
            });
        }

        function refreshData() {
            $.ajax({
                url: '/api/seats',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.event) {
                        currentEventId = data.event.id;
                    }
                    seatsData = data.seats || [];
                    renderSeats(seatsData);
                    fetchUserBookings();
                },
                error: function(xhr) {
                    logTerminal('[Fetch Error] Failed to load seating layout', xhr.status);
                }
            });
        }

        function renderSeats(seats) {
            var $grid = $('#seatsGrid');
            $grid.empty();

            $.each(seats, function(index, seat) {
                var btnClass = 'btn seat-btn ' + seat.status;
                if (seat.tier === 'vip') {
                    btnClass += ' vip-seat';
                }
                if (selectedSeatIds.indexOf(seat.id) !== -1) {
                    btnClass += ' selected';
                }

                var titleText = 'Seat ID: ' + seat.id + ' | Tier: ' + seat.tier + ' | Status: ' + seat.status;
                if (seat.active_booking_id) {
                    titleText += ' | Booking #' + seat.active_booking_id;
                }

                var $btn = $('<button>')
                    .addClass(btnClass)
                    .attr('title', titleText)
                    .text(seat.seat_no);

                $btn.on('click', function() {
                    onSeatClicked(seat);
                });

                $grid.append($btn);
            });
        }

        function onSeatClicked(seat) {
            targetSeatId = seat.id;
            $('#adminSeatId').val(seat.id);

            if (seat.active_booking_id) {
                $('#payBookingId').val(seat.active_booking_id);
                $('#cancelBookingId').val(seat.active_booking_id);
                $('#payTxId').val('CHG-' + Math.floor(100000 + Math.random() * 900000));

                if (seat.owner_user_email && currentUser && seat.owner_user_email !== currentUser.email) {
                    logTerminal('[Seat Info] Seat #' + seat.seat_no + ' (ID: ' + seat.id + ') belongs to Booking #' + seat.active_booking_id + ' owned by ' + seat.owner_user_name + ' (' + seat.owner_user_email + '). ⚠️ Note: Switch Active User dropdown to ' + seat.owner_user_email + ' to cancel or pay!', 400);
                } else {
                    logTerminal('[Seat Info] Seat #' + seat.seat_no + ' (ID: ' + seat.id + ') belongs to Booking #' + seat.active_booking_id + ' (' + seat.booking_status + '). Auto-filled Booking ID #' + seat.active_booking_id + '.');
                }
            }

            if (seat.status === 'available') {
                toggleSeatSelection(seat.id);
            } else {
                renderSeats(seatsData);
            }
        }

        function toggleSeatSelection(seatId) {
            var index = selectedSeatIds.indexOf(seatId);
            if (index > -1) {
                selectedSeatIds.splice(index, 1);
            } else {
                selectedSeatIds.push(seatId);
            }
            $('#selectedCount').text(selectedSeatIds.length + ' selected');
            renderSeats(seatsData);
        }

        function bookSelectedSeats() {
            if (selectedSeatIds.length === 0) {
                alert('Please click to select at least 1 available seat.');
                return;
            }

            var count = selectedSeatIds.length;
            var payload = {
                event_id: currentEventId || 1,
                seat_ids: selectedSeatIds
            };

            logTerminal('[POST /api/book] Reserving ' + count + ' seats...');

            $.ajax({
                url: '/api/book',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Accept': 'application/json'
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    logTerminal('[POST /api/book] HTTP 201: Booking #' + response.booking_id + ' created! (Hold 10 mins)', 201);
                    alert('Success! Booking #' + response.booking_id + ' created for ' + count + ' seat(s). Held for 10 minutes.');
                    $('#payBookingId').val(response.booking_id);
                    $('#cancelBookingId').val(response.booking_id);
                    $('#payTxId').val('CHG-' + Math.floor(100000 + Math.random() * 900000));
                    selectedSeatIds = [];
                    $('#selectedCount').text('0 selected');
                    refreshData();
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Reservation failed';
                    logTerminal('[POST /api/book] HTTP ' + xhr.status + ': ' + msg, xhr.status);
                    alert('Reservation Failed (' + xhr.status + '): ' + msg);
                }
            });
        }

        function processPayment(bookingId, txId) {
            var bId = bookingId || $('#payBookingId').val();
            var tId = txId || $('#payTxId').val() || ('CHG-' + Math.floor(100000 + Math.random() * 900000));

            if (!bId) {
                alert('Please select or enter a Booking Reference ID to pay');
                return;
            }

            var payload = {
                booking_id: parseInt(bId),
                transaction_id: tId
            };

            logTerminal('[POST /api/payment] Submitting payment for Booking #' + bId + '...');

            $.ajax({
                url: '/api/payment',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Accept': 'application/json'
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    logTerminal('[POST /api/payment] HTTP 200: Payment successful for Booking #' + bId + '!', 200);
                    alert('Payment Successful! Booking #' + bId + ' is now confirmed and paid.');
                    refreshData();
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Payment failed';
                    logTerminal('[POST /api/payment] HTTP ' + xhr.status + ': ' + msg, xhr.status);
                    alert('Payment Error (' + xhr.status + '): ' + msg);
                }
            });
        }

        function cancelBooking(bookingId) {
            var bId = bookingId || $('#cancelBookingId').val();
            if (!bId) {
                alert('Please select or enter a Booking Reference ID to cancel');
                return;
            }

            var payload = {
                booking_id: parseInt(bId)
            };

            logTerminal('[POST /api/cancel] Cancelling Booking #' + bId + '...');

            $.ajax({
                url: '/api/cancel',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Accept': 'application/json'
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    logTerminal('[POST /api/cancel] HTTP 200: Booking #' + bId + ' cancelled! Reserved seats released.', 200);
                    alert('Cancellation Successful! Booking #' + bId + ' has been cancelled and its seats released back to Available.');
                    refreshData();
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Cancellation failed';
                    logTerminal('[POST /api/cancel] HTTP ' + xhr.status + ': ' + msg, xhr.status);
                    alert('Cancellation Error (' + xhr.status + '): ' + msg);
                }
            });
        }

        function upgradeSeat() {
            var seatId = $('#adminSeatId').val();
            if (!seatId) {
                alert('Please click any seat on the layout or enter a Seat ID to upgrade');
                return;
            }

            var payload = {
                seat_id: parseInt(seatId),
                new_tier: 'vip'
            };

            logTerminal('[POST /api/admin/seats/upgrade] Upgrading Seat #' + seatId + ' to VIP...');

            $.ajax({
                url: '/api/admin/seats/upgrade',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Accept': 'application/json'
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    logTerminal('[POST /api/admin/seats/upgrade] HTTP 200: Seat #' + seatId + ' upgraded to VIP!', 200);
                    alert('Success! Seat #' + seatId + ' has been upgraded to VIP tier.');
                    refreshData();
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upgrade failed';
                    logTerminal('[POST /api/admin/seats/upgrade] HTTP ' + xhr.status + ': ' + msg, xhr.status);
                    alert('Upgrade Error (' + xhr.status + '): ' + msg);
                }
            });
        }

        function fetchUserBookings() {
            if (!currentToken) return;

            $.ajax({
                url: '/api/bookings',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Accept': 'application/json'
                },
                success: function(response) {
                    renderBookings(response.data || []);
                },
                error: function(xhr) {
                    console.log('Error fetching bookings:', xhr);
                }
            });
        }

        function renderBookings(bookings) {
            var $container = $('#bookingsList');
            $container.empty();

            if (!bookings || bookings.length === 0) {
                var noBookingsMsg = '<div class="text-muted fs-7">No active bookings for ' + (currentUser ? currentUser.name : 'session') + '.</div>';
                $container.html(noBookingsMsg);
                return;
            }

            $.each(bookings, function(index, b) {
                var seatNos = [];
                if (b.seats && b.seats.length > 0) {
                    $.each(b.seats, function(i, s) {
                        seatNos.push(s.seat_no);
                    });
                }
                var seatText = seatNos.join(', ');
                var isPending = (b.status === 'pending');

                var badgeClass = 'bg-warning text-dark';
                if (b.status === 'paid') badgeClass = 'bg-success';
                if (b.status === 'expired') badgeClass = 'bg-danger';
                if (b.status === 'cancelled') badgeClass = 'bg-secondary';

                var html = '<div class="p-2 mb-2 bg-light border rounded d-flex justify-content-between align-items-center" onclick="selectBooking(' + b.id + ')" style="cursor:pointer;">';
                html += '<div>';
                html += '<strong class="fs-7">Booking #' + b.id + '</strong> <span class="text-muted fs-8">(' + (b.event ? b.event.name : 'Concert') + ')</span><br>';
                html += '<span class="text-muted fs-8">Seats: ' + (seatText || 'None') + '</span>';
                html += '</div>';
                html += '<div class="d-flex align-items-center gap-2">';
                html += '<span class="badge ' + badgeClass + '">' + b.status + '</span>';

                if (isPending) {
                    html += ' <button class="btn btn-sm btn-outline-success py-0 px-2 fs-8" onclick="event.stopPropagation(); processPayment(' + b.id + ')">Pay</button>';
                    html += ' <button class="btn btn-sm btn-outline-danger py-0 px-2 fs-8" onclick="event.stopPropagation(); cancelBooking(' + b.id + ')">Cancel</button>';
                }

                html += '</div>';
                html += '</div>';

                $container.append(html);
            });
        }

        function selectBooking(bookingId) {
            $('#payBookingId').val(bookingId);
            $('#cancelBookingId').val(bookingId);
            $('#payTxId').val('CHG-' + Math.floor(100000 + Math.random() * 900000));
            logTerminal('[Booking Selected] Auto-filled Booking ID #' + bookingId + ' into inputs.');
        }

        function logTerminal(msg, status) {
            if (!status) status = 200;
            var $term = $('#terminalLog');
            var now = new Date();
            var timeStr = now.toLocaleTimeString();

            var $line = $('<div>')
                .addClass('log-line log-' + status)
                .text('[' + timeStr + '] ' + msg);

            $term.append($line);
            $term.scrollTop($term[0].scrollHeight);
        }
    </script>
</body>
</html>
