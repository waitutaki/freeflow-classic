<?php
namespace Core;

use Core\Models\ElementAssignmentsModel;
use Core\Models\ElementPositionsModel;
use Core\Models\ElementsModel;
use Core\Models\ElementTypesModel;
use Core\Models\MenusModel;
use Core\Models\MenuItemsModel;
use Core\Models\PagesModel;
use Core\Models\PostsModel;
use Core\Models\CategoriesModel;

class ElementRenderer
{
    public static function renderRegions(string $context, string $themeKey, string $path): array
    {
        Theme::syncPositions($themeKey, $context);
        $assignment = ElementAssignmentsModel::resolve($context, $path);
        if (!$assignment) {
            return [];
        }

        $positions = ElementPositionsModel::forTheme($themeKey, $context);
        if (!$positions) {
            return [];
        }
        $positionMap = [];
        foreach ($positions as $position) {
            $positionMap[(int)$position['id']] = $position['position'];
        }

        $elements = ElementsModel::allByAssignment((int)$assignment['id']);
        $regions = [];
        foreach ($elements as $element) {
            $positionId = (int)$element['template_position_id'];
            if (!isset($positionMap[$positionId])) {
                continue;
            }
            if (empty($element['is_published'])) {
                continue;
            }
            $html = self::renderElement($element);
            if ($html === '') {
                continue;
            }
            $key = $positionMap[$positionId];
            if (!isset($regions[$key])) {
                $regions[$key] = '';
            }
            $regions[$key] .= $html;
        }
        return $regions;
    }

    private static function renderElement(array $element): string
    {
        $config = self::parseConfig((string)($element['config_xml'] ?? ''));
        if (!self::passesConditions($config['conditions'])) {
            return '';
        }
        $classes = trim($config['classes']);
        $wrapperClass = $classes !== '' ? ' ' . Template::escape($classes) : '';
        $typeKey = $element['type_key'] ?? '';
        $settings = $config['settings'];

        $content = '';
        if ($typeKey === 'menu') {
            $content = self::renderMenu($settings);
        } elseif ($typeKey === 'pages') {
            $content = self::renderPagesElement($settings);
        } elseif ($typeKey === 'posts') {
            $content = self::renderPostsElement($settings);
        } elseif ($typeKey === 'categories') {
            $content = self::renderCategoriesElement($settings);
        } elseif ($typeKey === 'html') {
            $content = (string)($settings['html'] ?? '');
        } elseif ($typeKey === 'embed') {
            $content = self::renderEmbedElement($settings);
        } elseif ($typeKey === 'url') {
            $content = self::renderUrlElement($settings);
        } elseif ($typeKey === 'image') {
            $content = self::renderImageElement($settings);
        } elseif ($typeKey === 'text') {
            $content = self::renderTextElement($settings);
        } elseif ($typeKey === 'button') {
            $content = self::renderButtonElement($settings);
        }

        if ($content === '') {
            return '';
        }

        return '<div class="ff-element' . $wrapperClass . '">' . $content . '</div>';
    }

    private static function renderMenu(array $settings): string
    {
        $menuKey = $settings['key'] ?? '';
        if ($menuKey === '') {
            return '';
        }
        $menu = MenusModel::findByKey($menuKey);
        if (!$menu) {
            return '';
        }
        $items = MenuItemsModel::allForMenu((int)$menu['id']);
        $tree = self::buildMenuTree($items);

        $style = $settings['style'] ?? 'navbar';
        $depth = (int)($settings['depth'] ?? 2);
        if ($depth < 1 || $depth > 3) {
            $depth = 2;
        }

        if ($style === 'tabs') {
            return self::renderNavList($tree, 'nav nav-tabs', $depth);
        }
        if ($style === 'pills') {
            return self::renderNavList($tree, 'nav nav-pills', $depth);
        }
        if ($style === 'vertical') {
            return self::renderNavList($tree, 'nav flex-column', $depth);
        }

        return self::renderNavbar($tree, $depth);
    }

    private static function renderNavbar(array $tree, int $depth): string
    {
        $navId = 'ff-nav-' . bin2hex(random_bytes(4));
        $html = '<nav class="navbar navbar-expand-lg"><div class="container-fluid">';
        $html .= '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#' . $navId . '" aria-controls="' . $navId . '" aria-expanded="false" aria-label="' . Template::escape(Lang::get('FFCMS_TOGGLE_NAV')) . '">';
        $html .= '<span class="navbar-toggler-icon"></span></button>';
        $html .= '<div class="collapse navbar-collapse" id="' . $navId . '">';
        $html .= self::renderNavList($tree, 'navbar-nav me-auto mb-2 mb-lg-0', $depth, true);
        $html .= '</div></div></nav>';
        return $html;
    }

    private static function renderNavList(array $tree, string $class, int $depth, bool $dropdown = false): string
    {
        $html = '<ul class="' . Template::escape($class) . '">';
        foreach ($tree as $item) {
            if (empty($item['is_enabled'])) {
                continue;
            }
            $children = $item['children'] ?? [];
            $hasChildren = $children && $depth > 1;
            $url = Template::escape($item['url']);
            $title = Template::escape(self::resolveMenuTitle((string)($item['title'] ?? '')));
            if (!self::menuItemVisible($item)) {
                continue;
            }
            $classes = trim($item['css_class'] ?? '');
            $classAttr = $classes !== '' ? ' ' . Template::escape($classes) : '';
            $rel = trim((string)($item['rel'] ?? ''));
            $relAttr = $rel !== '' ? ' rel="' . Template::escape($rel) . '"' : '';
            $target = trim((string)($item['target'] ?? ''));
            $targetAttr = $target !== '' ? ' target="' . Template::escape($target) . '"' : '';

            if ($dropdown && $hasChildren) {
                $html .= '<li class="nav-item dropdown">';
                $html .= '<a class="nav-link dropdown-toggle' . $classAttr . '" href="' . $url . '" role="button" data-bs-toggle="dropdown" aria-expanded="false"' . $relAttr . $targetAttr . '>' . $title . '</a>';
                $html .= '<ul class="dropdown-menu">';
                foreach ($children as $child) {
                    if (empty($child['is_enabled'])) {
                        continue;
                    }
                    $childUrl = Template::escape($child['url']);
                    $childTitle = Template::escape(self::resolveMenuTitle((string)($child['title'] ?? '')));
                    $html .= '<li><a class="dropdown-item" href="' . $childUrl . '">' . $childTitle . '</a></li>';
                }
                $html .= '</ul></li>';
                continue;
            }

            $html .= '<li class="nav-item"><a class="nav-link' . $classAttr . '" href="' . $url . '"' . $relAttr . $targetAttr . '>' . $title . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private static function menuItemVisible(array $item): bool
    {
        $visibility = $item['visibility'] ?? 'all';
        if ($visibility === 'all') {
            return true;
        }
        $context = Session::context();
        if ($visibility === 'none') {
            return $context === '';
        }
        $loggedIn = Auth::isAuthenticated();
        if ($visibility === 'logged_in') {
            return $loggedIn;
        }
        if ($visibility === 'logged_out') {
            return !$loggedIn;
        }
        return true;
    }

    private static function renderPagesElement(array $settings): string
    {
        $mode = $settings['mode'] ?? 'all';
        $items = PagesModel::all();
        $list = self::filterContentItems($items, $mode, (int)($settings['page_id'] ?? 0));
        return self::renderContentList($list, 'page');
    }

    private static function renderPostsElement(array $settings): string
    {
        $mode = $settings['mode'] ?? 'all';
        $items = PostsModel::all();
        $list = self::filterContentItems($items, $mode, (int)($settings['post_id'] ?? 0));
        return self::renderContentList($list, 'blog');
    }

    private static function renderCategoriesElement(array $settings): string
    {
        $mode = $settings['mode'] ?? 'all';
        $categories = CategoriesModel::all();
        if ($mode === 'one') {
            $id = (int)($settings['category_id'] ?? 0);
            $filtered = [];
            foreach ($categories as $category) {
                if ((int)$category['parent_id'] === $id) {
                    $filtered[] = $category;
                }
            }
            return self::renderCategoryList($filtered);
        }
        return self::renderCategoryList($categories);
    }

    private static function renderEmbedElement(array $settings): string
    {
        $url = $settings['url'] ?? '';
        if (!Url::isSafe($url)) {
            return '';
        }
        return '<div class="ratio ratio-16x9"><iframe src="' . Template::escape($url) . '" title="' . Template::escape(Lang::get('FFCMS_EMBED')) . '" allowfullscreen></iframe></div>';
    }

    private static function renderUrlElement(array $settings): string
    {
        $url = $settings['url'] ?? '';
        if (!Url::isSafe($url)) {
            return '';
        }
        $label = $settings['label'] ?? Lang::get('FFCMS_LINK');
        return '<a class="btn btn-outline-primary" href="' . Template::escape($url) . '">' . Template::escape($label) . '</a>';
    }

    private static function renderImageElement(array $settings): string
    {
        $src = $settings['src'] ?? '';
        if (!Url::isSafe($src)) {
            return '';
        }
        $alt = $settings['alt'] ?? '';
        $width = (int)($settings['width'] ?? 0);
        $height = (int)($settings['height'] ?? 0);
        $style = '';
        if ($width > 0 && $height > 0) {
            $style = 'width:' . $width . 'px;height:' . $height . 'px;object-fit:cover;';
        } elseif ($width > 0) {
            $style = 'width:' . $width . 'px;height:auto;';
        } elseif ($height > 0) {
            $style = 'width:auto;height:' . $height . 'px;';
        }
        $styleAttr = $style !== '' ? ' style="' . Template::escape($style) . '"' : '';
        return '<img src="' . Template::escape($src) . '" alt="' . Template::escape($alt) . '"' . $styleAttr . ' class="img-fluid">';
    }

    private static function renderTextElement(array $settings): string
    {
        $content = $settings['content'] ?? '';
        return '<div>' . nl2br(Template::escape($content)) . '</div>';
    }

    private static function renderButtonElement(array $settings): string
    {
        $label = $settings['label'] ?? '';
        $url = $settings['url'] ?? '';
        $style = $settings['style'] ?? 'primary';
        if ($label === '' || !Url::isSafe($url)) {
            return '';
        }
        $btnClass = 'btn btn-' . Template::escape($style);
        return '<a class="' . $btnClass . '" href="' . Template::escape($url) . '">' . Template::escape($label) . '</a>';
    }

    private static function buildMenuTree(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $item['children'] = [];
            $item['url'] = self::resolveMenuItemUrl($item);
            $map[$itemId] = $item;
        }
        foreach ($map as $id => $item) {
            $parentId = (int)($item['parent_id'] ?? 0);
            if ($parentId > 0 && isset($map[$parentId])) {
                $map[$parentId]['children'][] = $item;
            }
        }
        $tree = [];
        foreach ($map as $id => $item) {
            $parentId = (int)($item['parent_id'] ?? 0);
            if ($parentId > 0 && isset($map[$parentId])) {
                continue;
            }
            $tree[] = $item;
        }
        return $tree;
    }

    private static function resolveMenuItemUrl(array $item): string
    {
        $type = $item['type'] ?? '';
        if ($type === 'page' && !empty($item['page_slug'])) {
            return '/page/' . $item['page_slug'];
        }
        if ($type === 'post' && !empty($item['post_slug'])) {
            return '/blog/' . $item['post_slug'];
        }
        $url = (string)($item['url'] ?? '');
        return Url::isSafe($url) ? $url : '#';
    }

    private static function resolveMenuTitle(string $title): string
    {
        if ($title === '' || !str_contains($title, '{user:')) {
            return $title;
        }

        $user = Auth::user();
        $map = $user ?: [];
        $map['full_name'] = trim((string)($map['name_first'] ?? '') . ' ' . (string)($map['name_last'] ?? ''));
        $guest = Lang::get('FFCMS_GUEST');

        return preg_replace_callback('/\\{user:([a-z0-9_]+)\\}/i', function ($matches) use ($map, $guest, $user) {
            $key = strtolower($matches[1] ?? '');
            if ($key === 'state') {
                if (Session::context() === '') {
                    return Lang::get('FFCMS_NONE');
                }
                return Auth::isAuthenticated() ? Lang::get('FFCMS_LOGGED_IN') : Lang::get('FFCMS_LOGGED_OUT');
            }
            if (!$user) {
                return $guest;
            }
            $value = $map[$key] ?? '';
            return is_string($value) ? $value : '';
        }, $title) ?? $title;
    }

    private static function filterContentItems(array $items, string $mode, int $selectedId): array
    {
        $filtered = [];
        foreach ($items as $item) {
            if (($item['status'] ?? '') !== 'published') {
                continue;
            }
            $filtered[] = $item;
        }
        if ($mode === 'one' && $selectedId > 0) {
            foreach ($filtered as $item) {
                if ((int)$item['id'] === $selectedId) {
                    return [$item];
                }
            }
            return [];
        }
        if (in_array($mode, ['latest', 'top', 'featured'], true)) {
            usort($filtered, function ($a, $b) {
                return strcmp((string)($b['published_at'] ?? ''), (string)($a['published_at'] ?? ''));
            });
            return array_slice($filtered, 0, 5);
        }
        return $filtered;
    }

    private static function renderContentList(array $items, string $prefix): string
    {
        if (!$items) {
            return '';
        }
        $html = '<ul class="list-group">';
        foreach ($items as $item) {
            $slug = $item['slug'] ?? '';
            if ($slug === '') {
                continue;
            }
            $title = $item['title'] ?? '';
            $html .= '<li class="list-group-item"><a href="/' . $prefix . '/' . Template::escape($slug) . '">' . Template::escape($title) . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private static function renderCategoryList(array $items): string
    {
        if (!$items) {
            return '';
        }
        $html = '<ul class="list-group">';
        foreach ($items as $item) {
            $title = $item['title'] ?? '';
            $html .= '<li class="list-group-item">' . Template::escape($title) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private static function parseConfig(string $xml): array
    {
        $config = [
            'settings' => [],
            'classes' => '',
            'conditions' => [],
        ];
        $xml = trim($xml);
        if ($xml === '') {
            return $config;
        }
        $data = @simplexml_load_string($xml);
        if ($data === false) {
            return $config;
        }
        $style = $data->style;
        if ($style) {
            $config['classes'] = (string)($style['classes'] ?? '');
        }
        if ($data->conditions) {
            $config['conditions'] = [
                'enabled' => (string)($data->conditions['enabled'] ?? '0'),
                'op' => (string)($data->conditions['op'] ?? 'ALL'),
                'rules' => [],
            ];
            foreach ($data->conditions->rule as $rule) {
                $config['conditions']['rules'][] = [
                    'type' => (string)($rule['type'] ?? ''),
                    'value' => (string)($rule['value'] ?? ''),
                ];
            }
        }
        if ($data->settings) {
            foreach ($data->settings->children() as $child) {
                foreach ($child->attributes() as $key => $value) {
                    $config['settings'][(string)$key] = (string)$value;
                }
                if ($child->getName() === 'html') {
                    $config['settings']['html'] = (string)$child;
                } elseif ($child->getName() === 'text') {
                    $config['settings']['content'] = (string)$child;
                }
            }
        }
        return $config;
    }

    private static function passesConditions(array $conditions): bool
    {
        if (!$conditions || ($conditions['enabled'] ?? '0') !== '1') {
            return true;
        }
        $rules = $conditions['rules'] ?? [];
        if (!$rules) {
            return true;
        }
        $opAll = ($conditions['op'] ?? 'ALL') !== 'ANY';
        $results = [];
        foreach ($rules as $rule) {
            $results[] = self::evaluateRule($rule);
        }
        if ($opAll) {
            return !in_array(false, $results, true);
        }
        return in_array(true, $results, true);
    }

    private static function evaluateRule(array $rule): bool
    {
        $type = $rule['type'] ?? '';
        $value = $rule['value'] ?? '';
        if ($type === 'user_logged_in') {
            return Auth::isAuthenticated() === ($value === '1');
        }
        if ($type === 'role_any') {
            $roles = array_filter(array_map('intval', explode(',', $value)));
            if (!$roles) {
                return false;
            }
            $userId = (int)Session::get('user_id', 0);
            if ($userId <= 0) {
                return false;
            }
            $userRoles = ACL::userRoles($userId);
            return (bool)array_intersect($roles, $userRoles);
        }
        if ($type === 'role_all') {
            $roles = array_filter(array_map('intval', explode(',', $value)));
            if (!$roles) {
                return false;
            }
            $userId = (int)Session::get('user_id', 0);
            if ($userId <= 0) {
                return false;
            }
            $userRoles = ACL::userRoles($userId);
            foreach ($roles as $roleId) {
                if (!in_array($roleId, $userRoles, true)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
