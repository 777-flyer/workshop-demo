<?php
/**
 * index.php
 *
 * Public landing page showing the real-time mechanic availability calendar.
 * Logged-in clients see a personalised welcome strip.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();

$pageTitle = 'Home';
$activeNav = 'home';
$rootPath = '';

include __DIR__ . '/includes/header.php';
?>

<?= render_flash() ?>

<!-- Hero -->
<div class="page-header mb-32" style="border-bottom: none; padding-bottom:0;">
 <h1>AutoCare Workshop</h1>
 <p>
 Book your car service appointment with your preferred mechanic — no phone calls, no queues.
 Check real-time slot availability below.
 </p>
 <?php if (!client_logged_in()): ?>
 <div class="flex gap-12 mt-12">
 <a href="register.php" class="btn btn-primary btn-lg">Create Account</a>
 <a href="login.php" class="btn btn-outline btn-lg">Sign In</a>
 </div>
 <?php else: ?>
 <div class="flex gap-12 mt-12">
 <a href="book.php" class="btn btn-primary btn-lg">Book Appointment</a>
 <a href="my-appointments.php" class="btn btn-outline btn-lg">My Appointments</a>
 </div>
 <?php endif; ?>
</div>

<hr class="divider">

<!-- Calendar
     The calendar is drawn by JavaScript (main.js).
     PHP only provides the HTML skeleton here.
     JavaScript fetches real-time data from api/availability.php
     and fills in the grid cells.
-->
<section class="calendar-section">
    <h2 style="margin-bottom:14px;">Mechanic Availability</h2>

    <!-- Prev / Month name / Next -->
    <div class="calendar-controls">
        <button id="cal-prev" class="btn btn-ghost btn-sm">&larr; Prev</button>
        <h2 id="cal-heading"></h2>  <!-- JS writes "March 2025" here -->
        <button id="cal-next" class="btn btn-ghost btn-sm">Next &rarr;</button>
    </div>

    <!-- The grid is empty now. JS draws 7 headers + day cells inside it. -->
    <div id="availability-calendar">
        <div id="cal-grid" class="cal-grid">
            <!-- JS will replace this with the actual calendar -->
            <p style="padding:16px; color:#888;">Loading calendar...</p>
        </div>
    </div>

    <p class="text-muted mt-12" style="font-size:.85rem;">
        Each cell shows how many slots each mechanic has free that day.
        Click a date to pre-fill the booking form.
    </p>
</section>

<!-- Mechanics overview -->
<section>
 <h2 style="margin-bottom:16px;">Our Mechanics</h2>
 <div class="mechanic-grid">
 <?php
 $mechanics = get_mechanics_with_slots(date('Y-m-d'));
 foreach ($mechanics as $m):
 $free = (int) $m['free_slots'];
 ?>
 <div class="mechanic-card" style="cursor:default;">
 <div class="mechanic-avatar">
 <?php if ($m['image_url']): ?>
 <img src="<?= e($m['image_url']) ?>" alt="<?= e($m['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
 <?php else: ?>
 ‍
 <?php endif; ?>
 </div>
 <div class="mechanic-name"><?= e($m['name']) ?></div>
 <div class="mechanic-speciality"><?= e($m['speciality']) ?></div>
 <span class="mechanic-slots <?= $free > 0 ? 'slots-available' : 'slots-full' ?>">
 <?= $free > 0 ? "{$free} free today" : 'Full today' ?>
 </span>
 </div>
 <?php endforeach; ?>
 </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
