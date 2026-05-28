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

-- ── DATE DEMO ──
INSERT INTO users (email, password_hash, first_name, last_name, experience, newsletter)
VALUES (
    'demo@heavyduty.ro',
    '$2y$12$Gv0tAZQFPQXjjbv0oH3uUuatWNUWPfCEmuhwvLqGIVT81TlPBy3um',
    'Andrei',
    'Popescu',
    'intermediar',
    1
)
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    password_hash = VALUES(password_hash),
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    experience = VALUES(experience),
    newsletter = VALUES(newsletter);

SET @demo_user_id = LAST_INSERT_ID();

DELETE FROM workout_journal WHERE user_id = @demo_user_id;
DELETE FROM body_fat_estimates WHERE user_id = @demo_user_id;

INSERT INTO body_fat_estimates (user_id, percentage, category, gender, age, weight, height, created_at) VALUES
(@demo_user_id, 18.4, 'fitness', 'male', 31, 86.2, 181.0, '2026-05-05 08:30:00'),
(@demo_user_id, 17.6, 'fitness', 'male', 31, 85.4, 181.0, '2026-05-12 08:30:00'),
(@demo_user_id, 16.9, 'athletic', 'male', 31, 84.7, 181.0, '2026-05-19 08:30:00');

INSERT INTO workout_journal (user_id, date, type, exercises, duration, intensity, notes, created_at) VALUES
(@demo_user_id, '2026-05-06', 'Piept + Spate', 'Împins la piept, fluturări, ramat cu bara, tracțiuni', 62, 8, 'Sesiune Heavy Duty cu progres la ramat.', '2026-05-06 19:15:00'),
(@demo_user_id, '2026-05-10', 'Picioare', 'Genuflexiuni, presă, îndreptări românești, ridicări pe vârfuri', 58, 9, 'Volum minim, intensitate maximă.', '2026-05-10 18:40:00'),
(@demo_user_id, '2026-05-15', 'Umeri + Brațe', 'Presă militară, ridicări laterale, flexii biceps, extensii triceps', 49, 8, 'Formă bună și recuperare rapidă.', '2026-05-15 19:05:00'),
(@demo_user_id, '2026-05-22', 'Spate + Abdomen', 'Tracțiuni, ramat ganteră, hiperextensii, crunch-uri controlate', 55, 7, 'Focus pe contracție și execuție strictă.', '2026-05-22 18:55:00');

-- ── UTILIZATOR TEST (parolă: Test1234!) ──
-- Șterge linia de mai jos după ce îți creezi contul propriu
-- INSERT INTO users (email, password_hash, first_name, last_name, experience)
-- VALUES ('test@test.ro', '$2y$12$...', 'Ion', 'Popescu', 'intermediar');
