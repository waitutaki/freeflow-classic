<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$form = $form ?? [];
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-outline-secondary" href="/admin/forms">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                Close
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4">Delete Form</h1>

        <p>Are you sure you want to delete the form "<?php echo Template::escape($form['title'] ?? ''); ?>"?</p>

        <form method="post">
            <?php echo Csrf::input(); ?>
            <button class="btn btn-danger" type="submit">Delete</button>
            <a class="btn btn-secondary" href="/admin/forms">Cancel</a>
        </form>
    </div>
</div>