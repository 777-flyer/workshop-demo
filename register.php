<?php
/**
 * register.php
 *
 * New client registration.
 * POST: name, email, password, confirm_password, address, phone
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();

// Redirect already-logged-in users
if (client_logged_in()) {
 redirect('dashboard.php');
}

$errors = [];
$old = []; // repopulate form fields on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 // Collect & sanitize inputs
 $old = [
 'name' => trim($_POST['name'] ?? ''),
 'email' => trim($_POST['email'] ?? ''),
 'address' => trim($_POST['address'] ?? ''),
 'phone' => trim($_POST['phone'] ?? ''),
 ];
 $password = $_POST['password'] ?? '';
 $confirmPassword = $_POST['confirm_password'] ?? '';

 // Server-side validation 
 if ($old['name'] === '') $errors['name'] = 'Full name is required.';
 elseif (strlen($old['name']) < 3) $errors['name'] = 'Name must be at least 3 characters.';

 if ($old['email'] === '') $errors['email'] = 'Email is required.';
 elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))
 $errors['email'] = 'Enter a valid email address.';

 if ($password === '') $errors['password']= 'Password is required.';
 elseif (strlen($password) < 8) $errors['password']= 'Password must be at least 8 characters.';
 elseif ($password !== $confirmPassword)$errors['confirm'] = 'Passwords do not match.';

 if ($old['address'] === '') $errors['address'] = 'Address is required.';

 if ($old['phone'] === '') $errors['phone'] = 'Phone number is required.';
 elseif (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $old['phone']))
 $errors['phone'] = 'Enter a valid phone number.';

 // Register if no errors 
 if (empty($errors)) {
 $result = register_client([
 'name' => $old['name'],
 'email' => $old['email'],
 'password' => $password,
 'address' => $old['address'],
 'phone' => $old['phone'],
 ]);

 if ($result['ok']) {
 login_client($result['client']);
 redirect('dashboard.php', 'Welcome! Your account has been created.', 'success');
 } else {
 $errors['general'] = $result['msg'];
 }
 }
}

// Render 
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Register — AutoCare Workshop</title>
 <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
 <div class="auth-box">
 <div class="auth-logo">
 <h1> AutoCare</h1>
 <p>Create your client account</p>
 </div>

 <?php if (!empty($errors['general'])): ?>
 <div class="flash flash-error"><?= e($errors['general']) ?></div>
 <?php endif; ?>

 <div class="card">
 <form method="POST" action="register.php" data-validate novalidate>

 <div class="form-row">
 <div class="form-group">
 <label class="form-label" for="name">Full Name</label>
 <input
 type="text"
 id="name"
 name="name"
 class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['name'] ?? '') ?>"
 required
 data-label="Full name"
 data-minlen="3"
 placeholder="Ahmed Hossain"
 autocomplete="name"
 >
 <?php if (isset($errors['name'])): ?>
 <span class="invalid-feedback"><?= e($errors['name']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-group">
 <label class="form-label" for="phone">Phone Number</label>
 <input
 type="tel"
 id="phone"
 name="phone"
 class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['phone'] ?? '') ?>"
 required
 data-label="Phone"
 data-pattern="^\+?[0-9\s\-]{7,15}$"
 data-pattern-msg="Enter a valid phone number."
 placeholder="+880 17XX XXXXXX"
 autocomplete="tel"
 >
 <?php if (isset($errors['phone'])): ?>
 <span class="invalid-feedback"><?= e($errors['phone']) ?></span>
 <?php endif; ?>
 </div>
 </div>

 <div class="form-group">
 <label class="form-label" for="email">Email Address</label>
 <input
 type="email"
 id="email"
 name="email"
 class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
 value="<?= e($old['email'] ?? '') ?>"
 required
 data-label="Email"
 placeholder="you@example.com"
 autocomplete="email"
 >
 <?php if (isset($errors['email'])): ?>
 <span class="invalid-feedback"><?= e($errors['email']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-group">
 <label class="form-label" for="address">Address</label>
 <textarea
 id="address"
 name="address"
 class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
 rows="2"
 required
 data-label="Address"
 placeholder="House, Road, Area, City"
 ><?= e($old['address'] ?? '') ?></textarea>
 <?php if (isset($errors['address'])): ?>
 <span class="invalid-feedback"><?= e($errors['address']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-row">
 <div class="form-group">
 <label class="form-label" for="password">Password</label>
 <input
 type="password"
 id="password"
 name="password"
 class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
 required
 data-label="Password"
 data-minlen="8"
 placeholder="Min. 8 characters"
 autocomplete="new-password"
 >
 <?php if (isset($errors['password'])): ?>
 <span class="invalid-feedback"><?= e($errors['password']) ?></span>
 <?php endif; ?>
 </div>

 <div class="form-group">
 <label class="form-label" for="confirm_password">Confirm Password</label>
 <input
 type="password"
 id="confirm_password"
 name="confirm_password"
 class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
 required
 data-label="Confirm password"
 data-match="password"
 placeholder="Repeat password"
 autocomplete="new-password"
 >
 <?php if (isset($errors['confirm'])): ?>
 <span class="invalid-feedback"><?= e($errors['confirm']) ?></span>
 <?php endif; ?>
 </div>
 </div>

 <button type="submit" class="btn btn-primary btn-block btn-lg">
 Create Account
 </button>

 </form>
 </div>

 <p class="text-center mt-12 text-muted" style="font-size:.9rem;">
 Already have an account? <a href="login.php">Sign in</a>
 </p>
 </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
