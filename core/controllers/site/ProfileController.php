<?php
namespace Core\Controllers\Site;

use Core\Auth;
use Core\Controller;
use Core\ElementRenderer;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Theme;
use Core\Models\DevstoreDevelopersModel;
use Core\Models\UserProfileModel;

class ProfileController extends Controller
{
    public function index(): Response
    {
        Session::startSite();
        if (!Auth::isAuthenticated()) {
            return new Response('', 302, ['Location' => '/login']);
        }

        $userId = (int)Session::get('user_id', 0);
        $profile = UserProfileModel::findByUserId($userId);
        if (!$profile) {
            return new Response(Lang::get('FFCMS_NOT_FOUND'), 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $data = [
            'page_title' => Lang::get('FFCMS_PROFILE'),
            'profile' => $profile,
            'can_devstore' => DevstoreDevelopersModel::isApprovedUser($userId),
        ];

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/profile');

        return $this->render('site/profile', $data, $themeKey, $regions);
    }
}
