CREATE DATABASE IF NOT EXISTS pneumo_monitoring
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pneumo_monitoring;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'district_admin',
    district_key VARCHAR(80) NOT NULL,
    district_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(180) NOT NULL,
    birth_day TINYINT UNSIGNED NOT NULL,
    birth_month TINYINT UNSIGNED NOT NULL,
    birth_year SMALLINT UNSIGNED NOT NULL,
    passport_series CHAR(2) NOT NULL,
    passport_number CHAR(7) NOT NULL,
    pinfl CHAR(14) NOT NULL,
    district_key VARCHAR(80) NOT NULL,
    district_name VARCHAR(150) NOT NULL,
    village VARCHAR(180) NOT NULL,
    last_visit VARCHAR(80) NOT NULL DEFAULT '',
    infection_date DATE NOT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Qabul kutilmoqda',
    details TEXT NULL,
    is_source TINYINT(1) NOT NULL DEFAULT 0,
    source_patient_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_patients_passport (passport_series, passport_number),
    UNIQUE KEY uq_patients_pinfl (pinfl),
    KEY idx_patients_district_village (district_key, village),
    KEY idx_patients_source (source_patient_id),
    CONSTRAINT fk_patients_source
        FOREIGN KEY (source_patient_id) REFERENCES patients(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patients_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patients_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_calls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    patient_name VARCHAR(180) NOT NULL,
    district_key VARCHAR(80) NOT NULL,
    district_name VARCHAR(150) NOT NULL,
    village VARCHAR(180) NOT NULL,
    called_by INT UNSIGNED NULL,
    call_status VARCHAR(50) NOT NULL DEFAULT 'new',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    seen_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_calls_district_status (district_key, call_status),
    KEY idx_calls_patient (patient_id),
    CONSTRAINT fk_calls_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_calls_called_by
        FOREIGN KEY (called_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, phone, password, role, district_key, district_name)
VALUES ('Butun Buxoro administratori', '+998772930688', 'Buxoro2025@', 'butun_buxoro', 'butun_buxoro', 'Butun Buxoro')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    district_key = VALUES(district_key),
    district_name = VALUES(district_name);
