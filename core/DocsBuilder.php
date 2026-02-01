<?php
namespace Core;

class DocsBuilder
{
    public static function buildAll(int $userId): array
    {
        $results = [];
        foreach (['user', 'developer', 'sysadmin'] as $section) {
            $results[$section] = self::buildSection($section, $userId);
        }
        self::updateAdminMenu();
        return $results;
    }

    public static function buildSection(string $section, int $userId): array
    {
        $section = strtolower($section);
        $sourceRoot = dirname(__DIR__) . '/docs/' . $section;
        $outputRoot = dirname(__DIR__) . '/docs-html/' . $section;

        $files = self::findMarkdown($sourceRoot);
        $links = [];
        foreach ($files as $file) {
            $relative = ltrim(str_replace($sourceRoot, '', $file), '/');
            $links[] = [
                'title' => self::titleFromPath($relative),
                'path' => '/' . trim('docs-html/' . $section . '/' . substr($relative, 0, -3) . '.html', '/'),
            ];
        }

        $success = true;
        foreach ($files as $file) {
            $relative = ltrim(str_replace($sourceRoot, '', $file), '/');
            $target = $outputRoot . '/' . substr($relative, 0, -3) . '.html';
            $title = self::titleFromPath($relative);

            $content = file_get_contents($file);
            if ($content === false) {
                $success = false;
                continue;
            }
            $body = DocsParser::toHtml($content);
            $html = self::wrapHtml($section, $title, $body, $links);

            $dir = dirname($target);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($target, $html);
        }

        $indexHtml = self::renderIndex($section, $links);
        if (!is_dir($outputRoot)) {
            mkdir($outputRoot, 0775, true);
        }
        file_put_contents($outputRoot . '/index.html', $indexHtml);

        $resultText = $success ? 'success' : 'fail';
        Settings::set('docs', $section . '_last_build', date('Y-m-d H:i:s'), 'string', $userId);
        Settings::set('docs', $section . '_last_result', $resultText, 'string', $userId);

        return [
            'count' => count($files),
            'status' => $resultText,
        ];
    }

    public static function sectionInfo(string $section): array
    {
        $section = strtolower($section);
        $sourceRoot = dirname(__DIR__) . '/docs/' . $section;
        $files = self::findMarkdown($sourceRoot);
        return [
            'count' => count($files),
            'last_build' => (string)Settings::get('docs', $section . '_last_build', ''),
            'last_result' => (string)Settings::get('docs', $section . '_last_result', ''),
        ];
    }

    private static function findMarkdown(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            $files[] = $file->getPathname();
        }
        sort($files);
        return $files;
    }

    private static function titleFromPath(string $relative): string
    {
        $base = basename($relative, '.md');
        $base = str_replace(['_', '-'], ' ', $base);
        return ucwords($base);
    }

    private static function wrapHtml(string $section, string $title, string $content, array $links): string
    {
        $sectionTitle = strtoupper($section);
        $nav = '';
        if ($links) {
            $nav .= '<div class="docs-sidebar"><ul class="list-unstyled">';
            foreach ($links as $link) {
                $nav .= '<li><a href="' . Template::escape($link['path']) . '">' . Template::escape($link['title']) . '</a></li>';
            }
            $nav .= '</ul></div>';
        }

        return '<!doctype html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . Template::escape($title) . '</title>'
            . '<link rel="stylesheet" href="/assets/core/lib/bootstrap/css/bootstrap.min.css">'
            . '<link rel="stylesheet" href="/assets/core/css/core.css">'
            . '</head>'
            . '<body>'
            . '<header class="py-4 bg-light border-bottom">'
            . '<div class="container">'
            . '<div class="text-uppercase text-muted">' . Template::escape($sectionTitle) . '</div>'
            . '<h1 class="h3">' . Template::escape($title) . '</h1>'
            . '</div>'
            . '</header>'
            . '<main class="container my-4">'
            . '<div class="row">'
            . '<div class="col-lg-3">' . $nav . '</div>'
            . '<div class="col-lg-9">' . $content . '</div>'
            . '</div>'
            . '</main>'
            . '</body>'
            . '</html>';
    }

    private static function renderIndex(string $section, array $links): string
    {
        $title = ucfirst($section) . ' Docs';
        $list = '<ul class="list-group">';
        foreach ($links as $link) {
            $list .= '<li class="list-group-item"><a href="' . Template::escape($link['path']) . '">' . Template::escape($link['title']) . '</a></li>';
        }
        $list .= '</ul>';

        return self::wrapHtml($section, $title, $list, $links);
    }

    private static function updateAdminMenu(): void
    {
        $stmt = Connection::prepare('DELETE FROM #__admin_menu_entries WHERE owner_type = \'core\' AND item_key IN (\'docs\', \'docs_user\', \'docs_developer\', \'docs_sysadmin\')');
        $stmt->execute();

        $parent = Connection::prepare('INSERT INTO #__admin_menu_entries (owner_type, owner_key, item_key, parent_id, label_key, icon, route, sort_order, perm_key, is_enabled, created_at, updated_at) VALUES (\'core\', \'core\', \'docs\', NULL, \'FFCMS_DOCS\', \'fa-book\', \'/docs-html/user/\', 20, NULL, 1, NOW(), NOW())');
        $parent->execute();
        $parentId = (int)Connection::pdo()->lastInsertId();

        $stmt = Connection::prepare('INSERT INTO #__admin_menu_entries (owner_type, owner_key, item_key, parent_id, label_key, icon, route, sort_order, perm_key, is_enabled, created_at, updated_at) VALUES (:owner_type, :owner_key, :item_key, :parent_id, :label_key, :icon, :route, :sort_order, :perm_key, 1, NOW(), NOW())');
        $items = [
            ['docs_user', 'FFCMS_DOCS_USER', '/docs-html/user/', null],
            ['docs_developer', 'FFCMS_DOCS_DEVELOPER', '/docs-html/developer/', null],
            ['docs_sysadmin', 'FFCMS_DOCS_SYSADMIN', '/docs-html/sysadmin/', 'view_sysadmin_docs'],
        ];
        foreach ($items as $item) {
            $stmt->execute([
                ':owner_type' => 'core',
                ':owner_key' => 'core',
                ':item_key' => $item[0],
                ':parent_id' => $parentId,
                ':label_key' => $item[1],
                ':icon' => 'fa-book',
                ':route' => $item[2],
                ':sort_order' => 1,
                ':perm_key' => $item[3],
            ]);
        }
    }
}
