<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$documents = $documents ?? [];
$csrfToken = Csrf::token();
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_DOCUMENT_PICKER'); ?></h1>
    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_FILENAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_SIZE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc) : ?>
                            <?php
                    $docId = (int)($doc['id'] ?? 0);
                    $downloadUrl = '/admin/documents/download/' . $docId;
                    $displayName = (string)($doc['display_name'] ?? '');
                    ?>
                    <tr>
                        <td><?php echo Template::escape($displayName); ?></td>
                        <td><?php echo Template::escape($doc['stored_name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($doc['mime_type'] ?? ''); ?></td>
                        <td><?php echo Template::escape((string)($doc['file_size'] ?? '')); ?></td>
                        <td>
                            <button class="ff-action-icon" type="button" data-document-action="insert" data-document-path="<?php echo Template::escape($downloadUrl); ?>" data-document-name="<?php echo Template::escape($displayName); ?>" data-document-id="<?php echo $docId; ?>" data-document-log-url="/admin/documents/insert-log" data-document-csrf="<?php echo Template::escape($csrfToken); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_INSERT')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
