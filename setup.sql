-- =====================================================
-- HeavyDutyRO — Setup Baza de Date
-- Rulează în phpMyAdmin sau MySQL CLI
-- =====================================================

CREATE DATABASE IF NOT EXISTS heavydutyro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE heavydutyro;

-- ── UTILIZATORI ──
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    first_name      VARCHAR(100) DEFAULT '',
    last_name       VARCHAR(100) DEFAULT '',
    experience      ENUM('incepator','intermediar','avansat') DEFAULT 'incepator',
    newsletter      TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── JURNAL ANTRENAMENTE ──
CREATE TABLE IF NOT EXISTS workout_journal (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    date        DATE NOT NULL,
    type        VARCHAR(100),
    exercises   TEXT,
    duration    INT DEFAULT 0,
    intensity   INT DEFAULT 5,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB;

-- ── ESTIMĂRI GRĂSIME CORPORALĂ ──
CREATE TABLE IF NOT EXISTS body_fat_estimates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    percentage  DECIMAL(4,1),
    category    VARCHAR(50),
    gender      VARCHAR(10),
    age         INT DEFAULT NULL,
    weight      DECIMAL(5,2) DEFAULT NULL,
    height      DECIMAL(5,2) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── UTILIZATOR TEST (parolă: Test1234!) ──
-- Șterge linia de mai jos după ce îți creezi contul propriu
-- INSERT INTO users (email, password_hash, first_name, last_name, experience)
-- VALUES ('test@test.ro', '$2y$12$...', 'Ion', 'Popescu', 'intermediar');
