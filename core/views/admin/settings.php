<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Settings;
use Core\Freegate;
use Core\Auth;

$tab = $tab ?? 'global';
$subtab = $subtab ?? 'general';
$logs = $logs ?? [];
$templates = $mail_templates ?? [];
$wrappers = $mail_wrappers ?? [];
$missing = $missing_templates ?? [];
$confirmClear = $_GET['confirm'] ?? '';
$maintenanceResult = $maintenance_result ?? null;
$testEmailResult = $_GET['test_email'] ?? '';
$errors = $errors ?? [];
$success = $success ?? '';

$channels = [];
if (isset($logs['log_files'])) {
    foreach ($logs['log_files'] as $logFile) {
        $channels[$logFile['channel']] = $logFile['file'];
    }
}

// Global settings - General
$siteName = $site_name ?? '';
$siteTagline = $site_tagline ?? '';
$siteBaseUrl = $site_base_url ?? '';

// Global settings - Maintenance
$maintenanceEnabled = $maintenance_enabled ?? false;
$maintenanceMessage = $maintenance_message ?? '';
$maintenanceRetry = $maintenance_retry_after_minutes ?? '';
$maintenanceAllowlist = $maintenance_allowlist_ips ?? '';

// Global settings - SEO
$seoDefaultTitle = $seo_default_title ?? '';
$seoDefaultDescription = $seo_default_description ?? '';
$seoRobotsIndex = $seo_default_robots_index ?? '';
$seoRobotsFollow = $seo_default_robots_follow ?? '';
$seoCanonicalOverride = $seo_canonical_base_url_override ?? '';
$seoOgSiteName = $seo_og_site_name ?? '';
$seoOgDefaultImage = $seo_og_default_image_asset_id ?? '';
$seoTwitterCard = $seo_twitter_card_type ?? '';
$seoSitemapEnabled = $seo_sitemap_enabled ?? '';

// Global settings - Meta Display
$metaPostEnabled = $meta_post_enabled ?? '';
$metaPostShowAuthor = $meta_post_show_author ?? '';
$metaPostAuthorPosition = $meta_post_author_position ?? '';
$metaPostShowPublishDate = $meta_post_show_publish_date ?? '';
$metaPostPublishDatePosition = $meta_post_publish_date_position ?? '';
$metaPostShowUpdatedDate = $meta_post_show_updated_date ?? '';
$metaPostUpdatedDatePosition = $meta_post_updated_date_position ?? '';
$metaPostShowCategories = $meta_post_show_categories ?? '';
$metaPostCategoriesPosition = $meta_post_categories_position ?? '';
$metaPostShowTags = $meta_post_show_tags ?? '';
$metaPostTagsPosition = $meta_post_tags_position ?? '';

$metaPageEnabled = $meta_page_enabled ?? '';
$metaPageShowAuthor = $meta_page_show_author ?? '';
$metaPageAuthorPosition = $meta_page_author_position ?? '';
$metaPageShowPublishDate = $meta_page_show_publish_date ?? '';
$metaPagePublishDatePosition = $meta_page_publish_date_position ?? '';
$metaPageShowUpdatedDate = $meta_page_show_updated_date ?? '';
$metaPageUpdatedDatePosition = $meta_page_updated_date_position ?? '';
$metaPageShowCategories = $meta_page_show_categories ?? '';
$metaPageCategoriesPosition = $meta_page_categories_position ?? '';
$metaPageShowTags = $meta_page_show_tags ?? '';
$metaPageTagsPosition = $meta_page_tags_position ?? '';

// Global settings - Media
$mediaUploadMax = $media_upload_max_mb ?? '';
$mediaImageMaxWidth = $media_image_max_width ?? '';
$mediaImageMaxHeight = $media_image_max_height ?? '';
$mediaImageQuality = $media_image_quality ?? '';
$mediaGenerateThumbnails = $media_image_generate_thumbnails ?? '';

// Global settings - Locale
$defaultLanguage = $default_language ?? '';
$languageAutoDetect = $language_auto_detect ?? '';
$forceLanguage = $force_language ?? '';
$dateFormat = $date_format ?? '';
$timeFormat = $time_format ?? '';
$datetimeFormat = $datetime_format ?? '';

$availableLanguages = ['en-GB', 'de-DE', 'fr-FR', 'es-ES', 'it-IT'];
$availableDateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y.m.d', 'd.m.Y', 'm.d.Y'];
$availableTimeFormats = ['H:i:s', 'H:i', 'g:i a', 'g:i A'];
$availableDatetimeFormats = ['d M Y - H:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'm/d/Y H:i:s'];

// Mail settings
$mailTransport = $mail_transport ?? '';
$mailFromEmail = $mail_from_email ?? '';
$mailFromName = $mail_from_name ?? '';
$mailReplyTo = $mail_replyto_email ?? '';
$smtpHost = $smtp_host ?? '';
$smtpPort = $smtp_port ?? '';
$smtpSecurity = $smtp_security ?? '';
$smtpUsername = $smtp_username ?? '';
$mailForcePlain = $mail_force_plain_text ?? '';
$mailLogEnabled = $mail_log_enabled ?? '';

$captchaProvider = $captcha_provider ?? '';
$antiEnabled = $antispam_enabled ?? '';
$antiHoney = $antispam_honeypot_default ?? '';
$antiTime = $antispam_time_default ?? '';
$antiMinSeconds = $antispam_min_seconds ?? '';
$failEnabled = $failcount_enabled ?? '';
$failLimit = $failcount_limit ?? '';
$failCooldownEnabled = $failcount_cooldown_enabled ?? '';
$failCooldownMinutes = $failcount_cooldown_minutes ?? '';
$formTypes = ['login', 'registration', 'password_reset_request', 'password_reset_confirm', 'email_verification_resend'];
$freegateMode = $mode ?? '';
$freegateDetected = Freegate::devstoreDetected();

// Override with POST values if there are errors to retain user input
if (!empty($errors)) {
    $siteName = $_POST['site_name'] ?? $siteName;
    $siteTagline = $_POST['site_tagline'] ?? $siteTagline;
    $siteBaseUrl = $_POST['site_base_url'] ?? $siteBaseUrl;
    $maintenanceEnabled = !empty($_POST['maintenance_enabled']);
    $maintenanceMessage = $_POST['maintenance_message'] ?? $maintenanceMessage;
    $maintenanceRetry = ($_POST['maintenance_retry_after_minutes'] ?? $maintenanceRetry);
    $maintenanceAllowlist = $_POST['maintenance_allowlist_ips'] ?? $maintenanceAllowlist;
    $seoDefaultTitle = $_POST['seo_default_title'] ?? $seoDefaultTitle;
    $seoDefaultDescription = $_POST['seo_default_description'] ?? $seoDefaultDescription;
    $seoRobotsIndex = !empty($_POST['seo_default_robots_index']);
    $seoRobotsFollow = !empty($_POST['seo_default_robots_follow']);
    $seoCanonicalOverride = $_POST['seo_canonical_base_url_override'] ?? $seoCanonicalOverride;
    $seoOgSiteName = $_POST['seo_og_site_name'] ?? $seoOgSiteName;
    $seoOgDefaultImage = ($_POST['seo_og_default_image_asset_id'] ?? $seoOgDefaultImage);
    $seoTwitterCard = $_POST['seo_twitter_card_type'] ?? $seoTwitterCard;
    $seoSitemapEnabled = !empty($_POST['seo_sitemap_enabled']);
    $metaPostEnabled = !empty($_POST['meta_post_enabled']);
    $metaPostShowAuthor = !empty($_POST['meta_post_show_author']);
    $metaPostAuthorPosition = $_POST['meta_post_author_position'] ?? $metaPostAuthorPosition;
    $metaPostShowPublishDate = !empty($_POST['meta_post_show_publish_date']);
    $metaPostPublishDatePosition = $_POST['meta_post_publish_date_position'] ?? $metaPostPublishDatePosition;
    $metaPostShowUpdatedDate = !empty($_POST['meta_post_show_updated_date']);
    $metaPostUpdatedDatePosition = $_POST['meta_post_updated_date_position'] ?? $metaPostUpdatedDatePosition;
    $metaPostShowCategories = !empty($_POST['meta_post_show_categories']);
    $metaPostCategoriesPosition = $_POST['meta_post_categories_position'] ?? $metaPostCategoriesPosition;
    $metaPostShowTags = !empty($_POST['meta_post_show_tags']);
    $metaPostTagsPosition = $_POST['meta_post_tags_position'] ?? $metaPostTagsPosition;
    $metaPageEnabled = !empty($_POST['meta_page_enabled']);
    $metaPageShowAuthor = !empty($_POST['meta_page_show_author']);
    $metaPageAuthorPosition = $_POST['meta_page_author_position'] ?? $metaPageAuthorPosition;
    $metaPageShowPublishDate = !empty($_POST['meta_page_show_publish_date']);
    $metaPagePublishDatePosition = $_POST['meta_page_publish_date_position'] ?? $metaPagePublishDatePosition;
    $metaPageShowUpdatedDate = !empty($_POST['meta_page_show_updated_date']);
    $metaPageUpdatedDatePosition = $_POST['meta_page_updated_date_position'] ?? $metaPageUpdatedDatePosition;
    $metaPageShowCategories = !empty($_POST['meta_page_show_categories']);
    $metaPageCategoriesPosition = $_POST['meta_page_categories_position'] ?? $metaPageCategoriesPosition;
    $metaPageShowTags = !empty($_POST['meta_page_show_tags']);
    $metaPageTagsPosition = $_POST['meta_page_tags_position'] ?? $metaPageTagsPosition;
    $mediaUploadMax = ($_POST['media_upload_max_mb'] ?? $mediaUploadMax);
    $mediaImageMaxWidth = ($_POST['media_image_max_width'] ?? $mediaImageMaxWidth);
    $mediaImageMaxHeight = ($_POST['media_image_max_height'] ?? $mediaImageMaxHeight);
    $mediaImageQuality = ($_POST['media_image_quality'] ?? $mediaImageQuality);
    $mediaGenerateThumbnails = !empty($_POST['media_image_generate_thumbnails']);
    $defaultLanguage = $_POST['default_language'] ?? $defaultLanguage;
    $languageAutoDetect = !empty($_POST['language_auto_detect']);
    $forceLanguage = $_POST['force_language'] ?? $forceLanguage;
    $dateFormat = $_POST['date_format'] ?? $dateFormat;
    $timeFormat = $_POST['time_format'] ?? $timeFormat;
    $datetimeFormat = $_POST['datetime_format'] ?? $datetimeFormat;
    $mailTransport = $_POST['mail_transport'] ?? $mailTransport;
    $mailFromEmail = $_POST['mail_from_email'] ?? $mailFromEmail;
    $mailFromName = $_POST['mail_from_name'] ?? $mailFromName;
    $mailReplyTo = $_POST['mail_replyto_email'] ?? $mailReplyTo;
    $smtpHost = $_POST['smtp_host'] ?? $smtpHost;
    $smtpPort = ($_POST['smtp_port'] ?? $smtpPort);
    $smtpSecurity = $_POST['smtp_security'] ?? $smtpSecurity;
    $smtpUsername = $_POST['smtp_username'] ?? $smtpUsername;
    $mailForcePlain = !empty($_POST['mail_force_plain_text']);
    $mailLogEnabled = !empty($_POST['mail_log_enabled']);
    $captchaProvider = $_POST['captcha_provider'] ?? $captchaProvider;
    $antiEnabled = !empty($_POST['antispam_enabled']);
    $antiHoney = !empty($_POST['antispam_honeypot']);
    $antiTime = !empty($_POST['antispam_time']);
    $antiMinSeconds = ($_POST['antispam_min_seconds'] ?? $antiMinSeconds);
    $failEnabled = !empty($_POST['failcount_enabled']);
    $failLimit = ($_POST['failcount_limit'] ?? $failLimit);
    $failCooldownEnabled = !empty($_POST['failcount_cooldown_enabled']);
    $failCooldownMinutes = ($_POST['failcount_cooldown_minutes'] ?? $failCooldownMinutes);
    $freegateMode = $_POST['freegate_mode'] ?? $freegateMode;
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_SETTINGS'); ?></h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'global' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=general"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_TAB_GLOBAL'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'mail' ? 'active' : ''; ?>" href="/admin/settings?tab=mail&subtab=templates"><?php echo Lang::get('FFCMS_MAIL'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'security' ? 'active' : ''; ?>" href="/admin/settings?tab=security"><?php echo Lang::get('FFCMS_SECURITY'); ?></a>
        </li>
        <?php if (Auth::isSuper()) : ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'freegate' ? 'active' : ''; ?>" href="/admin/settings?tab=freegate"><?php echo Lang::get('FFCMS_FREEGATE'); ?></a>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'logs' ? 'active' : ''; ?>" href="/admin/settings?tab=logs"><?php echo Lang::get('FFCMS_LOGS'); ?></a>
        </li>
        <?php if (Auth::isSuper()) : ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'maintenance' ? 'active' : ''; ?>" href="/admin/settings?tab=maintenance"><?php echo Lang::get('FFCMS_MAINTENANCE'); ?></a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if ($tab === 'global') : ?>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? 'general') === 'general' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=general"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_GENERAL'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'maintenance' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=maintenance"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_MAINTENANCE'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'seo' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=seo"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_SEO'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'meta' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=meta"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_META_DISPLAY'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'media' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=media"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_MEDIA'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'locale' ? 'active' : ''; ?>" href="/admin/settings?tab=global&subtab=locale"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GLOBAL_SUBTAB_LOCALE'); ?></a>
            </li>
        </ul>
    <?php endif; ?>

    <?php if ($tab === 'mail') : ?>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? 'templates') === 'templates' ? 'active' : ''; ?>" href="/admin/settings?tab=mail&subtab=templates"><?php echo Lang::get('FFCMS_MAIL_TEMPLATES'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'wrappers' ? 'active' : ''; ?>" href="/admin/settings?tab=mail&subtab=wrappers"><?php echo Lang::get('FFCMS_MAIL_WRAPPERS'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($subtab ?? '') === 'settings' ? 'active' : ''; ?>" href="/admin/settings?tab=mail&subtab=settings"><?php echo Lang::get('FFCMS_SETTINGS'); ?></a>
            </li>
        </ul>
    <?php endif; ?>

    <?php if ($tab === 'global') : ?>
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success" role="alert">
                <?php echo Template::escape($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo Template::escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (($subtab ?? 'general') === 'general') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-general" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-general" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="general">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_GENERAL_TITLE'); ?></h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_name"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SITE_NAME'); ?></label>
                        <input class="form-control" type="text" id="site_name" name="site_name" value="<?php echo Template::escape($siteName); ?>" required maxlength="120">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_tagline"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SITE_TAGLINE'); ?></label>
                        <input class="form-control" type="text" id="site_tagline" name="site_tagline" value="<?php echo Template::escape($siteTagline); ?>" maxlength="160">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_base_url"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SITE_BASE_URL'); ?></label>
                        <input class="form-control" type="url" id="site_base_url" name="site_base_url" value="<?php echo Template::escape($siteBaseUrl); ?>">
                    </div>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (($subtab ?? '') === 'maintenance') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-maintenance" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-maintenance" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="maintenance">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_TITLE'); ?></h2>
                <div class="form-check mb-3">
                    <input type="hidden" name="maintenance_enabled" value="0">
                    <input class="form-check-input" type="checkbox" id="maintenance_enabled" name="maintenance_enabled" value="1" <?php echo $maintenanceEnabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="maintenance_enabled"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_ENABLED'); ?></label>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="maintenance_message"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_MESSAGE'); ?></label>
                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" maxlength="1000"><?php echo Template::escape($maintenanceMessage); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="maintenance_retry_after_minutes"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_RETRY_AFTER'); ?></label>
                    <input class="form-control" type="number" id="maintenance_retry_after_minutes" name="maintenance_retry_after_minutes" value="<?php echo Template::escape($maintenanceRetry); ?>" min="5" max="10080">
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="maintenance_allowlist_ips"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_ALLOWLIST'); ?></label>
                    <textarea class="form-control" id="maintenance_allowlist_ips" name="maintenance_allowlist_ips" rows="3" placeholder="<?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_ALLOWLIST_PLACEHOLDER'); ?>"><?php echo Template::escape($maintenanceAllowlist); ?></textarea>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (($subtab ?? '') === 'seo') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-seo" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-seo" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="seo">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TITLE'); ?></h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="seo_default_title"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_DEFAULT_TITLE'); ?></label>
                        <input class="form-control" type="text" id="seo_default_title" name="seo_default_title" value="<?php echo Template::escape($seoDefaultTitle); ?>" maxlength="70">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="seo_default_description"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_DEFAULT_DESCRIPTION'); ?></label>
                        <input class="form-control" type="text" id="seo_default_description" name="seo_default_description" value="<?php echo Template::escape($seoDefaultDescription); ?>" maxlength="160">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="seo_default_robots_index" value="0">
                            <input class="form-check-input" type="checkbox" id="seo_default_robots_index" name="seo_default_robots_index" value="1" <?php echo $seoRobotsIndex ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="seo_default_robots_index"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_ROBOTS_INDEX'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="seo_default_robots_follow" value="0">
                            <input class="form-check-input" type="checkbox" id="seo_default_robots_follow" name="seo_default_robots_follow" value="1" <?php echo $seoRobotsFollow ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="seo_default_robots_follow"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_ROBOTS_FOLLOW'); ?></label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="seo_canonical_base_url_override"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_CANONICAL_OVERRIDE'); ?></label>
                    <input class="form-control" type="url" id="seo_canonical_base_url_override" name="seo_canonical_base_url_override" value="<?php echo Template::escape($seoCanonicalOverride); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="seo_og_site_name"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_OG_SITE_NAME'); ?></label>
                    <input class="form-control" type="text" id="seo_og_site_name" name="seo_og_site_name" value="<?php echo Template::escape($seoOgSiteName); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="seo_og_default_image_asset_id"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_OG_DEFAULT_IMAGE'); ?></label>
                    <input class="form-control" type="number" id="seo_og_default_image_asset_id" name="seo_og_default_image_asset_id" value="<?php echo Template::escape($seoOgDefaultImage); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="seo_twitter_card_type"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TWITTER_CARD'); ?></label>
                    <select class="form-select" id="seo_twitter_card_type" name="seo_twitter_card_type">
                        <option value="summary" <?php echo $seoTwitterCard === 'summary' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TWITTER_CARD_SUMMARY'); ?></option>
                        <option value="summary_large_image" <?php echo $seoTwitterCard === 'summary_large_image' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TWITTER_CARD_SUMMARY_LARGE'); ?></option>
                    </select>
                </div>
                
                <div class="form-check">
                    <input type="hidden" name="seo_sitemap_enabled" value="0">
                    <input class="form-check-input" type="checkbox" id="seo_sitemap_enabled" name="seo_sitemap_enabled" value="1" <?php echo $seoSitemapEnabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="seo_sitemap_enabled"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_SEO_SITEMAP_ENABLED'); ?></label>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (($subtab ?? '') === 'meta') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-meta" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-meta" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="meta">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSTS'); ?></h2>
                <div class="form-check mb-2">
                    <input type="hidden" name="meta_post_enabled" value="0">
                    <input class="form-check-input" type="checkbox" id="meta_post_enabled" name="meta_post_enabled" value="1" <?php echo $metaPostEnabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_post_enabled"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_ENABLED'); ?></label>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_post_show_author" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_post_show_author" name="meta_post_show_author" value="1" <?php echo $metaPostShowAuthor ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_post_show_author"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_AUTHOR'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_post_author_position" name="meta_post_author_position">
                            <option value="above" <?php echo $metaPostAuthorPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPostAuthorPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_post_show_publish_date" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_post_show_publish_date" name="meta_post_show_publish_date" value="1" <?php echo $metaPostShowPublishDate ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_post_show_publish_date"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_PUBLISH_DATE'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_post_publish_date_position" name="meta_post_publish_date_position">
                            <option value="above" <?php echo $metaPostPublishDatePosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPostPublishDatePosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_post_show_updated_date" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_post_show_updated_date" name="meta_post_show_updated_date" value="1" <?php echo $metaPostShowUpdatedDate ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_post_show_updated_date"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_UPDATED_DATE'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_post_updated_date_position" name="meta_post_updated_date_position">
                            <option value="above" <?php echo $metaPostUpdatedDatePosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPostUpdatedDatePosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_post_show_categories" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_post_show_categories" name="meta_post_show_categories" value="1" <?php echo $metaPostShowCategories ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_post_show_categories"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_CATEGORIES'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_post_categories_position" name="meta_post_categories_position">
                            <option value="above" <?php echo $metaPostCategoriesPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPostCategoriesPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_post_show_tags" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_post_show_tags" name="meta_post_show_tags" value="1" <?php echo $metaPostShowTags ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_post_show_tags"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_TAGS'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_post_tags_position" name="meta_post_tags_position">
                            <option value="above" <?php echo $metaPostTagsPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPostTagsPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_PAGES'); ?></h2>
                <div class="form-check mb-2">
                    <input type="hidden" name="meta_page_enabled" value="0">
                    <input class="form-check-input" type="checkbox" id="meta_page_enabled" name="meta_page_enabled" value="1" <?php echo $metaPageEnabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_page_enabled"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_ENABLED'); ?></label>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_page_show_author" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_page_show_author" name="meta_page_show_author" value="1" <?php echo $metaPageShowAuthor ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_page_show_author"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_AUTHOR'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_page_author_position" name="meta_page_author_position">
                            <option value="above" <?php echo $metaPageAuthorPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPageAuthorPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_page_show_publish_date" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_page_show_publish_date" name="meta_page_show_publish_date" value="1" <?php echo $metaPageShowPublishDate ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_page_show_publish_date"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_PUBLISH_DATE'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_page_publish_date_position" name="meta_page_publish_date_position">
                            <option value="above" <?php echo $metaPagePublishDatePosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPagePublishDatePosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_page_show_updated_date" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_page_show_updated_date" name="meta_page_show_updated_date" value="1" <?php echo $metaPageShowUpdatedDate ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_page_show_updated_date"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_UPDATED_DATE'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_page_updated_date_position" name="meta_page_updated_date_position">
                            <option value="above" <?php echo $metaPageUpdatedDatePosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPageUpdatedDatePosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_page_show_categories" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_page_show_categories" name="meta_page_show_categories" value="1" <?php echo $metaPageShowCategories ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_page_show_categories"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_CATEGORIES'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_page_categories_position" name="meta_page_categories_position">
                            <option value="above" <?php echo $metaPageCategoriesPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPageCategoriesPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="meta_page_show_tags" value="0">
                            <input class="form-check-input" type="checkbox" id="meta_page_show_tags" name="meta_page_show_tags" value="1" <?php echo $metaPageShowTags ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="meta_page_show_tags"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_SHOW_TAGS'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="meta_page_tags_position" name="meta_page_tags_position">
                            <option value="above" <?php echo $metaPageTagsPosition === 'above' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_ABOVE'); ?></option>
                            <option value="below" <?php echo $metaPageTagsPosition === 'below' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_BELOW'); ?></option>
                        </select>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (($subtab ?? '') === 'media') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-media" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-media" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="media">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_TITLE'); ?></h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="media_upload_max_mb"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_UPLOAD_MAX'); ?></label>
                        <input class="form-control" type="number" id="media_upload_max_mb" name="media_upload_max_mb" value="<?php echo Template::escape($mediaUploadMax); ?>" min="1" max="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="media_image_max_width"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_MAX_WIDTH'); ?></label>
                        <input class="form-control" type="number" id="media_image_max_width" name="media_image_max_width" value="<?php echo Template::escape($mediaImageMaxWidth); ?>" min="100" max="8000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="media_image_max_height"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_MAX_HEIGHT'); ?></label>
                        <input class="form-control" type="number" id="media_image_max_height" name="media_image_max_height" value="<?php echo Template::escape($mediaImageMaxHeight); ?>" min="100" max="8000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="media_image_quality"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_QUALITY'); ?></label>
                        <input class="form-control" type="number" id="media_image_quality" name="media_image_quality" value="<?php echo Template::escape($mediaImageQuality); ?>" min="40" max="95">
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="hidden" name="media_image_generate_thumbnails" value="0">
                    <input class="form-check-input" type="checkbox" id="media_image_generate_thumbnails" name="media_image_generate_thumbnails" value="1" <?php echo $mediaGenerateThumbnails ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="media_image_generate_thumbnails"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_GENERATE_THUMBNAILS'); ?></label>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (($subtab ?? '') === 'locale') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-locale" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-locale" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="subtab" value="locale">
                
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_TITLE'); ?></h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="default_language"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_DEFAULT'); ?></label>
                        <select class="form-select" id="default_language" name="default_language">
                            <?php foreach ($availableLanguages as $lang) : ?>
                                <option value="<?php echo $lang; ?>" <?php echo $defaultLanguage === $lang ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="force_language"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_FORCE'); ?></label>
                        <select class="form-select" id="force_language" name="force_language">
                            <option value="off" <?php echo $forceLanguage === 'off' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_OFF'); ?></option>
                            <?php foreach ($availableLanguages as $lang) : ?>
                                <option value="<?php echo $lang; ?>" <?php echo $forceLanguage === $lang ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input type="hidden" name="language_auto_detect" value="0">
                    <input class="form-check-input" type="checkbox" id="language_auto_detect" name="language_auto_detect" value="1" <?php echo $languageAutoDetect ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="language_auto_detect"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_AUTO_DETECT'); ?></label>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="date_format"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_DATE_FORMAT'); ?></label>
                        <select class="form-select" id="date_format" name="date_format">
                            <?php foreach ($availableDateFormats as $format) : ?>
                                <option value="<?php echo $format; ?>" <?php echo $dateFormat === $format ? 'selected' : ''; ?>><?php echo $format; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="time_format"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_TIME_FORMAT'); ?></label>
                        <select class="form-select" id="time_format" name="time_format">
                            <?php foreach ($availableTimeFormats as $format) : ?>
                                <option value="<?php echo $format; ?>" <?php echo $timeFormat === $format ? 'selected' : ''; ?>><?php echo $format; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="datetime_format"><?php echo Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_DATETIME_FORMAT'); ?></label>
                        <select class="form-select" id="datetime_format" name="datetime_format">
                            <?php foreach ($availableDatetimeFormats as $format) : ?>
                                <option value="<?php echo $format; ?>" <?php echo $datetimeFormat === $format ? 'selected' : ''; ?>><?php echo $format; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tab === 'freegate') : ?>
        <div class="zulu-toolbar-row">
            <div class="btn-group">
                <button class="btn btn-primary" form="settings-freegate" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                    <?php echo Lang::get('FFCMS_SAVE'); ?>
                </button>
            </div>
        </div>
        <form id="settings-freegate" method="post" class="zulu-panel">
            <?php echo Csrf::input(); ?>
            <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS.FREEGATE_INTEGRATIONS'); ?></h2>
            <div class="mb-3">
                <label class="form-label" for="freegate_mode"><?php echo Lang::get('FFCMS.FREEGATE_INTEGRATIONS_DEVSTORE'); ?></label>
                <select class="form-select" id="freegate_mode" name="freegate_mode">
                    <option value="external" <?php echo $freegateMode === 'external' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MODE_EXTERNAL'); ?></option>
                    <?php if ($freegateDetected) : ?>
                        <option value="home" <?php echo $freegateMode === 'home' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MODE_HOME'); ?></option>
                    <?php endif; ?>
                </select>
                <?php if (!$freegateDetected) : ?>
                    <p class="mt-2 mb-0"><?php echo Lang::get('FFCMS.FREEGATE_INTEGRATIONS_NO_DEVSTORE'); ?></p>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($tab === 'mail') : ?>
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success" role="alert">
                <?php echo Template::escape($success); ?>
            </div>
        <?php endif; ?>
        <?php if (($subtab ?? 'templates') === 'templates') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <a class="btn btn-primary" href="/admin/settings/mailtemplates/new">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                        <?php echo Lang::get('FFCMS_NEW'); ?>
                    </a>
                    <a class="btn btn-outline-secondary" href="/admin/settings?tab=mail&subtab=templates">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                        <?php echo Lang::get('FFCMS_CLOSE'); ?>
                    </a>
                </div>
            </div>

            <?php if (!empty($missing)) : ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo Lang::get('FFCMS_MISSING_TEMPLATES'); ?>: <?php echo Template::escape(implode(', ', $missing)); ?>
                </div>
            <?php endif; ?>

            <div class="zulu-panel">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                            <th><?php echo Lang::get('FFCMS_TEMPLATE_KEY'); ?></th>
                            <th><?php echo Lang::get('FFCMS_LANGUAGE'); ?></th>
                            <th><?php echo Lang::get('FFCMS_CORE'); ?></th>
                            <th><?php echo Lang::get('FFCMS_ENABLED'); ?></th>
                            <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                            <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template) : ?>
                            <tr>
                                <td><?php echo Template::escape($template['name'] ?? ''); ?></td>
                                <td><?php echo Template::escape($template['template_key'] ?? ''); ?></td>
                                <td><?php echo Template::escape($template['language_tag'] ?? ''); ?></td>
                                <td><?php echo !empty($template['is_core']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                                <td><?php echo !empty($template['is_enabled']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                                <td><?php echo Template::escape($template['updated_at'] ?? ''); ?></td>
                                <td>
                                    <div class="ff-action-group">
                                        <a class="ff-action-icon" href="/admin/settings/mailtemplates/edit/<?php echo $template['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                        </a>
                                        <?php if (empty($template['is_core'])) : ?>
                                            <a class="ff-action-icon" href="/admin/settings/mailtemplates/delete/<?php echo $template['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (($subtab ?? '') === 'wrappers') : ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <a class="btn btn-primary" href="/admin/settings/mailwrappers/new">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                        <?php echo Lang::get('FFCMS_NEW'); ?>
                    </a>
                    <a class="btn btn-outline-secondary" href="/admin/settings?tab=mail&subtab=wrappers">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                        <?php echo Lang::get('FFCMS_CLOSE'); ?>
                    </a>
                </div>
            </div>

            <div class="zulu-panel">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                            <th><?php echo Lang::get('FFCMS_WRAPPER_KEY'); ?></th>
                            <th><?php echo Lang::get('FFCMS_DEFAULT'); ?></th>
                            <th><?php echo Lang::get('FFCMS_ENABLED'); ?></th>
                            <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                            <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wrappers as $wrapper) : ?>
                            <tr>
                                <td><?php echo Template::escape($wrapper['name'] ?? ''); ?></td>
                                <td><?php echo Template::escape($wrapper['wrapper_key'] ?? ''); ?></td>
                                <td><?php echo !empty($wrapper['is_default']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                                <td><?php echo !empty($wrapper['is_enabled']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                                <td><?php echo Template::escape($wrapper['updated_at'] ?? ''); ?></td>
                                <td>
                                    <div class="ff-action-group">
                                        <a class="ff-action-icon" href="/admin/settings/mailwrappers/edit/<?php echo $wrapper['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                        </a>
                                        <?php if (empty($wrapper['is_default'])) : ?>
                                            <a class="ff-action-icon" href="/admin/settings/mailwrappers/delete/<?php echo $wrapper['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (($subtab ?? '') === 'settings') : ?>
            <?php if ($testEmailResult === 'success') : ?>
                <div class="alert alert-success" role="alert">
                    Test email sent successfully.
                </div>
            <?php elseif ($testEmailResult === 'failed') : ?>
                <div class="alert alert-danger" role="alert">
                    Failed to send test email.
                </div>
            <?php endif; ?>
            <div class="zulu-toolbar-row">
                <div class="btn-group">
                    <button class="btn btn-primary" form="settings-mail" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                        <?php echo Lang::get('FFCMS_SAVE'); ?>
                    </button>
                    <button class="btn btn-outline-secondary" form="test-email-form" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-paper-plane"></i></span>
                        <?php echo Lang::get('FFCMS_TEST_EMAIL'); ?>
                    </button>
                </div>
            </div>
            <form id="settings-mail" method="post" class="zulu-panel">
                <?php echo Csrf::input(); ?>
                <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_MAIL_SETTINGS'); ?></h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_transport"><?php echo Lang::get('FFCMS_MAIL_TRANSPORT'); ?></label>
                        <select class="form-select" id="mail_transport" name="mail_transport">
                            <option value="php_mail" <?php echo $mailTransport === 'php_mail' ? 'selected' : ''; ?>>PHP Mail</option>
                            <option value="smtp" <?php echo $mailTransport === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_from_email"><?php echo Lang::get('FFCMS_FROM_EMAIL'); ?></label>
                        <input class="form-control" type="email" id="mail_from_email" name="mail_from_email" value="<?php echo Template::escape($mailFromEmail); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_from_name"><?php echo Lang::get('FFCMS_FROM_NAME'); ?></label>
                        <input class="form-control" type="text" id="mail_from_name" name="mail_from_name" value="<?php echo Template::escape($mailFromName); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_replyto_email"><?php echo Lang::get('FFCMS_REPLYTO_EMAIL'); ?></label>
                        <input class="form-control" type="email" id="mail_replyto_email" name="mail_replyto_email" value="<?php echo Template::escape($mailReplyTo); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_host"><?php echo Lang::get('FFCMS_SMTP_HOST'); ?></label>
                        <input class="form-control" type="text" id="smtp_host" name="smtp_host" value="<?php echo Template::escape($smtpHost); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="smtp_port"><?php echo Lang::get('FFCMS_SMTP_PORT'); ?></label>
                        <input class="form-control" type="number" id="smtp_port" name="smtp_port" value="<?php echo Template::escape($smtpPort); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="smtp_security"><?php echo Lang::get('FFCMS_SMTP_SECURITY'); ?></label>
                        <select class="form-select" id="smtp_security" name="smtp_security">
                            <option value="none" <?php echo $smtpSecurity === 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="tls" <?php echo $smtpSecurity === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $smtpSecurity === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_username"><?php echo Lang::get('FFCMS_SMTP_USERNAME'); ?></label>
                        <input class="form-control" type="text" id="smtp_username" name="smtp_username" value="<?php echo Template::escape($smtpUsername); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_password"><?php echo Lang::get('FFCMS_SMTP_PASSWORD'); ?></label>
                        <input class="form-control" type="password" id="smtp_password" name="smtp_password" value="">
                    </div>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="mail_force_plain_text" value="0">
                    <input class="form-check-input" type="checkbox" id="mail_force_plain_text" name="mail_force_plain_text" value="1" <?php echo $mailForcePlain ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="mail_force_plain_text"><?php echo Lang::get('FFCMS_MAIL_FORCE_PLAIN'); ?></label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="mail_log_enabled" value="0">
                    <input class="form-check-input" type="checkbox" id="mail_log_enabled" name="mail_log_enabled" value="1" <?php echo $mailLogEnabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="mail_log_enabled"><?php echo Lang::get('FFCMS_MAIL_LOG_ENABLED'); ?></label>
                </div>
            </form>
            <form id="test-email-form" method="post" action="/admin/settings/test-email" class="d-none">
                <?php echo Csrf::input(); ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tab === 'security') : ?>
        <div class="zulu-toolbar-row">
            <div class="btn-group">
                <button class="btn btn-primary" form="settings-security" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                    <?php echo Lang::get('FFCMS_SAVE'); ?>
                </button>
            </div>
        </div>
        <?php if ($captchaProvider === 'none') : ?>
            <div class="alert alert-warning" role="alert">
                <?php echo Lang::get('FFCMS_CAPTCHA_PROVIDER_NONE'); ?>
            </div>
        <?php endif; ?>
        <form id="settings-security" method="post" class="zulu-panel">
            <?php echo Csrf::input(); ?>
            <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_CAPTCHA_PROVIDER'); ?></h2>
            <div class="mb-3">
                <label class="form-label" for="captcha_provider"><?php echo Lang::get('FFCMS_PROVIDER'); ?></label>
                <select class="form-select" id="captcha_provider" name="captcha_provider">
                    <option value="none" <?php echo $captchaProvider === 'none' ? 'selected' : ''; ?>>None</option>
                    <option value="hcaptcha" <?php echo $captchaProvider === 'hcaptcha' ? 'selected' : ''; ?>>hCaptcha</option>
                    <option value="recaptcha" <?php echo $captchaProvider === 'recaptcha' ? 'selected' : ''; ?>>reCAPTCHA</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="hcaptcha_site_key"><?php echo Lang::get('FFCMS_HCAPTCHA_SITE_KEY'); ?></label>
                    <input class="form-control" type="password" id="hcaptcha_site_key" name="hcaptcha_site_key" value="">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="hcaptcha_secret_key"><?php echo Lang::get('FFCMS_HCAPTCHA_SECRET_KEY'); ?></label>
                    <input class="form-control" type="password" id="hcaptcha_secret_key" name="hcaptcha_secret_key" value="">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="recaptcha_site_key"><?php echo Lang::get('FFCMS_RECAPTCHA_SITE_KEY'); ?></label>
                    <input class="form-control" type="password" id="recaptcha_site_key" name="recaptcha_site_key" value="">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="recaptcha_secret_key"><?php echo Lang::get('FFCMS_RECAPTCHA_SECRET_KEY'); ?></label>
                    <input class="form-control" type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="">
                </div>
            </div>

            <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_ANTISPAM'); ?></h2>
            <div class="form-check mb-2">
                <input type="hidden" name="antispam_enabled" value="0">
                <input class="form-check-input" type="checkbox" id="antispam_enabled" name="antispam_enabled" value="1" <?php echo $antiEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label" for="antispam_enabled"><?php echo Lang::get('FFCMS_ANTISPAM_ENABLED'); ?></label>
            </div>
            <div class="form-check mb-2">
                <input type="hidden" name="antispam_honeypot" value="0">
                <input class="form-check-input" type="checkbox" id="antispam_honeypot" name="antispam_honeypot" value="1" <?php echo $antiHoney ? 'checked' : ''; ?>>
                <label class="form-check-label" for="antispam_honeypot"><?php echo Lang::get('FFCMS_HONEYPOT'); ?></label>
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="antispam_time" value="0">
                <input class="form-check-input" type="checkbox" id="antispam_time" name="antispam_time" value="1" <?php echo $antiTime ? 'checked' : ''; ?>>
                <label class="form-check-label" for="antispam_time"><?php echo Lang::get('FFCMS_TIME_TO_SUBMIT'); ?></label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="antispam_min_seconds"><?php echo Lang::get('FFCMS_MIN_SECONDS'); ?></label>
                <select class="form-select" id="antispam_min_seconds" name="antispam_min_seconds">
                    <?php foreach ([1,2,3,5,10] as $opt) : ?>
                        <option value="<?php echo $opt; ?>" <?php echo $antiMinSeconds === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_FAILURE_COUNT'); ?></h2>
            <div class="form-check mb-2">
                <input type="hidden" name="failcount_enabled" value="0">
                <input class="form-check-input" type="checkbox" id="failcount_enabled" name="failcount_enabled" value="1" <?php echo $failEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label" for="failcount_enabled"><?php echo Lang::get('FFCMS_FAILURE_COUNT_ENABLED'); ?></label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="failcount_limit"><?php echo Lang::get('FFCMS_FAILURE_LIMIT'); ?></label>
                <select class="form-select" id="failcount_limit" name="failcount_limit">
                    <?php foreach ([3,5,10] as $opt) : ?>
                        <option value="<?php echo $opt; ?>" <?php echo $failLimit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check mb-2">
                <input type="hidden" name="failcount_cooldown_enabled" value="0">
                <input class="form-check-input" type="checkbox" id="failcount_cooldown_enabled" name="failcount_cooldown_enabled" value="1" <?php echo $failCooldownEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label" for="failcount_cooldown_enabled"><?php echo Lang::get('FFCMS_COOLDOWN'); ?></label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="failcount_cooldown_minutes"><?php echo Lang::get('FFCMS_COOLDOWN_MINUTES'); ?></label>
                <select class="form-select" id="failcount_cooldown_minutes" name="failcount_cooldown_minutes">
                    <?php foreach ([5,15,60,240] as $opt) : ?>
                        <option value="<?php echo $opt; ?>" <?php echo $failCooldownMinutes === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_PER_FORM'); ?></h2>
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_FORM'); ?></th>
                        <th><?php echo Lang::get('FFCMS_CAPTCHA'); ?></th>
                        <th><?php echo Lang::get('FFCMS_HONEYPOT'); ?></th>
                        <th><?php echo Lang::get('FFCMS_TIME_TO_SUBMIT'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formTypes as $formType) : ?>
                        <tr>
                            <td><?php echo Template::escape($formType); ?></td>
                            <td><input type="checkbox" name="captcha_<?php echo Template::escape($formType); ?>" value="1" <?php echo Settings::get('captcha_' . $formType, false, 'security') ? 'checked' : ''; ?>></td>
                            <td><input type="checkbox" name="honeypot_<?php echo Template::escape($formType); ?>" value="1" <?php echo Settings::get('antispam_honeypot_' . $formType, true, 'security') ? 'checked' : ''; ?>></td>
                            <td><input type="checkbox" name="time_<?php echo Template::escape($formType); ?>" value="1" <?php echo Settings::get('antispam_time_' . $formType, true, 'security') ? 'checked' : ''; ?>></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php endif; ?>

    <?php if ($tab === 'maintenance') : ?>
        <?php if ($maintenanceResult) : ?>
            <div class="alert alert-info" role="alert">
                <?php echo Lang::get('FFCMS_MAINTENANCE_RESULT'); ?>
                <div class="small">
                    <?php echo Template::escape($maintenanceResult['action'] ?? ''); ?>
                    <?php if (isset($maintenanceResult['removed'])) : ?>
                        - <?php echo Template::escape($maintenanceResult['removed']); ?>
                    <?php endif; ?>
                    <?php if (isset($maintenanceResult['bytes'])) : ?>
                        - <?php echo Template::escape($maintenanceResult['bytes']); ?>
                    <?php endif; ?>
                    <?php if (isset($maintenanceResult['orphans'])) : ?>
                        - <?php echo Template::escape($maintenanceResult['orphans']); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="zulu-panel">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_TASK'); ?></th>
                        <th><?php echo Lang::get('FFCMS_DESCRIPTION'); ?></th>
                        <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_TMP'); ?></td>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_TMP_DESC'); ?></td>
                        <td>
                            <form method="post">
                                <?php echo Csrf::input(); ?>
                                <input type="hidden" name="maintenance_action" value="cleanup_tmp">
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_RUN')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-broom"></i></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_UPDATES'); ?></td>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_UPDATES_DESC'); ?></td>
                        <td>
                            <form method="post">
                                <?php echo Csrf::input(); ?>
                                <input type="hidden" name="maintenance_action" value="cleanup_updates">
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_RUN')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-broom"></i></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_LOGS'); ?></td>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_LOGS_DESC'); ?></td>
                        <td>
                            <form method="post">
                                <?php echo Csrf::input(); ?>
                                <input type="hidden" name="maintenance_action" value="truncate_logs">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="confirm_truncate" name="confirm_truncate" value="1" required>
                                    <label class="form-check-label" for="confirm_truncate"><?php echo Lang::get('FFCMS_CONFIRM'); ?></label>
                                </div>
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_RUN')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_THUMBS'); ?></td>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_THUMBS_DESC'); ?></td>
                        <td>
                            <form method="post">
                                <?php echo Csrf::input(); ?>
                                <input type="hidden" name="maintenance_action" value="cleanup_thumbs">
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_RUN')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-broom"></i></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_ORPHANS'); ?></td>
                        <td><?php echo Lang::get('FFCMS_MAINTENANCE_ORPHANS_DESC'); ?></td>
                        <td>
                            <form method="post">
                                <?php echo Csrf::input(); ?>
                                <input type="hidden" name="maintenance_action" value="scan_orphans">
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_RUN')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'logs') : ?>
        <div class="zulu-panel">
            <form method="get" action="/admin/settings" class="mb-3 zulu-form-inline">
                <input type="hidden" name="tab" value="logs">
                <label class="form-label me-2" for="log-lines"><?php echo Lang::get('FFCMS_LOG_LINES'); ?></label>
                <select class="form-select w-auto me-2" id="log-lines" name="lines">
                    <?php foreach ([10,25,50,100,0] as $option) : ?>
                        <?php $label = $option === 0 ? Lang::get('FFCMS_ALL') : $option; ?>
                        <option value="<?php echo $option; ?>" <?php echo (($logs['selected_lines'] ?? 50) === $option) ? 'selected' : ''; ?>><?php echo Template::escape($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">
                    <span class="me-2" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                    <?php echo Lang::get('FFCMS_APPLY'); ?>
                </button>
            </form>

            <div class="zulu-list">
                <?php foreach (($logs['log_files'] ?? []) as $logFile) : ?>
                    <div class="zulu-list-row">
                        <div class="zulu-list-main">
                            <strong><?php echo Template::escape($logFile['file']); ?></strong><br>
                            <small>Size: <?php echo number_format($logFile['size']); ?> bytes | Modified: <?php echo Template::escape($logFile['modified_at']); ?></small>
                        </div>
                        <div class="zulu-list-actions ff-action-group">
                            <a class="ff-action-icon" href="/admin/settings?tab=logs&view=<?php echo Template::escape($logFile['channel']); ?>&lines=<?php echo ($logs['selected_lines'] ?? 50); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_VIEW')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                            </a>
                            <a class="ff-action-icon" href="/admin/settings?tab=logs&download=<?php echo Template::escape($logFile['channel']); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DOWNLOAD')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                            </a>
                            <form method="post" action="/admin/settings?tab=logs&clear=<?php echo Template::escape($logFile['channel']); ?>" class="d-inline">
                                <?php echo Csrf::input(); ?>
                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_CLEAR')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($confirmClear !== '') : ?>
    <div class="zulu-modal is-open">
        <div class="zulu-modal-backdrop"></div>
        <div class="zulu-modal-panel">
            <h2 class="zulu-modal-title"><?php echo Lang::get('FFCMS_CONFIRM'); ?></h2>
            <p><?php echo Lang::get('FFCMS_CONFIRM_CLEAR_LOG'); ?></p>
            <div class="zulu-modal-actions">
                <form method="post" action="/admin/settings?tab=logs&clear=<?php echo Template::escape($confirmClear); ?>">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="confirm" value="1">
                    <button class="btn btn-danger" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                        <?php echo Lang::get('FFCMS_CLEAR'); ?>
                    </button>
                </form>
                <a class="btn btn-outline-secondary" href="/admin/settings?tab=logs"><?php echo Lang::get('FFCMS_CLOSE'); ?></a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($logs['view_log']) && $confirmClear === '') : ?>
    <div class="zulu-modal is-open">
        <div class="zulu-modal-backdrop"></div>
        <div class="zulu-modal-panel zulu-modal-panel-wide">
            <h2 class="zulu-modal-title"><?php echo Lang::get('FFCMS_LOGS_TITLE'); ?>: <?php echo Template::escape($logs['channels'][$logs['view_log']] ?? ''); ?></h2>
            <pre class="zulu-log-content"><?php echo $logs['view_content'] ?? ''; ?></pre>
            <div class="zulu-modal-actions">
                <a class="btn btn-outline-secondary" href="/admin/settings?tab=logs"><?php echo Lang::get('FFCMS_CLOSE'); ?></a>
            </div>
        </div>
    </div>
<?php endif; ?>
