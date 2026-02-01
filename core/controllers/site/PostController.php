<?php
namespace Core\Controllers\Site;

use Core\Controller;
use Core\ElementRenderer;
use Core\Freewrite;
use Core\Lang;
use Core\Response;
use Core\Theme;
use Core\Models\PostsModel;

class PostController extends Controller
{
    public function show(\Core\Request $request): Response
    {
        $slug = $request->param('slug') ?? '';
        $post = PostsModel::findBySlug($slug);
        if (!$post) {
            return new Response(Lang::get('FFCMS_NOT_FOUND'), 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $rendered = Freewrite::render((string)$post['content_xml']);
        $data = [
            'page_title' => $post['title'] ?? Lang::get('FFCMS_POST'),
            'post' => $post,
            'content_html' => $rendered['html'],
        ];

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, $request->path());
        return $this->render('site/post', $data, $themeKey, $regions);
    }
}
