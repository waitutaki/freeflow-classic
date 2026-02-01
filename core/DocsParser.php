<?php
namespace Core;

class DocsParser
{
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $html = '';
        $inCode = false;
        $codeLines = [];
        $paragraph = [];
        $listType = '';
        $listItems = [];

        foreach ($lines as $line) {
            $trimmed = rtrim($line, "\r\n");

            if (str_starts_with($trimmed, '```')) {
                if ($inCode) {
                    $html .= self::flushCode($codeLines);
                    $codeLines = [];
                    $inCode = false;
                } else {
                    $html .= self::flushParagraph($paragraph);
                    $paragraph = [];
                    $html .= self::flushList($listType, $listItems);
                    $listType = '';
                    $listItems = [];
                    $inCode = true;
                }
                continue;
            }

            if ($inCode) {
                $codeLines[] = $trimmed;
                continue;
            }

            if (trim($trimmed) === '') {
                $html .= self::flushParagraph($paragraph);
                $paragraph = [];
                $html .= self::flushList($listType, $listItems);
                $listType = '';
                $listItems = [];
                continue;
            }

            if (trim($trimmed) === '---') {
                $html .= self::flushParagraph($paragraph);
                $paragraph = [];
                $html .= self::flushList($listType, $listItems);
                $listType = '';
                $listItems = [];
                $html .= '<hr>';
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $matches)) {
                $html .= self::flushParagraph($paragraph);
                $paragraph = [];
                $html .= self::flushList($listType, $listItems);
                $listType = '';
                $listItems = [];
                $level = strlen($matches[1]);
                $content = self::parseInline($matches[2]);
                $html .= '<h' . $level . '>' . $content . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^\s*([-*])\s+(.+)$/', $trimmed, $matches)) {
                if ($listType === '' || $listType === 'ol') {
                    $html .= self::flushParagraph($paragraph);
                    $paragraph = [];
                    if ($listType === 'ol') {
                        $html .= self::flushList($listType, $listItems);
                        $listItems = [];
                    }
                    $listType = 'ul';
                }
                $listItems[] = self::parseInline($matches[2]);
                continue;
            }

            if (preg_match('/^\s*\d+\.\s+(.+)$/', $trimmed, $matches)) {
                if ($listType === '' || $listType === 'ul') {
                    $html .= self::flushParagraph($paragraph);
                    $paragraph = [];
                    if ($listType === 'ul') {
                        $html .= self::flushList($listType, $listItems);
                        $listItems = [];
                    }
                    $listType = 'ol';
                }
                $listItems[] = self::parseInline($matches[1]);
                continue;
            }

            $paragraph[] = $trimmed;
        }

        if ($inCode) {
            $html .= self::flushCode($codeLines);
        }
        $html .= self::flushParagraph($paragraph);
        $html .= self::flushList($listType, $listItems);

        return $html;
    }

    private static function flushParagraph(array $paragraph): string
    {
        if (!$paragraph) {
            return '';
        }
        $text = trim(implode(' ', $paragraph));
        if ($text === '') {
            return '';
        }
        return '<p>' . self::parseInline($text) . '</p>';
    }

    private static function flushList(string $type, array $items): string
    {
        if ($type === '' || !$items) {
            return '';
        }
        $html = '<' . $type . '>';
        foreach ($items as $item) {
            $html .= '<li>' . $item . '</li>';
        }
        $html .= '</' . $type . '>';
        return $html;
    }

    private static function flushCode(array $codeLines): string
    {
        $code = Template::escape(implode("\n", $codeLines));
        return '<pre><code>' . $code . '</code></pre>';
    }

    private static function parseInline(string $text): string
    {
        $segments = preg_split('/(`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($segments as $segment) {
            if (preg_match('/^`([^`]+)`$/', $segment, $matches)) {
                $out .= '<code>' . Template::escape($matches[1]) . '</code>';
            } else {
                $escaped = Template::escape($segment);
                $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
                $escaped = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped);
                $escaped = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function ($matches) {
                    $label = $matches[1];
                    $url = html_entity_decode($matches[2], ENT_QUOTES);
                    if (!self::isSafeUrl($url)) {
                        return $label . ' (' . Template::escape($url) . ')';
                    }
                    return '<a href="' . Template::escape($url) . '">' . $label . '</a>';
                }, $escaped);
                $out .= $escaped;
            }
        }
        return $out;
    }

    private static function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }
        $lower = strtolower($url);
        return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
    }
}
