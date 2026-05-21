-- SS Operations — Migration 003: Phase 7 additions
-- 1. Denormalize compliance_status + compliance_pct ke checklist_submissions
-- 2. Tabel spv_outlet_assignments untuk area-assignment (7.7)
-- Jalankan: mysql -u USER -p DBNAME < 003_phase7.sql

SET NAMES utf8mb4;

-- ============================================================
-- Tambah kolom ke checklist_submissions (sudah ada → skip jika error)
-- ============================================================
ALTER TABLE `checklist_submissions`
  ADD COLUMN IF NOT EXISTS `compliance_status` ENUM('ok','warn','danger') NULL
    COMMENT 'Dihitung saat submit: danger=kritikal terlewat, warn=kurang, ok=lengkap'
    AFTER `locked`,
  ADD COLUMN IF NOT EXISTS `compliance_pct` TINYINT UNSIGNED NULL
    COMMENT 'Persentase item selesai saat submit (0-100)'
    AFTER `compliance_status`;

ALTER TABLE `checklist_submissions`
  ADD INDEX IF NOT EXISTS `idx_submissions_status_computed` (`compliance_status`),
  ADD INDEX IF NOT EXISTS `idx_submissions_date_status`    (`submission_date`, `compliance_status`);

-- ============================================================
-- spv_outlet_assignments
-- ============================================================
CREATE TABLE IF NOT EXISTS `spv_outlet_assignments` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `spv_id`      INT UNSIGNED    NOT NULL,
  `outlet_id`   INT UNSIGNED    NOT NULL,
  `assigned_by` INT UNSIGNED    NULL COMMENT 'Admin/Owner yang melakukan assignment',
  `assigned_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY  `uq_spv_outlet`   (`spv_id`, `outlet_id`),
  KEY         `idx_spv_area_spv`    (`spv_id`),
  KEY         `idx_spv_area_outlet` (`outlet_id`),
  CONSTRAINT  `fk_spv_area_spv`
    FOREIGN KEY (`spv_id`)      REFERENCES `users`   (`id`) ON DELETE CASCADE,
  CONSTRAINT  `fk_spv_area_outlet`
    FOREIGN KEY (`outlet_id`)   REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  CONSTRAINT  `fk_spv_area_by`
    FOREIGN KEY (`assigned_by`) REFERENCES `users`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Area-assignment SPV ke outlet. SPV tanpa assignment = akses semua outlet.';

-- ============================================================
-- email + wa_number di users (opsional, untuk notifikasi)
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email`     VARCHAR(150) NULL AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `wa_number` VARCHAR(20)  NULL
    COMMENT 'Nomor WA tanpa +, mis. 628123456789'
    AFTER `email`;
