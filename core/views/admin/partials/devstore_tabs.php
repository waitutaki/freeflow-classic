<?php
use Core\Lang;

$devstore_tab = $devstore_tab ?? 'dashboard';
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'dashboard' ? 'active' : ''; ?>" href="/admin/devstore"><?php echo Lang::get('DEVSTORE.DASHBOARD_TITLE'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'developers' ? 'active' : ''; ?>" href="/admin/devstore/developers"><?php echo Lang::get('DEVSTORE.DEVELOPERS_TITLE'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'packages' ? 'active' : ''; ?>" href="/admin/devstore/packages"><?php echo Lang::get('DEVSTORE.PACKAGES_TITLE'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'repository' ? 'active' : ''; ?>" href="/admin/devstore/repository"><?php echo Lang::get('DEVSTORE.REPOSITORY_TITLE'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'keys' ? 'active' : ''; ?>" href="/admin/devstore/keys"><?php echo Lang::get('DEVSTORE.KEYS_TITLE'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $devstore_tab === 'settings' ? 'active' : ''; ?>" href="/admin/devstore/settings"><?php echo Lang::get('DEVSTORE.SETTINGS_TITLE'); ?></a>
    </li>
</ul>
