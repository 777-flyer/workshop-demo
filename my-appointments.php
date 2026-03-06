<?php
/**
 * my-appointments.php
 *
 * Client: view all their appointments, cancel upcoming ones.
 * POST action=cancel&id=X
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
require_client();

$clientId = current_client_id();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
 $apptId = (int)($_POST['id'] ?? 0);
 if ($apptId > 0) {
 $db = get_db();
 // Only cancel appointments that belong to this client and are not yet done
 $stmt = $db->prepare("
 UPDATE appointments
 SET status = 'cancelled'
 WHERE id = ? AND client_id = ? AND status IN ('pending','confirmed')
 AND appointment_date >= CURDATE()
 ");
 $stmt->execute([$apptId, $clientId]);
 if ($stmt->rowCount() > 0) {
 redirect('my-appointments.php', 'Appointment cancelled.', 'info');
 } else {
 redirect('my-appointments.php', 'Unable to cancel that appointment.', 'error');
 }
 }
}

$appointments = get_client_appointments($clientId);
$today = date('Y-m-d');

$pageTitle = 'My Appointments';
$activeNav = 'appointments';
$rootPath = '';

include __DIR__ . '/includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header">
 <h1>My Appointments</h1>
 <p>View and manage all your service appointments.</p>
</div>

<div class="flex gap-12 mb-20" style="flex-wrap:wrap;">
 <a href="book.php" class="btn btn-primary">+ New Appointment</a>
</div>

<div class="card">
 <div class="card-title"> Appointment History (<?= count($appointments) ?>)</div>

 <?php if (empty($appointments)): ?>
 <div class="empty-state">
 <div class="icon"></div>
 <h3>No appointments yet</h3>
 <p><a href="book.php">Book your first appointment →</a></p>
 </div>
 <?php else: ?>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>Date</th>
 <th>Mechanic</th>
 <th>Speciality</th>
 <th>Car</th>
 <th>Status</th>
 <th>Notes</th>
 <th>Action</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($appointments as $appt):
 $canCancel = in_array($appt['status'], ['pending','confirmed'])
 && $appt['appointment_date'] >= $today;
 ?>
 <tr>
 <td style="white-space:nowrap;">
 <?= date('d M Y', strtotime($appt['appointment_date'])) ?>
 </td>
 <td><?= e($appt['mechanic_name']) ?></td>
 <td class="text-muted" style="font-size:.85rem;"><?= e($appt['mechanic_speciality']) ?></td>
 <td>
 <?= e($appt['license_number']) ?>
 <span class="text-muted" style="font-size:.82rem;"> <?= e($appt['make']) ?> <?= e($appt['model']) ?></span>
 </td>
 <td><span class="badge badge-<?= e($appt['status']) ?>"><?= e($appt['status']) ?></span></td>
 <td class="text-muted" style="font-size:.85rem; max-width:180px;">
 <?= $appt['notes'] ? e(mb_strimwidth($appt['notes'], 0, 60, '…')) : '—' ?>
 </td>
 <td>
 <?php if ($canCancel): ?>
 <form method="POST" action="my-appointments.php"
 onsubmit="return confirm('Cancel this appointment?')">
 <input type="hidden" name="action" value="cancel">
 <input type="hidden" name="id" value="<?= (int)$appt['id'] ?>">
 <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
 </form>
 <?php else: ?>
 <span class="text-muted" style="font-size:.82rem;">—</span>
 <?php endif; ?>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
