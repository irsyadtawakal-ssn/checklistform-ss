-- SS Operations — Migration 001: Initial Schema
-- MySQL 8.0 / MariaDB 10.5+
-- Jalankan via phpMyAdmin atau: mysql -u USER -p DBNAME < 001_init.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- outlets
-- ============================================================
CREATE TABLE IF NOT EXISTS `outlets` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `code`               VARCHAR(20)     NOT NULL COMMENT 'Kode unik outlet, mis. SS-JKT-01',
  `name`               VARCHAR(100)    NOT NULL,
  `type`               ENUM('internal','mitra') NOT NULL DEFAULT 'internal',
  `address`            TEXT            NULL,
  `daily_sales_target` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Target penjualan harian (Rupiah). 0 = tidak ada target.',
  `active`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_outlets_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)     NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `full_name`     VARCHAR(100)    NOT NULL,
  `role`          ENUM('outlet','spv','owner','admin') NOT NULL,
  `outlet_id`     INT UNSIGNED    NULL COMMENT 'NULL untuk SPV/Owner/Admin',
  `active`        TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP       NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_outlet` (`outlet_id`),
  KEY `idx_users_role` (`role`),
  CONSTRAINT `fk_users_outlet`
    FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- checklist_submissions
-- 1 outlet × 1 tanggal × 1 shift = 1 baris (UNIQUE constraint)
-- ============================================================
CREATE TABLE IF NOT EXISTS `checklist_submissions` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `outlet_id`        INT UNSIGNED    NOT NULL,
  `user_id`          INT UNSIGNED    NOT NULL,
  `shift`            ENUM('open','ops','close') NOT NULL,
  `submission_date`  DATE            NOT NULL,
  `status`           ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
  `data_fields_json` JSON            NULL COMMENT 'Numerik shift: suhu, stok, kas, dll',
  `pic_name`         VARCHAR(100)    NOT NULL COMMENT 'Nama PIC shift (wajib, akun shared)',
  `spv_name`         VARCHAR(100)    NULL,
  `handover_note`    TEXT            NULL,
  `late`             TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 jika submit di luar window shift',
  `locked`           TINYINT(1)      NOT NULL DEFAULT 0,
  `unlocked_by`      INT UNSIGNED    NULL,
  `unlocked_at`      TIMESTAMP       NULL,
  `submitted_at`     TIMESTAMP       NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission_per_shift` (`outlet_id`, `submission_date`, `shift`),
  KEY `idx_submissions_outlet_date` (`outlet_id`, `submission_date`),
  KEY `idx_submissions_status` (`status`),
  KEY `idx_submissions_date` (`submission_date`),
  CONSTRAINT `fk_submissions_outlet`
    FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`),
  CONSTRAINT `fk_submissions_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_submissions_unlocked_by`
    FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- checklist_items_state
-- Satu baris per item per submission
-- ============================================================
CREATE TABLE IF NOT EXISTS `checklist_items_state` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `submission_id` INT UNSIGNED    NOT NULL,
  `item_code`     VARCHAR(20)     NOT NULL COMMENT 'Kode item mis. o1a, p2c',
  `checked`       TINYINT(1)      NOT NULL DEFAULT 0,
  `notes`         TEXT            NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_per_submission` (`submission_id`, `item_code`),
  CONSTRAINT `fk_items_submission`
    FOREIGN KEY (`submission_id`) REFERENCES `checklist_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- spv_visits
-- ============================================================
CREATE TABLE IF NOT EXISTS `spv_visits` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `outlet_id`    INT UNSIGNED    NOT NULL,
  `spv_id`       INT UNSIGNED    NOT NULL,
  `visit_date`   DATE            NOT NULL,
  `time_arrive`  TIME            NULL,
  `time_leave`   TIME            NULL,
  `visit_shift`  ENUM('open','ops','close') NULL,
  `pic_on_duty`  VARCHAR(100)    NULL,
  `payload_json` JSON            NULL COMMENT 'Data inventaris, penjualan, stok, evaluasi',
  `summary_json` JSON            NULL COMMENT 'Auto-generated flag red/amber/green',
  `submitted_at` TIMESTAMP       NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visits_outlet_date` (`outlet_id`, `visit_date`),
  KEY `idx_visits_spv` (`spv_id`),
  KEY `idx_visits_date` (`visit_date`),
  CONSTRAINT `fk_visits_outlet`
    FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`),
  CONSTRAINT `fk_visits_spv`
    FOREIGN KEY (`spv_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- spv_visit_photos
-- ============================================================
CREATE TABLE IF NOT EXISTS `spv_visit_photos` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `visit_id`    INT UNSIGNED    NOT NULL,
  `file_path`   VARCHAR(500)    NOT NULL COMMENT 'Relatif dari project root, mis. uploads/spv/SS-JKT-01/2026-05-15/uuid.jpg',
  `thumb_path`  VARCHAR(500)    NULL,
  `tag`         VARCHAR(50)     NULL COMMENT 'exterior|dapur|stok|kasir|drip-tray|karyawan|rusak|lain',
  `label`       VARCHAR(200)    NULL COMMENT 'Caption / alasan foto (wajib diisi jika ada foto)',
  `uploaded_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_photos_visit` (`visit_id`),
  CONSTRAINT `fk_photos_visit`
    FOREIGN KEY (`visit_id`) REFERENCES `spv_visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- spv_visit_employees
-- ============================================================
CREATE TABLE IF NOT EXISTS `spv_visit_employees` (
  `id`       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `visit_id` INT UNSIGNED    NOT NULL,
  `name`     VARCHAR(100)    NOT NULL,
  `role`     VARCHAR(50)     NULL COMMENT 'Jabatan / posisi karyawan',
  `eval_json` JSON           NULL COMMENT '6 kriteria star-rating: {kebersihan, sikap, kecepatan, pengetahuan, kehadiran, grooming}',
  `notes`    TEXT            NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employees_visit` (`visit_id`),
  CONSTRAINT `fk_employees_visit`
    FOREIGN KEY (`visit_id`) REFERENCES `spv_visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- audit_log
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NULL,
  `action`      VARCHAR(100)    NOT NULL COMMENT 'mis. login, logout, checklist_unlock, user_create',
  `target_type` VARCHAR(50)     NULL COMMENT 'mis. checklist_submission, user, outlet',
  `target_id`   INT UNSIGNED    NULL,
  `payload_json` JSON           NULL COMMENT 'Detail tambahan',
  `ip`          VARCHAR(45)     NULL COMMENT 'IPv4 atau IPv6',
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
