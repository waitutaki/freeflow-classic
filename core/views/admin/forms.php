<?php
use Core\Lang;
use Core\Template;

$forms = $forms ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4">Forms</h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/forms/new">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                New Form
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
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form) : ?>
                    <tr>
                        <td><?php echo Template::escape($form['title'] ?? ''); ?></td>
                        <td><?php echo Template::escape($form['slug'] ?? ''); ?></td>
                        <td><?php echo Template::escape($form['modified_at'] ?? $form['created_at'] ?? ''); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/forms/<?php echo (int)$form['id']; ?>/edit" aria-label="Edit">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/forms/<?php echo (int)$form['id']; ?>/submissions" aria-label="Submissions">
                                    <span aria-hidden="true"><i class="fa-solid fa-list"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/forms/<?php echo (int)$form['id']; ?>/delete" aria-label="Delete">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>