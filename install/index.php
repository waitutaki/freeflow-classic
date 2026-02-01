<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$configPath = $rootDir . '/core/admin/config/config.php';

$availableLangs = ['en-GB', 'de-DE', 'fr-FR', 'es-ES', 'it-IT'];
$langTag = resolveLangTag($availableLangs);
$translations = loadTranslations($rootDir . '/core/languages/' . $langTag . '/install.ini');
$fallbackTranslations = loadTranslations($rootDir . '/core/languages/en-GB/install.ini');

if (is_file($configPath)) {
    renderPage(
        t('INSTALL.ALREADY_INSTALLED_TITLE', $translations),
        renderAlreadyInstalled()
    );
    exit;
}

$step = resolveStep();
$requirements = checkRequirements();
$requirementsOk = !in_array(false, array_column($requirements, 'ok'), true);

if (!$requirementsOk && $step !== 'welcome') {
    renderPage(t('INSTALL.WELCOME_TITLE', $translations), renderWelcome($requirements, $requirementsOk));
    exit;
}

if ($step === 'welcome') {
    renderPage(t('INSTALL.WELCOME_TITLE', $translations), renderWelcome($requirements, $requirementsOk));
    exit;
}

if ($step === 'db') {
    $dbResult = handleDbStep($requirementsOk);
    if ($dbResult['show_site']) {
        renderPage(t('INSTALL.SITE_TITLE', $translations), renderSiteStep($dbResult));
        exit;
    }
    renderPage(t('INSTALL.DB_TITLE', $translations), renderDbStep($dbResult));
    exit;
}

if ($step === 'site') {
    $siteResult = handleSiteStep();
    if ($siteResult['complete']) {
        renderPage(t('INSTALL.COMPLETE_TITLE', $translations), renderComplete());
        exit;
    }
    if ($siteResult['show_back']) {
        renderPage(t('INSTALL.SITE_TITLE', $translations), renderInstallError($siteResult));
        exit;
    }
    renderPage(t('INSTALL.SITE_TITLE', $translations), renderSiteStep($siteResult));
    exit;
}

renderPage(t('INSTALL.WELCOME_TITLE', $translations), renderWelcome($requirements, $requirementsOk));

function resolveStep(): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST['step']) ? (string)$_POST['step'] : 'welcome';
    }

    return isset($_GET['step']) ? (string)$_GET['step'] : 'welcome';
}

function resolveLangTag(array $available): string
{
    $candidate = '';
    if (isset($_POST['lang'])) {
        $candidate = (string)$_POST['lang'];
    } elseif (isset($_GET['lang'])) {
        $candidate = (string)$_GET['lang'];
    } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $candidate = (string)$_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

    $candidate = trim($candidate);
    $detected = '';

    if ($candidate !== '') {
        $parts = preg_split('/,/', $candidate) ?: [];
        foreach ($parts as $part) {
            $chunk = trim($part);
            if ($chunk === '') {
                continue;
            }
            $chunk = preg_split('/;/', $chunk)[0] ?? $chunk;
            $chunk = normalizeTag($chunk);
            if (in_array($chunk, $available, true)) {
                $detected = $chunk;
                break;
            }
            $short = strtolower(substr($chunk, 0, 2));
            if ($short === 'en') {
                $detected = 'en-GB';
                break;
            }
            if ($short === 'de') {
                $detected = 'de-DE';
                break;
            }
            if ($short === 'fr') {
                $detected = 'fr-FR';
                break;
            }
            if ($short === 'es') {
                $detected = 'es-ES';
                break;
            }
            if ($short === 'it') {
                $detected = 'it-IT';
                break;
            }
        }
    }

    if ($detected === '') {
        $detected = 'en-GB';
    }

    return $detected;
}

function normalizeTag(string $tag): string
{
    $tag = str_replace('_', '-', trim($tag));
    $parts = explode('-', $tag);
    if (count($parts) !== 2) {
        return $tag;
    }
    return strtolower($parts[0]) . '-' . strtoupper($parts[1]);
}

function loadTranslations(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $data = parse_ini_file($path, false, INI_SCANNER_RAW);
    return is_array($data) ? $data : [];
}

function t(string $key, array $translations): string
{
    if ($key === '') {
        return '';
    }
    if (isset($translations[$key])) {
        return (string)$translations[$key];
    }
    $fallback = $GLOBALS['fallbackTranslations'] ?? [];
    if (is_array($fallback) && isset($fallback[$key])) {
        return (string)$fallback[$key];
    }
    return $key;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderPage(string $title, string $bodyHtml): void
{
    $logo = '/storage/media/system/fflogo.png';
    echo '<!doctype html>';
    $langTag = isset($GLOBALS['langTag']) ? (string)$GLOBALS['langTag'] : 'en-GB';
    echo '<html lang="' . h($langTag) . '">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . '</title>';
    echo '<link rel="stylesheet" href="/core/assets/lib/bootstrap/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="/core/assets/lib/fontawesome6/css/all.min.css">';
    echo '<link rel="stylesheet" href="/core/assets/css/core.css">';
    echo '</head>';
    echo '<body class="bg-light">';
    echo '<div class="container py-5">';
    echo '<div class="row justify-content-center">';
    echo '<div class="col-12 col-lg-9">';
    echo '<div class="text-center mb-4">';
    $altText = t('INSTALL.LOGO_ALT', $GLOBALS['translations']);
    echo '<img src="' . h($logo) . '" alt="' . h($altText) . '" class="d-block mx-auto mb-3 w-25">';
    echo '<h1 class="h3 mb-1">' . h($title) . '</h1>';
    echo '</div>';
    echo $bodyHtml;
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<script src="/core/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>';
    echo '</body>';
    echo '</html>';
}

function checkRequirements(): array
{
    $rootDir = dirname(__DIR__);
    $configDir = $rootDir . '/core/admin/config';
    $storageDir = $rootDir . '/storage';

    return [
        ['label' => 'INSTALL.REQ_PHP', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ['label' => 'INSTALL.REQ_PDO', 'ok' => extension_loaded('pdo')],
        ['label' => 'INSTALL.REQ_PDO_MYSQL', 'ok' => extension_loaded('pdo_mysql')],
        ['label' => 'INSTALL.REQ_CURL', 'ok' => extension_loaded('curl')],
        ['label' => 'INSTALL.REQ_OPENSSL', 'ok' => extension_loaded('openssl')],
        ['label' => 'INSTALL.REQ_GD', 'ok' => extension_loaded('gd')],
        ['label' => 'INSTALL.REQ_FILEINFO', 'ok' => extension_loaded('fileinfo')],
        ['label' => 'INSTALL.REQ_DOM', 'ok' => extension_loaded('dom')],
        ['label' => 'INSTALL.REQ_CONFIG_WRITABLE', 'ok' => is_dir($configDir) && is_writable($configDir)],
        ['label' => 'INSTALL.REQ_STORAGE_WRITABLE', 'ok' => is_dir($storageDir) && is_writable($storageDir)],
    ];
}

function renderWelcome(array $requirements, bool $requirementsOk): string
{
    $translations = $GLOBALS['translations'];
    $langTag = $GLOBALS['langTag'];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<p class="text-muted">' . h(t('INSTALL.WELCOME_INTRO', $translations)) . '</p>';
    $html .= '<div class="list-group mb-4">';

    foreach ($requirements as $req) {
        $ok = !empty($req['ok']);
        $icon = $ok ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger';
        $label = t((string)$req['label'], $translations);
        $statusText = $ok ? t('INSTALL.STATUS_OK', $translations) : t('INSTALL.STATUS_FAIL', $translations);
        $statusLabel = $ok ? t('INSTALL.STATUS_ENABLED', $translations) : t('INSTALL.STATUS_DISABLED', $translations);
        $html .= '<div class="list-group-item d-flex justify-content-between align-items-center">';
        $html .= '<span>' . h($label) . '</span>';
        $html .= '<span class="d-inline-flex align-items-center gap-2">';
        $html .= '<i class="fa-solid ' . h($icon) . '" aria-hidden="true"></i>';
        $html .= '<span class="small" aria-label="' . h($statusLabel) . '">' . h($statusText) . '</span>';
        $html .= '</span>';
        $html .= '</div>';
    }

    $html .= '</div>';

    if ($requirementsOk) {
        $html .= '<div class="alert alert-success" role="status">' . h(t('INSTALL.REQ_OK', $translations)) . '</div>';
        $html .= '<form method="post" action="?step=db">';
        $html .= '<input type="hidden" name="step" value="db">';
        $html .= '<input type="hidden" name="lang" value="' . h($langTag) . '">';
        $html .= '<button type="submit" class="btn btn-primary">';
        $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.BTN_CONTINUE', $translations)) . '</span>';
        $html .= '</button>';
        $html .= '</form>';
    } else {
        $html .= '<div class="alert alert-danger" role="alert">' . h(t('INSTALL.REQ_FAIL', $translations)) . '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function handleDbStep(bool $requirementsOk): array
{
    $values = [
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => '',
        'db_user' => '',
        'db_pass' => '',
        'db_prefix' => 'ff_',
    ];

    $errors = [];
    $message = '';
    $showSite = false;
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($values as $key => $default) {
            if (isset($_POST[$key])) {
                $values[$key] = trim((string)$_POST[$key]);
            }
        }

        if ($values['db_host'] === '') {
            $values['db_host'] = 'localhost';
        }
        if ($values['db_port'] === '') {
            $values['db_port'] = '3306';
        }
        if ($values['db_prefix'] === '') {
            $values['db_prefix'] = 'ff_';
        }

        if ($values['db_name'] === '') {
            $errors[] = 'INSTALL.ERROR_DB_NAME_REQUIRED';
        }
        if ($values['db_user'] === '') {
            $errors[] = 'INSTALL.ERROR_DB_USER_REQUIRED';
        }
        if ($values['db_port'] !== '' && (!ctype_digit($values['db_port']) || (int)$values['db_port'] <= 0)) {
            $errors[] = 'INSTALL.ERROR_DB_PORT_INVALID';
        }

        if (!preg_match('/^[a-z0-9_]+$/', $values['db_prefix'])) {
            $errors[] = 'INSTALL.ERROR_DB_PREFIX_INVALID';
        } elseif (!str_ends_with($values['db_prefix'], '_')) {
            $errors[] = 'INSTALL.ERROR_DB_PREFIX_SUFFIX';
        }

        if ($requirementsOk && $errors === []) {
            $test = testDbConnection($values);
            if ($test['ok']) {
                $success = true;
                $message = 'INSTALL.DB_SUCCESS';
                $showSite = true;
            } else {
                $errors[] = $test['error'];
                $message = 'INSTALL.DB_FAIL';
            }
        }
    }

    return [
        'values' => $values,
        'db' => $values,
        'errors' => $errors,
        'message' => $message,
        'success' => $success,
        'show_site' => $showSite,
        'requirements_ok' => $requirementsOk,
        'db_notice' => $success ? $message : '',
    ];
}

function testDbConnection(array $values): array
{
    try {
        $pdo = createPdo($values);
        $probe = $values['db_prefix'] . 'install_probe_' . substr(sha1(uniqid('', true)), 0, 8);
        $pdo->exec('CREATE TABLE `' . $probe . '` (id INT NOT NULL)');
        $pdo->exec('DROP TABLE `' . $probe . '`');
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        if (stripos($message, 'CREATE') !== false || stripos($message, 'DROP') !== false) {
            return ['ok' => false, 'error' => 'INSTALL.ERROR_DB_PRIVILEGES'];
        }
        return ['ok' => false, 'error' => 'INSTALL.ERROR_DB_CONNECT'];
    }
}

function renderDbStep(array $data): string
{
    $translations = $GLOBALS['translations'];
    $langTag = $GLOBALS['langTag'];
    $values = $data['values'];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<p class="text-muted">' . h(t('INSTALL.DB_INTRO', $translations)) . '</p>';

    if (!empty($data['message'])) {
        $alertClass = !empty($data['success']) ? 'alert-success' : 'alert-danger';
        $html .= '<div class="alert ' . $alertClass . '" role="status">' . h(t((string)$data['message'], $translations)) . '</div>';
    }

    if (!empty($data['errors'])) {
        $html .= '<div class="alert alert-danger" role="alert">';
        $html .= '<div class="fw-semibold mb-2">' . h(t('INSTALL.DB_ERRORS_TITLE', $translations)) . '</div>';
        $html .= '<ul class="mb-0">';
        foreach ($data['errors'] as $error) {
            $html .= '<li>' . h(t((string)$error, $translations)) . '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }

    $html .= '<form method="post" action="?step=db">';
    $html .= '<input type="hidden" name="step" value="db">';
    $html .= '<input type="hidden" name="lang" value="' . h($langTag) . '">';

    $html .= '<div class="row g-3">';
    $html .= inputField('db_host', t('INSTALL.DB_HOST', $translations), $values['db_host']);
    $html .= inputField('db_port', t('INSTALL.DB_PORT', $translations), $values['db_port']);
    $html .= inputField('db_name', t('INSTALL.DB_NAME', $translations), $values['db_name']);
    $html .= inputField('db_user', t('INSTALL.DB_USER', $translations), $values['db_user']);
    $html .= passwordField('db_pass', t('INSTALL.DB_PASS', $translations));
    $html .= inputField('db_prefix', t('INSTALL.DB_PREFIX', $translations), $values['db_prefix'], t('INSTALL.DB_PREFIX_HELP', $translations));
    $html .= '</div>';

    $html .= '<div class="mt-4">';
    $html .= '<button type="submit" class="btn btn-primary">';
    $html .= '<i class="fa-solid fa-gear" aria-hidden="true"></i> <span>' . h(t('INSTALL.DB_TEST', $translations)) . '</span>';
    $html .= '</button>';
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function renderSiteStep(array $data): string
{
    $translations = $GLOBALS['translations'];
    $langTag = $GLOBALS['langTag'];

    $values = isset($data['values']) && is_array($data['values']) ? $data['values'] : [];
    $errors = isset($data['errors']) && is_array($data['errors']) ? $data['errors'] : [];

    $dbValues = $data['db'] ?? [];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<p class="text-muted">' . h(t('INSTALL.SITE_INTRO', $translations)) . '</p>';

    if (!empty($data['db_notice'])) {
        $html .= '<div class="alert alert-success" role="status">' . h(t((string)$data['db_notice'], $translations)) . '</div>';
    }

    if ($errors !== []) {
        $html .= '<div class="alert alert-danger" role="alert">';
        $html .= '<ul class="mb-0">';
        foreach ($errors as $error) {
            $html .= '<li>' . h(t((string)$error, $translations)) . '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }

    $html .= '<form method="post" action="?step=site">';
    $html .= '<input type="hidden" name="step" value="site">';
    $html .= '<input type="hidden" name="lang" value="' . h($langTag) . '">';

    foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'db_prefix'] as $key) {
        $val = isset($dbValues[$key]) ? (string)$dbValues[$key] : '';
        $html .= '<input type="hidden" name="' . h($key) . '" value="' . h($val) . '">';
    }

    $html .= '<div class="row g-3">';
    $html .= inputField('site_name', t('INSTALL.SITE_NAME', $translations), $values['site_name'] ?? '');
    $html .= inputField('site_tagline', t('INSTALL.SITE_TAGLINE', $translations), $values['site_tagline'] ?? '');
    $html .= inputField('site_url', t('INSTALL.SITE_URL', $translations), $values['site_url'] ?? detectSiteUrl());
    $html .= inputField('super_name', t('INSTALL.SUPER_NAME', $translations), $values['super_name'] ?? '');
    $html .= inputField('super_username', t('INSTALL.SUPER_USERNAME', $translations), $values['super_username'] ?? '');
    $html .= inputField('super_email', t('INSTALL.SUPER_EMAIL', $translations), $values['super_email'] ?? '', '', 'email');
    $html .= passwordField('super_password', t('INSTALL.SUPER_PASSWORD', $translations));
    $html .= passwordField('super_password_confirm', t('INSTALL.SUPER_PASSWORD_CONFIRM', $translations));
    $html .= '</div>';

    $html .= '<div class="mt-4">';
    $html .= '<button type="submit" class="btn btn-primary">';
    $html .= '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <span>' . h(t('INSTALL.SITE_SUBMIT', $translations)) . '</span>';
    $html .= '</button>';
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function handleSiteStep(): array
{
    $values = [
        'site_name' => '',
        'site_tagline' => '',
        'site_url' => '',
        'super_name' => '',
        'super_username' => '',
        'super_email' => '',
    ];

    $db = [];
    $errors = [];
    $complete = false;
    $showBack = false;
    $errorDetail = '';

    foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'db_prefix'] as $key) {
        $db[$key] = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    }

    foreach ($values as $key => $default) {
        if (isset($_POST[$key])) {
            $values[$key] = trim((string)$_POST[$key]);
        }
    }

    $password = isset($_POST['super_password']) ? (string)$_POST['super_password'] : '';
    $confirm = isset($_POST['super_password_confirm']) ? (string)$_POST['super_password_confirm'] : '';

    if ($values['site_name'] === '') {
        $errors[] = 'INSTALL.ERROR_SITE_NAME_REQUIRED';
    }
    if ($values['site_url'] === '') {
        $errors[] = 'INSTALL.ERROR_SITE_URL_REQUIRED';
    } elseif (!filter_var($values['site_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'INSTALL.ERROR_SITE_URL_INVALID';
    }

    if ($values['super_name'] === '') {
        $errors[] = 'INSTALL.ERROR_SUPER_NAME_REQUIRED';
    }

    if ($values['super_username'] === '') {
        $errors[] = 'INSTALL.ERROR_SUPER_USERNAME_REQUIRED';
    } elseif (!isValidSuperUsername($values['super_username'])) {
        $errors[] = 'INSTALL.ERROR_SUPER_USERNAME_INVALID';
    }

    if ($values['super_email'] === '') {
        $errors[] = 'INSTALL.ERROR_SUPER_EMAIL_REQUIRED';
    } elseif (!filter_var($values['super_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'INSTALL.ERROR_SUPER_EMAIL_INVALID';
    }

    if ($password === '') {
        $errors[] = 'INSTALL.ERROR_SUPER_PASSWORD_REQUIRED';
    } elseif ($password !== $confirm) {
        $errors[] = 'INSTALL.ERROR_SUPER_PASSWORD_CONFIRM';
    } elseif (!isStrongPassword($password)) {
        $errors[] = 'INSTALL.ERROR_SUPER_PASSWORD_WEAK';
    }

    if ($errors === []) {
        $result = runInstall($db, $values, $password);
        if ($result['ok']) {
            $complete = true;
        } else {
            $errors[] = $result['error'];
            if (!empty($result['detail'])) {
                $errorDetail = (string)$result['detail'];
            }
            $showBack = true;
        }
    }

    return [
        'values' => $values,
        'db' => $db,
        'errors' => $errors,
        'complete' => $complete,
        'show_back' => $showBack,
        'error_detail' => $errorDetail,
    ];
}

function runInstall(array $db, array $values, string $password): array
{
    $rootDir = dirname(__DIR__);
    $sqlPath = $rootDir . '/core/admin/sql/install.sql';
    if (!is_file($sqlPath)) {
        return ['ok' => false, 'error' => 'INSTALL.ERROR_INSTALL_FILE_MISSING', 'detail' => ''];
    }

    try {
        $pdo = createPdo($db);
    } catch (Throwable $exception) {
        return ['ok' => false, 'error' => 'INSTALL.ERROR_DB_CONNECT', 'detail' => ''];
    }

    $sql = (string)file_get_contents($sqlPath);
    if ($sql === '') {
        return ['ok' => false, 'error' => 'INSTALL.ERROR_INSTALL_FILE_MISSING', 'detail' => ''];
    }

    $sql = str_replace('#__', $db['db_prefix'], $sql);

    $tokenMap = [
        '{{site_name}}' => $values['site_name'],
        '{{site_tagline}}' => $values['site_tagline'],
        '{{site_url}}' => $values['site_url'],
        '{{super_email}}' => $values['super_email'],
    ];

    foreach ($tokenMap as $token => $value) {
        $sql = str_replace($token, sqlEscape($value), $sql);
    }

    $statements = splitSqlStatements($sql);
    $index = 0;
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '') {
            continue;
        }
        $index++;
        try {
            $pdo->exec($trimmed);
        } catch (Throwable $exception) {
            $detail = 'SQL ' . $index . ': ' . sanitizeError($exception->getMessage());
            return ['ok' => false, 'error' => 'INSTALL.ERROR_INSTALL_SQL', 'detail' => $detail];
        }
    }

    $nameFirst = trim($values['super_name']);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO `' . $db['db_prefix'] . 'users` (username, email, password_hash, status, role_id, name_first, name_last, created_at, updated_at, email_verified_at) VALUES (:username, :email, :hash, :status, :role_id, :name_first, :name_last, :created_at, :updated_at, :email_verified_at)');
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':username' => normalizeSuperUsername($values['super_username']),
            ':email' => $values['super_email'],
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':status' => 1,
            ':role_id' => 998,
            ':name_first' => $nameFirst,
            ':name_last' => '',
            ':created_at' => $now,
            ':updated_at' => $now,
            ':email_verified_at' => $now,
        ]);
        $userId = (int)$pdo->lastInsertId();

        $profileStmt = $pdo->prepare('INSERT INTO `' . $db['db_prefix'] . 'user_profiles` (user_id, created_at, updated_at) VALUES (:user_id, :created_at, :updated_at)');
        $profileStmt->execute([
            ':user_id' => $userId,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $roleStmt = $pdo->prepare('INSERT INTO `' . $db['db_prefix'] . 'user_roles` (user_id, role_id) VALUES (:user_id, :role_id)');
        $roleStmt->execute([
            ':user_id' => $userId,
            ':role_id' => 998,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        $detail = sanitizeError($exception->getMessage());
        return ['ok' => false, 'error' => 'INSTALL.ERROR_INSTALL_USER', 'detail' => $detail];
    }

    $configPayload = buildConfigPayload($db);
    $configPath = $rootDir . '/core/admin/config/config.php';
    if (!writeConfigFile($configPath, $configPayload)) {
        return ['ok' => false, 'error' => 'INSTALL.ERROR_INSTALL_CONFIG', 'detail' => ''];
    }

    return ['ok' => true, 'error' => '', 'detail' => ''];
}

function createPdo(array $db): PDO
{
    $host = $db['db_host'] !== '' ? $db['db_host'] : 'localhost';
    $port = $db['db_port'] !== '' ? (int)$db['db_port'] : 3306;
    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        if ($inString) {
            $buffer .= $char;
            if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $inString = false;
                $stringChar = '';
            }
            continue;
        }

        if ($char === '\'' || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}

function sqlEscape(string $value): string
{
    $value = str_replace('\\', '\\\\', $value);
    $value = str_replace("'", "\\'", $value);
    return $value;
}

function sanitizeError(string $message): string
{
    $message = preg_replace('/\s+/', ' ', $message) ?? '';
    $message = trim($message);
    if ($message === '') {
        return '';
    }
    if (strlen($message) > 180) {
        $message = substr($message, 0, 180);
    }
    return $message;
}

function buildConfigPayload(array $db): string
{
    $installedAt = date('Y-m-d H:i:s');
    $payload = "<?php\n\n";
    $payload .= "return [\n";
    $payload .= "    'db' => [\n";
    $payload .= "        'host' => '" . addslashes($db['db_host']) . "',\n";
    $payload .= "        'port' => '" . addslashes($db['db_port']) . "',\n";
    $payload .= "        'name' => '" . addslashes($db['db_name']) . "',\n";
    $payload .= "        'user' => '" . addslashes($db['db_user']) . "',\n";
    $payload .= "        'password' => '" . addslashes($db['db_pass']) . "',\n";
    $payload .= "        'prefix' => '" . addslashes($db['db_prefix']) . "',\n";
    $payload .= "    ],\n";
    $payload .= "    'meta' => [\n";
    $payload .= "        'product_name' => 'Freeflow CMS',\n";
    $payload .= "        'installed_at' => '" . addslashes($installedAt) . "',\n";
    $payload .= "        'license' => 'Proprietary',\n";
    $payload .= "    ],\n";
    $payload .= "];\n";

    return $payload;
}

function writeConfigFile(string $path, string $contents): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    $written = file_put_contents($path, $contents);
    if ($written === false) {
        return false;
    }
    @chmod($path, 0440);
    return true;
}

function isValidSuperUsername(string $username): bool
{
    $username = normalizeSuperUsername($username);
    if ($username === '') {
        return false;
    }
    if (strlen($username) < 6) {
        return false;
    }
    return (bool)preg_match('/^[a-z0-9_-]+$/', $username);
}

function normalizeSuperUsername(string $username): string
{
    $username = strtolower($username);
    $username = preg_replace('/[^a-z0-9_-]+/', '', $username) ?? '';
    return $username;
}

function isStrongPassword(string $password): bool
{
    if (strlen($password) < 10) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    return true;
}

function inputField(string $name, string $label, string $value, string $help = '', string $type = 'text'): string
{
    $html = '<div class="col-12 col-lg-6">';
    $html .= '<label class="form-label" for="' . h($name) . '">' . h($label) . '</label>';
    $html .= '<input type="' . h($type) . '" class="form-control" id="' . h($name) . '" name="' . h($name) . '" value="' . h($value) . '">';
    if ($help !== '') {
        $html .= '<div class="form-text">' . h($help) . '</div>';
    }
    $html .= '</div>';
    return $html;
}

function passwordField(string $name, string $label): string
{
    $html = '<div class="col-12 col-lg-6">';
    $html .= '<label class="form-label" for="' . h($name) . '">' . h($label) . '</label>';
    $html .= '<input type="password" class="form-control" id="' . h($name) . '" name="' . h($name) . '" value="">';
    $html .= '</div>';
    return $html;
}

function detectSiteUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'), '/');
    $base = $scheme . '://' . $host;
    if ($path !== '' && $path !== '/') {
        $base .= $path;
    }
    $base = preg_replace('/\/install$/', '', $base) ?? $base;
    return $base;
}

function renderAlreadyInstalled(): string
{
    $translations = $GLOBALS['translations'];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<p class="text-muted">' . h(t('INSTALL.ALREADY_INSTALLED_BODY', $translations)) . '</p>';
    $html .= '<div class="d-flex gap-2">';
    $html .= '<a class="btn btn-outline-primary" href="/">';
    $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.ALREADY_INSTALLED_GO_SITE', $translations)) . '</span>';
    $html .= '</a>';
    $html .= '<a class="btn btn-outline-primary" href="/admin">';
    $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.ALREADY_INSTALLED_GO_ADMIN', $translations)) . '</span>';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function renderComplete(): string
{
    $translations = $GLOBALS['translations'];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<div class="alert alert-success" role="status">' . h(t('INSTALL.COMPLETE_MESSAGE', $translations)) . '</div>';
    $html .= '<p class="text-muted">' . h(t('INSTALL.COMPLETE_NOTICE', $translations)) . '</p>';
    $html .= '<div class="d-flex gap-2">';
    $html .= '<a class="btn btn-primary" href="/">';
    $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.GO_SITE', $translations)) . '</span>';
    $html .= '</a>';
    $html .= '<a class="btn btn-outline-primary" href="/admin">';
    $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.GO_ADMIN', $translations)) . '</span>';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function renderInstallError(array $data): string
{
    $translations = $GLOBALS['translations'];
    $langTag = $GLOBALS['langTag'];

    $values = isset($data['values']) ? $data['values'] : [];
    $dbValues = isset($data['db']) ? $data['db'] : [];

    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body">';
    $html .= '<div class="alert alert-danger" role="alert">';
    $html .= '<ul class="mb-0">';
    foreach ($data['errors'] as $error) {
        $html .= '<li>' . h(t((string)$error, $translations)) . '</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';
    if (!empty($data['error_detail'])) {
        $html .= '<div class="alert alert-warning" role="status">';
        $html .= '<span class="fw-semibold">' . h(t('INSTALL.ERROR_DETAIL_LABEL', $translations)) . '</span> ';
        $html .= h((string)$data['error_detail']);
        $html .= '</div>';
    }

    $html .= '<form method="post" action="?step=site">';
    $html .= '<input type="hidden" name="step" value="site">';
    $html .= '<input type="hidden" name="lang" value="' . h($langTag) . '">';

    foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'db_prefix'] as $key) {
        $val = isset($dbValues[$key]) ? (string)$dbValues[$key] : '';
        $html .= '<input type="hidden" name="' . h($key) . '" value="' . h($val) . '">';
    }

    foreach (['site_name', 'site_tagline', 'site_url', 'super_name', 'super_username', 'super_email'] as $key) {
        $val = isset($values[$key]) ? (string)$values[$key] : '';
        $html .= '<input type="hidden" name="' . h($key) . '" value="' . h($val) . '">';
    }

    $html .= '<button type="submit" class="btn btn-outline-primary">';
    $html .= '<i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . h(t('INSTALL.BTN_BACK', $translations)) . '</span>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
