<?php
require_once __DIR__ . '/includes/auth.php';
start_session();
logout_client(); // destroys session and redirects to login.php
