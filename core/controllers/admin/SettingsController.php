<?php
namespace Core\Controllers\Admin;

use Core\Controller;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Template;
use Core\Session;

class SettingsController extends Controller
{
    public function index(): Response
    {
        if (class_exists(Session::class)) {
            Session::startAdmin();
        }
        if (class_exists(\Core\Auth::class)) {
            \Core\Auth::requireSuper();
        }

        $tab = $_GET['tab'] ?? 'logs';
        $channels = Logger::files();
        $selectedLines = $this->resolveLineLimit();

        $download = $_GET['download'] ?? '';
        if ($download !== '') {
            return $this->downloadLog($channels, $download);
        }

        $clear = $_GET['clear'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($clear !== '') {
            $token = $_POST['csrf_token'] ?? null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !\Core\Csrf::validate($token)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            return $this->clearLog($channels, $clear, $confirm === '1');
        }

        $view = $_GET['view'] ?? '';
        $viewContent = '';
        if ($view !== '') {
            $viewContent = $this->readLog($channels, $view, $selectedLines);
        }

        $data = [
            'page_title' => Lang::get('FFCMS_SETTINGS'),
            'tab' => $tab,
            'channels' => $channels,
            'selected_lines' => $selectedLines,
            'view_log' => $view,
            'view_content' => $viewContent,
        ];

        return $this->render('admin/settings', $data, 'Zulu');
    }

    private function resolveLineLimit(): int
    {
        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 0;
        $allowed = [10, 25, 50, 100, 0];
        if (!in_array($lines, $allowed, true)) {
            $lines = 50;
        }

        if (class_exists(Session::class)) {
            if ($lines > 0 || $lines === 0) {
                Session::set('log_lines', $lines);
            }
            $stored = Session::get('log_lines');
            if (is_int($stored)) {
                $lines = $stored;
            }
        }

        return $lines;
    }

    private function readLog(array $channels, string $name, int $lines): string
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return '';
        }
        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!is_file($path)) {
            return '';
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        $rows = preg_split('/\\r\\n|\\r|\\n/', $content);
        $rows = array_filter($rows, static fn ($line) => $line !== '');
        if ($lines > 0) {
            $rows = array_slice($rows, -$lines);
        }

        $safe = array_map(static fn ($line) => Template::escape($line), $rows);
        return implode("\n", $safe);
    }

    private function downloadLog(array $channels, string $name): Response
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!is_file($path)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return new Response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $file . '"',
        ]);
    }

    private function clearLog(array $channels, string $name, bool $confirmed): Response
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!$confirmed) {
            $data = [
                'page_title' => Lang::get('FFCMS_SETTINGS'),
                'tab' => 'logs',
                'channels' => $channels,
                'selected_lines' => $this->resolveLineLimit(),
                'view_log' => '',
                'view_content' => '',
                'confirm_clear' => $name,
            ];
            return $this->render('admin/settings', $data, 'Zulu');
        }

        file_put_contents($path, '');
        return new Response('', 302, ['Location' => '/admin/settings?tab=logs']);
    }
}
