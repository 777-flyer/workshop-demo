-- ============================================================
-- Car Workshop Appointment System - Database Setup
-- ============================================================

CREATE DATABASE IF NOT EXISTS workshop_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE workshop_db;

-- -------------------------------------------------------
-- Clients (end users who book appointments)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,           -- bcrypt hash
    address     TEXT          NOT NULL,
    phone       VARCHAR(20)   NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- Admins (workshop managers)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(60)   NOT NULL UNIQUE,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,           -- bcrypt hash
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- Mechanics
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS mechanics (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    speciality  VARCHAR(150)  NOT NULL DEFAULT 'General Mechanic',
    bio         TEXT,
    image_url   VARCHAR(255)  DEFAULT NULL,       -- relative path or NULL
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- Cars owned by clients (one client → many cars)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS cars (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED NOT NULL,
    license_number  VARCHAR(50)  NOT NULL,
    engine_number   VARCHAR(100) NOT NULL,
    make            VARCHAR(60)  NOT NULL,
    model           VARCHAR(60)  NOT NULL,
    year            YEAR         NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cars_client FOREIGN KEY (client_id)
        REFERENCES clients(id) ON DELETE CASCADE,

    -- A license number must be unique per client
    UNIQUE KEY uq_client_license (client_id, license_number)
);

-- -------------------------------------------------------
-- Appointments
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id        INT UNSIGNED NOT NULL,
    car_id           INT UNSIGNED NOT NULL,
    mechanic_id      INT UNSIGNED NOT NULL,
    appointment_date DATE         NOT NULL,
    status           ENUM('pending','confirmed','completed','cancelled')
                                  NOT NULL DEFAULT 'pending',
    notes            TEXT,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_appt_client   FOREIGN KEY (client_id)
        REFERENCES clients(id)   ON DELETE CASCADE,
    CONSTRAINT fk_appt_car      FOREIGN KEY (car_id)
        REFERENCES cars(id)      ON DELETE CASCADE,
    CONSTRAINT fk_appt_mechanic FOREIGN KEY (mechanic_id)
        REFERENCES mechanics(id) ON DELETE RESTRICT,

    -- A client can have at most ONE appointment per day (across any mechanic)
    UNIQUE KEY uq_client_date (client_id, appointment_date),

    INDEX idx_mechanic_date (mechanic_id, appointment_date),
    INDEX idx_appointment_date (appointment_date)
);


-- -------------------------------------------------------
-- Seed: 5 mechanics with placeholder avatar paths
-- -------------------------------------------------------
INSERT IGNORE INTO mechanics (name, speciality, bio, image_url) VALUES
('Henrich Gussler',  'Engine Overhaul',       'Expert in engine diagnostics and full overhauls with 12 years of experience.', 'assets/images/1.jpg'),
('Fredrick Mitchell',    'Electrical Systems',    'Specialist in automotive electrical wiring, ECU programming and battery systems.', 'assets/images/2.jpg'),
('Marion Davids',      'Transmission & Gears',  'Seasoned professional handling manual & automatic transmission rebuilds.', 'assets/images/3.jpg'),
('Fred Muller',    'Brakes & Suspension',   'Focused on chassis safety: brakes, shocks, struts and alignment.', 'assets/images/4.jpg'),
('Fabian Gustav','AC & Cooling Systems', 'Certified HVAC technician for automotive air conditioning and engine cooling.', 'assets/images/5.jpg');
