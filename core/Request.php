<?php
namespace Core;

class Request
{
    private string $method;
    private string $path;
    private string $context;
    private array $params = [];

    public function __construct(string $context)
    {
        $this->context = $context;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = $this->normalizePath($uri, $context);
    }

    private function normalizePath(string $uri, string $context): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $path === null ? '/' : $path;

        if ($context === Application::CONTEXT_ADMIN) {
            if (str_starts_with($path, '/admin')) {
                $path = substr($path, 6);
                if ($path === '') {
                    $path = '/';
                }
            }
        }

        return $path;
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, string $default = ''): string
    {
        return $this->params[$key] ?? $default;
    }

    public function ip(): string
    {
        // Get the client IP address from various possible sources
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Check for forwarded IPs (common with proxies, load balancers, etc.)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwardedIps[0]); // Use the first IP in the chain
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // Validate the IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0'; // Fallback to default if invalid
        }
        
        return $ip;
    }
}
