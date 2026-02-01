<?php
use Core\Lang;

$zones = [];
for ($i = 1; $i <= 6; $i++) {
    $zones['dashboard_' . $i] =
        '<div class="zulu-dashboard-card">' .
        '<h2 class="zulu-dashboard-title">' . Lang::get('FFCMS_DASHBOARD_ZONE') . ' ' . $i . '</h2>' .
        '<p>' . Lang::get('FFCMS_DASHBOARD_ZONE_DESC') . '</p>' .
        '</div>';
}

return $zones;
