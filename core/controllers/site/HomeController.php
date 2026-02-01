<?php
namespace Core\Controllers\Site;

use Core\Controller;
use Core\ElementRenderer;
use Core\Lang;
use Core\Theme;

class HomeController extends Controller
{
    public function index(): \Core\Response
    {
        $data = [
            'page_title' => Lang::get('FFCMS_HOME'),
        ];
        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/');

        return $this->render('site/home', $data, $themeKey, $regions);
    }
}
