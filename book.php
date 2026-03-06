<?php
/**
 * book.php
 *
 * Client appointment booking.
 * Shows the calendar, mechanic selector, car selector, and date picker.
 * POST: car_id, mechanic_id, appointment_date, notes
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
require_client();

$clientId = current_client_id();
$errors = [];
$old = [];

// Must have at least one car registered
$cars = get_client_cars($clientId);
if (empty($cars)) {
 redirect('my-cars.php', 'Please add a car before booking an appointment.', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 $old = [
 'car_id' => (int) ($_POST['car_id'] ?? 0),
 'mechanic_id' => (int) ($_POST['mechanic_id'] ?? 0),
 'appointment_date' => trim( $_POST['appointment_date'] ?? ''),
 'notes' => trim( $_POST['notes'] ?? ''),
 ];

 // Validation
 if ($old['car_id'] <= 0) $errors['car_id'] = 'Please select a car.';
 if ($old['mechanic_id'] <= 0) $errors['mechanic_id'] = 'Please select a mechanic.';

 if ($old['appointment_date'] === '') {
 $errors['appointment_date'] = 'Appointment date is required.';
 } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['appointment_date'])) {
 $errors['appointment_date'] = 'Invalid date format.';
 } elseif ($old['appointment_date'] < date('Y-m-d')) {
 $errors['appointment_date'] = 'Appointment date must be today or a future date.';
 }

 if (empty($errors)) {
 $result = book_appointment(
 $clientId,
 $old['car_id'],
 $old['mechanic_id'],
 $old['appointment_date'],
 $old['notes']
 );

 if ($result['ok']) {
 redirect('my-appointments.php', 'Appointment booked successfully!', 'success');
 } else {
 $errors['general'] = $result['msg'];
 }
 }
}

// Fetch mechanics with slots for today (JS will update live on date change)
$today = date('Y-m-d');
$mechanics = get_mechanics_with_slots($old['appointment_date'] ?? $today);

$pageTitle = 'Book Appointment';
$activeNav = 'book';
$rootPath = '';

include __DIR__ . '/includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header">
 <h1>Book an Appointment</h1>
 <p>Select a date, pick your mechanic, and confirm your car.</p>
</div>

<?php if (!empty($errors['general'])): ?>
 <div class="flash flash-error"><?= e($errors['general']) ?></div>
<?php endif; ?>

<!-- Calendar — click a date to fill in the date field below.
     The grid is empty. JavaScript (main.js) draws it after
     fetching data from api/availability.php.
-->
<div class="card mb-32">
    <div class="card-title">Check Availability</div>

    <div class="calendar-controls">
        <button id="cal-prev" class="btn btn-ghost btn-sm">&larr; Prev</button>
        <h2 id="cal-heading"></h2>  <!-- JS writes the month name here -->
        <button id="cal-next" class="btn btn-ghost btn-sm">Next &rarr;</button>
    </div>

    <div id="availability-calendar">
        <div id="cal-grid" class="cal-grid">
            <p style="padding:16px; color:#888;">Loading calendar...</p>
        </div>
    </div>

    <p class="text-muted mt-12" style="font-size:.82rem;">
        Click a date to fill in the date field and refresh mechanic slot counts.
    </p>
</div>

<!-- Booking form -->
<div class="card">
 <div class="card-title"> Appointment Details</div>

 <form id="booking-form" method="POST" action="book.php" novalidate>

 <!-- Date -->
 <div class="form-group">
 <label class="form-label" for="appointment_date">Appointment Date</label>
 <input
 type="date"
 id="appointment_date"
 name="appointment_date"
 class="form-control <?= isset($errors['appointment_date']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['appointment_date'] ?? '') ?>"
 required
 data-label="Appointment date"
 min="<?= date('Y-m-d') ?>"
 >
 <?php if (isset($errors['appointment_date'])): ?>
 <span class="invalid-feedback"><?= e($errors['appointment_date']) ?></span>
 <?php endif; ?>
 </div>

 <!-- Mechanic selector -->
 <div class="form-group">
 <label class="form-label">Select Mechanic</label>
 <!-- Hidden input holds the chosen mechanic_id -->
 <input
 type="hidden"
 id="mechanic_id"
 name="mechanic_id"
 value="<?= e($old['mechanic_id'] ?? '') ?>"
 required
 data-label="Mechanic"
 >
 <?php if (isset($errors['mechanic_id'])): ?>
 <div class="flash flash-error mb-12"><?= e($errors['mechanic_id']) ?></div>
 <?php endif; ?>

 <div class="mechanic-grid">
 <?php foreach ($mechanics as $m):
 $free = (int) $m['free_slots'];
 $isFull = $free <= 0;
 $selected = ((int)($old['mechanic_id'] ?? 0)) === (int)$m['id'];
 $classes = 'mechanic-card'
 . ($isFull ? ' full' : '')
 . ($selected ? ' selected' : '');
 ?>
 <div class="<?= $classes ?>" data-id="<?= (int)$m['id'] ?>">
 <div class="mechanic-avatar">
 <?php if ($m['image_url']): ?>
 <img src="<?= e($m['image_url']) ?>" alt="<?= e($m['name']) ?>">
 <?php else: ?>
 ‍
 <?php endif; ?>
 </div>
 <div class="mechanic-name"><?= e($m['name']) ?></div>
 <div class="mechanic-speciality"><?= e($m['speciality']) ?></div>
 <span class="mechanic-slots <?= $isFull ? 'slots-full' : 'slots-available' ?>">
 <?= $isFull ? 'Fully booked' : "{$free} slot" . ($free !== 1 ? 's' : '') . ' free' ?>
 </span>
 </div>
 <?php endforeach; ?>
 </div>
 </div>

 <!-- Car selector -->
 <div class="form-group">
 <label class="form-label" for="car_id">Select Car</label>
 <select
 id="car_id"
 name="car_id"
 class="form-control <?= isset($errors['car_id']) ? 'is-invalid' : '' ?>"
 required
 data-label="Car"
 >
 <option value="">— Choose a car —</option>
 <?php foreach ($cars as $car): ?>
 <option
 value="<?= (int)$car['id'] ?>"
 <?= ((int)($old['car_id'] ?? 0)) === (int)$car['id'] ? 'selected' : '' ?>
 >
 <?= e($car['license_number']) ?> — <?= e($car['make']) ?> <?= e($car['model']) ?> (<?= e($car['year']) ?>)
 </option>
 <?php endforeach; ?>
 </select>
 <?php if (isset($errors['car_id'])): ?>
 <span class="invalid-feedback"><?= e($errors['car_id']) ?></span>
 <?php endif; ?>
 <span class="form-text"><a href="my-cars.php">Add a new car →</a></span>
 </div>

 <!-- Notes (optional) -->
 <div class="form-group">
 <label class="form-label" for="notes">Issue Description (optional)</label>
 <textarea
 id="notes"
 name="notes"
 class="form-control"
 rows="3"
 placeholder="Describe the problem, e.g. 'engine knocking on cold start'…"
 ><?= e($old['notes'] ?? '') ?></textarea>
 </div>

 <button type="submit" class="btn btn-primary btn-lg">
 Confirm Appointment
 </button>

 </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
