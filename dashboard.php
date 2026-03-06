<?php
/**
 * dashboard.php
 *
 * Client home after login — shows summary stats and quick actions.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
require_client();

$clientId = current_client_id();
$appointments = get_client_appointments($clientId);
$cars = get_client_cars($clientId);

// Count stats
$total = count($appointments);
$upcoming = array_filter($appointments, fn($a) =>
 $a['appointment_date'] >= date('Y-m-d') && $a['status'] !== 'cancelled'
);
$pending = array_filter($appointments, fn($a) => $a['status'] === 'pending');

$pageTitle = 'Dashboard';
$activeNav = '';
$rootPath = '';

include __DIR__ . '/includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header">
 <h1>Welcome, <?= e($_SESSION['client_name']) ?></h1>
 <p>Here's an overview of your workshop activity.</p>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
 <div class="stat-card">
 <div class="stat-value"><?= $total ?></div>
 <div class="stat-label">Total Appointments</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= count($upcoming) ?></div>
 <div class="stat-label">Upcoming</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= count($pending) ?></div>
 <div class="stat-label">Pending</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= count($cars) ?></div>
 <div class="stat-label">Registered Cars</div>
 </div>
</div>

<!-- Quick actions -->
<div class="flex gap-12 mb-32" style="flex-wrap:wrap;">
 <a href="book.php" class="btn btn-primary"> Book Appointment</a>
 <a href="my-cars.php" class="btn btn-ghost"> Manage Cars</a>
 <a href="my-appointments.php" class="btn btn-ghost"> All Appointments</a>
</div>

<!-- Upcoming appointments (max 5) -->
<div class="card">
 <div class="card-title"> Upcoming Appointments</div>

 <?php
 $upcomingList = array_values($upcoming);
 usort($upcomingList, fn($a,$b) => strcmp($a['appointment_date'], $b['appointment_date']));
 $upcomingList = array_slice($upcomingList, 0, 5);
 ?>

 <?php if (empty($upcomingList)): ?>
 <div class="empty-state">
 <div class="icon"></div>
 <h3>No upcoming appointments</h3>
 <p><a href="book.php">Book one now</a></p>
 </div>
 <?php else: ?>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>Date</th>
 <th>Mechanic</th>
 <th>Car</th>
 <th>Status</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($upcomingList as $appt): ?>
 <tr>
 <td><?= e($appt['appointment_date']) ?></td>
 <td><?= e($appt['mechanic_name']) ?></td>
 <td><?= e($appt['license_number']) ?> (<?= e($appt['make']) ?> <?= e($appt['model']) ?>)</td>
 <td><span class="badge badge-<?= e($appt['status']) ?>"><?= e($appt['status']) ?></span></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <div class="mt-12">
 <a href="my-appointments.php" class="btn btn-ghost btn-sm">View all →</a>
 </div>
 <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
