<?php
use Core\Template;

$page = $page ?? [];
$contentHtml = $content_html ?? '';
?>
<div class="alpha-content">
    <h1 class="alpha-title"><?php echo Template::escape($page['title'] ?? ''); ?></h1>
    <div class="ff-content-body"><?php echo $contentHtml; ?></div>
</div>
