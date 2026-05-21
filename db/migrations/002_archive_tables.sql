-- SS Operations — Migration 002: Archive Tables
-- Tabel arsip untuk data > 2 tahun (dijalankan oleh scripts/archive-data.php via cron)
-- Jalankan via phpMyAdmin atau: mysql -u USER -p DBNAME < 002_archive_tables.sql

SET NAMES utf8mb4;

-- ============================================================
-- checklist_submissions_archive
-- ============================================================
CREATE TABLE IF NOT EXISTS `checklist_submissions_archive` (
  `id`               INT UNSIGNED    NOT NULL,
  `outlet_id`        INT UNSIGNED    NOT NULL,
  `user_id`          INT UNSIGNED    NOT NULL,
  `shift`            ENUM('open','ops','close') NOT NULL,
  `submission_date`  DATE            NOT NULL,
  `status`           ENUM('draft','submitted') NOT NULL,
  `data_fields_json` JSON            NULL,
  `pic_name`         VARCHAR(100)    NOT NULL,
  `spv_name`         VARCHAR(100)    NULL,
  `handover_note`    TEXT            NULL,
  `late`             TINYINT(1)      NOT NULL DEFAULT 0,
  `locked`           TINYINT(1)      NOT NULL DEFAULT 0,
  `unlocked_by`      INT UNSIGNED    NULL,
  `unlocked_at`      TIMESTAMP       NULL,
  `submitted_at`     TIMESTAMP       NULL,
  `created_at`       TIMESTAMP       NOT NULL,
  `updated_at`       TIMESTAMP       NOT NULL,
  `archived_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_arch_sub_outlet_date` (`outlet_id`, `submission_date`),
  KEY `idx_arch_sub_date`        (`submission_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Arsip submission checklist lebih dari 2 tahun';

-- ============================================================
-- checklist_items_state_archive
-- ============================================================
CREATE TABLE IF NOT EXISTS `checklist_items_state_archive` (
  `id`            INT UNSIGNED    NOT NULL,
  `submission_id` INT UNSIGNED    NOT NULL,
  `item_code`     VARCHAR(20)     NOT NULL,
  `checked`       TINYINT(1)      NOT NULL DEFAULT 0,
  `notes`         TEXT            NULL,
  `archived_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_arch_items_sub` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Arsip item state untuk submission yang sudah diarsipkan';

-- ============================================================
-- spv_visits_archive
-- ============================================================
CREATE TABLE IF NOT EXISTS `spv_visits_archive` (
  `id`           INT UNSIGNED    NOT NULL,
  `outlet_id`    INT UNSIGNED    NOT NULL,
  `spv_id`       INT UNSIGNED    NOT NULL,
  `visit_date`   DATE            NOT NULL,
  `time_arrive`  TIME            NULL,
  `time_leave`   TIME            NULL,
  `visit_shift`  ENUM('open','ops','close') NULL,
  `pic_on_duty`  VARCHAR(100)    NULL,
  `payload_json` JSON            NULL,
  `summary_json` JSON            NULL,
  `submitted_at` TIMESTAMP       NULL,
  `created_at`   TIMESTAMP       NOT NULL,
  `updated_at`   TIMESTAMP       NOT NULL,
  `archived_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_arch_visits_outlet_date` (`outlet_id`, `visit_date`),
  KEY `idx_arch_visits_date`        (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Arsip kunjungan SPV lebih dari 2 tahun';
