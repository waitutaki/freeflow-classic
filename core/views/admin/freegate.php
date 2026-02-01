<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$tab = $tab ?? 'overview';
$mode = $mode ?? 'external';
$rate = $rate_limit ?? 120;
$adaptiveEnabled = $adaptive_enabled ?? true;
$maxRequestKb = $max_request_kb ?? 512;
$challenge = $challenge ?? 'none';
$blockMinutes = $block_minutes ?? 15;
$notifyUsers = $notify_users ?? false;
$notifyAdmins = $notify_admins ?? true;
$helpLink = $help_link ?? '';
$retentionDays = $retention_days ?? 30;
$recoveryUntil = $recovery_until ?? '';
$devstoreDetected = $devstore_detected ?? false;
$rules = $rules ?? [];
$suggestions = $suggestions ?? [];
$events = $events ?? [];
$traffic = $traffic ?? [];
$scans = $scans ?? [];
$timeline = $timeline ?? [];
$scanId = $scan_id ?? 0;
$scanFindings = $scan_findings ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_FREEGATE'); ?></h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'overview' ? 'active' : ''; ?>" href="/admin/freegate?tab=overview"><?php echo Lang::get('FFCMS_OVERVIEW'); ?></a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'protection' ? 'active' : ''; ?>" href="/admin/freegate?tab=protection"><?php echo Lang::get('FFCMS_PROTECTION'); ?></a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'rules' ? 'active' : ''; ?>" href="/admin/freegate?tab=rules"><?php echo Lang::get('FFCMS_RULES'); ?></a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'scan' ? 'active' : ''; ?>" href="/admin/freegate?tab=scan"><?php echo Lang::get('FFCMS_SCAN'); ?></a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'traffic' ? 'active' : ''; ?>" href="/admin/freegate?tab=traffic"><?php echo Lang::get('FFCMS_TRAFFIC'); ?></a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'tools' ? 'active' : ''; ?>" href="/admin/freegate?tab=tools"><?php echo Lang::get('FFCMS_TOOLS'); ?></a></li>
    </ul>

    <?php if ($tab === 'overview') : ?>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_STATUS'); ?></h2>
                        <p class="mb-1"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_MODE'); ?>: <?php echo Template::escape(strtoupper($mode)); ?></p>
                        <p class="mb-1"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_ADAPTIVE'); ?>: <?php echo $adaptiveEnabled ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED'); ?></p>
                        <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_RATE'); ?>: <?php echo (int)$rate; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_ACTIVITY'); ?></h2>
                        <p class="mb-1"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_EVENTS'); ?>: <?php echo count($events); ?></p>
                        <p class="mb-1"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_TRAFFIC'); ?>: <?php echo count($traffic); ?></p>
                        <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_SCANS'); ?>: <?php echo count($scans); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_DEVSTORE'); ?></h2>
                        <p class="mb-0"><?php echo $devstoreDetected ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="zulu-panel">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_TIMELINE'); ?></h2>
            <?php if (!$timeline) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_DASHBOARD_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                                <th><?php echo Lang::get('FFCMS_TITLE'); ?></th>
                                <th><?php echo Lang::get('FFCMS_DETAILS'); ?></th>
                                <th><?php echo Lang::get('FFCMS_SEVERITY'); ?></th>
                                <th><?php echo Lang::get('FFCMS_DATE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timeline as $row) : ?>
                                <tr>
                                    <td><?php echo Template::escape($row['event_type'] ?? ''); ?></td>
                                    <td><?php echo Template::escape(Lang::get($row['title'] ?? '')); ?></td>
                                    <td><?php echo Template::escape($row['details'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($row['severity'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($row['created_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'protection') : ?>
        <div class="zulu-toolbar-row">
            <div class="btn-group">
                <button class="btn btn-primary" form="freegate-protection" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                    <?php echo Lang::get('FFCMS_SAVE'); ?>
                </button>
            </div>
        </div>
        <form id="freegate-protection" method="post" class="zulu-panel">
            <?php echo Csrf::input(); ?>
            <input type="hidden" name="action" value="save_protection">
            <div class="mb-3">
                <label class="form-label" for="mode"><?php echo Lang::get('FFCMS_MODE'); ?></label>
                <select class="form-select" id="mode" name="mode">
                    <option value="external" <?php echo $mode === 'external' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MODE_EXTERNAL'); ?></option>
                    <?php if ($devstoreDetected) : ?>
                        <option value="home" <?php echo $mode === 'home' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MODE_HOME'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="rate_limit_per_min"><?php echo Lang::get('FFCMS_RATE_LIMIT'); ?></label>
                <input class="form-control" type="number" id="rate_limit_per_min" name="rate_limit_per_min" value="<?php echo (int)$rate; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="adaptive_enabled"><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_ADAPTIVE'); ?></label>
                <select class="form-select" id="adaptive_enabled" name="adaptive_enabled">
                    <option value="1" <?php echo $adaptiveEnabled ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ENABLED'); ?></option>
                    <option value="0" <?php echo !$adaptiveEnabled ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_DISABLED'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="max_request_kb"><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_MAX_REQUEST'); ?></label>
                <input class="form-control" type="number" id="max_request_kb" name="max_request_kb" value="<?php echo (int)$maxRequestKb; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="challenge"><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_CHALLENGE'); ?></label>
                <select class="form-select" id="challenge" name="challenge">
                    <option value="none" <?php echo $challenge === 'none' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_DISABLED'); ?></option>
                    <option value="delay" <?php echo $challenge === 'delay' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_CHALLENGE_DELAY'); ?></option>
                    <option value="js" <?php echo $challenge === 'js' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_CHALLENGE_JS'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="block_minutes"><?php echo Lang::get('FFCMS.FREEGATE_PROTECTION_BLOCK_MIN'); ?></label>
                <input class="form-control" type="number" id="block_minutes" name="block_minutes" value="<?php echo (int)$blockMinutes; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="notify_users"><?php echo Lang::get('FFCMS.FREEGATE_ALERTS_USERS'); ?></label>
                <select class="form-select" id="notify_users" name="notify_users">
                    <option value="1" <?php echo $notifyUsers ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ENABLED'); ?></option>
                    <option value="0" <?php echo !$notifyUsers ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_DISABLED'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="notify_admins"><?php echo Lang::get('FFCMS.FREEGATE_ALERTS_ADMINS'); ?></label>
                <select class="form-select" id="notify_admins" name="notify_admins">
                    <option value="1" <?php echo $notifyAdmins ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ENABLED'); ?></option>
                    <option value="0" <?php echo !$notifyAdmins ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_DISABLED'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="help_link"><?php echo Lang::get('FFCMS.FREEGATE_ALERTS_HELP'); ?></label>
                <input class="form-control" type="text" id="help_link" name="help_link" value="<?php echo Template::escape($helpLink); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="retention_days"><?php echo Lang::get('FFCMS.FREEGATE_RETENTION'); ?></label>
                <input class="form-control" type="number" id="retention_days" name="retention_days" value="<?php echo (int)$retentionDays; ?>">
            </div>
        </form>
    <?php endif; ?>

    <?php if ($tab === 'rules') : ?>
        <div class="zulu-panel mb-3">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_RULES_ADD'); ?></h2>
            <form method="post" class="row g-3">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="action" value="rule_create">
                <div class="col-md-4">
                    <label class="form-label" for="rule_key"><?php echo Lang::get('FFCMS_KEY'); ?></label>
                    <input class="form-control" type="text" id="rule_key" name="rule_key" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="pattern"><?php echo Lang::get('FFCMS_PATTERN'); ?></label>
                    <input class="form-control" type="text" id="pattern" name="pattern" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="rule_action"><?php echo Lang::get('FFCMS_ACTION'); ?></label>
                    <select class="form-select" id="rule_action" name="rule_action">
                        <option value="allow"><?php echo Lang::get('FFCMS_ALLOW'); ?></option>
                        <option value="block"><?php echo Lang::get('FFCMS_BLOCK'); ?></option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                        <?php echo Lang::get('FFCMS_CREATE'); ?>
                    </button>
                </div>
            </form>
        </div>
        <div class="zulu-panel mb-3">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_RULES_LIST'); ?></h2>
            <?php if (!$rules) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_RULES_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_KEY'); ?></th>
                                <th><?php echo Lang::get('FFCMS_ACTION'); ?></th>
                                <th><?php echo Lang::get('FFCMS_PATTERN'); ?></th>
                                <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                                <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rules as $rule) : ?>
                                <tr>
                                    <td><?php echo Template::escape($rule['rule_key'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($rule['action'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($rule['pattern'] ?? ''); ?></td>
                                    <td><?php echo (int)($rule['is_enabled'] ?? 0) === 1 ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED'); ?></td>
                                    <td>
                                        <div class="ff-action-group">
                                            <form method="post" class="d-inline">
                                                <?php echo Csrf::input(); ?>
                                                <input type="hidden" name="action" value="rule_toggle">
                                                <input type="hidden" name="rule_id" value="<?php echo (int)($rule['id'] ?? 0); ?>">
                                                <input type="hidden" name="is_enabled" value="<?php echo (int)($rule['is_enabled'] ?? 0) === 1 ? 0 : 1; ?>">
                                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape((int)($rule['is_enabled'] ?? 0) === 1 ? Lang::get('FFCMS_DISABLE') : Lang::get('FFCMS_ENABLE')); ?>">
                                                    <span aria-hidden="true"><i class="fa-solid <?php echo (int)($rule['is_enabled'] ?? 0) === 1 ? 'fa-circle-xmark' : 'fa-circle-check'; ?>"></i></span>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <?php echo Csrf::input(); ?>
                                                <input type="hidden" name="action" value="rule_delete">
                                                <input type="hidden" name="rule_id" value="<?php echo (int)($rule['id'] ?? 0); ?>">
                                                <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="zulu-panel">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_RULES_SUGGESTIONS'); ?></h2>
            <?php if (!$suggestions) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_RULES_SUGGESTIONS_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_KEY'); ?></th>
                                <th><?php echo Lang::get('FFCMS_ACTION'); ?></th>
                                <th><?php echo Lang::get('FFCMS_PATTERN'); ?></th>
                                <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                                <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suggestions as $suggestion) : ?>
                                <tr>
                                    <td><?php echo Template::escape($suggestion['rule_key'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($suggestion['action'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($suggestion['pattern'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($suggestion['status'] ?? ''); ?></td>
                                    <td>
                                        <?php if (($suggestion['status'] ?? '') === 'pending') : ?>
                                            <div class="ff-action-group">
                                                <form method="post" class="d-inline">
                                                    <?php echo Csrf::input(); ?>
                                                    <input type="hidden" name="action" value="suggestion_approve">
                                                    <input type="hidden" name="suggestion_id" value="<?php echo (int)($suggestion['id'] ?? 0); ?>">
                                                    <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_APPROVE')); ?>">
                                                        <span aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <?php echo Csrf::input(); ?>
                                                    <input type="hidden" name="action" value="suggestion_reject">
                                                    <input type="hidden" name="suggestion_id" value="<?php echo (int)($suggestion['id'] ?? 0); ?>">
                                                    <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_REJECT')); ?>">
                                                        <span aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'scan') : ?>
        <div class="zulu-panel mb-3">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_SCAN_ACTIONS'); ?></h2>
            <div class="d-flex flex-wrap gap-2">
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="scan_run">
                    <input type="hidden" name="scan_type" value="quick">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-bolt"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_SCAN_QUICK'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="scan_run">
                    <input type="hidden" name="scan_type" value="full">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_SCAN_FULL'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="scan_run">
                    <input type="hidden" name="scan_type" value="integrity">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-shield"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_SCAN_INTEGRITY'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="scan_run">
                    <input type="hidden" name="scan_type" value="risk">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_SCAN_RISK'); ?>
                    </button>
                </form>
            </div>
        </div>
        <div class="zulu-panel mb-3">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_SCAN_HISTORY'); ?></h2>
            <?php if (!$scans) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_SCAN_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                                <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                                <th><?php echo Lang::get('FFCMS_SUMMARY'); ?></th>
                                <th><?php echo Lang::get('FFCMS_DATE'); ?></th>
                                <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scans as $scan) : ?>
                                <tr>
                                    <td><?php echo Template::escape($scan['scan_type'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($scan['status'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($scan['summary'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($scan['started_at'] ?? ''); ?></td>
                                    <td>
                                        <a class="ff-action-icon" href="/admin/freegate?tab=scan&scan_id=<?php echo (int)($scan['id'] ?? 0); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_VIEW')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($scanId > 0) : ?>
            <div class="zulu-panel">
                <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_SCAN_FINDINGS'); ?></h2>
                <?php if (!$scanFindings) : ?>
                    <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_SCAN_FINDINGS_EMPTY'); ?></p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th><?php echo Lang::get('FFCMS_SEVERITY'); ?></th>
                                    <th><?php echo Lang::get('FFCMS_MESSAGE'); ?></th>
                                    <th><?php echo Lang::get('FFCMS_PATH'); ?></th>
                                    <th><?php echo Lang::get('FFCMS_DATE'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scanFindings as $finding) : ?>
                                    <tr>
                                        <td><?php echo Template::escape($finding['severity'] ?? ''); ?></td>
                                        <td><?php echo Template::escape($finding['message'] ?? ''); ?></td>
                                        <td><?php echo Template::escape($finding['path'] ?? ''); ?></td>
                                        <td><?php echo Template::escape($finding['created_at'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tab === 'traffic') : ?>
        <div class="zulu-panel mb-3">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_TRAFFIC_EVENTS'); ?></h2>
            <?php if (!$events) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_TRAFFIC_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_ACTION'); ?></th>
                                <th><?php echo Lang::get('FFCMS_REASON'); ?></th>
                                <th><?php echo Lang::get('FFCMS_PATH'); ?></th>
                                <th><?php echo Lang::get('FFCMS_IP'); ?></th>
                                <th><?php echo Lang::get('FFCMS_DATE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event) : ?>
                                <tr>
                                    <td><?php echo Template::escape($event['action'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($event['reason_code'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($event['path'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($event['ip'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($event['created_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="zulu-panel">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_TRAFFIC_RECENT'); ?></h2>
            <?php if (!$traffic) : ?>
                <p class="mb-0"><?php echo Lang::get('FFCMS.FREEGATE_TRAFFIC_EMPTY'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><?php echo Lang::get('FFCMS_IP'); ?></th>
                                <th><?php echo Lang::get('FFCMS_DATE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traffic as $row) : ?>
                                <tr>
                                    <td><?php echo Template::escape($row['ip'] ?? ''); ?></td>
                                    <td><?php echo Template::escape($row['created_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'tools') : ?>
        <div class="zulu-panel">
            <h2 class="h5"><?php echo Lang::get('FFCMS.FREEGATE_TOOLS_TITLE'); ?></h2>
            <div class="d-flex flex-wrap gap-2">
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="tools_clear_blocks">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_TOOLS_CLEAR_BLOCKS'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="tools_purge_events">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_TOOLS_PURGE_EVENTS'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo Csrf::input(); ?>
                    <input type="hidden" name="action" value="tools_purge_traffic">
                    <button class="btn btn-outline-secondary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                        <?php echo Lang::get('FFCMS.FREEGATE_TOOLS_PURGE_TRAFFIC'); ?>
                    </button>
                </form>
            </div>
            <div class="mt-3">
                <h3 class="h6"><?php echo Lang::get('FFCMS.FREEGATE_TOOLS_RECOVERY'); ?></h3>
                <?php if ($recoveryUntil !== '') : ?>
                    <p class="mb-2"><?php echo Lang::get('FFCMS.FREEGATE_TOOLS_RECOVERY_ACTIVE'); ?>: <?php echo Template::escape($recoveryUntil); ?></p>
                    <form method="post">
                        <?php echo Csrf::input(); ?>
                        <input type="hidden" name="action" value="tools_recovery_disable">
                        <button class="btn btn-outline-danger" type="submit">
                            <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                            <?php echo Lang::get('FFCMS.FREEGATE_TOOLS_RECOVERY_DISABLE'); ?>
                        </button>
                    </form>
                <?php else : ?>
                    <form method="post" class="row g-2 align-items-end">
                        <?php echo Csrf::input(); ?>
                        <input type="hidden" name="action" value="tools_recovery_enable">
                        <div class="col-md-4">
                            <label class="form-label" for="recovery_minutes"><?php echo Lang::get('FFCMS.FREEGATE_TOOLS_RECOVERY_MINUTES'); ?></label>
                            <input class="form-control" type="number" id="recovery_minutes" name="recovery_minutes" value="15">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-secondary" type="submit">
                                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-shield"></i></span>
                                <?php echo Lang::get('FFCMS.FREEGATE_TOOLS_RECOVERY_ENABLE'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
