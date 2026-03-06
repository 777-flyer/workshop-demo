<?php
/**
 * includes/functions.php
 *
 * Pure business-logic helpers.
 * All DB interactions go through PDO prepared statements — no raw
 * user input is ever interpolated into SQL.
 */

require_once __DIR__ . '/../config/db.php';

// ── Mechanics ───────────────────────────────────────────────

/**
 * Return all active mechanics with their free-slot count for a given date.
 *
 * @return list<array{id,name,speciality,bio,image_url,booked,free_slots}>
 */
function get_mechanics_with_slots(string $date): array
{
    $db  = get_db();
    $sql = "
        SELECT
            m.id,
            m.name,
            m.speciality,
            m.bio,
            m.image_url,
            COUNT(a.id)                                AS booked,
            (:max_slots - COUNT(a.id))                 AS free_slots
        FROM mechanics m
        LEFT JOIN appointments a
               ON a.mechanic_id = m.id
              AND a.appointment_date = :date
              AND a.status NOT IN ('cancelled')
        WHERE m.is_active = 1
        GROUP BY m.id
        ORDER BY m.name ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':max_slots' => MAX_SLOTS_PER_MECHANIC, ':date' => $date]);
    return $stmt->fetchAll();
}

/** Return a single mechanic row by id. */
function get_mechanic(int $id): ?array
{
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM mechanics WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ── Slot availability ───────────────────────────────────────

/**
 * Count non-cancelled appointments for a mechanic on a specific date.
 */
function count_mechanic_bookings(int $mechanicId, string $date): int
{
    $db   = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE mechanic_id = ? AND appointment_date = ? AND status != 'cancelled'
    ");
    $stmt->execute([$mechanicId, $date]);
    return (int) $stmt->fetchColumn();
}

/**
 * Check whether a client already has a non-cancelled appointment on the given date.
 */
function client_has_appointment_on_date(int $clientId, string $date): bool
{
    $db   = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE client_id = ? AND appointment_date = ? AND status != 'cancelled'
    ");
    $stmt->execute([$clientId, $date]);
    return (int) $stmt->fetchColumn() > 0;
}

// ── Appointments ────────────────────────────────────────────

/**
 * Create a new appointment after all validations pass.
 *
 * Returns ['ok' => true, 'id' => int] on success or
 *         ['ok' => false, 'msg' => string] on failure.
 */
function book_appointment(
    int    $clientId,
    int    $carId,
    int    $mechanicId,
    string $date,
    string $notes = ''
): array {
    // 1. Date must be today or in the future
    if ($date < date('Y-m-d')) {
        return ['ok' => false, 'msg' => 'Appointment date must be today or a future date.'];
    }

    // 2. Client duplicate check
    if (client_has_appointment_on_date($clientId, $date)) {
        return ['ok' => false, 'msg' => 'You already have an appointment on that date. Please choose a different date.'];
    }

    // 3. Mechanic slot check
    if (count_mechanic_bookings($mechanicId, $date) >= MAX_SLOTS_PER_MECHANIC) {
        return ['ok' => false, 'msg' => 'This mechanic is fully booked on the selected date. Please choose another mechanic or date.'];
    }

    // 4. Confirm car belongs to this client
    $db   = get_db();
    $stmt = $db->prepare('SELECT id FROM cars WHERE id = ? AND client_id = ?');
    $stmt->execute([$carId, $clientId]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'msg' => 'Selected car does not belong to your account.'];
    }

    // 5. Insert
    $stmt = $db->prepare("
        INSERT INTO appointments (client_id, car_id, mechanic_id, appointment_date, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $carId, $mechanicId, $date, $notes]);
    return ['ok' => true, 'id' => (int) $db->lastInsertId()];
}

/**
 * Return all appointments for a client (newest first).
 */
function get_client_appointments(int $clientId): array
{
    $db   = get_db();
    $stmt = $db->prepare("
        SELECT
            a.id,
            a.appointment_date,
            a.status,
            a.notes,
            a.created_at,
            c.license_number,
            c.make,
            c.model,
            m.name  AS mechanic_name,
            m.speciality AS mechanic_speciality
        FROM appointments a
        JOIN cars       c ON c.id = a.car_id
        JOIN mechanics  m ON m.id = a.mechanic_id
        WHERE a.client_id = ?
        ORDER BY a.appointment_date DESC, a.created_at DESC
    ");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}

/**
 * Return all appointments (for admin), with optional filters.
 *
 * @param array $filters  Keys: date, mechanic_id, status
 */
function get_all_appointments(array $filters = []): array
{
    $db     = get_db();
    $where  = [];
    $params = [];

    if (!empty($filters['date'])) {
        $where[]  = 'a.appointment_date = :date';
        $params[':date'] = $filters['date'];
    }
    if (!empty($filters['mechanic_id'])) {
        $where[]  = 'a.mechanic_id = :mechanic_id';
        $params[':mechanic_id'] = (int) $filters['mechanic_id'];
    }
    if (!empty($filters['status'])) {
        $where[]  = 'a.status = :status';
        $params[':status'] = $filters['status'];
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT
            a.id,
            a.appointment_date,
            a.status,
            a.notes,
            a.created_at,
            cl.name  AS client_name,
            cl.phone AS client_phone,
            c.license_number,
            c.make,
            c.model,
            m.id    AS mechanic_id,
            m.name  AS mechanic_name
        FROM appointments a
        JOIN clients    cl ON cl.id = a.client_id
        JOIN cars        c ON  c.id = a.car_id
        JOIN mechanics   m ON  m.id = a.mechanic_id
        {$whereSql}
        ORDER BY a.appointment_date DESC, a.id DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Admin: update date and/or mechanic for an appointment.
 * Runs the same slot + duplicate-date validations again.
 */
function admin_update_appointment(int $apptId, string $newDate, int $newMechanicId): array
{
    $db   = get_db();

    // Fetch the appointment
    $stmt = $db->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$apptId]);
    $appt = $stmt->fetch();
    if (!$appt) {
        return ['ok' => false, 'msg' => 'Appointment not found.'];
    }

    // Check mechanic slot (exclude the current appointment from the count)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE mechanic_id = ? AND appointment_date = ? AND status != 'cancelled' AND id != ?
    ");
    $stmt->execute([$newMechanicId, $newDate, $apptId]);
    if ((int)$stmt->fetchColumn() >= MAX_SLOTS_PER_MECHANIC) {
        return ['ok' => false, 'msg' => 'Selected mechanic is fully booked on that date.'];
    }

    // Check client duplicate date (exclude current appointment)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE client_id = ? AND appointment_date = ? AND status != 'cancelled' AND id != ?
    ");
    $stmt->execute([$appt['client_id'], $newDate, $apptId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return ['ok' => false, 'msg' => 'Client already has another appointment on that date.'];
    }

    $stmt = $db->prepare("
        UPDATE appointments SET appointment_date = ?, mechanic_id = ? WHERE id = ?
    ");
    $stmt->execute([$newDate, $newMechanicId, $apptId]);
    return ['ok' => true];
}

// ── Cars ─────────────────────────────────────────────────────

/** Return all cars owned by a client. */
function get_client_cars(int $clientId): array
{
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM cars WHERE client_id = ? ORDER BY created_at DESC');
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}

/**
 * Add a car for a client.
 * Returns ['ok' => true, 'id' => int] or ['ok' => false, 'msg' => string].
 */
function add_car(int $clientId, array $data): array
{
    $db = get_db();

    // Check duplicate license per client
    $stmt = $db->prepare('SELECT id FROM cars WHERE client_id = ? AND license_number = ?');
    $stmt->execute([$clientId, $data['license_number']]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'msg' => 'You already have a car with that license number.'];
    }

    $stmt = $db->prepare("
        INSERT INTO cars (client_id, license_number, engine_number, make, model, year)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $clientId,
        $data['license_number'],
        $data['engine_number'],
        $data['make'],
        $data['model'],
        (int) $data['year'],
    ]);
    return ['ok' => true, 'id' => (int) $db->lastInsertId()];
}

// ── Client account ───────────────────────────────────────────

/**
 * Register a new client.
 * Returns ['ok' => true, 'client' => array] or ['ok' => false, 'msg' => string].
 */
function register_client(array $data): array
{
    $db = get_db();

    // Unique email check
    $stmt = $db->prepare('SELECT id FROM clients WHERE email = ?');
    $stmt->execute([strtolower(trim($data['email']))]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'msg' => 'An account with that email already exists.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("
        INSERT INTO clients (name, email, password, address, phone)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($data['name']),
        strtolower(trim($data['email'])),
        $hash,
        trim($data['address']),
        trim($data['phone']),
    ]);
    $id = (int) $db->lastInsertId();
    return ['ok' => true, 'client' => ['id' => $id, 'name' => $data['name'], 'email' => $data['email']]];
}

/**
 * Authenticate a client by email + password.
 * Returns the client row on success, null on failure.
 */
function authenticate_client(string $email, string $password): ?array
{
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM clients WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $client = $stmt->fetch();

    if ($client && password_verify($password, $client['password'])) {
        return $client;
    }
    return null;
}

/**
 * Authenticate an admin by username + password.
 */
function authenticate_admin(string $username, string $password): ?array
{
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([trim($username)]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        return $admin;
    }
    return null;
}

// ── Calendar data ─────────────────────────────────────────────

/**
 * Return mechanic availability for every day in the given month.
 * Used by the AJAX calendar endpoint.
 *
 * Returns: array keyed by YYYY-MM-DD, each value is
 *   [ {mechanic_id, name, booked, free_slots}, ... ]
 */
function get_monthly_availability(int $year, int $month): array
{
    $db   = get_db();
    $from = sprintf('%04d-%02d-01', $year, $month);
    $to   = date('Y-m-t', strtotime($from)); // last day of month

    // Count non-cancelled bookings per mechanic per day for this month
    $stmt = $db->prepare("
        SELECT
            a.appointment_date,
            a.mechanic_id,
            COUNT(a.id) AS booked
        FROM appointments a
        WHERE a.appointment_date BETWEEN :from AND :to
          AND a.status != 'cancelled'
        GROUP BY a.appointment_date, a.mechanic_id
    ");
    $stmt->execute([':from' => $from, ':to' => $to]);
    $booked = $stmt->fetchAll();

    // Index bookings: [ 'YYYY-MM-DD' => [ mechanic_id => booked_count ] ]
    $index = [];
    foreach ($booked as $row) {
        $index[$row['appointment_date']][(int)$row['mechanic_id']] = (int)$row['booked'];
    }

    // Fetch active mechanics
    $mechanics = $db->query('SELECT id, name, speciality FROM mechanics WHERE is_active = 1 ORDER BY name')->fetchAll();

    // Build result for every day in the month
    $result   = [];
    $daysInMonth = (int) date('t', strtotime($from));
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayData  = [];
        foreach ($mechanics as $m) {
            $mid     = (int)$m['id'];
            $bookedN = $index[$dateStr][$mid] ?? 0;
            $dayData[] = [
                'mechanic_id' => $mid,
                'name'        => $m['name'],
                'speciality'  => $m['speciality'],
                'booked'      => $bookedN,
                'free_slots'  => MAX_SLOTS_PER_MECHANIC - $bookedN,
            ];
        }
        $result[$dateStr] = $dayData;
    }
    return $result;
}
