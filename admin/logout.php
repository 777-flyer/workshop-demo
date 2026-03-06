<?php
require_once __DIR__ . '/../includes/auth.php';
start_session();
logout_admin(); // destroys session and redirects to admin/login.php
