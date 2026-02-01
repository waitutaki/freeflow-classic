<?php
namespace Core;

class Application
{
    public const CONTEXT_SITE = 'site';
    public const CONTEXT_ADMIN = 'admin';

    private static ?Application $instance = null;
    private static bool $ran = false;

    private string $context;

    private function __construct(string $context)
    {
        $this->context = $context;
    }

    private function checkMaintenanceMode(Request $request): ?Response
    {
        $maintenanceEnabled = (bool)Settings::get('maintenance_enabled', false, 'global');
        if (!$maintenanceEnabled) {
            return null;
        }

        // Check if this is an asset request that should be allowed
        $path = $request->path();
        if (str_starts_with($path, '/assets/') || str_starts_with($path, '/themes/')) {
            return null;
        }

        // Check IP allowlist
        $clientIp = $request->ip();
        $allowlist = (string)Settings::get('maintenance_allowlist_ips', '', 'global');
        if (!empty($allowlist)) {
            $allowedIps = array_map('trim', explode("\n", $allowlist));
            $allowedIps = array_filter($allowedIps);

            foreach ($allowedIps as $allowedIp) {
                if ($this->ipMatches($clientIp, $allowedIp)) {
                    return null;
                }
            }
        }

        // Maintenance mode is active - return 503
        $message = (string)Settings::get('maintenance_message', 'Site under maintenance', 'global');
        $retryMinutes = (int)Settings::get('maintenance_retry_after_minutes', 60, 'global');
        
        $headers = [
            'Content-Type' => 'text/html; charset=utf-8',
            'Retry-After' => $retryMinutes * 60,
        ];
        
        $html = $this->renderMaintenancePage($message);
        return new Response($html, 503, $headers);
    }

    private function ipMatches(string $clientIp, string $allowedIp): bool
    {
        if ($clientIp === $allowedIp) {
            return true;
        }
        
        // Simple IPv4 CIDR support (e.g., 192.168.1.0/24)
        if (str_contains($allowedIp, '/')) {
            [$ip, $cidr] = explode('/', $allowedIp);
            $cidr = (int)$cidr;
            if ($cidr < 1 || $cidr > 32) {
                return false;
            }
            
            $clientLong = ip2long($clientIp);
            $ipLong = ip2long($ip);
            if ($clientLong === false || $ipLong === false) {
                return false;
            }
            
            $mask = -1 << (32 - $cidr);
            return ($clientLong & $mask) === ($ipLong & $mask);
        }
        
        return false;
    }

    private function renderMaintenancePage(string $message): string
    {
        $siteName = Settings::get('site_name', 'Freeflow CMS', 'global');
        
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head>';
        $html .= '<meta charset="utf-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $html .= '<title>Maintenance Mode - ' . Template::escape($siteName) . '</title>';
        $html .= '<style>';
        $html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; color: #333; }';
        $html .= '.container { max-width: 800px; margin: 100px auto; padding: 20px; text-align: center; }';
        $html .= '.logo { font-size: 2rem; font-weight: bold; margin-bottom: 20px; color: #007bff; }';
        $html .= '.message { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
        $html .= '.message h1 { font-size: 1.8rem; margin-top: 0; }';
        $html .= '.message p { font-size: 1.1rem; line-height: 1.6; }';
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="container">';
        $html .= '<div class="logo">' . Template::escape($siteName) . '</div>';
        $html .= '<div class="message">';
        $html .= '<h1>Maintenance Mode</h1>';
        $html .= '<p>' . nl2br(Template::escape($message)) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }

    public static function instance(string $context): self
    {
        if (self::$instance === null) {
            self::$instance = new self($context);
        }

        if (self::$instance->context !== $context) {
            self::$instance->context = $context;
        }

        return self::$instance;
    }

    public function run(): void
    {
        if (self::$ran) {
            return;
        }
        self::$ran = true;

        if ($this->context !== self::CONTEXT_SITE && $this->context !== self::CONTEXT_ADMIN) {
            echo 'Invalid execution context';
            return;
        }

        $request = new Request($this->context);
        $path = $request->path();
        if (str_starts_with($path, '/themes/') && str_ends_with($path, '/overrides.css')) {
            $parts = explode('/', trim($path, '/'));
            $themeKey = $parts[1] ?? '';
            $ctx = $_GET['ctx'] ?? 'site';
            $response = Theme::overridesCss($themeKey, $ctx);
            $response->send();
            return;
        }
        $apiResponse = ApiDispatcher::handle($request, $this->context);
        if ($apiResponse instanceof Response) {
            $apiResponse->send();
            return;
        }
        if (str_starts_with($path, '/assets/')) {
            $response = Asset::serve(substr($path, 8));
            $response->send();
            return;
        }
        if (str_starts_with($path, '/storage/devstore/repository/') && preg_match('#/preview\.(png|jpg)$#', $path)) {
            $file = __DIR__ . '/../..' . $path;
            if (is_file($file)) {
                $content = file_get_contents($file);
                $mime = str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg';
                $response = new Response($content, 200, ['Content-Type' => $mime]);
                $response->send();
                return;
            }
        }
        if (str_starts_with($path, '/docs/')) {
            $response = new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            $response->send();
            return;
        }
        if (str_starts_with($path, '/docs-html/')) {
            $response = Docs::serve($path);
            $response->send();
            return;
        }

        $freegateResponse = Freegate::evaluate($request);
        if ($freegateResponse instanceof Response) {
            $freegateResponse->send();
            return;
        }

        // Check maintenance mode for site context only
        if ($this->context === self::CONTEXT_SITE) {
            $maintenanceResponse = $this->checkMaintenanceMode($request);
            if ($maintenanceResponse instanceof Response) {
                $maintenanceResponse->send();
                return;
            }
        }

        if ($this->context === self::CONTEXT_ADMIN) {
            Session::startAdmin();
            if (!in_array($path, ['/login', '/logout'], true)) {
                Auth::requireAdmin();
            }
        } else {
            Session::startSite();
        }

        $router = new Router();
        if ($this->context === self::CONTEXT_SITE) {
            $router->add('GET', '/', function ($request) {
                $controller = new Controllers\Site\HomeController();
                return $controller->index();
            });
            $router->add('GET', '/login', function ($request) {
                $controller = new Controllers\Site\AuthController();
                return $controller->login();
            });
            $router->add('POST', '/login', function ($request) {
                $controller = new Controllers\Site\AuthController();
                return $controller->login();
            });
            $router->add('GET', '/logout', function ($request) {
                $controller = new Controllers\Site\AuthController();
                return $controller->logout();
            });
            $router->add('GET', '/profile', function ($request) {
                $controller = new Controllers\Site\ProfileController();
                return $controller->index();
            });
            $router->add('GET', '/register', function ($request) {
                $controller = new Controllers\Site\RegisterController();
                return $controller->register();
            });
            $router->add('POST', '/register', function ($request) {
                $controller = new Controllers\Site\RegisterController();
                return $controller->register();
            });
            $router->add('GET', '/verify-email', function ($request) {
                $controller = new Controllers\Site\RegisterController();
                return $controller->verifyEmail();
            });
            $router->add('GET', '/check-username', function ($request) {
                $controller = new Controllers\Site\RegisterController();
                return $controller->checkUsername();
            });
            $router->add('GET', '/devstore', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->portal();
            });
            $router->add('POST', '/devstore/register', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->register();
            });
            $router->add('GET', '/devstore/upload', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->upload();
            });
            $router->add('POST', '/devstore/upload', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->upload();
            });
            $router->add('GET', '/devstore/my-submissions', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->submissions();
            });
            $router->add('GET', '/directory/extension', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->directoryextension();
            });
            $router->add('GET', '/directory/themes', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->directoryThemes();
            });
            $router->add('GET', '/directory/extension/{key}', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->directoryExtensionDetail($request);
            });
            $router->add('GET', '/directory/themes/{key}', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->directoryThemeDetail($request);
            });
            $router->add('GET', '/repository', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->repository();
            });
            $router->add('GET', '/repository/install/{type}/{key}', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->installFromRepository($request);
            });
            $router->add('GET', '/repository/download/{type}/{key}', function ($request) {
                $controller = new Controllers\Site\DevstoreController();
                return $controller->downloadFromRepository($request);
            });
            $router->add('GET', '/page/{slug}', function ($request) {
                $controller = new Controllers\Site\PageController();
                return $controller->show($request);
            });
            $router->add('POST', '/forms/submit', function ($request) {
                $controller = new Controllers\Site\FormController();
                return $controller->submit();
            });
            $router->add('GET', '/blog/{slug}', function ($request) {
                $controller = new Controllers\Site\PostController();
                return $controller->show($request);
            });
        } else {
            $router->add('GET', '/', function ($request) {
                $controller = new Controllers\Admin\DashboardController();
                return $controller->index();
            });
            $router->add('GET', '/login', function ($request) {
                $controller = new Controllers\Admin\AuthController();
                return $controller->login();
            });
            $router->add('POST', '/login', function ($request) {
                $controller = new Controllers\Admin\AuthController();
                return $controller->login();
            });
            $router->add('GET', '/logout', function ($request) {
                $controller = new Controllers\Admin\AuthController();
                return $controller->logout();
            });
            $router->add('GET', '/settings', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsController();
                return $controller->index();
            });
            $router->add('POST', '/settings', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsController();
                return $controller->index();
            });
            $router->add('POST', '/settings/test-email', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsController();
                return $controller->testEmail();
            });
            $router->add('GET', '/settings/mailtemplates/new', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->create();
            });
            $router->add('POST', '/settings/mailtemplates/new', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->create();
            });
            $router->add('GET', '/settings/mailtemplates/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->edit($request);
            });
            $router->add('POST', '/settings/mailtemplates/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->edit($request);
            });
            $router->add('GET', '/settings/mailtemplates/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/settings/mailtemplates/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailTemplatesController();
                return $controller->delete($request);
            });
            $router->add('GET', '/settings/mailwrappers/new', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->create();
            });
            $router->add('POST', '/settings/mailwrappers/new', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->create();
            });
            $router->add('GET', '/settings/mailwrappers/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->edit($request);
            });
            $router->add('POST', '/settings/mailwrappers/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->edit($request);
            });
            $router->add('GET', '/settings/mailwrappers/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->delete($request);
            });
            $router->add('POST', '/settings/mailwrappers/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminSettingsMailWrappersController();
                return $controller->delete($request);
            });
            $router->add('GET', '/roles', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->index();
            });
            $router->add('POST', '/roles', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->index();
            });
            $router->add('GET', '/roles/new', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->create();
            });
            $router->add('POST', '/roles/new', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->create();
            });
            $router->add('GET', '/roles/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->edit($request);
            });
            $router->add('POST', '/roles/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->edit($request);
            });
            $router->add('GET', '/roles/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/roles/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/roles/perm-toggle', function ($request) {
                $controller = new Controllers\Admin\AdminRolesController();
                return $controller->toggle();
            });
            $router->add('GET', '/updates', function ($request) {
                $controller = new Controllers\Admin\AdminUpdatesController();
                return $controller->index();
            });
            $router->add('POST', '/updates/check', function ($request) {
                $controller = new Controllers\Admin\AdminUpdatesController();
                return $controller->check();
            });
            $router->add('POST', '/updates/run/{type}/{key}', function ($request) {
                $controller = new Controllers\Admin\AdminUpdatesController();
                return $controller->run($request);
            });
            $router->add('POST', '/updates/run-batch/{scope}', function ($request) {
                $controller = new Controllers\Admin\AdminUpdatesController();
                return $controller->runBatch($request);
            });
            $router->add('GET', '/docs', function ($request) {
                $controller = new Controllers\Admin\AdminDocsController();
                return $controller->index();
            });
            $router->add('POST', '/docs/build', function ($request) {
                $controller = new Controllers\Admin\AdminDocsController();
                return $controller->build();
            });
            $router->add('GET', '/devstore', function ($request) {
                $controller = new Controllers\Admin\DevstoreDashboardController();
                return $controller->index();
            });
            $router->add('GET', '/devstore/developers', function ($request) {
                $controller = new Controllers\Admin\DevstoreDevelopersController();
                return $controller->index();
            });
            $router->add('POST', '/devstore/developers/{id}/approve', function ($request) {
                $controller = new Controllers\Admin\DevstoreDevelopersController();
                return $controller->approve($request);
            });
            $router->add('POST', '/devstore/developers/{id}/reject', function ($request) {
                $controller = new Controllers\Admin\DevstoreDevelopersController();
                return $controller->reject($request);
            });
            $router->add('POST', '/devstore/developers/{id}/suspend', function ($request) {
                $controller = new Controllers\Admin\DevstoreDevelopersController();
                return $controller->suspend($request);
            });
            $router->add('GET', '/devstore/packages', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->index();
            });
            $router->add('GET', '/devstore/packages/{id}', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->view($request);
            });
            $router->add('POST', '/devstore/packages/{id}/publish', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->publish($request);
            });
            $router->add('POST', '/devstore/packages/{id}/unpublish', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->unpublish($request);
            });
            $router->add('POST', '/devstore/packages/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/devstore/packages/upload', function ($request) {
                $controller = new Controllers\Admin\DevstorePackagesController();
                return $controller->upload($request);
            });
            $router->add('GET', '/devstore/repository', function ($request) {
                $controller = new Controllers\Admin\DevstoreRepositoryController();
                return $controller->index();
            });
            $router->add('GET', '/devstore/keys', function ($request) {
                $controller = new Controllers\Admin\DevstoreKeysController();
                return $controller->index();
            });
            $router->add('POST', '/devstore/keys', function ($request) {
                $controller = new Controllers\Admin\DevstoreKeysController();
                return $controller->index();
            });
            $router->add('GET', '/devstore/settings', function ($request) {
                $controller = new Controllers\Admin\DevstoreSettingsController();
                return $controller->index();
            });
            $router->add('POST', '/devstore/settings', function ($request) {
                $controller = new Controllers\Admin\DevstoreSettingsController();
                return $controller->index();
            });
            $router->add('GET', '/freegate', function ($request) {
                $controller = new Controllers\Admin\AdminFreegateController();
                return $controller->index();
            });
            $router->add('POST', '/freegate', function ($request) {
                $controller = new Controllers\Admin\AdminFreegateController();
                return $controller->index();
            });
            $router->add('GET', '/users', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->index();
            });
            $router->add('GET', '/users/new', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->create();
            });
            $router->add('POST', '/users/new', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->create();
            });
            $router->add('GET', '/users/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->edit($request);
            });
            $router->add('POST', '/users/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->edit($request);
            });
            $router->add('GET', '/users/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->delete($request);
            });
            $router->add('POST', '/users/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminUsersController();
                return $controller->delete($request);
            });
            $router->add('GET', '/media', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->index();
            });
            $router->add('POST', '/media/upload', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->upload();
            });
            $router->add('GET', '/media/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->delete($request);
            });
            $router->add('POST', '/media/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->delete($request);
            });
            $router->add('POST', '/media/rotate', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->rotate();
            });
            $router->add('GET', '/media/picker', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->picker();
            });
            $router->add('POST', '/media/picker', function ($request) {
                $controller = new Controllers\Admin\AdminMediaController();
                return $controller->picker();
            });
            $router->add('GET', '/documents', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->index();
            });
            $router->add('POST', '/documents/upload', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->upload();
            });
            $router->add('GET', '/documents/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->edit($request);
            });
            $router->add('POST', '/documents/edit/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->edit($request);
            });
            $router->add('GET', '/documents/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/documents/delete/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->delete($request);
            });
            $router->add('GET', '/documents/download/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->download($request);
            });
            $router->add('GET', '/documents/picker', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->picker();
            });
            $router->add('POST', '/documents/insert-log', function ($request) {
                $controller = new Controllers\Admin\AdminDocumentsController();
                return $controller->insertLog();
            });
            $router->add('GET', '/content/pages', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->index();
            });
            $router->add('GET', '/content/pages/new', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->create();
            });
            $router->add('POST', '/content/pages/new', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->create();
            });
            $router->add('GET', '/content/pages/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->edit($request);
            });
            $router->add('POST', '/content/pages/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->edit($request);
            });
            $router->add('GET', '/content/pages/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/content/pages/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/content/pages/{id}/toggle-publish', function ($request) {
                $controller = new Controllers\Admin\AdminPagesController();
                return $controller->togglePublish($request);
            });
            $router->add('GET', '/content/posts', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->index();
            });
            $router->add('GET', '/content/posts/new', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->create();
            });
            $router->add('POST', '/content/posts/new', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->create();
            });
            $router->add('GET', '/content/posts/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->edit($request);
            });
            $router->add('POST', '/content/posts/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->edit($request);
            });
            $router->add('GET', '/content/posts/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/content/posts/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/content/posts/{id}/toggle-publish', function ($request) {
                $controller = new Controllers\Admin\AdminPostsController();
                return $controller->togglePublish($request);
            });
            $router->add('GET', '/forms', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->index();
            });
            $router->add('GET', '/forms/new', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->create();
            });
            $router->add('POST', '/forms/new', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->create();
            });
            $router->add('GET', '/forms/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->edit($request);
            });
            $router->add('POST', '/forms/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->edit($request);
            });
            $router->add('GET', '/forms/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/forms/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->delete($request);
            });
            $router->add('GET', '/forms/{id}/submissions', function ($request) {
                $controller = new Controllers\Admin\AdminFormsController();
                return $controller->submissions($request);
            });
            $router->add('GET', '/content/categories', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->index();
            });
            $router->add('GET', '/content/categories/new', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->create();
            });
            $router->add('POST', '/content/categories/new', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->create();
            });
            $router->add('GET', '/content/categories/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->edit($request);
            });
            $router->add('POST', '/content/categories/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->edit($request);
            });
            $router->add('GET', '/content/categories/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->delete($request);
            });
            $router->add('POST', '/content/categories/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminCategoriesController();
                return $controller->delete($request);
            });
            $router->add('GET', '/menus', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->index();
            });
            $router->add('GET', '/menus/new', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->create();
            });
            $router->add('POST', '/menus/new', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->create();
            });
            $router->add('GET', '/menus/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->edit($request);
            });
            $router->add('POST', '/menus/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->edit($request);
            });
            $router->add('GET', '/menus/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->delete($request);
            });
            $router->add('POST', '/menus/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMenusController();
                return $controller->delete($request);
            });
            $router->add('GET', '/menus/{menu_id}/items', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->index($request);
            });
            $router->add('GET', '/menus/{menu_id}/items/new', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->create($request);
            });
            $router->add('POST', '/menus/{menu_id}/items/new', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->create($request);
            });
            $router->add('GET', '/menus/{menu_id}/items/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->edit($request);
            });
            $router->add('POST', '/menus/{menu_id}/items/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->edit($request);
            });
            $router->add('GET', '/menus/{menu_id}/items/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/menus/{menu_id}/items/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/menus/{menu_id}/items/reorder', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->reorder($request);
            });
            $router->add('POST', '/menus/{menu_id}/items/{id}/move', function ($request) {
                $controller = new Controllers\Admin\AdminMenuItemsController();
                return $controller->move($request);
            });
            $router->add('GET', '/elements', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->index();
            });
            $router->add('GET', '/elements/builder', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->builder();
            });
            $router->add('GET', '/elements/assignments', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignments();
            });
            $router->add('GET', '/elements/assignments/new', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentCreate();
            });
            $router->add('POST', '/elements/assignments/new', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentCreate();
            });
            $router->add('GET', '/elements/assignments/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentEdit($request);
            });
            $router->add('POST', '/elements/assignments/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentEdit($request);
            });
            $router->add('GET', '/elements/assignments/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentDelete($request);
            });
            $router->add('POST', '/elements/assignments/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->assignmentDelete($request);
            });
            $router->add('GET', '/elements/new', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->create();
            });
            $router->add('POST', '/elements/new', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->create();
            });
            $router->add('GET', '/elements/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->edit($request);
            });
            $router->add('POST', '/elements/{id}/edit', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->edit($request);
            });
            $router->add('GET', '/elements/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/elements/{id}/delete', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->delete($request);
            });
            $router->add('POST', '/elements/{id}/toggle', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->toggle($request);
            });
            $router->add('POST', '/elements/{id}/move', function ($request) {
                $controller = new Controllers\Admin\AdminElementsController();
                return $controller->move($request);
            });
            $router->add('GET', '/extension', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->index($request);
            });
            $router->add('GET', '/extension/install', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->installForm($request);
            });
            $router->add('POST', '/extension/install', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->install($request);
            });
            $router->add('GET', '/extension/{ext_key}/uninstall', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->uninstallConfirm($request);
            });
            $router->add('POST', '/extension/{ext_key}/uninstall', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->uninstall($request);
            });
            $router->add('POST', '/extension/{ext_key}/toggle', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->toggleExtension($request);
            });
            $router->add('POST', '/extension/install-from-web', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->installFromWeb($request);
            });
            $router->add('POST', '/admin/extension/install-from-web', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->installFromWeb($request);
            });
            $router->add('POST', '/extension/themes/{id}/activate', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->activateTheme($request);
            });
            $router->add('POST', '/extension/themes/{id}/deactivate', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->deactivateTheme($request);
            });
            $router->add('GET', '/extension/themes/{id}/uninstall', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->uninstallThemeConfirm($request);
            });
            $router->add('POST', '/extension/themes/{id}/uninstall', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->uninstallTheme($request);
            });
            $router->add('GET', '/extension/inactive/{ext_key}', function ($request) {
                $controller = new Controllers\Admin\AdminExtensionController();
                return $controller->inactive($request);
            });
            $router->add('GET', '/ext/{ext_key}', function ($request) {
                return ExtensionRouter::dispatchAdmin($request);
            });
            $router->add('POST', '/ext/{ext_key}', function ($request) {
                return ExtensionRouter::dispatchAdmin($request);
            });
            $router->add('GET', '/ext/{ext_key}/{controller}/{action}', function ($request) {
                return ExtensionRouter::dispatchAdmin($request);
            });
            $router->add('POST', '/ext/{ext_key}/{controller}/{action}', function ($request) {
                return ExtensionRouter::dispatchAdmin($request);
            });
            $router->add('GET', '/themes', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->index();
            });
            $router->add('POST', '/themes/upload', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->upload();
            });
            $router->add('POST', '/themes/activate/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->activate($request);
            });
            $router->add('POST', '/themes/deactivate/{id}', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->deactivate($request);
            });
            $router->add('GET', '/themes/customize/{key}', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->customize($request);
            });
            $router->add('POST', '/themes/customize/save', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->saveToken();
            });
            $router->add('POST', '/themes/customize/save-all', function ($request) {
                $controller = new Controllers\Admin\AdminThemesController();
                return $controller->saveAll();
            });
        }

        $response = $router->dispatch($request);
        $response->send();
    }
}
