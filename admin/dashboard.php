<?php
/**
 * admin/dashboard.php
 *
 * Overview stats and today's appointments.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

start_session();
require_admin();

$db = get_db();
$today = date('Y-m-d');

// Stats
$totalAppts = (int) $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$todayAppts = (int) $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$today}' AND status != 'cancelled'")->fetchColumn();
$totalClients = (int) $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$pendingAppts = (int) $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();

// Today's appointments
$todayList = get_all_appointments(['date' => $today]);

// Mechanic load today
$mechanicsToday = get_mechanics_with_slots($today);

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin-dash';
$rootPath = '../';

include __DIR__ . '/../includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header">
 <h1>Workshop Dashboard</h1>
 <p>Today: <?= date('l, d F Y') ?></p>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
 <div class="stat-card">
 <div class="stat-value"><?= $totalClients ?></div>
 <div class="stat-label">Total Clients</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= $totalAppts ?></div>
 <div class="stat-label">Total Appointments</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= $todayAppts ?></div>
 <div class="stat-label">Active Today</div>
 </div>
 <div class="stat-card">
 <div class="stat-value"><?= $pendingAppts ?></div>
 <div class="stat-label">Pending</div>
 </div>
</div>

<!-- Mechanic load today -->
<div class="card mb-32">
 <div class="card-title">Mechanic Load — Today</div>
 <div class="mechanic-grid">
 <?php foreach ($mechanicsToday as $m):
 $booked = (int) $m['booked'];
 $free = (int) $m['free_slots'];
 $pct = ($booked / MAX_SLOTS_PER_MECHANIC) * 100;
 ?>
 <div class="mechanic-card" style="cursor:default; text-align:left;">
 <div class="mechanic-name"><?= e($m['name']) ?></div>
 <div class="mechanic-speciality"><?= e($m['speciality']) ?></div>
 <!-- Simple B&W progress bar -->
 <div style="background:#e8e8e8; height:5px; margin:8px 0;">
 <div style="width:<?= $pct ?>%; height:100%; background:#000;"></div>
 </div>
 <div style="font-size:.8rem; color:#888;">
 <?= $booked ?> / <?= MAX_SLOTS_PER_MECHANIC ?> slots used
 </div>
 </div>
 <?php endforeach; ?>
 </div>
</div>

<!-- Today's appointments -->
<div class="card">
 <div class="card-title flex justify-between" style="align-items:center;">
 <span> Today's Appointments</span>
 <a href="appointments.php" class="btn btn-ghost btn-sm">View All</a>
 </div>

 <?php if (empty($todayList)): ?>
 <div class="empty-state">
 <div class="icon"></div>
 <h3>No appointments today</h3>
 </div>
 <?php else: ?>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>Client</th>
 <th>Phone</th>
 <th>Car</th>
 <th>Mechanic</th>
 <th>Status</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($todayList as $a): ?>
 <tr>
 <td><?= e($a['client_name']) ?></td>
 <td class="text-muted"><?= e($a['client_phone']) ?></td>
 <td><?= e($a['license_number']) ?> <span class="text-muted"><?= e($a['make']) ?> <?= e($a['model']) ?></span></td>
 <td><?= e($a['mechanic_name']) ?></td>
 <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
