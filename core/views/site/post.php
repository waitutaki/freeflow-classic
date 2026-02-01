<?php
use Core\Template;

$post = $post ?? [];
$contentHtml = $content_html ?? '';
?>
<div class="alpha-content">
    <h1 class="alpha-title"><?php echo Template::escape($post['title'] ?? ''); ?></h1>
    <div class="ff-content-body"><?php echo $contentHtml; ?></div>
</div>
