<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$documents = $documents ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_DOCUMENTS'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <form method="post" action="/admin/documents/upload" enctype="multipart/form-data" class="d-inline">
                <?php echo Csrf::input(); ?>
                <input type="file" name="document" class="form-control d-inline-block w-auto">
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-upload"></i></span>
                    <?php echo Lang::get('FFCMS_UPLOAD'); ?>
                </button>
            </form>
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
                    <th><?php echo Lang::get('FFCMS_USER'); ?></th>
                    <th><?php echo Lang::get('FFCMS_FILENAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_SIZE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPLOADED'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc) : ?>
                    <tr>
                        <td><?php echo Template::escape($doc['display_name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($doc['owner_username'] ?? (string)($doc['owner_user_id'] ?? '')); ?></td>
                        <td><?php echo Template::escape($doc['stored_name'] ?? ''); ?></td>
                        <td><?php echo Template::escape((string)($doc['file_size'] ?? '')); ?></td>
                        <td><?php echo Template::escape($doc['mime_type'] ?? ''); ?></td>
                        <td><?php echo Template::escape($doc['uploaded_at'] ?? ''); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/documents/edit/<?php echo (int)$doc['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/documents/download/<?php echo (int)$doc['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DOWNLOAD')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/documents/delete/<?php echo (int)$doc['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
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
