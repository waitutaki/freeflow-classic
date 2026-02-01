<?php
namespace Core;

class ApiResponder
{
    public static function ok(string $content = ''): Response
    {
        $body = '<response status="ok">' . $content . '</response>';
        return new Response($body, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public static function error(string $message, int $status = 400): Response
    {
        $safe = Template::escape($message);
        $body = '<response status="error"><message>' . $safe . '</message></response>';
        return new Response($body, $status, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
