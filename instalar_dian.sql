-- ============================================================
-- INSTALADOR FACTURACION ELECTRONICA DIAN COLOMBIA
-- ------------------------------------------------------------
-- Este SQL hace TODO de una vez:
--   1) Crea la base col_restaurante_db (si no existe)
--   2) Aplica los cambios de esquema DIAN sobre las tablas existentes
--      (asume que ya importaste bk_basededatos.sql previamente)
--   3) Crea las tablas DIAN nuevas
--   4) Inserta las claves DIAN por defecto en `settings`
--
-- USO (desde HeidiSQL o phpMyAdmin de Laragon):
--   - Primero importa bk_basededatos.sql en col_restaurante_db
--   - Luego importa este archivo en la misma BD
--
-- Si prefieres usar artisan migrate, NO ejecutes este SQL:
--   php artisan migrate hace exactamente lo mismo.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `col_restaurante_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `col_restaurante_db`;

-- ------------------------------------------------------------
-- 1) DROP del esquema SUNAT (si existe)
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `credit_notes`;
DROP TABLE IF EXISTS `daily_summaries`;
DROP TABLE IF EXISTS `document_series`;

-- Eliminar índice único de orders si existía
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                      AND table_name   = 'orders'
                      AND index_name   = 'orders_doc_serie_corr_unique');
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `orders` DROP INDEX `orders_doc_serie_corr_unique`', 'SELECT 0;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar columnas SUNAT una a una (idempotente)
SET @cols := 'serie,correlativo,igv,total_gravada,total_exonerada,total_inafecta,total_gratuita,sunat_status,sunat_code,sunat_description,cdr_path,hash';
DROP PROCEDURE IF EXISTS sp_drop_cols;
DELIMITER //
CREATE PROCEDURE sp_drop_cols(IN cols TEXT)
BEGIN
    DECLARE c VARCHAR(64);
    DECLARE done INT DEFAULT 0;
    DECLARE pos INT;

    WHILE LENGTH(cols) > 0 DO
        SET pos = LOCATE(',', cols);
        IF pos = 0 THEN
            SET c = cols;
            SET cols = '';
        ELSE
            SET c = SUBSTRING(cols, 1, pos - 1);
            SET cols = SUBSTRING(cols, pos + 1);
        END IF;

        SET @exists := (SELECT COUNT(*) FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = 'orders'
                          AND column_name = c);
        IF @exists > 0 THEN
            SET @s := CONCAT('ALTER TABLE `orders` DROP COLUMN `', c, '`');
            PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
        END IF;
    END WHILE;
END //
DELIMITER ;
CALL sp_drop_cols(@cols);
DROP PROCEDURE IF EXISTS sp_drop_cols;

-- Limpiar settings SUNAT y registros de migraciones eliminadas
DELETE FROM `settings`  WHERE `key` LIKE 'sunat_%' OR `key` = 'igv_factor';
DELETE FROM `migrations` WHERE migration IN (
    '2026_05_17_120001_add_sunat_fields_to_orders_table',
    '2026_05_17_120002_create_document_series_table',
    '2026_05_17_120003_create_credit_notes_table',
    '2026_05_17_120004_create_daily_summaries_table'
);

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 2) CREATE tablas DIAN
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dian_resolutions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `environment` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `numero_resolucion` VARCHAR(30) NOT NULL,
    `fecha_resolucion` DATE NULL,
    `prefijo` VARCHAR(10) NOT NULL DEFAULT 'SETP',
    `rango_desde` BIGINT UNSIGNED NOT NULL,
    `rango_hasta` BIGINT UNSIGNED NOT NULL,
    `vigencia_desde` DATE NULL,
    `vigencia_hasta` DATE NULL,
    `clave_tecnica` VARCHAR(150) NULL,
    `consecutivo_actual` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas DIAN a orders (idempotente)
DROP PROCEDURE IF EXISTS sp_add_col_orders;
DELIMITER //
CREATE PROCEDURE sp_add_col_orders(IN colName VARCHAR(64), IN colDef TEXT)
BEGIN
    SET @exists := (SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name='orders' AND column_name = colName);
    IF @exists = 0 THEN
        SET @s := CONCAT('ALTER TABLE `orders` ADD COLUMN `', colName, '` ', colDef);
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END //
DELIMITER ;

CALL sp_add_col_orders('dian_prefijo',         "VARCHAR(10) NULL AFTER `document_type`");
CALL sp_add_col_orders('dian_numero',          "BIGINT UNSIGNED NULL AFTER `dian_prefijo`");
CALL sp_add_col_orders('dian_resolution_id',   "BIGINT UNSIGNED NULL AFTER `dian_numero`");
CALL sp_add_col_orders('subtotal',             "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `total`");
CALL sp_add_col_orders('iva',                  "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `subtotal`");
CALL sp_add_col_orders('ico',                  "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `iva`");
CALL sp_add_col_orders('descuento_total',      "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `ico`");
CALL sp_add_col_orders('total_a_pagar',        "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `descuento_total`");
CALL sp_add_col_orders('client_tipo_documento',"VARCHAR(5) NULL AFTER `client_document`");
CALL sp_add_col_orders('client_dv',            "VARCHAR(2) NULL AFTER `client_tipo_documento`");
CALL sp_add_col_orders('client_email',         "VARCHAR(255) NULL AFTER `client_dv`");
CALL sp_add_col_orders('client_phone',         "VARCHAR(40) NULL AFTER `client_email`");
CALL sp_add_col_orders('client_address',       "VARCHAR(255) NULL AFTER `client_phone`");
CALL sp_add_col_orders('client_city_code',     "VARCHAR(10) NULL AFTER `client_address`");
CALL sp_add_col_orders('client_dept_code',     "VARCHAR(10) NULL AFTER `client_city_code`");
CALL sp_add_col_orders('dian_status',          "VARCHAR(30) NOT NULL DEFAULT 'PENDING' AFTER `client_dept_code`");
CALL sp_add_col_orders('cufe',                 "VARCHAR(96) NULL AFTER `dian_status`");
CALL sp_add_col_orders('dian_zip_id',          "VARCHAR(50) NULL AFTER `cufe`");
CALL sp_add_col_orders('dian_response_code',   "VARCHAR(10) NULL AFTER `dian_zip_id`");
CALL sp_add_col_orders('dian_description',     "TEXT NULL AFTER `dian_response_code`");
CALL sp_add_col_orders('dian_errors',          "TEXT NULL AFTER `dian_description`");
CALL sp_add_col_orders('xml_path',             "VARCHAR(255) NULL AFTER `dian_errors`");
CALL sp_add_col_orders('ar_path',              "VARCHAR(255) NULL AFTER `xml_path`");
CALL sp_add_col_orders('pdf_path',             "VARCHAR(255) NULL AFTER `ar_path`");
CALL sp_add_col_orders('qr_url',               "VARCHAR(500) NULL AFTER `pdf_path`");
CALL sp_add_col_orders('sent_at',              "TIMESTAMP NULL AFTER `qr_url`");
CALL sp_add_col_orders('accepted_at',          "TIMESTAMP NULL AFTER `sent_at`");
DROP PROCEDURE IF EXISTS sp_add_col_orders;

-- Indice único prefijo+numero (si aún no existe)
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics
                    WHERE table_schema = DATABASE() AND table_name='orders'
                      AND index_name='orders_dian_prefijo_numero_unique');
SET @sql := IF(@idx_exists = 0,
               "ALTER TABLE `orders` ADD UNIQUE KEY `orders_dian_prefijo_numero_unique` (`dian_prefijo`,`dian_numero`)",
               "SELECT 0;");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- FK a dian_resolutions
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.table_constraints
                   WHERE table_schema = DATABASE() AND table_name='orders'
                     AND constraint_name='orders_dian_resolution_id_foreign');
SET @sql := IF(@fk_exists = 0,
               "ALTER TABLE `orders` ADD CONSTRAINT `orders_dian_resolution_id_foreign`
                FOREIGN KEY (`dian_resolution_id`) REFERENCES `dian_resolutions`(`id`) ON DELETE SET NULL",
               "SELECT 0;");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS `dian_credit_notes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `prefijo` VARCHAR(10) NOT NULL,
    `numero` BIGINT UNSIGNED NOT NULL,
    `dian_resolution_id` BIGINT UNSIGNED NULL,
    `reason_code` TINYINT UNSIGNED NOT NULL,
    `reason_description` VARCHAR(255) NOT NULL,
    `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `iva` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `dian_status` VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    `cude` VARCHAR(96) NULL,
    `dian_zip_id` VARCHAR(50) NULL,
    `dian_response_code` VARCHAR(10) NULL,
    `dian_description` TEXT NULL,
    `dian_errors` TEXT NULL,
    `xml_path` VARCHAR(255) NULL,
    `ar_path` VARCHAR(255) NULL,
    `pdf_path` VARCHAR(255) NULL,
    `qr_url` VARCHAR(500) NULL,
    `sent_at` TIMESTAMP NULL,
    `accepted_at` TIMESTAMP NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `dian_credit_notes_prefijo_numero_unique`(`prefijo`,`numero`),
    CONSTRAINT `dian_credit_notes_order_id_foreign`
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `dian_credit_notes_dian_resolution_id_foreign`
        FOREIGN KEY (`dian_resolution_id`) REFERENCES `dian_resolutions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `dian_credit_notes_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dian_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `documentable_type` VARCHAR(255) NOT NULL,
    `documentable_id`   BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `response_code` VARCHAR(10) NULL,
    `description` TEXT NULL,
    `request_payload` LONGTEXT NULL,
    `response_payload` LONGTEXT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `dian_events_morph_idx`(`documentable_type`,`documentable_id`),
    CONSTRAINT `dian_events_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3) Seed de settings DIAN por defecto
-- ------------------------------------------------------------
INSERT INTO `settings` (`key`,`value`,`created_at`,`updated_at`) VALUES
    ('dian_company_nit','',NOW(),NOW()),
    ('dian_company_dv','',NOW(),NOW()),
    ('dian_company_razon_social','',NOW(),NOW()),
    ('dian_company_nombre_comercial','',NOW(),NOW()),
    ('dian_company_tipo_documento','31',NOW(),NOW()),
    ('dian_company_tipo_persona','1',NOW(),NOW()),
    ('dian_company_regimen','49',NOW(),NOW()),
    ('dian_company_responsabilidad','R-99-PN',NOW(),NOW()),
    ('dian_company_address','',NOW(),NOW()),
    ('dian_company_city_code','11001',NOW(),NOW()),
    ('dian_company_dept_code','11',NOW(),NOW()),
    ('dian_company_country_code','CO',NOW(),NOW()),
    ('dian_company_phone','',NOW(),NOW()),
    ('dian_company_email','',NOW(),NOW()),
    ('dian_company_actividad_economica','5611',NOW(),NOW()),
    ('dian_company_municipio_nombre','BOGOTA',NOW(),NOW()),
    ('dian_environment','2',NOW(),NOW()),
    ('dian_test_set_id','',NOW(),NOW()),
    ('dian_software_id','',NOW(),NOW()),
    ('dian_software_pin','',NOW(),NOW()),
    ('dian_cert_path','',NOW(),NOW()),
    ('dian_cert_password','',NOW(),NOW()),
    ('iva_rate','19',NOW(),NOW()),
    ('ico_rate','0',NOW(),NOW())
ON DUPLICATE KEY UPDATE `updated_at`=NOW();

-- ------------------------------------------------------------
-- 4) Registrar migraciones DIAN como ya ejecutadas (para que
--    artisan migrate NO las vuelva a aplicar después)
-- ------------------------------------------------------------
SET @batch := IFNULL((SELECT MAX(batch) FROM migrations), 0) + 1;
INSERT IGNORE INTO `migrations`(`migration`,`batch`) VALUES
    ('2026_05_22_180000_drop_sunat_schema', @batch),
    ('2026_05_22_180001_create_dian_resolutions_table', @batch),
    ('2026_05_22_180002_add_dian_fields_to_orders_table', @batch),
    ('2026_05_22_180003_create_dian_credit_notes_table', @batch),
    ('2026_05_22_180004_create_dian_events_table', @batch),
    ('2026_05_22_180005_seed_dian_default_settings', @batch);

-- ============================================================
-- LISTO. Ahora entra al sistema y configura Settings > DIAN.
-- ============================================================
