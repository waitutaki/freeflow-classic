<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Url;
use Core\Models\MenusModel;
use Core\Models\MenuItemsModel;
use Core\Models\PagesModel;
use Core\Models\PostsModel;

class AdminMenuItemsController extends Controller
{
    public function index(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $items = MenuItemsModel::allForMenu($menuId);
        $tree = $this->buildTree($items);

        return $this->render('admin/menu_items', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menu' => $menu,
            'items' => $tree,
        ], 'Zulu');
    }

    public function create(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = $this->defaultValues($menuId);
        $errors = [];
        $items = MenuItemsModel::allForMenu($menuId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, 0, $items);
                if (!$errors) {
                    $values['ordering'] = $this->nextOrdering($items, $values['parent_id']);
                    $itemId = MenuItemsModel::create($values);
                    Logger::info('admin', 'Menu item created', ['menu_id' => $menuId, 'item_id' => $itemId, 'user_id' => (int)Session::get('user_id', 0)]);
                    return new Response('', 302, ['Location' => '/admin/menus/' . $menuId . '/items']);
                }
            }
        }

        return $this->render('admin/menu_item_form', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menu' => $menu,
            'values' => $values,
            'errors' => $errors,
            'items' => $items,
            'pages' => PagesModel::listForMenu(),
            'posts' => PostsModel::listForMenu(),
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $id = (int)$request->param('id');
        $item = MenuItemsModel::find($id);
        if (!$item || (int)$item['menu_id'] !== $menuId) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'menu_id' => $menuId,
            'type' => $item['type'] ?? 'url',
            'title' => $item['title'] ?? '',
            'parent_id' => (int)($item['parent_id'] ?? 0),
            'ordering' => (int)($item['ordering'] ?? 0),
            'is_enabled' => (int)($item['is_enabled'] ?? 1),
            'url' => $item['url'] ?? '',
            'target' => $item['target'] ?? '',
            'page_id' => (int)($item['page_id'] ?? 0),
            'post_id' => (int)($item['post_id'] ?? 0),
            'css_class' => $item['css_class'] ?? '',
            'rel' => $item['rel'] ?? '',
            'visibility' => $item['visibility'] ?? 'all',
        ];
        $errors = [];
        $items = MenuItemsModel::allForMenu($menuId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, $id, $items);
                if (!$errors) {
                    MenuItemsModel::update($id, $values);
                    Logger::info('admin', 'Menu item updated', ['menu_id' => $menuId, 'item_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                    return new Response('', 302, ['Location' => '/admin/menus/' . $menuId . '/items']);
                }
            }
        }

        return $this->render('admin/menu_item_form', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menu' => $menu,
            'values' => $values,
            'errors' => $errors,
            'items' => $items,
            'pages' => PagesModel::listForMenu(),
            'posts' => PostsModel::listForMenu(),
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $id = (int)$request->param('id');
        $item = MenuItemsModel::find($id);
        if (!$item || (int)$item['menu_id'] !== $menuId) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $this->deleteWithChildren($menuId, $id);
            Logger::info('admin', 'Menu item deleted', ['menu_id' => $menuId, 'item_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/menus/' . $menuId . '/items']);
        }

        return $this->render('admin/menu_item_delete', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menu' => $menu,
            'item' => $item,
        ], 'Zulu');
    }

    public function reorder(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $ids = $_POST['item_id'] ?? [];
        $parents = $_POST['parent_id'] ?? [];
        $orders = $_POST['ordering'] ?? [];
        if (!is_array($ids) || !is_array($parents) || !is_array($orders)) {
            return new Response('Invalid payload', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $items = [];
        foreach ($ids as $index => $id) {
            $items[] = [
                'id' => (int)$id,
                'parent_id' => (int)($parents[$index] ?? 0),
                'ordering' => (int)($orders[$index] ?? 0),
            ];
        }

        $existing = MenuItemsModel::allForMenu($menuId);
        $existingIds = array_map(function ($row) {
            return (int)$row['id'];
        }, $existing);
        foreach ($items as $item) {
            if (!in_array($item['id'], $existingIds, true)) {
                return new Response('Invalid ordering', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
        }

        if (!$this->validateOrdering($items)) {
            return new Response('Invalid ordering', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        MenuItemsModel::updateOrdering($menuId, $items);
        Logger::info('admin', 'Menu items reordered', ['menu_id' => $menuId, 'user_id' => (int)Session::get('user_id', 0)]);
        return new Response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function move(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $menuId = (int)$request->param('menu_id');
        $menu = MenusModel::find($menuId);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $id = (int)$request->param('id');
        $direction = $_POST['direction'] ?? '';
        $items = MenuItemsModel::allForMenu($menuId);
        $map = [];
        foreach ($items as $item) {
            $map[(int)$item['id']] = $item;
        }
        if (!isset($map[$id])) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $current = $map[$id];
        $siblings = [];
        foreach ($items as $item) {
            if ((int)($item['parent_id'] ?? 0) === (int)($current['parent_id'] ?? 0)) {
                $siblings[] = $item;
            }
        }
        usort($siblings, function ($a, $b) {
            return ((int)$a['ordering']) <=> ((int)$b['ordering']);
        });
        $index = null;
        foreach ($siblings as $idx => $sibling) {
            if ((int)$sibling['id'] === $id) {
                $index = $idx;
                break;
            }
        }
        if ($index === null) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($siblings[$swapIndex])) {
            return new Response('', 302, ['Location' => '/admin/menus/' . $menuId . '/items']);
        }
        $currentItem = $siblings[$index];
        $swapItem = $siblings[$swapIndex];
        $itemsToUpdate = [
            ['id' => (int)$currentItem['id'], 'parent_id' => (int)($currentItem['parent_id'] ?? 0), 'ordering' => (int)$swapItem['ordering']],
            ['id' => (int)$swapItem['id'], 'parent_id' => (int)($swapItem['parent_id'] ?? 0), 'ordering' => (int)$currentItem['ordering']],
        ];
        MenuItemsModel::updateOrdering($menuId, $itemsToUpdate);
        Logger::info('admin', 'Menu item moved', ['menu_id' => $menuId, 'item_id' => $id, 'direction' => $direction, 'user_id' => (int)Session::get('user_id', 0)]);
        return new Response('', 302, ['Location' => '/admin/menus/' . $menuId . '/items']);
    }

    private function defaultValues(int $menuId): array
    {
        return [
            'menu_id' => $menuId,
            'type' => 'url',
            'title' => '',
            'parent_id' => 0,
            'ordering' => 0,
            'is_enabled' => 1,
            'url' => '',
            'target' => '',
            'page_id' => 0,
            'post_id' => 0,
            'css_class' => '',
            'rel' => '',
            'visibility' => 'all',
        ];
    }

    private function collect(array $values): array
    {
        $values['type'] = trim($_POST['type'] ?? 'url');
        $values['title'] = trim($_POST['title'] ?? '');
        $values['parent_id'] = (int)($_POST['parent_id'] ?? 0);
        $values['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        $values['url'] = trim($_POST['url'] ?? '');
        $values['target'] = trim($_POST['target'] ?? '');
        $values['page_id'] = (int)($_POST['page_id'] ?? 0);
        $values['post_id'] = (int)($_POST['post_id'] ?? 0);
        $values['css_class'] = trim($_POST['css_class'] ?? '');
        $values['rel'] = trim($_POST['rel'] ?? '');
        $values['visibility'] = trim($_POST['visibility'] ?? 'all');
        return $values;
    }

    private function validate(array $values, int $id, array $items): array
    {
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = Lang::get('FFCMS_REQUIRED');
        }
        if (!in_array($values['type'], ['url', 'page', 'post'], true)) {
            $errors['type'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['type'] === 'url') {
            if ($values['url'] === '' || !Url::isSafe($values['url'])) {
                $errors['url'] = Lang::get('FFCMS_URL_INVALID');
            }
        }
        if ($values['type'] === 'page' && $values['page_id'] <= 0) {
            $errors['page_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['type'] === 'page' && $values['page_id'] > 0 && !PagesModel::find((int)$values['page_id'])) {
            $errors['page_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['type'] === 'post' && $values['post_id'] <= 0) {
            $errors['post_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['type'] === 'post' && $values['post_id'] > 0 && !PostsModel::find((int)$values['post_id'])) {
            $errors['post_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['css_class'] !== '' && !preg_match('/^[A-Za-z0-9\\s_-]+$/', $values['css_class'])) {
            $errors['css_class'] = Lang::get('FFCMS_CLASS_INVALID');
        }
        if ($values['rel'] !== '' && !preg_match('/^[A-Za-z0-9\\s_-]+$/', $values['rel'])) {
            $errors['rel'] = Lang::get('FFCMS_REL_INVALID');
        }
        if ($values['target'] !== '' && !in_array($values['target'], ['_self', '_blank'], true)) {
            $errors['target'] = Lang::get('FFCMS_TARGET_INVALID');
        }
        if (!in_array($values['visibility'], ['all', 'logged_in', 'logged_out', 'none'], true)) {
            $errors['visibility'] = Lang::get('FFCMS_REQUIRED');
        }

        $parentId = $values['parent_id'];
        if ($parentId > 0) {
            if ($parentId === $id) {
                $errors['parent_id'] = Lang::get('FFCMS_MENU_CYCLE');
            } else {
                $depth = $this->computeDepth($items, $parentId);
                if ($depth >= 3) {
                    $errors['parent_id'] = Lang::get('FFCMS_MENU_DEPTH');
                }
                if ($this->isDescendant($items, $parentId, $id)) {
                    $errors['parent_id'] = Lang::get('FFCMS_MENU_CYCLE');
                }
            }
        }
        return $errors;
    }

    private function computeDepth(array $items, int $id): int
    {
        $map = [];
        foreach ($items as $item) {
            $map[(int)$item['id']] = (int)($item['parent_id'] ?? 0);
        }
        $depth = 1;
        $current = $id;
        $seen = [];
        while ($current > 0 && isset($map[$current]) && !isset($seen[$current])) {
            $seen[$current] = true;
            $parent = $map[$current];
            if ($parent > 0) {
                $depth++;
            }
            $current = $parent;
        }
        return $depth;
    }

    private function isDescendant(array $items, int $parentId, int $childId): bool
    {
        if ($childId <= 0) {
            return false;
        }
        $map = [];
        foreach ($items as $item) {
            $map[(int)$item['id']] = (int)($item['parent_id'] ?? 0);
        }
        $current = $parentId;
        $seen = [];
        while ($current > 0 && isset($map[$current]) && !isset($seen[$current])) {
            if ($current === $childId) {
                return true;
            }
            $seen[$current] = true;
            $current = $map[$current];
        }
        return false;
    }

    private function nextOrdering(array $items, int $parentId): int
    {
        $max = 0;
        foreach ($items as $item) {
            if ((int)($item['parent_id'] ?? 0) === $parentId) {
                $max = max($max, (int)($item['ordering'] ?? 0));
            }
        }
        return $max + 1;
    }

    private function buildTree(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $item['invalid'] = $this->isInvalidItem($item);
            $map[(int)$item['id']] = $item;
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

    private function isInvalidItem(array $item): bool
    {
        if (($item['type'] ?? '') === 'page') {
            return empty($item['page_slug']);
        }
        if (($item['type'] ?? '') === 'post') {
            return empty($item['post_slug']);
        }
        return false;
    }

    private function deleteWithChildren(int $menuId, int $id): void
    {
        $items = MenuItemsModel::allForMenu($menuId);
        $children = [];
        foreach ($items as $item) {
            $parentId = (int)($item['parent_id'] ?? 0);
            if ($parentId === $id) {
                $children[] = (int)$item['id'];
            }
        }
        foreach ($children as $childId) {
            $this->deleteWithChildren($menuId, $childId);
        }
        MenuItemsModel::delete($id);
    }

    private function validateOrdering(array $items): bool
    {
        $map = [];
        foreach ($items as $item) {
            $map[$item['id']] = $item['parent_id'];
        }
        foreach ($items as $item) {
            $current = $item['id'];
            $depth = 1;
            $seen = [];
            while (isset($map[$current]) && $map[$current] > 0) {
                if (isset($seen[$current])) {
                    return false;
                }
                $seen[$current] = true;
                $current = $map[$current];
                $depth++;
                if ($depth > 3) {
                    return false;
                }
            }
        }
        return true;
    }
}
