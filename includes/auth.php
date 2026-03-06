<?php
/**
 * includes/auth.php
 *
 * Session bootstrap + authentication helpers.
 * Call start_session() at the very top of every page.
 */

// ── Constants ───────────────────────────────────────────────
const MAX_SLOTS_PER_MECHANIC = 4;   // max active appointments per mechanic per day
const SESSION_LIFETIME       = 3600; // seconds (1 hour idle timeout)

// ── Session bootstrap ───────────────────────────────────────

/**
 * Initialise a secure session.
 * Must be called before any output.
 */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,               // browser-session cookie
        'path'     => '/',
        'secure'   => false,           // set true when using HTTPS in production
        'httponly' => true,            // JS cannot access the cookie
        'samesite' => 'Lax',
    ]);

    session_start();

    // Idle timeout check
    if (isset($_SESSION['last_active']) &&
        (time() - $_SESSION['last_active']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_active'] = time();

    // Regenerate ID on first load to prevent session fixation
    if (empty($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = true;
    }
}

// ── Client helpers ──────────────────────────────────────────

/** Returns true when a client is logged in. */
function client_logged_in(): bool
{
    return !empty($_SESSION['client_id']);
}

/** Redirect to login if no client session. */
function require_client(): void
{
    if (!client_logged_in()) {
        redirect('login.php', 'Please log in to continue.');
    }
}

/** Log in a client and store minimal session data. */
function login_client(array $client): void
{
    session_regenerate_id(true);
    $_SESSION['client_id']   = (int) $client['id'];
    $_SESSION['client_name'] = $client['name'];
    $_SESSION['client_email']= $client['email'];
}

/** Destroy client session and redirect to login. */
function logout_client(): void
{
    session_unset();
    session_destroy();
    redirect('login.php');
}

/** Returns the logged-in client's id (or null). */
function current_client_id(): ?int
{
    return $_SESSION['client_id'] ?? null;
}

// ── Admin helpers ───────────────────────────────────────────

/** Returns true when an admin is logged in. */
function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Redirect to admin login if no admin session. */
function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('login.php', 'Admin access required.');
    }
}

/** Log in an admin. */
function login_admin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id']       = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
}

/** Destroy admin session. */
function logout_admin(): void
{
    session_unset();
    session_destroy();
    redirect('login.php');
}

// ── Flash messages ──────────────────────────────────────────

/**
 * Store a one-time flash message.
 *
 * @param string $type  'success' | 'error' | 'info' | 'warning'
 */
function set_flash(string $msg, string $type = 'info'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

/**
 * Return and clear the current flash message (or null).
 *
 * @return array{msg:string,type:string}|null
 */
function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ── Utility ─────────────────────────────────────────────────

/**
 * Safe redirect helper (prevents header injection).
 *
 * @param string $url  Relative or absolute URL
 * @param string $flash Optional flash message
 * @param string $flashType
 */
function redirect(string $url, string $flash = '', string $flashType = 'info'): never
{
    if ($flash !== '') {
        set_flash($flash, $flashType);
    }
    // Allow only relative URLs or same-origin absolute URLs
    $url = filter_var($url, FILTER_SANITIZE_URL);
    header('Location: ' . $url);
    exit;
}

/** Sanitize a string for safe HTML output. */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Render the HTML for a flash message banner (clears the message). */
function render_flash(): string
{
    $flash = get_flash();
    if ($flash === null) {
        return '';
    }
    $type = e($flash['type']);
    $msg  = e($flash['msg']);
    return "<div class=\"flash flash-{$type}\" role=\"alert\">{$msg}</div>";
}
