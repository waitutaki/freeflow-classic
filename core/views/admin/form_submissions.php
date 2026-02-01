<?php
use Core\Lang;
use Core\Template;

$form = $form ?? [];
$submissions = $submissions ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4">Submissions for "<?php echo Template::escape($form['title'] ?? ''); ?>"</h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-outline-secondary" href="/admin/forms">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                Close
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Submitted At</th>
                    <th>IP</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission) : ?>
                    <tr>
                        <td><?php echo (int)$submission['id']; ?></td>
                        <td><?php echo Template::escape($submission['submitted_at'] ?? ''); ?></td>
                        <td><?php echo Template::escape($submission['ip_address'] ?? ''); ?></td>
                        <td><pre><?php echo htmlspecialchars($submission['submission_xml'] ?? ''); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>