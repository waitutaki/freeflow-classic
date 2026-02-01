<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\ACL;
use Core\Auth;

$tab = $tab ?? 'roles';
$roles = $roles ?? [];
$permissions = $permissions ?? [];
$matrix = $matrix ?? [];
$superOnly = $super_only ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_ROLES'); ?></h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'roles' ? 'active' : ''; ?>" href="/admin/roles?tab=roles"><?php echo Lang::get('FFCMS_ROLES'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'permissions' ? 'active' : ''; ?>" href="/admin/roles?tab=permissions"><?php echo Lang::get('FFCMS_PERMISSIONS'); ?></a>
        </li>
    </ul>

    <?php if ($tab === 'roles') : ?>
        <div class="zulu-toolbar-row">
            <div class="btn-group">
                <a class="btn btn-primary" href="/admin/roles/new">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                    <?php echo Lang::get('FFCMS_NEW'); ?>
                </a>
                <a class="btn btn-outline-secondary" href="/admin/">
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
                        <th><?php echo Lang::get('FFCMS_SYSTEM'); ?></th>
                        <th><?php echo Lang::get('FFCMS_PARENT_ROLE'); ?></th>
                        <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role) : ?>
                        <tr>
                            <td><?php echo Template::escape($role['title'] ?? ''); ?></td>
                            <td><?php echo !empty($role['is_system']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                            <td><?php echo Template::escape((string)($role['parent_role_id'] ?? '')); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/roles/edit/<?php echo (int)$role['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <?php if (empty($role['is_system'])) : ?>
                                    <a class="ff-action-icon" href="/admin/roles/delete/<?php echo (int)$role['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
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

    <?php if ($tab === 'permissions') : ?>
        <div class="zulu-panel">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_PERMISSION'); ?></th>
                        <?php foreach ($roles as $role) : ?>
                            <th><?php echo Template::escape($role['title'] ?? ''); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm) : ?>
                        <tr>
                            <td><?php echo Template::escape($perm['title'] ?? $perm['perm_key'] ?? ''); ?></td>
                            <?php foreach ($roles as $role) : ?>
                                <?php
                                $roleId = (int)$role['id'];
                                $permKey = $perm['perm_key'] ?? '';
                                $explicit = !empty(($matrix[$roleId] ?? [])[$permKey]);
                                $inherited = false;
                                if (!$explicit && $role['parent_role_id']) {
                                $parentPerms = $matrix[(int)$role['parent_role_id']] ?? [];
                                $inherited = !empty($parentPerms[$permKey]);
                            }
                            $canOverride = Auth::isSuper();
                            $locked = !$canOverride && ($inherited || (in_array($permKey, $superOnly, true) && $roleId !== 998));
                            $currentState = ($explicit || $inherited) ? '1' : '0';
                            $stateText = $currentState === '1' ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
                            $stateIcon = $currentState === '1' ? 'fa-circle-check' : 'fa-circle-xmark';
                            $stateClass = $currentState === '1' ? 'text-success' : 'text-danger';
                            if ($locked) {
                                $stateClass = 'text-muted opacity-75';
                            }
                            $stateLabel = $stateText;
                            if ($locked) {
                                $stateLabel .= ' (' . Lang::get('FFCMS_LOCKED') . ')';
                            }
                            $actionText = $currentState === '1' ? Lang::get('FFCMS_REVOKE') : Lang::get('FFCMS_ALLOW');
                            $nextState = $currentState === '1' ? '0' : '1';
                                ?>
                                <td>
                                    <?php if ($locked) : ?>
                                        <span class="perm-state <?php echo $stateClass; ?>" aria-label="<?php echo Template::escape($stateLabel); ?>">
                                            <span aria-hidden="true"><i class="fa-solid <?php echo $stateIcon; ?>"></i></span>
                                            <span class="visually-hidden"><?php echo $stateText; ?></span>
                                        </span>
                                    <?php else : ?>
                                        <form method="post" action="/admin/roles/perm-toggle" class="d-inline js-perm-toggle" data-current-state="<?php echo $currentState; ?>" data-label-enabled="<?php echo Template::escape(Lang::get('FFCMS_ENABLED')); ?>" data-label-disabled="<?php echo Template::escape(Lang::get('FFCMS_DISABLED')); ?>">
                                            <?php echo Csrf::input(); ?>
                                            <input type="hidden" name="role_id" value="<?php echo $roleId; ?>">
                                            <input type="hidden" name="perm_key" value="<?php echo Template::escape($permKey); ?>">
                                            <input type="hidden" name="state" value="<?php echo $nextState; ?>">
                                            <button class="ff-action-icon perm-state <?php echo $stateClass; ?>" type="submit" data-label-allow="<?php echo Template::escape(Lang::get('FFCMS_ALLOW')); ?>" data-label-revoke="<?php echo Template::escape(Lang::get('FFCMS_REVOKE')); ?>" aria-label="<?php echo Template::escape($actionText); ?>">
                                                <span aria-hidden="true"><i class="fa-solid <?php echo $stateIcon; ?>"></i></span>
                                                <span class="visually-hidden"><?php echo $stateText; ?></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
