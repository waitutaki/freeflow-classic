<?php
namespace Core;

class Controller
{
    protected function render(string $view, array $data = [], string $template = '', array $regions = []): Response
    {
        $result = View::render($view, $data);
        $content = $result['content'] ?? '';
        $returned = $result['return'] ?? null;

        if (is_array($returned)) {
            $regions = array_merge($regions, $returned);
        }

        if ($content !== '') {
            $regions['content'] = $content;
        }

        if ($template === 'Zulu') {
            $regions = AdminLayout::apply($regions);
        }

        if ($template !== '') {
            $html = Template::render($template, $regions, $data);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
