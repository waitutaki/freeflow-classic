-- Core schema
CREATE TABLE IF NOT EXISTS `#__users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_first` VARCHAR(100) NOT NULL,
  `name_last` VARCHAR(100) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified_at` DATETIME NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__user_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `avatar_path` VARCHAR(255) NULL,
  `cover_path` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_profile_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__sessions` (
  `session_id` VARCHAR(128) NOT NULL,
  `context` VARCHAR(20) NOT NULL,
  `data` MEDIUMTEXT NOT NULL,
  `expires_at` INT UNSIGNED NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`session_id`, `context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `token_type` VARCHAR(50) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` VARCHAR(32) NOT NULL DEFAULT 'global',
  `setting_group` VARCHAR(64) NOT NULL DEFAULT 'global',
  `setting_key` VARCHAR(128) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `value_type` VARCHAR(32) NOT NULL DEFAULT 'string',
  `is_sensitive` TINYINT(1) NOT NULL DEFAULT 0,
  `is_protected` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_by` BIGINT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_scope_key` (`scope`, `setting_key`),
  INDEX `idx_scope_group` (`scope`, `setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__mail_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_type` VARCHAR(20) NOT NULL,
  `owner_key` VARCHAR(60) NOT NULL,
  `template_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `language_tag` VARCHAR(10) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_doc_xml` LONGTEXT NOT NULL,
  `html_cache` MEDIUMTEXT NULL,
  `text_cache` MEDIUMTEXT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `is_core` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `updated_by` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mail_template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__mail_wrappers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wrapper_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `wrapper_doc_xml` LONGTEXT NOT NULL,
  `html_cache` MEDIUMTEXT NULL,
  `text_cache` MEDIUMTEXT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mail_wrapper_key` (`wrapper_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__roles` (
  `id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `parent_role_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_roles_parent` (`parent_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `perm_key` VARCHAR(80) NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_perm_key` (`perm_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__admin_menu_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_type` ENUM('core','ext') NOT NULL DEFAULT 'core',
  `owner_key` VARCHAR(60) NOT NULL,
  `item_key` VARCHAR(80) NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `label_key` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(50) NULL,
  `route` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `perm_key` VARCHAR(80) NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_menu_item` (`item_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(20) NOT NULL,
  `reason_code` VARCHAR(50) NOT NULL,
  `confidence` DECIMAL(4,2) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL DEFAULT '',
  `rule_id` INT UNSIGNED NULL,
  `correlation_id` VARCHAR(40) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_traffic` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_freegate_traffic_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rule_key` VARCHAR(80) NOT NULL,
  `action` VARCHAR(20) NOT NULL,
  `pattern` VARCHAR(200) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `source` VARCHAR(20) NOT NULL DEFAULT 'manual',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_freegate_rule` (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_rule_suggestions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rule_key` VARCHAR(80) NOT NULL,
  `action` VARCHAR(20) NOT NULL,
  `pattern` VARCHAR(200) NOT NULL,
  `confidence` DECIMAL(4,2) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_blocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `reason_code` VARCHAR(50) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_freegate_blocks_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_scans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scan_type` VARCHAR(30) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `summary` VARCHAR(255) NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_scan_findings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scan_id` INT UNSIGNED NOT NULL,
  `severity` VARCHAR(20) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_freegate_scan_id` (`scan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_integrity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(255) NOT NULL,
  `hash` VARCHAR(64) NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_freegate_integrity` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__freegate_timeline` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(30) NOT NULL,
  `title` VARCHAR(60) NOT NULL,
  `details` VARCHAR(255) NOT NULL,
  `severity` VARCHAR(20) NOT NULL,
  `correlation_id` VARCHAR(40) NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `content_xml` LONGTEXT NOT NULL,
  `excerpt` TEXT NULL,
  `status` VARCHAR(20) NOT NULL,
  `featured_image_path` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  `published_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `content_xml` LONGTEXT NOT NULL,
  `excerpt` TEXT NULL,
  `status` VARCHAR(20) NOT NULL,
  `featured_image_path` VARCHAR(255) NULL,
  `category_id` INT UNSIGNED NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  `published_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_posts_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__element_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_core` TINYINT(1) NOT NULL DEFAULT 1,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_element_type` (`type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__element_positions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `theme_key` VARCHAR(60) NOT NULL,
  `position` VARCHAR(80) NOT NULL,
  `context` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__element_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context` VARCHAR(20) NOT NULL,
  `target_type` VARCHAR(30) NOT NULL,
  `target_value` VARCHAR(255) NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__elements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `element_type_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `template_position_id` INT UNSIGNED NOT NULL,
  `assignment_id` INT UNSIGNED NOT NULL,
  `config_xml` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  `modified_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__menus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(120) NOT NULL,
  `key` VARCHAR(80) NOT NULL,
  `description` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_menu_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `url` VARCHAR(255) NULL,
  `target` VARCHAR(20) NULL,
  `page_id` INT UNSIGNED NULL,
  `post_id` INT UNSIGNED NULL,
  `css_class` VARCHAR(120) NULL,
  `rel` VARCHAR(50) NULL,
  `visibility` VARCHAR(20) NOT NULL DEFAULT 'all',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(120) NOT NULL,
  `file_ext` VARCHAR(10) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `storage_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `modified_at` DATETIME NULL,
  `modified_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__themes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `theme_key` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `context` VARCHAR(20) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_core` TINYINT(1) NOT NULL DEFAULT 0,
  `version` VARCHAR(20) NULL,
  `author` VARCHAR(120) NULL,
  `description` TEXT NULL,
  `installed_at` DATETIME NOT NULL,
  `installed_by` INT UNSIGNED NULL,
  `updated_at` DATETIME NULL,
  `updated_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_theme_key` (`theme_key`, `context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__theme_style_overrides` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `theme_id` INT UNSIGNED NOT NULL,
  `context` VARCHAR(20) NOT NULL,
  `token` VARCHAR(80) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `updated_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_theme_override` (`theme_id`,`context`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__extension` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ext_key` VARCHAR(80) NOT NULL,
  `type` VARCHAR(40) NOT NULL DEFAULT 'component',
  `name` VARCHAR(120) NOT NULL,
  `version` VARCHAR(20) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_core` TINYINT(1) NOT NULL DEFAULT 0,
  `author` VARCHAR(120) NULL,
  `description` TEXT NULL,
  `admin_menu_xml` TEXT NULL,
  `installed_at` DATETIME NOT NULL,
  `installed_by` INT UNSIGNED NULL,
  `updated_at` DATETIME NULL,
  `updated_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ext_key` (`ext_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__api_routes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ext_key` VARCHAR(80) NOT NULL,
  `route_path` VARCHAR(255) NOT NULL,
  `http_method` VARCHAR(10) NOT NULL,
  `controller` VARCHAR(120) NOT NULL,
  `action` VARCHAR(120) NOT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 0,
  `required_permission` VARCHAR(120) NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_api_route` (`ext_key`,`route_path`,`http_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__update_checks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `component_type` VARCHAR(20) NOT NULL,
  `component_key` VARCHAR(80) NOT NULL,
  `installed_version` VARCHAR(20) NOT NULL,
  `available_version` VARCHAR(20) NULL,
  `status` VARCHAR(20) NOT NULL,
  `checked_at` DATETIME NOT NULL,
  `last_error` VARCHAR(255) NULL,
  `raw_xml` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_update_component` (`component_type`,`component_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__ext_devstore_developers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `approved_at` DATETIME NULL,
  `approved_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_devstore_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__ext_devstore_packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `developer_id` INT UNSIGNED NOT NULL,
  `package_type` VARCHAR(20) NOT NULL,
  `package_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `description` TEXT NULL,
  `author` VARCHAR(120) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `sha256` CHAR(64) NULL,
  `archive_name` VARCHAR(160) NULL,
  `repo_path` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `published_at` DATETIME NULL,
  `published_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__ext_devstore_compliance_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT UNSIGNED NOT NULL,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `report_text` MEDIUMTEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__ext_devstore_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(80) NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `detail_text` MEDIUMTEXT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed roles
INSERT IGNORE INTO `#__roles` (`id`,`title`,`is_system`,`parent_role_id`,`created_at`,`updated_at`) VALUES
(100,'User',1,NULL,NOW(),NOW()),
(200,'Developer',1,NULL,NOW(),NOW()),
(300,'Editor',1,NULL,NOW(),NOW()),
(400,'Manager',1,NULL,NOW(),NOW()),
(500,'Admin',1,NULL,NOW(),NOW()),
(998,'SuperUser',1,NULL,NOW(),NOW());

-- Seed permissions
INSERT IGNORE INTO `#__permissions` (`perm_key`,`title`,`description`,`is_system`,`created_at`) VALUES
('manage_settings_global','Manage Global Settings','',1,NOW()),
('manage_settings_maintenance','Manage Maintenance Settings','',1,NOW()),
('manage_settings_seo','Manage SEO Settings','',1,NOW()),
('manage_settings_meta','Manage Meta Display Settings','',1,NOW()),
('manage_settings_media','Manage Media Settings','',1,NOW()),
('manage_settings_locale','Manage Locale Settings','',1,NOW()),
('manage_settings_mail_templates','Manage Mail Templates','',1,NOW()),
('manage_settings_mail_wrappers','Manage Mail Wrappers','',1,NOW()),
('manage_settings_security','Manage Security Settings','',1,NOW()),
('manage_permissions','Manage Permissions','',1,NOW()),
('manage_updates_core','Manage Core Updates','',1,NOW()),
('manage_updates_extension','Manage Extension Updates','',1,NOW()),
('manage_updates_themes','Manage Theme Updates','',1,NOW()),
('manage_docs_build','Manage Docs Builder','',1,NOW()),
('view_sysadmin_docs','View Sysadmin Docs','',1,NOW()),
('devstore_manage','Manage Devstore','',1,NOW()),
('devstore_approve_developers','Approve Devstore Developers','',1,NOW()),
('devstore_publish_packages','Publish Devstore Packages','',1,NOW()),
('devstore_unpublish_packages','Unpublish Devstore Packages','',1,NOW()),
('devstore_regenerate_feeds','Regenerate Devstore Feeds','',1,NOW()),
('devstore_view_audit','View Devstore Audit','',1,NOW()),
('devstore_access_frontend','Access Devstore Portal','',1,NOW()),
('export_database','Export Database','',1,NOW()),
('import_database','Import Database','',1,NOW()),
('manage_extension','Manage extension','',1,NOW()),
('install_extension','Install extension','',1,NOW()),
('install_themes','Install Themes','',1,NOW()),
('run_maintenance','Run Maintenance','',1,NOW()),
('use_block_html','Use HTML Block','',1,NOW()),
('use_block_embed','Use Embed Block','',1,NOW()),
('manage_users','Manage Users','',1,NOW()),
('manage_content','Manage Content','',1,NOW()),
('manage_content_own','Manage Own Content','',1,NOW()),
('publish_content','Publish Content','',1,NOW()),
('manage_elements','Manage Elements','',1,NOW()),
('manage_menus','Manage Menus','',1,NOW()),
('manage_documents','Manage Documents','',1,NOW()),
('manage_documents_own','Manage Own Documents','',1,NOW()),
('upload_documents_own','Upload Own Documents','',1,NOW()),
('download_documents_own','Download Own Documents','',1,NOW()),
('delete_documents_own','Delete Own Documents','',1,NOW()),
('rename_documents_own','Rename Own Documents','',1,NOW()),
('manage_themes','Manage Themes','',1,NOW()),
('manage_media_admin','Manage Media Admin','',1,NOW()),
('manage_media_own','Manage Own Media','',1,NOW()),
('media_browse_all','Browse All Media','',1,NOW()),
('media_upload','Upload Media','',1,NOW()),
('media_delete','Delete Media','',1,NOW()),
('media_edit_image','Edit Media','',1,NOW()),
('media_crop_image','Crop Media','',1,NOW()),
('media_regenerate_thumbnails','Regenerate Thumbnails','',1,NOW()),
('media_manage_system_bucket','Manage System Bucket','',1,NOW()),
('media_manage_user_buckets','Manage User Buckets','',1,NOW()),
('media_manage_ext_theme_buckets','Manage Ext/Theme Buckets','',1,NOW()),
('manage_freegate','Manage Freegate','',1,NOW());

-- Seed superuser permissions
INSERT IGNORE INTO `#__role_permissions` (`role_id`,`permission_id`)
SELECT 998, id FROM `#__permissions`;

-- Seed own-management permissions for all roles
INSERT IGNORE INTO `#__role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `#__roles` r
JOIN `#__permissions` p ON p.perm_key IN ('manage_content_own','manage_media_own','manage_documents_own');

-- Seed admin menu entries
INSERT IGNORE INTO `#__admin_menu_entries` (`owner_type`,`owner_key`,`item_key`,`parent_id`,`label_key`,`icon`,`route`,`sort_order`,`perm_key`,`is_enabled`,`created_at`,`updated_at`) VALUES
('core','core','dashboard',NULL,'FFCMS_DASHBOARD','fa-eye','/admin/',1,NULL,1,NOW(),NOW()),
('core','core','system',NULL,'FFCMS_SYSTEM','fa-gear','/admin/settings?tab=global&subtab=general',2,'manage_settings_global',1,NOW(),NOW()),
('core','core','roles',NULL,'FFCMS_ROLES','fa-key','/admin/roles',3,'manage_permissions',1,NOW(),NOW()),
('core','core','users',NULL,'FFCMS_USERS','fa-user','/admin/users',4,'manage_users',1,NOW(),NOW()),
('core','core','media',NULL,'FFCMS_MEDIA_MANAGER','fa-image','/admin/media',5,'manage_media_own',1,NOW(),NOW()),
('core','core','content',NULL,'FFCMS_CONTENT','fa-folder','/admin/content/pages',6,'manage_content_own',1,NOW(),NOW()),
('core','core','menus',NULL,'FFCMS_MENUS','fa-list','/admin/menus',7,'manage_menus',1,NOW(),NOW()),
('core','core','elements',NULL,'FFCMS_ELEMENTS','fa-layer-group','/admin/elements',8,'manage_elements',1,NOW(),NOW()),
('core','core','documents',NULL,'FFCMS_DOCUMENTS','fa-file-lines','/admin/documents',9,'manage_documents_own',1,NOW(),NOW()),
('core','core','forms',NULL,'Forms','fa-clipboard-list','/admin/forms',10,'manage_content',1,NOW(),NOW()),
('core','core','themes',NULL,'FFCMS_THEMES','fa-paintbrush','/admin/themes',10,'manage_themes',1,NOW(),NOW()),
('core','core','extension',NULL,'FFCMS_EXTENSION','fa-boxes-stacked','/admin/extension',11,'manage_extension',1,NOW(),NOW()),
('core','core','devstore',NULL,'DEVSTORE.MENU_DEVSTORE','fa-boxes','/admin/devstore',12,'devstore_manage',1,NOW(),NOW()),
('core','core','freegate',NULL,'FFCMS_FREEGATE','fa-shield','/admin/freegate',13,'manage_freegate',1,NOW(),NOW());

INSERT IGNORE INTO `#__admin_menu_entries` (`owner_type`,`owner_key`,`item_key`,`parent_id`,`label_key`,`icon`,`route`,`sort_order`,`perm_key`,`is_enabled`,`created_at`,`updated_at`) VALUES
('core','core','settings',(SELECT id FROM `#__admin_menu_entries` WHERE owner_key = 'core' AND item_key = 'system' LIMIT 1),'FFCMS_SETTINGS','fa-gear','/admin/settings?tab=global',1,'manage_settings_global',1,NOW(),NOW()),
('core','core','updates',(SELECT id FROM `#__admin_menu_entries` WHERE owner_key = 'core' AND item_key = 'system' LIMIT 1),'FFCMS_UPDATES','fa-rotate','/admin/updates',2,'manage_updates_core',1,NOW(),NOW());

INSERT IGNORE INTO `#__admin_menu_entries` (`owner_type`,`owner_key`,`item_key`,`parent_id`,`label_key`,`icon`,`route`,`sort_order`,`perm_key`,`is_enabled`,`created_at`,`updated_at`) VALUES
('core','core','pages',(SELECT id FROM `#__admin_menu_entries` WHERE owner_key = 'core' AND item_key = 'content' LIMIT 1),'FFCMS_PAGES','fa-file-lines','/admin/content/pages',1,'manage_content_own',1,NOW(),NOW()),
('core','core','posts',(SELECT id FROM `#__admin_menu_entries` WHERE owner_key = 'core' AND item_key = 'content' LIMIT 1),'FFCMS_POSTS','fa-newspaper','/admin/content/posts',2,'manage_content_own',1,NOW(),NOW()),
('core','core','categories',(SELECT id FROM `#__admin_menu_entries` WHERE owner_key = 'core' AND item_key = 'content' LIMIT 1),'FFCMS_CATEGORIES','fa-tags','/admin/content/categories',3,'manage_content',1,NOW(),NOW());

-- Seed Devstore API routes
INSERT IGNORE INTO `#__api_routes` (`ext_key`,`route_path`,`http_method`,`controller`,`action`,`is_public`,`required_permission`,`is_enabled`,`created_at`,`created_by`) VALUES
('devstore','feeds/extension/index','GET','DevstoreFeedsApiController','extensionIndex',1,NULL,1,NOW(),NULL),
('devstore','feeds/themes/index','GET','DevstoreFeedsApiController','themesIndex',1,NULL,1,NOW(),NULL),
('devstore','feeds/extension/{key}','GET','DevstoreFeedsApiController','extensionFeed',1,NULL,1,NOW(),NULL),
('devstore','feeds/themes/{key}','GET','DevstoreFeedsApiController','themeFeed',1,NULL,1,NOW(),NULL),
('devstore','feeds/main','GET','DevstoreFeedsApiController','mainFeed',1,NULL,1,NOW(),NULL);

-- Seed core themes
INSERT IGNORE INTO `#__themes` (`theme_key`,`name`,`context`,`is_enabled`,`is_active`,`is_core`,`version`,`author`,`description`,`installed_at`,`installed_by`,`updated_at`,`updated_by`) VALUES
('alpha','Alpha','site',1,1,1,'1.0','Core','Core site theme',NOW(),NULL,NOW(),NULL),
('zulu','Zulu','admin',1,1,1,'1.0','Core','Core admin theme',NOW(),NULL,NOW(),NULL);

-- Seed element types
INSERT IGNORE INTO `#__element_types` (`type_key`,`name`,`description`,`is_core`,`is_enabled`,`created_at`,`created_by`) VALUES
('menu','Menu','Menu element',1,1,NOW(),NULL),
('pages','Pages','Pages listing',1,1,NOW(),NULL),
('posts','Posts','Posts listing',1,1,NOW(),NULL),
('categories','Categories','Categories listing',1,1,NOW(),NULL),
('html','HTML','HTML block',1,1,NOW(),NULL),
('embed','Embed','Embed block',1,1,NOW(),NULL),
('url','URL','URL link',1,1,NOW(),NULL),
('image','Image','Image element',1,1,NOW(),NULL),
('text','Text','Text block',1,1,NOW(),NULL),
('button','Button','Button element',1,1,NOW(),NULL);

INSERT IGNORE INTO `#__element_positions` (`theme_key`,`position`,`context`) VALUES
('alpha','topbar','site'),
('alpha','header_left','site'),
('alpha','header_right','site'),
('alpha','menu','site'),
('alpha','below_menu','site'),
('alpha','above_content','site'),
('alpha','sidebar_left','site'),
('alpha','sidebar_right','site'),
('alpha','below_content','site'),
('alpha','above_footer_1','site'),
('alpha','above_footer_2','site'),
('alpha','above_footer_3','site'),
('alpha','above_footer_4','site'),
('alpha','footer_1','site'),
('alpha','footer_2','site'),
('alpha','footer_3','site'),
('alpha','footer_4','site'),
('alpha','below_footer','site'),
('alpha','copyright','site'),
('zulu','topbar_logo','admin'),
('zulu','sidebar_toggle','admin'),
('zulu','sidebar_menu','admin'),
('zulu','toolbar_left','admin'),
('zulu','toolbar_notices','admin'),
('zulu','toolbar_right','admin'),
('zulu','dashboard_1','admin'),
('zulu','dashboard_2','admin'),
('zulu','dashboard_3','admin'),
('zulu','dashboard_4','admin'),
('zulu','dashboard_5','admin'),
('zulu','dashboard_6','admin');

INSERT IGNORE INTO `#__element_assignments` (`context`,`target_type`,`target_value`,`is_enabled`,`created_at`,`created_by`) VALUES
('site','global','',1,NOW(),NULL),
('admin','global','',1,NOW(),NULL);

-- Seed default mail wrapper
INSERT IGNORE INTO `#__mail_wrappers` (`wrapper_key`,`name`,`wrapper_doc_xml`,`html_cache`,`text_cache`,`is_default`,`is_enabled`,`created_at`,`updated_at`) VALUES
('default','Default Wrapper','<freewrite version="1"><block type="mailcontent"></block></freewrite>','[mailcontent]','[mailcontent]',1,1,NOW(),NOW());

-- Seed default mail templates
INSERT IGNORE INTO `#__mail_templates` (`owner_type`,`owner_key`,`template_key`,`name`,`language_tag`,`subject`,`body_doc_xml`,`html_cache`,`text_cache`,`is_enabled`,`is_core`,`created_at`,`updated_at`,`updated_by`) VALUES
('core','core','register_verify','Registration Verify','en-GB','Verify your email','<freewrite version="1"><block type="richtext"><![CDATA[Please verify: {{verify_link}}]]></block></freewrite>','Please verify: {{verify_link}}','Please verify: {{verify_link}}',1,1,NOW(),NOW(),NULL),
('core','core','welcome','Welcome','en-GB','Welcome','<freewrite version="1"><block type="richtext"><![CDATA[Welcome {{username}}. Login: {{login_link}}]]></block></freewrite>','Welcome {{username}}. Login: {{login_link}}','Welcome {{username}}. Login: {{login_link}}',1,1,NOW(),NOW(),NULL),
('core','core','FREEGATE.LOCKOUT.USER','Freegate Lockout User','en-GB','Security lockout','<freewrite version="1"><block type="richtext"><![CDATA[Your access was temporarily restricted. {{help_link}}]]></block></freewrite>','Your access was temporarily restricted. {{help_link}}','Your access was temporarily restricted. {{help_link}}',1,1,NOW(),NOW(),NULL),
('core','core','FREEGATE.LOCKOUT.ADMIN','Freegate Lockout Admin','en-GB','Security lockout alert','<freewrite version="1"><block type="richtext"><![CDATA[A user was temporarily restricted: {{ip}} {{path}}]]></block></freewrite>','A user was temporarily restricted: {{ip}} {{path}}','A user was temporarily restricted: {{ip}} {{path}}',1,1,NOW(),NOW(),NULL),
('core','core','FREEGATE.ALERT.SUSPICIOUS_LOGIN','Freegate Suspicious Login','en-GB','Suspicious login detected','<freewrite version="1"><block type="richtext"><![CDATA[Repeated login failures detected: {{ip}}]]></block></freewrite>','Repeated login failures detected: {{ip}}','Repeated login failures detected: {{ip}}',1,1,NOW(),NOW(),NULL);

INSERT IGNORE INTO `#__settings` (`scope`,`setting_group`,`setting_key`,`setting_value`,`value_type`,`is_sensitive`,`is_protected`,`updated_by`,`updated_at`) VALUES
('security','security','freeflow_public_key_fingerprint','fcdadb0ea431b0d47bdefbf29352090c9ccfc860f588d305d77f71d84c816a25','string',0,1,NULL,NOW()),
('system','version','1.0','string',0,0,1,NULL,NOW()),
('devstore','enabled','1','bool',0,0,1,NULL,NOW()),
('docs','user_last_build','','string',0,0,1,NULL,NOW()),
('docs','user_last_result','','string',0,0,1,NULL,NOW()),
('docs','developer_last_build','','string',0,0,1,NULL,NOW()),
('docs','developer_last_result','','string',0,0,1,NULL,NOW()),
('docs','sysadmin_last_build','','string',0,0,1,NULL,NOW()),
('docs','sysadmin_last_result','','string',0,0,1,NULL,NOW()),
('freegate','mode','external','string',0,0,1,NULL,NOW()),
('freegate','rate_limit_per_min','120','int',0,0,1,NULL,NOW()),
('freegate','adaptive_enabled','1','int',0,0,1,NULL,NOW()),
('freegate','adaptive_level','1','int',0,0,1,NULL,NOW()),
('freegate','adaptive_max','3','int',0,0,1,NULL,NOW()),
('freegate','max_request_kb','512','int',0,0,1,NULL,NOW()),
('freegate','challenge','none','string',0,0,1,NULL,NOW()),
('freegate','block_minutes','15','int',0,0,1,NULL,NOW()),
('freegate','notify_users','0','int',0,0,1,NULL,NOW()),
('freegate','notify_admins','1','int',0,0,1,NULL,NOW()),
('freegate','help_link','','string',0,0,1,NULL,NOW()),
('freegate','recovery_until','','string',0,0,1,NULL,NOW()),
('freegate','retention_days','30','int',0,0,1,NULL,NOW()),
('freegate','trigger_block_rate','15','int',0,0,1,NULL,NOW()),
('freegate','trigger_login_fail','8','int',0,0,1,NULL,NOW()),
('freegate','trigger_404','20','int',0,0,1,NULL,NOW()),
('freegate','trigger_scan_critical','1','int',0,0,1,NULL,NOW());

CREATE TABLE IF NOT EXISTS `#__forms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `form_xml` LONGTEXT NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_forms_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__form_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `submission_xml` LONGTEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `submitted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_form_submissions_form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;