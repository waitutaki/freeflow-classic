<?php
namespace Core;

class Freewrite
{
    private static array $allowedBlocks = [
        'richtext',
        'heading',
        'paragraph',
        'lead',
        'small',
        'quote',
        'pullquote',
        'code',
        'preformatted',
        'divider',
        'spacer',
        'list_bulleted',
        'list_numbered',
        'list_checklist',
        'list_definition',
        'key_value',
        'grid',
        'section',
        'group',
        'container',
        'card',
        'tabs',
        'accordion',
        'callout',
        'shortcode',
        'image',
        'figure',
        'gallery',
        'icon',
        'table',
        'button',
        'file',
        'link_card',
        'embed',
        'html',
        'mailcontent',
    ];

    public static function render(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return ['html' => '', 'text' => ''];
        }

        $dom = new \DOMDocument();
        $prev = libxml_disable_entity_loader(true);
        $loaded = @$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_disable_entity_loader($prev);
        if (!$loaded) {
            $safe = Template::escape($xml);
            return ['html' => '<p>' . $safe . '</p>', 'text' => strip_tags($safe)];
        }

        $root = $dom->documentElement;
        if (!$root) {
            return ['html' => '', 'text' => ''];
        }

        $htmlParts = [];
        $textParts = [];
        $index = 0;
        foreach ($root->childNodes as $node) {
            if (!$node instanceof \DOMElement || $node->tagName !== 'block') {
                continue;
            }
            $htmlParts[] = self::renderBlock($node, $textParts, 0, $index);
        }

        return [
            'html' => implode('', array_filter($htmlParts)),
            'text' => implode("\n\n", array_filter($textParts)),
        ];
    }

    public static function sanitizeXml(string $xml, bool $canHtml, bool $canEmbed): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return ['ok' => true, 'xml' => '<freewrite version="1"></freewrite>'];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_disable_entity_loader(true);
        $loaded = @$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_disable_entity_loader($prev);
        if (!$loaded) {
            return ['ok' => false, 'error' => 'invalid_xml'];
        }

        $root = $dom->documentElement;
        if (!$root || $root->tagName !== 'freewrite') {
            return ['ok' => false, 'error' => 'invalid_root'];
        }
        $root->setAttribute('version', '1');

        $blocks = $root->getElementsByTagName('block');
        for ($i = $blocks->length - 1; $i >= 0; $i--) {
            $block = $blocks->item($i);
            if (!$block instanceof \DOMElement) {
                continue;
            }
            $type = $block->getAttribute('type');
            if (!in_array($type, self::$allowedBlocks, true)) {
                $block->parentNode?->removeChild($block);
                continue;
            }
            if ($type === 'html' && !$canHtml) {
                return ['ok' => false, 'error' => 'html_not_allowed'];
            }
            if ($type === 'embed' && !$canEmbed) {
                return ['ok' => false, 'error' => 'embed_not_allowed'];
            }

            if ($type === 'heading') {
                $level = (int)$block->getAttribute('level');
                if ($level < 1 || $level > 6) {
                    $block->setAttribute('level', '2');
                }
            }

            if ($type === 'richtext') {
                $clean = self::sanitizeRichtextHtml($block->textContent ?? '');
                self::replaceWithCdata($dom, $block, $clean);
            }

            if ($type === 'html') {
                $clean = self::sanitizeHtmlBlock($block->textContent ?? '');
                self::replaceWithCdata($dom, $block, $clean);
            }

            if (in_array($type, ['image', 'figure'], true)) {
                self::sanitizeUrlAttribute($block, 'src');
                self::sanitizeSimpleAttribute($block, 'alt');
                self::sanitizeSimpleAttribute($block, 'caption');
                self::sanitizeSimpleAttribute($block, 'align');
                self::sanitizeSimpleAttribute($block, 'size');
                self::sanitizeSimpleAttribute($block, 'width');
                self::sanitizeSimpleAttribute($block, 'height');
            }

            if ($type === 'gallery') {
                foreach ($block->getElementsByTagName('image') as $image) {
                    if (!$image instanceof \DOMElement) {
                        continue;
                    }
                    self::sanitizeUrlAttribute($image, 'src');
                    self::sanitizeSimpleAttribute($image, 'alt');
                }
            }

            if (in_array($type, ['button', 'file', 'link_card', 'embed'], true)) {
                self::sanitizeUrlAttribute($block, 'url');
            }

            if ($type === 'icon') {
                $style = $block->getAttribute('style');
                if (!in_array($style, ['solid', 'regular', 'brands'], true)) {
                    $block->setAttribute('style', 'solid');
                }
                $name = $block->getAttribute('name');
                if ($name !== '' && !preg_match('/^[a-z0-9-]+$/', $name)) {
                    $block->setAttribute('name', '');
                }
            }
        }

        $cleanXml = $dom->saveXML($root);
        return ['ok' => true, 'xml' => $cleanXml ?: '<freewrite version="1"></freewrite>'];
    }

    public static function sanitizeHtmlFragment(string $html): string
    {
        return self::sanitizeHtmlBlock($html);
    }

    private static function renderBlock(\DOMElement $node, array &$textParts, int $depth, int &$index): string
    {
        $type = $node->getAttribute('type');
        if (!in_array($type, self::$allowedBlocks, true)) {
            return '';
        }

        if ($type === 'richtext') {
            $html = self::sanitizeRichtextHtml($node->textContent ?? '');
            $textParts[] = strip_tags($html);
            return $html;
        }

        if ($type === 'heading') {
            $level = (int)$node->getAttribute('level');
            if ($level < 1 || $level > 6) {
                $level = 2;
            }
            $text = trim($node->textContent ?? '');
            $textParts[] = $text;
            return '<h' . $level . '>' . Template::escape($text) . '</h' . $level . '>';
        }

        if (in_array($type, ['paragraph', 'lead', 'small', 'quote', 'pullquote', 'code', 'preformatted', 'callout', 'shortcode'], true)) {
            $text = trim($node->textContent ?? '');
            $textParts[] = $text;
            if ($type === 'lead') {
                return '<p class="lead">' . Template::escape($text) . '</p>';
            }
            if ($type === 'small') {
                return '<p class="small">' . Template::escape($text) . '</p>';
            }
            if ($type === 'quote') {
                return '<blockquote class="blockquote">' . Template::escape($text) . '</blockquote>';
            }
            if ($type === 'pullquote') {
                return '<blockquote class="ff-freewrite-pullquote">' . Template::escape($text) . '</blockquote>';
            }
            if ($type === 'code') {
                return '<pre><code>' . Template::escape($text) . '</code></pre>';
            }
            if ($type === 'preformatted') {
                return '<pre>' . Template::escape($text) . '</pre>';
            }
            if ($type === 'callout') {
                return '<div class="alert alert-info">' . Template::escape($text) . '</div>';
            }
            if ($type === 'shortcode') {
                $rendered = self::processShortcode($text);
                if ($rendered !== null) {
                    return $rendered;
                }
                return '<pre class="ff-freewrite-shortcode">' . Template::escape($text) . '</pre>';
            }
            return '<p>' . Template::escape($text) . '</p>';
        }

        if ($type === 'divider') {
            return '<hr class="ff-freewrite-divider">';
        }

        if ($type === 'spacer') {
            return '<div class="ff-freewrite-spacer" aria-hidden="true"></div>';
        }

        if ($type === 'mailcontent') {
            $textParts[] = '[mailcontent]';
            return '[mailcontent]';
        }

        if ($type === 'list_bulleted' || $type === 'list_numbered') {
            $items = [];
            foreach ($node->getElementsByTagName('item') as $item) {
                $items[] = trim($item->textContent ?? '');
            }
            $tag = $type === 'list_numbered' ? 'ol' : 'ul';
            $html = '<' . $tag . '>';
            foreach ($items as $item) {
                if ($item === '') {
                    continue;
                }
                $html .= '<li>' . Template::escape($item) . '</li>';
                $textParts[] = $item;
            }
            $html .= '</' . $tag . '>';
            return $html;
        }

        if ($type === 'list_checklist') {
            $html = '<ul class="ff-freewrite-checklist">';
            foreach ($node->getElementsByTagName('item') as $item) {
                $text = trim($item->textContent ?? '');
                if ($text === '') {
                    continue;
                }
                $checked = $item->getAttribute('checked') === '1';
                $stateText = $checked ? Lang::get('FFCMS_CHECKED') : Lang::get('FFCMS_UNCHECKED');
                $icon = $checked ? 'fa-circle-check' : 'fa-circle';
                $html .= '<li class="ff-freewrite-checklist-item"><span class="ff-freewrite-check-icon" aria-hidden="true"><i class="fa-solid ' . $icon . '"></i></span><span class="visually-hidden">' . Template::escape($stateText) . '</span>' . Template::escape($text) . '</li>';
                $textParts[] = $text;
            }
            $html .= '</ul>';
            return $html;
        }

        if ($type === 'list_definition') {
            $html = '<dl>';
            foreach ($node->getElementsByTagName('pair') as $pair) {
                $key = trim($pair->getAttribute('key'));
                $value = trim($pair->getAttribute('value'));
                if ($key === '' && $value === '') {
                    continue;
                }
                $html .= '<dt>' . Template::escape($key) . '</dt><dd>' . Template::escape($value) . '</dd>';
                $textParts[] = trim($key . ' ' . $value);
            }
            $html .= '</dl>';
            return $html;
        }

        if ($type === 'key_value') {
            $html = '<div class="ff-freewrite-kv">';
            foreach ($node->getElementsByTagName('pair') as $pair) {
                $key = trim($pair->getAttribute('key'));
                $value = trim($pair->getAttribute('value'));
                if ($key === '' && $value === '') {
                    continue;
                }
                $html .= '<div class="ff-freewrite-kv-row"><div><strong>' . Template::escape($key) . '</strong></div><div>' . Template::escape($value) . '</div></div>';
                $textParts[] = trim($key . ' ' . $value);
            }
            $html .= '</div>';
            return $html;
        }

        if ($type === 'grid') {
            $columns = (int)$node->getAttribute('columns');
            if ($columns < 1 || $columns > 6) {
                $columns = 2;
            }
            $html = '<div class="row g-3">';
            foreach ($node->childNodes as $child) {
                if (!$child instanceof \DOMElement || $child->tagName !== 'column') {
                    continue;
                }
                $width = (int)$child->getAttribute('width');
                if ($width <= 0 || $width > 12) {
                    $width = (int)floor(12 / $columns);
                }
                $html .= '<div class="col-12 col-lg-' . $width . '">';
                $html .= self::renderChildBlocks($child, $textParts, $depth + 1, $index);
                $html .= '</div>';
            }
            $html .= '</div>';
            return $html;
        }

        if (in_array($type, ['section', 'group', 'container', 'card'], true)) {
            $body = self::renderChildBlocks($node, $textParts, $depth + 1, $index);
            if ($type === 'section') {
                return '<section class="ff-freewrite-section">' . $body . '</section>';
            }
            if ($type === 'group') {
                return '<div class="ff-freewrite-group">' . $body . '</div>';
            }
            if ($type === 'container') {
                return '<div class="container ff-freewrite-container">' . $body . '</div>';
            }
            return '<div class="card"><div class="card-body">' . $body . '</div></div>';
        }

        if ($type === 'tabs') {
            $index++;
            $tabId = 'ff-tabs-' . $index;
            $nav = '<ul class="nav nav-tabs" role="tablist">';
            $panes = '<div class="tab-content">';
            $tabIndex = 0;
            foreach ($node->childNodes as $tab) {
                if (!$tab instanceof \DOMElement || $tab->tagName !== 'tab') {
                    continue;
                }
                $tabIndex++;
                $title = trim($tab->getAttribute('title'));
                $tabKey = $tabId . '-' . $tabIndex;
                $active = $tabIndex === 1 ? ' active' : '';
                $selected = $tabIndex === 1 ? 'true' : 'false';
                $nav .= '<li class="nav-item" role="presentation"><button class="nav-link' . $active . '" id="' . $tabKey . '-tab" data-bs-toggle="tab" data-bs-target="#' . $tabKey . '" type="button" role="tab" aria-controls="' . $tabKey . '" aria-selected="' . $selected . '">' . Template::escape($title !== '' ? $title : Lang::get('FFCMS_TAB')) . '</button></li>';
                $panes .= '<div class="tab-pane fade show' . ($tabIndex === 1 ? ' active' : '') . '" id="' . $tabKey . '" role="tabpanel" aria-labelledby="' . $tabKey . '-tab">' . self::renderChildBlocks($tab, $textParts, $depth + 1, $index) . '</div>';
            }
            $nav .= '</ul>';
            $panes .= '</div>';
            return $nav . $panes;
        }

        if ($type === 'accordion') {
            $index++;
            $accId = 'ff-acc-' . $index;
            $html = '<div class="accordion" id="' . $accId . '">';
            $itemIndex = 0;
            foreach ($node->childNodes as $item) {
                if (!$item instanceof \DOMElement || $item->tagName !== 'item') {
                    continue;
                }
                $itemIndex++;
                $title = trim($item->getAttribute('title'));
                $itemKey = $accId . '-' . $itemIndex;
                $collapsed = $itemIndex === 1 ? '' : ' collapsed';
                $show = $itemIndex === 1 ? ' show' : '';
                $html .= '<div class="accordion-item">';
                $html .= '<h2 class="accordion-header" id="' . $itemKey . '-heading">';
                $html .= '<button class="accordion-button' . $collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . $itemKey . '" aria-expanded="' . ($itemIndex === 1 ? 'true' : 'false') . '" aria-controls="' . $itemKey . '">' . Template::escape($title !== '' ? $title : Lang::get('FFCMS_PANEL')) . '</button>';
                $html .= '</h2>';
                $html .= '<div id="' . $itemKey . '" class="accordion-collapse collapse' . $show . '" aria-labelledby="' . $itemKey . '-heading" data-bs-parent="#' . $accId . '">';
                $html .= '<div class="accordion-body">' . self::renderChildBlocks($item, $textParts, $depth + 1, $index) . '</div>';
                $html .= '</div></div>';
            }
            $html .= '</div>';
            return $html;
        }

        if ($type === 'image' || $type === 'figure') {
            $src = $node->getAttribute('src');
            if (!Url::isSafe($src)) {
                return '';
            }
            $alt = trim($node->getAttribute('alt'));
            $caption = trim($node->getAttribute('caption'));
            $align = $node->getAttribute('align');
            $size = $node->getAttribute('size');
            $class = 'img-fluid';
            if ($size === 'small') {
                $class .= ' w-25';
            } elseif ($size === 'large') {
                $class .= ' w-75';
            } elseif ($size === 'full') {
                $class .= ' w-100';
            } else {
                $class .= ' w-50';
            }
            if ($align === 'right') {
                $class .= ' float-end ms-3';
            } elseif ($align === 'center') {
                $class .= ' d-block mx-auto';
            } else {
                $class .= ' float-start me-3';
            }
            $img = '<img class="' . Template::escape($class) . '" src="' . Template::escape($src) . '" alt="' . Template::escape($alt) . '">';
            if ($type === 'figure') {
                $fig = '<figure class="ff-freewrite-figure">' . $img;
                if ($caption !== '') {
                    $fig .= '<figcaption class="figure-caption">' . Template::escape($caption) . '</figcaption>';
                }
                $fig .= '</figure>';
                $textParts[] = $alt !== '' ? $alt : $caption;
                return $fig;
            }
            $textParts[] = $alt;
            return $img;
        }

        if ($type === 'gallery') {
            $html = '<div class="row g-3">';
            foreach ($node->getElementsByTagName('image') as $image) {
                if (!$image instanceof \DOMElement) {
                    continue;
                }
                $src = $image->getAttribute('src');
                if (!Url::isSafe($src)) {
                    continue;
                }
                $alt = trim($image->getAttribute('alt'));
                $html .= '<div class="col-6 col-md-4"><img class="img-fluid" src="' . Template::escape($src) . '" alt="' . Template::escape($alt) . '"></div>';
                $textParts[] = $alt;
            }
            $html .= '</div>';
            return $html;
        }

        if ($type === 'icon') {
            $style = $node->getAttribute('style');
            $name = $node->getAttribute('name');
            if (!in_array($style, ['solid', 'regular', 'brands'], true)) {
                $style = 'solid';
            }
            if ($name === '' || !preg_match('/^[a-z0-9-]+$/', $name)) {
                return '';
            }
            $class = 'fa-' . $style . ' fa-' . $name;
            return '<i class="' . Template::escape($class) . '" aria-hidden="true"></i>';
        }

        if ($type === 'table') {
            $html = '<table class="table table-bordered"><tbody>';
            foreach ($node->getElementsByTagName('row') as $row) {
                $html .= '<tr>';
                foreach ($row->getElementsByTagName('cell') as $cell) {
                    $value = trim($cell->textContent ?? '');
                    $html .= '<td>' . Template::escape($value) . '</td>';
                    $textParts[] = $value;
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            return $html;
        }

        if ($type === 'button') {
            $label = trim($node->getAttribute('label'));
            $url = $node->getAttribute('url');
            if (!Url::isSafe($url)) {
                return '';
            }
            $style = $node->getAttribute('style');
            $btnClass = $style === 'secondary' ? 'btn btn-secondary' : ($style === 'outline' ? 'btn btn-outline-primary' : 'btn btn-primary');
            $textParts[] = $label;
            return '<a class="' . Template::escape($btnClass) . '" href="' . Template::escape($url) . '">' . Template::escape($label !== '' ? $label : Lang::get('FFCMS_LINK')) . '</a>';
        }

        if ($type === 'file') {
            $url = $node->getAttribute('url');
            $label = trim($node->getAttribute('label'));
            if (!Url::isSafe($url)) {
                return '';
            }
            $textParts[] = $label;
            $label = $label !== '' ? $label : Lang::get('FFCMS_DOWNLOAD');
            return '<a class="ff-freewrite-file" href="' . Template::escape($url) . '">' . Template::escape($label) . '</a>';
        }

        if ($type === 'link_card') {
            $title = trim($node->getAttribute('title'));
            $url = $node->getAttribute('url');
            $label = trim($node->getAttribute('label'));
            $desc = trim($node->getAttribute('description'));
            if (!Url::isSafe($url)) {
                return '';
            }
            $button = $label !== '' ? '<a class="btn btn-outline-primary btn-sm" href="' . Template::escape($url) . '">' . Template::escape($label) . '</a>' : '';
            $textParts[] = $title;
            return '<div class="card"><div class="card-body"><h5 class="card-title">' . Template::escape($title) . '</h5><p class="card-text">' . Template::escape($desc) . '</p>' . $button . '</div></div>';
        }

        if ($type === 'embed') {
            $url = $node->getAttribute('url');
            if (!Url::isSafe($url)) {
                return '';
            }
            return '<div class="ratio ratio-16x9"><iframe src="' . Template::escape($url) . '" title="' . Template::escape(Lang::get('FFCMS_EMBED')) . '" allowfullscreen></iframe></div>';
        }

        if ($type === 'html') {
            $clean = self::sanitizeHtmlBlock($node->textContent ?? '');
            return $clean;
        }

        return '';
    }

    private static function renderChildBlocks(\DOMElement $parent, array &$textParts, int $depth, int &$index): string
    {
        $html = '';
        foreach ($parent->childNodes as $node) {
            if (!$node instanceof \DOMElement || $node->tagName !== 'block') {
                continue;
            }
            $html .= self::renderBlock($node, $textParts, $depth, $index);
        }
        return $html;
    }

    private static function sanitizeUrlAttribute(\DOMElement $node, string $attr): void
    {
        $value = $node->getAttribute($attr);
        if ($value === '' || Url::isSafe($value)) {
            return;
        }
        $node->setAttribute($attr, '');
    }

    private static function sanitizeSimpleAttribute(\DOMElement $node, string $attr): void
    {
        $value = $node->getAttribute($attr);
        if ($value === '') {
            return;
        }
        $node->setAttribute($attr, preg_replace('/[^A-Za-z0-9\\s._-]/', '', $value));
    }

    private static function replaceWithCdata(\DOMDocument $dom, \DOMElement $node, string $value): void
    {
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }
        $node->appendChild($dom->createCDATASection($value));
    }

    private static function sanitizeRichtextHtml(string $html): string
    {
        $allowed = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'u' => [],
            's' => [],
            'a' => ['href', 'title', 'target', 'rel', 'class'],
            'code' => [],
            'kbd' => [],
            'sub' => [],
            'sup' => [],
            'span' => ['class'],
        ];
        return self::sanitizeHtml($html, $allowed);
    }

    private static function sanitizeHtmlBlock(string $html): string
    {
        $allowed = [
            'p' => ['class'],
            'br' => [],
            'strong' => [],
            'em' => [],
            'u' => [],
            's' => [],
            'a' => ['href', 'title', 'target', 'rel', 'class'],
            'code' => [],
            'kbd' => [],
            'sub' => [],
            'sup' => [],
            'span' => ['class'],
            'div' => ['class'],
            'ul' => ['class'],
            'ol' => ['class'],
            'li' => ['class'],
            'blockquote' => ['class'],
            'h1' => ['class'],
            'h2' => ['class'],
            'h3' => ['class'],
            'h4' => ['class'],
            'h5' => ['class'],
            'h6' => ['class'],
            'hr' => ['class'],
            'table' => ['class'],
            'thead' => ['class'],
            'tbody' => ['class'],
            'tr' => ['class'],
            'th' => ['class'],
            'td' => ['class'],
            'img' => ['src', 'alt', 'class', 'width', 'height'],
            'figure' => ['class'],
            'figcaption' => ['class'],
        ];
        return self::sanitizeHtml($html, $allowed);
    }

    private static function sanitizeHtml(string $html, array $allowed): string
    {
        if ($html === '') {
            return '';
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div>' . $html . '</div>';
        $prev = libxml_disable_entity_loader(true);
        @$dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_disable_entity_loader($prev);
        $container = $dom->getElementsByTagName('div')->item(0);
        if (!$container instanceof \DOMElement) {
            return '';
        }
        self::sanitizeNode($container, $allowed);
        $output = '';
        foreach ($container->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }
        return $output;
    }

    private static function sanitizeNode(\DOMNode $node, array $allowed): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);
            if (!isset($allowed[$tag])) {
                $node->parentNode?->replaceChild(new \DOMText($node->textContent ?? ''), $node);
                return;
            }
            if ($node instanceof \DOMElement) {
                foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                    $name = strtolower($attr->nodeName);
                    if ($name === 'style' || str_starts_with($name, 'on')) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }
                    if (!in_array($name, $allowed[$tag], true)) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }
                    if ($tag === 'a' && $name === 'href' && !Url::isSafe($attr->nodeValue)) {
                        $node->removeAttribute('href');
                    }
                    if ($tag === 'img' && $name === 'src' && !Url::isSafe($attr->nodeValue)) {
                        $node->removeAttribute('src');
                    }
                    if (in_array($tag, ['span', 'div', 'p', 'ul', 'ol', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption'], true) && $name === 'class') {
                        $node->setAttribute('class', preg_replace('/[^A-Za-z0-9\\s_-]/', '', $attr->nodeValue));
                    }
                }
            }
        }
        if ($node->hasChildNodes()) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                self::sanitizeNode($child, $allowed);
            }
        }
    }

    private static function processShortcode(string $text): ?string
    {
        if (preg_match('/^\[form\s+id="(\d+)"\]$/', $text, $matches)) {
            $formId = (int)$matches[1];
            return \Core\FormRenderer::renderForm($formId);
        }
        return null;
    }
}
