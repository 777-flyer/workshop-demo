<?php
/* admin/index.php — Redirect to dashboard or login */
require_once __DIR__ . '/../includes/auth.php';
start_session();
admin_logged_in() ? redirect('dashboard.php') : redirect('login.php');
