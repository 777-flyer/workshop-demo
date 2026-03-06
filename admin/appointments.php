<?php
/**
 * admin/appointments.php
 *
 * Full appointment list with filters and inline edit (date + mechanic).
 * POST action=update: appt_id, new_date, new_mechanic_id
 * POST action=status: appt_id, new_status
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

start_session();
require_admin();

$db = get_db();

// Handle form submissions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 $action = $_POST['action'] ?? '';

 if ($action === 'update') {
 $apptId = (int) ($_POST['appt_id'] ?? 0);
 $newDate = trim( $_POST['new_date'] ?? '');
 $newMechanic = (int) ($_POST['new_mechanic_id'] ?? 0);

 if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate) || $newMechanic <= 0) {
 redirect('appointments.php', 'Invalid data submitted.', 'error');
 }

 $result = admin_update_appointment($apptId, $newDate, $newMechanic);
 if ($result['ok']) {
 redirect('appointments.php', 'Appointment updated successfully.', 'success');
 } else {
 redirect('appointments.php', $result['msg'], 'error');
 }
 }

 if ($action === 'status') {
 $apptId = (int) ($_POST['appt_id'] ?? 0);
 $newStatus = $_POST['new_status'] ?? '';
 $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
 if ($apptId > 0 && in_array($newStatus, $allowed)) {
 $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
 $stmt->execute([$newStatus, $apptId]);
 redirect('appointments.php', 'Status updated.', 'success');
 }
 }
}

// Filters from GET 
$filters = [
 'date' => trim($_GET['date'] ?? ''),
 'mechanic_id' => (int) ($_GET['mechanic_id'] ?? 0),
 'status' => trim($_GET['status'] ?? ''),
];

$appointments = get_all_appointments(array_filter($filters));

// Fetch all mechanics for dropdowns
$allMechanics = $db->query('SELECT id, name FROM mechanics WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'Manage Appointments';
$activeNav = 'admin-appts';
$rootPath = '../';

include __DIR__ . '/../includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header flex justify-between" style="align-items:flex-start; flex-wrap:wrap; gap:12px;">
 <div>
 <h1>Appointments</h1>
 <p>View, filter, and edit all bookings.</p>
 </div>
</div>

<!-- Filters -->
<div class="card mb-24">
 <div class="card-title"> Filter</div>
 <form method="GET" action="appointments.php">
 <div class="form-row" style="grid-template-columns:1fr 1fr 1fr auto; align-items:end; gap:16px;">
 <div class="form-group mb-0">
 <label class="form-label" for="f-date">Date</label>
 <input type="date" id="f-date" name="date" class="form-control" value="<?= e($filters['date']) ?>">
 </div>
 <div class="form-group mb-0">
 <label class="form-label" for="f-mech">Mechanic</label>
 <select id="f-mech" name="mechanic_id" class="form-control">
 <option value="">All Mechanics</option>
 <?php foreach ($allMechanics as $m): ?>
 <option value="<?= $m['id'] ?>" <?= (int)$filters['mechanic_id'] === (int)$m['id'] ? 'selected' : '' ?>>
 <?= e($m['name']) ?>
 </option>
 <?php endforeach; ?>
 </select>
 </div>
 <div class="form-group mb-0">
 <label class="form-label" for="f-status">Status</label>
 <select id="f-status" name="status" class="form-control">
 <option value="">All Statuses</option>
 <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
 <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
 <?php endforeach; ?>
 </select>
 </div>
 <div style="padding-bottom:1px;">
 <button type="submit" class="btn btn-primary">Filter</button>
 <a href="appointments.php" class="btn btn-ghost" style="margin-left:8px;">Clear</a>
 </div>
 </div>
 </form>
</div>

<!-- Appointments table -->
<div class="card">
 <div class="card-title">
 Results (<?= count($appointments) ?> appointments)
 </div>

 <?php if (empty($appointments)): ?>
 <div class="empty-state">
 <div class="icon"></div>
 <h3>No appointments found</h3>
 <p>Try adjusting your filters.</p>
 </div>
 <?php else: ?>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>#</th>
 <th>Date</th>
 <th>Client</th>
 <th>Phone</th>
 <th>Car</th>
 <th>Mechanic</th>
 <th>Status</th>
 <th>Actions</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($appointments as $a): ?>
 <tr>
 <td class="text-muted" style="font-size:.82rem;"><?= (int)$a['id'] ?></td>
 <td style="white-space:nowrap;">
 <?= date('d M Y', strtotime($a['appointment_date'])) ?>
 </td>
 <td><?= e($a['client_name']) ?></td>
 <td class="text-muted"><?= e($a['client_phone']) ?></td>
 <td>
 <span style="font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; letter-spacing:.03em;">
 <?= e($a['license_number']) ?>
 </span>
 <span class="text-muted" style="font-size:.82rem;">
 <?= e($a['make']) ?> <?= e($a['model']) ?>
 </span>
 </td>
 <td><?= e($a['mechanic_name']) ?></td>
 <td>
 <!-- Inline status form -->
 <form method="POST" action="appointments.php" style="display:inline;">
 <input type="hidden" name="action" value="status">
 <input type="hidden" name="appt_id" value="<?= (int)$a['id'] ?>">
 <select name="new_status" class="form-control"
 style="padding:4px 30px 4px 8px; font-size:.82rem; height:auto;"
 onchange="this.form.submit()">
 <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
 <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
 <?php endforeach; ?>
 </select>
 </form>
 </td>
 <td>
 <button
 class="btn btn-outline btn-sm btn-edit-appt"
 data-id="<?= (int)$a['id'] ?>"
 data-date="<?= e($a['appointment_date']) ?>"
 data-mechanic="<?= (int)$a['mechanic_id'] ?>"
 >
 Edit
 </button>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay">
 <div class="modal-box">
 <div class="modal-header">
 <h3> Edit Appointment</h3>
 <button id="modal-close" class="modal-close" aria-label="Close"></button>
 </div>

 <form id="edit-form" method="POST" action="appointments.php">
 <input type="hidden" name="action" value="update">
 <input type="hidden" name="appt_id" id="edit-appt-id">

 <div class="form-group">
 <label class="form-label" for="edit-appt-date">New Appointment Date</label>
 <input
 type="date"
 id="edit-appt-date"
 name="new_date"
 class="form-control"
 required
 min="<?= date('Y-m-d') ?>"
 >
 </div>

 <div class="form-group">
 <label class="form-label" for="edit-mechanic">Assign Mechanic</label>
 <select id="edit-mechanic" name="new_mechanic_id" class="form-control" required>
 <?php foreach ($allMechanics as $m): ?>
 <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
 <?php endforeach; ?>
 </select>
 </div>

 <div class="flex gap-12">
 <button type="submit" class="btn btn-primary">Save Changes</button>
 <button type="button" id="modal-close-2" class="btn btn-ghost"
 onclick="document.getElementById('edit-modal').classList.remove('open')">
 Cancel
 </button>
 </div>
 </form>
 </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
