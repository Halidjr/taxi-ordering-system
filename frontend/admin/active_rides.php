<?php
require_once '../../backend/config.php';
check_role('admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Rides - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Add any specific styles for active rides here if needed */
        .refresh-status {
            font-size: 0.9em;
            color: #666;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Admin Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="active_rides.php" class="active">🚖 Active Rides</a></li>
                <li><a href="rides.php">🚖 All Rides</a></li>
                <li><a href="taxi_trips.php">🚐 Taxi Trips</a></li>
                <li><a href="drivers.php">🚗 Drivers</a></li>
                <li><a href="reports.php">📈 Reports</a></li>
                <li><a href="system_logs.php">📋 System Logs</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Active Rides <span id="last-updated" class="refresh-status"></span></h1>
                <div class="user-info">
                    <span>👤 <?php echo $_SESSION['full_name']; ?></span>
                </div>
            </div>

            <!-- Active Rides Table -->
            <div class="card">
                <div class="card-header">
                    Current Active Rides
                    <button id="refresh-btn" class="btn-small" style="float: right;">Refresh Now</button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="active-rides-table">
                            <thead>
                                <tr>
                                    <th>Ride ID</th>
                                    <th>Passenger</th>
                                    <th>Driver</th>
                                    <th>Pickup</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JS -->
                                <tr id="loading-row">
                                    <td colspan="8" style="text-align: center;">Loading active rides...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#active-rides-table tbody');
            const lastUpdatedSpan = document.getElementById('last-updated');
            const refreshBtn = document.getElementById('refresh-btn');

            function fetchActiveRides() {
                fetch('../../backend/ajax/get_active_rides.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateTable(data.data);
                            updateLastRefreshed();
                        } else {
                            console.error('Error fetching rides:', data.message);
                        }
                    })
                    .catch(error => console.error('Fetch error:', error));
            }

            function updateTable(rides) {
                tableBody.innerHTML = '';
                
                if (rides.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No active rides at the moment.</td></tr>';
                    return;
                }

                rides.forEach(ride => {
                    const statusClass = getStatusClass(ride.ride_status);
                    const formattedStatus = ride.ride_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const time = new Date(ride.request_time).toLocaleString();

                    const row = `
                        <tr>
                            <td><strong>#${ride.ride_id}</strong></td>
                            <td>${escapeHtml(ride.passenger_name)}</td>
                            <td>${escapeHtml(ride.driver_name || 'Not Assigned')}</td>
                            <td>${escapeHtml(ride.pickup_location)}</td>
                            <td>${escapeHtml(ride.dropoff_location)}</td>
                            <td><span class="badge ${statusClass}">${formattedStatus}</span></td>
                            <td>${time}</td>
                            <td>
                                <a href="ride_details.php?id=${ride.ride_id}" class="btn-icon" title="View Details">👁️</a>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            }

            function getStatusClass(status) {
                switch(status) {
                    case 'requested': return 'badge-info';
                    case 'assigned': return 'badge-primary';
                    case 'in_progress': return 'badge-warning';
                    default: return 'badge-secondary';
                }
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function updateLastRefreshed() {
                const now = new Date();
                lastUpdatedSpan.textContent = '(Updated: ' + now.toLocaleTimeString() + ')';
            }

            // Initial fetch
            fetchActiveRides();

            // Refresh every 10 seconds
            setInterval(fetchActiveRides, 10000);

            // Manual refresh
            refreshBtn.addEventListener('click', function() {
                lastUpdatedSpan.textContent = '(Refreshing...)';
                fetchActiveRides();
            });
        });
    </script>
</body>
</html>
