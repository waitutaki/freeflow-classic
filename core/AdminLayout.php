<?php
namespace Core;

class AdminLayout
{
    public static function apply(array $regions): array
    {
        if (!isset($regions['topbar_logo'])) {
            $regions['topbar_logo'] = '<img class="zulu-logo" src="' . Asset::url('system', 'fflogo.png') . '" alt="' . Lang::get('FFCMS_PRODUCT_NAME') . '">';
        }

        if (!isset($regions['sidebar_toggle'])) {
            $regions['sidebar_toggle'] = '<button class="zulu-sidebar-toggle" type="button" aria-label="' . Lang::get('FFCMS_TOGGLE_SIDEBAR') . '"><span class="zulu-toggle-track" aria-hidden="true"><span class="zulu-toggle-thumb"></span></span></button>';
        }

        if (!isset($regions['sidebar_menu']) && Auth::isAuthenticated()) {
            $regions['sidebar_menu'] = Menu::renderAdminMenu();
        }

        if (!isset($regions['toolbar_right']) && Auth::isAuthenticated()) {
            $regions['toolbar_right'] = self::toolbarRight();
        }

        if (!isset($regions['toolbar_notices']) && Auth::isAuthenticated()) {
            $banner = Trust::bannerHtml();
            if ($banner !== '') {
                $regions['toolbar_notices'] = $banner;
            }
        }

        return $regions;
    }

    private static function toolbarRight(): string
    {
        $viewSite = '<a class="btn btn-outline-light btn-sm me-2" href="/" target="_blank" rel="noopener">'
            . '<span class="me-1" aria-hidden="true"><i class="fa-solid fa-eye"></i></span>'
            . Lang::get('FFCMS_VIEW_SITE')
            . '</a>';

        $logout = '<a class="btn btn-outline-light btn-sm" href="/admin/logout">'
            . '<span class="me-1" aria-hidden="true"><i class="fa-solid fa-right-from-bracket"></i></span>'
            . Lang::get('FFCMS_LOGOUT')
            . '</a>';

        return '<div class="btn-group" role="group" aria-label="' . Lang::get('FFCMS_USER_ACTIONS') . '">' . $viewSite . $logout . '</div>';
    }
}
