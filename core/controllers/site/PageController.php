<?php
namespace Core\Controllers\Site;

use Core\Controller;
use Core\ElementRenderer;
use Core\Freewrite;
use Core\Lang;
use Core\Response;
use Core\Theme;
use Core\Models\PagesModel;

class PageController extends Controller
{
    public function show(\Core\Request $request): Response
    {
        $slug = $request->param('slug') ?? '';
        $page = PagesModel::findBySlug($slug);
        if (!$page) {
            return new Response(Lang::get('FFCMS_NOT_FOUND'), 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $rendered = Freewrite::render((string)$page['content_xml']);
        $data = [
            'page_title' => $page['title'] ?? Lang::get('FFCMS_PAGE'),
            'page' => $page,
            'content_html' => $rendered['html'],
        ];

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, $request->path());
        return $this->render('site/page', $data, $themeKey, $regions);
    }
}
