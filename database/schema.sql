-- ============================================================================
-- IPNA Performance Management System - Database Schema
-- MySQL / MariaDB compatible
-- ============================================================================

CREATE DATABASE IF NOT EXISTS ipna_pms
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ipna_pms;

-- ============================================================================
-- 1. users
-- ============================================================================
CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)    NOT NULL,
  username    VARCHAR(50)     NOT NULL,
  email       VARCHAR(150)    NOT NULL,
  password    VARCHAR(255)    NOT NULL,
  role        ENUM('admin','reporter','photographer') NOT NULL DEFAULT 'reporter',
  position    VARCHAR(100)    NOT NULL DEFAULT '',
  avatar      VARCHAR(255)    NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login  DATETIME        NULL,
  created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. activity_types
-- ============================================================================
CREATE TABLE activity_types (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)    NOT NULL,
  code       VARCHAR(50)     NOT NULL,
  score      INT             NOT NULL DEFAULT 1,
  category   ENUM('news','photo','video') NOT NULL DEFAULT 'news',
  status     ENUM('active','inactive')   NOT NULL DEFAULT 'active',
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_activity_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. monthly_targets
-- ============================================================================
CREATE TABLE monthly_targets (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id                 INT UNSIGNED    NOT NULL,
  year                    INT             NOT NULL,
  month                   INT             NOT NULL,
  news_target             INT             NOT NULL DEFAULT 0,
  exclusive_news_target   INT             NOT NULL DEFAULT 0,
  interview_target        INT             NOT NULL DEFAULT 0,
  special_report_target   INT             NOT NULL DEFAULT 0,
  photo_target            INT             NOT NULL DEFAULT 0,
  gallery_target          INT             NOT NULL DEFAULT 0,
  video_target            INT             NOT NULL DEFAULT 0,
  score_target            INT             NOT NULL DEFAULT 0,
  created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_monthly_targets_user_year_month (user_id, year, month),
  CONSTRAINT fk_monthly_targets_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. activities
-- ============================================================================
CREATE TABLE activities (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED    NOT NULL,
  activity_date   DATE            NOT NULL,
  activity_type   INT UNSIGNED    NOT NULL,
  quantity        INT             NOT NULL DEFAULT 1,
  title           VARCHAR(500)    NOT NULL,
  link            VARCHAR(1000)   NULL,
  mission         TINYINT         NOT NULL DEFAULT 0,
  description     TEXT            NULL,
  score           INT             NOT NULL DEFAULT 0,
  status          ENUM('active','deleted') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_activities_user_id       (user_id),
  INDEX idx_activities_activity_date (activity_date),
  INDEX idx_activities_activity_type (activity_type),
  INDEX idx_activities_created_at    (created_at),
  INDEX idx_activities_user_date_type (user_id, activity_date, activity_type),
  CONSTRAINT fk_activities_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_activities_type
    FOREIGN KEY (activity_type) REFERENCES activity_types (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. audit_logs
-- ============================================================================
CREATE TABLE audit_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED    NULL,
  action      VARCHAR(100)    NOT NULL,
  entity      VARCHAR(100)    NOT NULL,
  entity_id   INT UNSIGNED    NULL,
  old_value   TEXT            NULL,
  new_value   TEXT            NULL,
  ip_address  VARCHAR(45)     NOT NULL DEFAULT '',
  user_agent  VARCHAR(500)    NOT NULL DEFAULT '',
  created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_audit_logs_user_id    (user_id),
  INDEX idx_audit_logs_entity     (entity, entity_id),
  INDEX idx_audit_logs_created_at (created_at),
  CONSTRAINT fk_audit_logs_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. system_settings
-- ============================================================================
CREATE TABLE system_settings (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key   VARCHAR(100)    NOT NULL,
  setting_value TEXT            NOT NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_system_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. login_attempts
-- ============================================================================
CREATE TABLE login_attempts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(100)    NOT NULL DEFAULT '',
  ip_address   VARCHAR(45)     NOT NULL DEFAULT '',
  attempted_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_login_attempts_username   (username),
  INDEX idx_login_attempts_ip_address (ip_address),
  INDEX idx_login_attempts_attempted  (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Default activity_types
-- ============================================================================
INSERT INTO activity_types (name, code, score, category, status) VALUES
  ('خبر عادی',        'news',           1, 'news',  'active'),
  ('خبر اختصاصی',     'exclusive_news', 3, 'news',  'active'),
  ('مصاحبه',          'interview',      4, 'news',  'active'),
  ('گزارش ویژه',      'special_report', 5, 'news',  'active'),
  ('عکس خبری',        'news_photo',     1, 'photo', 'active'),
  ('گالری عکس',       'photo_gallery',  3, 'photo', 'active'),
  ('ویدئوی اختصاصی',  'exclusive_video',5, 'video', 'active');

-- ============================================================================
-- Default system settings
-- ============================================================================
INSERT INTO system_settings (setting_key, setting_value) VALUES
  ('site_name',              'سامانه مدیریت عملکرد خبرنگاران IPNA'),
  ('site_version',           '1.0.0'),
  ('site_url',               'https://pms.ipna.ir'),
  ('organization',           'خبرگزاری جمهوری اسلامی ایران (ایرنا)'),
  ('academic_year_start',    '03-21'),
  ('fiscal_year_start_month','1'),
  ('max_login_attempts',     '5'),
  ('lockout_duration_minutes','15'),
  ('session_timeout_minutes','30'),
  ('report_page_size',       '20'),
  ('default_news_target',    '30'),
  ('default_photo_target',   '20'),
  ('default_video_target',   '5'),
  ('score_multiplier',       '1'),
  ('maintenance_mode',       'false');
