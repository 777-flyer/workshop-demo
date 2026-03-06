<?php
/**
 * my-cars.php
 *
 * Client: view and add cars.
 * Each client can register multiple cars.
 * POST: license_number, engine_number, make, model, year
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
require_client();

$clientId = current_client_id();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 $old = [
 'license_number' => strtoupper(trim($_POST['license_number'] ?? '')),
 'engine_number' => strtoupper(trim($_POST['engine_number'] ?? '')),
 'make' => trim($_POST['make'] ?? ''),
 'model' => trim($_POST['model'] ?? ''),
 'year' => trim($_POST['year'] ?? ''),
 ];

 // Validation
 if ($old['license_number'] === '') $errors['license_number'] = 'License number is required.';
 elseif (!preg_match('/^[A-Z0-9\s\-]{3,15}$/', $old['license_number']))
 $errors['license_number'] = 'Invalid license number format.';

 if ($old['engine_number'] === '') $errors['engine_number'] = 'Engine number is required.';
 elseif (!preg_match('/^[A-Z0-9\-]{5,25}$/', $old['engine_number']))
 $errors['engine_number'] = 'Engine number must be 5-25 alphanumeric characters.';

 if ($old['make'] === '') $errors['make'] = 'Car make is required.';
 if ($old['model'] === '') $errors['model'] = 'Car model is required.';

 $yearNum = (int) $old['year'];
 if ($old['year'] === '' || $yearNum < 1970 || $yearNum > (int)date('Y') + 1)
 $errors['year'] = 'Enter a valid year (1970–' . (date('Y')+1) . ').';

 if (empty($errors)) {
 $result = add_car($clientId, $old);
 if ($result['ok']) {
 redirect('my-cars.php', 'Car registered successfully!', 'success');
 } else {
 $errors['general'] = $result['msg'];
 }
 }
}

$cars = get_client_cars($clientId);
$pageTitle = 'My Cars';
$activeNav = 'cars';
$rootPath = '';

include __DIR__ . '/includes/header.php';
?>

<?= render_flash() ?>

<div class="page-header">
 <h1>My Cars</h1>
 <p>Register the vehicles you want to bring in for service.</p>
</div>

<!-- Add car form -->
<div class="card mb-32">
 <div class="card-title"> Add New Car</div>

 <?php if (!empty($errors['general'])): ?>
 <div class="flash flash-error"><?= e($errors['general']) ?></div>
 <?php endif; ?>

 <form method="POST" action="my-cars.php" data-validate novalidate>

 <div class="form-row">
 <div class="form-group">
 <label class="form-label" for="license_number">License Plate Number</label>
 <input
 type="text"
 id="license_number"
 name="license_number"
 class="form-control <?= isset($errors['license_number']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['license_number'] ?? '') ?>"
 required
 data-label="License number"
 data-pattern="^[A-Za-z0-9\s\-]{3,15}$"
 data-pattern-msg="3–15 alphanumeric characters only."
 placeholder="DHAKA-12-3456"
 style="text-transform:uppercase;"
 >
 <?php if (isset($errors['license_number'])): ?>
 <span class="invalid-feedback"><?= e($errors['license_number']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-group">
 <label class="form-label" for="engine_number">Engine Number</label>
 <input
 type="text"
 id="engine_number"
 name="engine_number"
 class="form-control <?= isset($errors['engine_number']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['engine_number'] ?? '') ?>"
 required
 data-label="Engine number"
 data-pattern="^[A-Za-z0-9\-]{5,25}$"
 data-pattern-msg="5–25 alphanumeric characters."
 placeholder="ENG-2024-XXXXX"
 style="text-transform:uppercase;"
 >
 <?php if (isset($errors['engine_number'])): ?>
 <span class="invalid-feedback"><?= e($errors['engine_number']) ?></span>
 <?php endif; ?>
 </div>
 </div>

 <div class="form-row">
 <div class="form-group">
 <label class="form-label" for="make">Make (Brand)</label>
 <input
 type="text"
 id="make"
 name="make"
 class="form-control <?= isset($errors['make']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['make'] ?? '') ?>"
 required
 data-label="Make"
 placeholder="Toyota"
 >
 <?php if (isset($errors['make'])): ?>
 <span class="invalid-feedback"><?= e($errors['make']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-group">
 <label class="form-label" for="model">Model</label>
 <input
 type="text"
 id="model"
 name="model"
 class="form-control <?= isset($errors['model']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['model'] ?? '') ?>"
 required
 data-label="Model"
 placeholder="Corolla"
 >
 <?php if (isset($errors['model'])): ?>
 <span class="invalid-feedback"><?= e($errors['model']) ?></span>
 <?php endif; ?>
 </div>
 </div>

 <div class="form-row" style="max-width:300px;">
 <div class="form-group">
 <label class="form-label" for="year">Year</label>
 <input
 type="number"
 id="year"
 name="year"
 class="form-control <?= isset($errors['year']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['year'] ?? date('Y')) ?>"
 required
 min="1970"
 max="<?= date('Y') + 1 ?>"
 data-label="Year"
 placeholder="<?= date('Y') ?>"
 >
 <?php if (isset($errors['year'])): ?>
 <span class="invalid-feedback"><?= e($errors['year']) ?></span>
 <?php endif; ?>
 </div>
 </div>

 <button type="submit" class="btn btn-primary">Add Car</button>

 </form>
</div>

<!-- Cars list -->
<div class="card">
 <div class="card-title"> Registered Vehicles (<?= count($cars) ?>)</div>

 <?php if (empty($cars)): ?>
 <div class="empty-state">
 <div class="icon"></div>
 <h3>No cars registered yet</h3>
 <p>Add your first vehicle above to start booking appointments.</p>
 </div>
 <?php else: ?>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>License Plate</th>
 <th>Engine No.</th>
 <th>Make</th>
 <th>Model</th>
 <th>Year</th>
 <th>Registered</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($cars as $car): ?>
 <tr>
 <td style="font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; letter-spacing:.04em;"><?= e($car['license_number']) ?></td>
 <td class="text-muted" style="font-size:.85rem;"><?= e($car['engine_number']) ?></td>
 <td><?= e($car['make']) ?></td>
 <td><?= e($car['model']) ?></td>
 <td><?= e($car['year']) ?></td>
 <td class="text-muted" style="font-size:.85rem;"><?= date('d M Y', strtotime($car['created_at'])) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
