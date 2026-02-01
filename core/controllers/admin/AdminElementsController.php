<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Theme;
use Core\Url;
use Core\Freewrite;
use Core\Models\ElementAssignmentsModel;
use Core\Models\ElementPositionsModel;
use Core\Models\ElementTypesModel;
use Core\Models\ElementsModel;
use Core\Models\MenusModel;
use Core\Models\PagesModel;
use Core\Models\PostsModel;
use Core\Models\CategoriesModel;
use Core\Models\RolesModel;

class AdminElementsController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $context = $this->contextFromRequest();
        $assignment = $this->resolveAssignment($context);
        $elements = $assignment ? ElementsModel::allByAssignment((int)$assignment['id']) : [];

        return $this->render('admin/elements', [
            'page_title' => Lang::get('FFCMS_ELEMENTS'),
            'context' => $context,
            'assignment' => $assignment,
            'assignments' => ElementAssignmentsModel::all($context),
            'elements' => $elements,
        ], 'Zulu');
    }

    public function builder(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $context = $this->contextFromRequest();
        $assignment = $this->resolveAssignment($context);
        $themeKey = $context === 'admin' ? 'zulu' : Theme::activeKey('site');
        Theme::syncPositions($themeKey, $context);
        $positions = ElementPositionsModel::forTheme($themeKey, $context);
        $elements = $assignment ? ElementsModel::allByAssignment((int)$assignment['id']) : [];

        return $this->render('admin/elements_builder', [
            'page_title' => Lang::get('FFCMS_ELEMENTS_BUILDER'),
            'context' => $context,
            'assignment' => $assignment,
            'assignments' => ElementAssignmentsModel::all($context),
            'positions' => $positions,
            'elements' => $elements,
        ], 'Zulu');
    }

    public function assignments(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $context = $this->contextFromRequest();
        return $this->render('admin/element_assignments', [
            'page_title' => Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'),
            'context' => $context,
            'assignments' => ElementAssignmentsModel::all($context),
        ], 'Zulu');
    }

    public function assignmentCreate(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $values = [
            'context' => $this->contextFromRequest(),
            'target_type' => 'global',
            'target_value' => '',
            'is_enabled' => 1,
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collectAssignment($values);
                $errors = $this->validateAssignment($values, 0);
                if (!$errors) {
                    $values['created_by'] = (int)Session::get('user_id', 0);
                    $assignmentId = ElementAssignmentsModel::create($values);
                    Logger::info('admin', 'Element assignment created', ['assignment_id' => $assignmentId, 'user_id' => $values['created_by']]);
                    return new Response('', 302, ['Location' => '/admin/elements/assignments?context=' . $values['context']]);
                }
            }
        }

        return $this->render('admin/element_assignment_form', [
            'page_title' => Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => false,
        ], 'Zulu');
    }

    public function assignmentEdit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $id = (int)$request->param('id');
        $assignment = ElementAssignmentsModel::find($id);
        if (!$assignment) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'context' => $assignment['context'] ?? 'site',
            'target_type' => $assignment['target_type'] ?? 'global',
            'target_value' => $assignment['target_value'] ?? '',
            'is_enabled' => (int)($assignment['is_enabled'] ?? 1),
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collectAssignment($values);
                $errors = $this->validateAssignment($values, $id);
                if (!$errors) {
                    ElementAssignmentsModel::update($id, $values);
                    Logger::info('admin', 'Element assignment updated', ['assignment_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                    return new Response('', 302, ['Location' => '/admin/elements/assignments?context=' . $values['context']]);
                }
            }
        }

        return $this->render('admin/element_assignment_form', [
            'page_title' => Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => true,
        ], 'Zulu');
    }

    public function assignmentDelete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $id = (int)$request->param('id');
        $assignment = ElementAssignmentsModel::find($id);
        if (!$assignment) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            ElementAssignmentsModel::delete($id);
            Logger::info('admin', 'Element assignment deleted', ['assignment_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/elements/assignments?context=' . ($assignment['context'] ?? 'site')]);
        }

        return $this->render('admin/element_assignment_delete', [
            'page_title' => Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'),
            'assignment' => $assignment,
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $context = $this->contextFromRequest();
        $themeKey = $context === 'admin' ? 'zulu' : Theme::activeKey('site');
        Theme::syncPositions($themeKey, $context);

        $values = [
            'element_type_id' => 0,
            'title' => '',
            'is_published' => 1,
            'ordering' => 0,
            'template_position_id' => 0,
            'assignment_id' => 0,
            'classes' => '',
            'condition_enabled' => 0,
            'condition_op' => 'ALL',
            'condition_login' => '',
            'roles_any' => [],
            'roles_all' => [],
            'settings' => [],
        ];
        $errors = [];
        $assignments = ElementAssignmentsModel::all($context);
        $types = $this->filterElementTypes();
        $positions = ElementPositionsModel::forTheme($themeKey, $context);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collectElement($values);
                $errors = $this->validateElement($values, $types, $positions);
                if (!$errors) {
                    $values['config_xml'] = $this->buildConfigXml($values);
                    $values['created_by'] = (int)Session::get('user_id', 0);
                    $values['modified_by'] = $values['created_by'];
                    $elementId = ElementsModel::create($values);
                    Logger::info('admin', 'Element created', ['element_id' => $elementId, 'user_id' => $values['created_by']]);
                    return new Response('', 302, ['Location' => '/admin/elements?context=' . $context . '&assignment_id=' . (int)$values['assignment_id']]);
                }
            }
        }

        return $this->render('admin/element_form', [
            'page_title' => Lang::get('FFCMS_ELEMENTS'),
            'context' => $context,
            'values' => $values,
            'errors' => $errors,
            'types' => $types,
            'assignments' => $assignments,
            'positions' => $positions,
            'roles' => RolesModel::all(),
            'menus' => MenusModel::all(),
            'pages' => PagesModel::listForMenu(),
            'posts' => PostsModel::listForMenu(),
            'categories' => CategoriesModel::all(),
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $id = (int)$request->param('id');
        $element = ElementsModel::find($id);
        if (!$element) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $typeKey = $this->typeKeyFromId((int)$element['element_type_id']);
        if ($typeKey === 'html' && !$this->canUseHtml()) {
            return new Response(Lang::get('FFCMS_FORBIDDEN'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if ($typeKey === 'embed' && !$this->canUseEmbed()) {
            return new Response(Lang::get('FFCMS_FORBIDDEN'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $assignment = ElementAssignmentsModel::find((int)$element['assignment_id']);
        $context = $assignment['context'] ?? 'site';
        $themeKey = $context === 'admin' ? 'zulu' : Theme::activeKey('site');
        Theme::syncPositions($themeKey, $context);
        $positions = ElementPositionsModel::forTheme($themeKey, $context);
        $types = $this->filterElementTypes();
        $config = $this->parseConfigXml((string)($element['config_xml'] ?? ''));

        $values = [
            'element_type_id' => (int)($element['element_type_id'] ?? 0),
            'title' => $element['title'] ?? '',
            'is_published' => (int)($element['is_published'] ?? 1),
            'ordering' => (int)($element['ordering'] ?? 0),
            'template_position_id' => (int)($element['template_position_id'] ?? 0),
            'assignment_id' => (int)($element['assignment_id'] ?? 0),
            'classes' => $config['classes'],
            'condition_enabled' => $config['conditions']['enabled'] ?? 0,
            'condition_op' => $config['conditions']['op'] ?? 'ALL',
            'condition_login' => $config['conditions']['login'] ?? '',
            'roles_any' => $config['conditions']['roles_any'] ?? [],
            'roles_all' => $config['conditions']['roles_all'] ?? [],
            'settings' => $config['settings'],
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collectElement($values);
                $errors = $this->validateElement($values, $types, $positions);
                if (!$errors) {
                    $values['config_xml'] = $this->buildConfigXml($values);
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    ElementsModel::update($id, $values);
                    Logger::info('admin', 'Element updated', ['element_id' => $id, 'user_id' => $values['modified_by']]);
                    return new Response('', 302, ['Location' => '/admin/elements?context=' . $context . '&assignment_id=' . (int)$values['assignment_id']]);
                }
            }
        }

        return $this->render('admin/element_form', [
            'page_title' => Lang::get('FFCMS_ELEMENTS'),
            'context' => $context,
            'values' => $values,
            'errors' => $errors,
            'types' => $types,
            'assignments' => ElementAssignmentsModel::all($context),
            'positions' => $positions,
            'roles' => RolesModel::all(),
            'menus' => MenusModel::all(),
            'pages' => PagesModel::listForMenu(),
            'posts' => PostsModel::listForMenu(),
            'categories' => CategoriesModel::all(),
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        $id = (int)$request->param('id');
        $element = ElementsModel::find($id);
        if (!$element) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            ElementsModel::delete($id);
            Logger::info('admin', 'Element deleted', ['element_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/elements']);
        }

        return $this->render('admin/element_delete', [
            'page_title' => Lang::get('FFCMS_ELEMENTS'),
            'element' => $element,
        ], 'Zulu');
    }

    public function toggle(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $id = (int)$request->param('id');
        $element = ElementsModel::find($id);
        if (!$element) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $newState = empty($element['is_published']) ? 1 : 0;
        ElementsModel::setPublished($id, $newState);
        Logger::info('admin', 'Element publish toggled', ['element_id' => $id, 'state' => $newState, 'user_id' => (int)Session::get('user_id', 0)]);
        return new Response('', 302, ['Location' => '/admin/elements']);
    }

    public function move(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_elements');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $id = (int)$request->param('id');
        $direction = $_POST['direction'] ?? '';
        $element = ElementsModel::find($id);
        if (!$element) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $assignmentId = (int)($element['assignment_id'] ?? 0);
        $positionId = (int)($element['template_position_id'] ?? 0);
        $elements = ElementsModel::allByAssignment($assignmentId);
        $siblings = [];
        foreach ($elements as $row) {
            if ((int)$row['template_position_id'] === $positionId) {
                $siblings[] = $row;
            }
        }
        usort($siblings, function ($a, $b) {
            return ((int)$a['ordering']) <=> ((int)$b['ordering']);
        });
        $index = null;
        foreach ($siblings as $idx => $row) {
            if ((int)$row['id'] === $id) {
                $index = $idx;
                break;
            }
        }
        if ($index === null) {
            return new Response('', 302, ['Location' => '/admin/elements']);
        }
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($siblings[$swapIndex])) {
            return new Response('', 302, ['Location' => '/admin/elements']);
        }
        $current = $siblings[$index];
        $swap = $siblings[$swapIndex];
        ElementsModel::updateOrdering([
            ['id' => (int)$current['id'], 'ordering' => (int)$swap['ordering'], 'template_position_id' => $positionId],
            ['id' => (int)$swap['id'], 'ordering' => (int)$current['ordering'], 'template_position_id' => $positionId],
        ]);
        Logger::info('admin', 'Element moved', ['element_id' => $id, 'direction' => $direction, 'user_id' => (int)Session::get('user_id', 0)]);
        return new Response('', 302, ['Location' => '/admin/elements/builder']);
    }

    private function contextFromRequest(): string
    {
        $context = $_GET['context'] ?? 'site';
        return $context === 'admin' ? 'admin' : 'site';
    }

    private function resolveAssignment(string $context): ?array
    {
        $assignments = ElementAssignmentsModel::all($context);
        
        // If no assignments exist, create a global one
        if (!$assignments) {
            $assignmentId = ElementAssignmentsModel::create([
                'context' => $context,
                'target_type' => 'global',
                'target_value' => '',
                'is_enabled' => 1,
                'created_by' => (int)Session::get('user_id', 0),
            ]);
            return ElementAssignmentsModel::find($assignmentId);
        }
        
        $selected = (int)($_GET['assignment_id'] ?? 0);
        foreach ($assignments as $assignment) {
            if ((int)$assignment['id'] === $selected) {
                return $assignment;
            }
        }
        return $assignments[0] ?? null;
    }

    private function filterElementTypes(): array
    {
        $types = ElementTypesModel::allEnabled();
        $canHtml = \Core\ACL::can('use_block_html');
        $canEmbed = \Core\ACL::can('use_block_embed');
        $filtered = [];
        foreach ($types as $type) {
            $key = $type['type_key'] ?? '';
            if ($key === 'html' && !$canHtml) {
                continue;
            }
            if ($key === 'embed' && !$canEmbed) {
                continue;
            }
            $filtered[] = $type;
        }
        return $filtered;
    }

    private function collectAssignment(array $values): array
    {
        $values['context'] = $_POST['context'] ?? $values['context'];
        $values['context'] = $values['context'] === 'admin' ? 'admin' : 'site';
        $values['target_type'] = $_POST['target_type'] ?? 'global';
        $values['target_value'] = trim($_POST['target_value'] ?? '');
        $values['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        return $values;
    }

    private function validateAssignment(array $values, int $id): array
    {
        $errors = [];
        $type = $values['target_type'];
        if (!in_array($type, ['global', 'route', 'route_prefix'], true)) {
            $errors['target_type'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($type !== 'global' && $values['target_value'] === '') {
            $errors['target_value'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($type !== 'global' && $values['target_value'] !== '' && $values['target_value'][0] !== '/') {
            $errors['target_value'] = Lang::get('FFCMS_ROUTE_INVALID');
        }
        if ($type === 'global') {
            $existing = ElementAssignmentsModel::all($values['context']);
            foreach ($existing as $assignment) {
                if ($assignment['target_type'] === 'global' && (int)$assignment['id'] !== $id) {
                    $errors['target_type'] = Lang::get('FFCMS_ASSIGNMENT_GLOBAL_EXISTS');
                    break;
                }
            }
        }
        return $errors;
    }

    private function collectElement(array $values): array
    {
        $values['element_type_id'] = (int)($_POST['element_type_id'] ?? 0);
        $values['title'] = trim($_POST['title'] ?? '');
        $values['is_published'] = isset($_POST['is_published']) ? 1 : 0;
        $values['ordering'] = (int)($_POST['ordering'] ?? 0);
        $values['template_position_id'] = (int)($_POST['template_position_id'] ?? 0);
        $values['assignment_id'] = (int)($_POST['assignment_id'] ?? 0);
        $values['classes'] = $this->sanitizeClasses(trim($_POST['classes'] ?? ''));
        $values['condition_enabled'] = isset($_POST['condition_enabled']) ? 1 : 0;
        $values['condition_op'] = $_POST['condition_op'] ?? 'ALL';
        $values['condition_login'] = $_POST['condition_login'] ?? '';
        $values['roles_any'] = $_POST['roles_any'] ?? [];
        $values['roles_all'] = $_POST['roles_all'] ?? [];
        $values['settings'] = [
            'menu_key' => trim($_POST['menu_key'] ?? ''),
            'menu_style' => trim($_POST['menu_style'] ?? 'navbar'),
            'menu_depth' => (string)(int)($_POST['menu_depth'] ?? 2),
            'page_mode' => trim($_POST['page_mode'] ?? 'all'),
            'page_id' => (int)($_POST['page_id'] ?? 0),
            'post_mode' => trim($_POST['post_mode'] ?? 'all'),
            'post_id' => (int)($_POST['post_id'] ?? 0),
            'category_mode' => trim($_POST['category_mode'] ?? 'all'),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'html' => trim($_POST['html'] ?? ''),
            'embed_url' => trim($_POST['embed_url'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'url_label' => trim($_POST['url_label'] ?? ''),
            'image_src' => trim($_POST['image_src'] ?? ''),
            'image_alt' => trim($_POST['image_alt'] ?? ''),
            'image_width' => (int)($_POST['image_width'] ?? 0),
            'image_height' => (int)($_POST['image_height'] ?? 0),
            'text_content' => trim($_POST['text_content'] ?? ''),
            'button_label' => trim($_POST['button_label'] ?? ''),
            'button_url' => trim($_POST['button_url'] ?? ''),
            'button_style' => trim($_POST['button_style'] ?? 'primary'),
        ];
        return $values;
    }

    private function validateElement(array $values, array $types, array $positions): array
    {
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = Lang::get('FFCMS_REQUIRED');
        }
        $typeIds = array_map('intval', array_column($types, 'id'));
        $values['element_type_id'] = (int)$values['element_type_id'];
        file_put_contents(__DIR__ . '/../../../storage/logs/debug.log', 'Validating type: ' . $values['element_type_id'] . ' in ' . json_encode($typeIds) . PHP_EOL, FILE_APPEND);
        Logger::info('admin', 'Validating element type', ['element_type_id' => $values['element_type_id'], 'typeIds' => $typeIds]);
        if (!in_array($values['element_type_id'], $typeIds, true)) {
            $errors['element_type_id'] = 'Element type is required';
        }
        $positionIds = array_map('intval', array_column($positions, 'id'));
        $values['template_position_id'] = (int)$values['template_position_id'];
        file_put_contents(__DIR__ . '/../../../storage/logs/debug.log', 'Validating position: ' . $values['template_position_id'] . ' in ' . json_encode($positionIds) . PHP_EOL, FILE_APPEND);
        Logger::info('admin', 'Validating element position', ['template_position_id' => $values['template_position_id'], 'positionIds' => $positionIds]);
        if (!in_array($values['template_position_id'], $positionIds, true)) {
            $errors['template_position_id'] = 'Position is required';
        }
        if ($values['assignment_id'] <= 0) {
            $errors['assignment_id'] = 'Assignment is required';
        }

        $typeKey = '';
        foreach ($types as $type) {
            if ((int)$type['id'] === $values['element_type_id']) {
                $typeKey = $type['type_key'];
                break;
            }
        }
        $settings = $values['settings'];
        if ($typeKey === 'menu' && $settings['menu_key'] === '') {
            $errors['menu_key'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'menu') {
            $depth = (int)$settings['menu_depth'];
            if ($depth < 1 || $depth > 3) {
                $errors['menu_depth'] = Lang::get('FFCMS_MENU_DEPTH');
            }
            if (!in_array($settings['menu_style'], ['navbar', 'vertical', 'tabs', 'pills'], true)) {
                $errors['menu_style'] = Lang::get('FFCMS_REQUIRED');
            }
        }
        if ($typeKey === 'pages' && $settings['page_mode'] === 'one' && $settings['page_id'] <= 0) {
            $errors['page_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'pages' && !in_array($settings['page_mode'], ['all', 'top', 'featured', 'latest', 'one'], true)) {
            $errors['page_mode'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'posts' && $settings['post_mode'] === 'one' && $settings['post_id'] <= 0) {
            $errors['post_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'posts' && !in_array($settings['post_mode'], ['all', 'top', 'featured', 'latest', 'one'], true)) {
            $errors['post_mode'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'categories' && $settings['category_mode'] === 'one' && $settings['category_id'] <= 0) {
            $errors['category_id'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'categories' && !in_array($settings['category_mode'], ['all', 'one'], true)) {
            $errors['category_mode'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'embed' && !Url::isSafe($settings['embed_url'])) {
            $errors['embed_url'] = Lang::get('FFCMS_URL_INVALID');
        }
        if ($typeKey === 'url' && !Url::isSafe($settings['url'])) {
            $errors['url'] = Lang::get('FFCMS_URL_INVALID');
        }
        if ($typeKey === 'image' && $settings['image_src'] === '') {
            $errors['image_src'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'image' && !Url::isSafe($settings['image_src'])) {
            $errors['image_src'] = Lang::get('FFCMS_URL_INVALID');
        }
        if ($typeKey === 'text' && $settings['text_content'] === '') {
            $errors['text_content'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'button' && $settings['button_label'] === '') {
            $errors['button_label'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($typeKey === 'button' && !Url::isSafe($settings['button_url'])) {
            $errors['button_url'] = Lang::get('FFCMS_URL_INVALID');
        }
        if ($typeKey === 'html' && !$this->canUseHtml()) {
            $errors['html'] = Lang::get('FFCMS_FORBIDDEN');
        }
        if ($typeKey === 'embed' && !$this->canUseEmbed()) {
            $errors['embed_url'] = Lang::get('FFCMS_FORBIDDEN');
        }
        return $errors;
    }

    private function buildConfigXml(array $values): string
    {
        $typeKey = $this->typeKeyFromId($values['element_type_id']);
        $settings = $values['settings'];
        $classes = $values['classes'];
        $xml = new \SimpleXMLElement('<element></element>');
        $settingsNode = $xml->addChild('settings');
        $styleNode = $xml->addChild('style');
        $styleNode->addAttribute('classes', $classes);

        if ($typeKey === 'menu') {
            $node = $settingsNode->addChild('menu');
            $node->addAttribute('key', $settings['menu_key']);
            $node->addAttribute('style', $settings['menu_style']);
            $node->addAttribute('depth', $settings['menu_depth']);
        } elseif ($typeKey === 'pages') {
            $node = $settingsNode->addChild('pages');
            $node->addAttribute('mode', $settings['page_mode']);
            $node->addAttribute('page_id', (string)$settings['page_id']);
        } elseif ($typeKey === 'posts') {
            $node = $settingsNode->addChild('posts');
            $node->addAttribute('mode', $settings['post_mode']);
            $node->addAttribute('post_id', (string)$settings['post_id']);
        } elseif ($typeKey === 'categories') {
            $node = $settingsNode->addChild('categories');
            $node->addAttribute('mode', $settings['category_mode']);
            $node->addAttribute('category_id', (string)$settings['category_id']);
        } elseif ($typeKey === 'html') {
            $node = $settingsNode->addChild('html');
            $node[0] = htmlspecialchars(Freewrite::sanitizeHtmlFragment($settings['html']), ENT_QUOTES, 'UTF-8');
        } elseif ($typeKey === 'embed') {
            $node = $settingsNode->addChild('embed');
            $node->addAttribute('url', $settings['embed_url']);
        } elseif ($typeKey === 'url') {
            $node = $settingsNode->addChild('url');
            $node->addAttribute('url', $settings['url']);
            $node->addAttribute('label', $settings['url_label']);
        } elseif ($typeKey === 'image') {
            $node = $settingsNode->addChild('image');
            $node->addAttribute('src', $settings['image_src']);
            $node->addAttribute('alt', $settings['image_alt']);
            $node->addAttribute('width', (string)$settings['image_width']);
            $node->addAttribute('height', (string)$settings['image_height']);
        } elseif ($typeKey === 'text') {
            $node = $settingsNode->addChild('text');
            $node[0] = htmlspecialchars($settings['text_content'], ENT_QUOTES, 'UTF-8');
        } elseif ($typeKey === 'button') {
            $node = $settingsNode->addChild('button');
            $node->addAttribute('label', $settings['button_label']);
            $node->addAttribute('url', $settings['button_url']);
            $node->addAttribute('style', $settings['button_style']);
        }

        if (!empty($values['condition_enabled'])) {
            $condNode = $xml->addChild('conditions');
            $condNode->addAttribute('enabled', '1');
            $condNode->addAttribute('op', $values['condition_op']);
            if ($values['condition_login'] !== '') {
                $rule = $condNode->addChild('rule');
                $rule->addAttribute('type', 'user_logged_in');
                $rule->addAttribute('value', $values['condition_login']);
            }
            $rolesAny = array_filter(array_map('intval', (array)$values['roles_any']));
            if ($rolesAny) {
                $rule = $condNode->addChild('rule');
                $rule->addAttribute('type', 'role_any');
                $rule->addAttribute('value', implode(',', $rolesAny));
            }
            $rolesAll = array_filter(array_map('intval', (array)$values['roles_all']));
            if ($rolesAll) {
                $rule = $condNode->addChild('rule');
                $rule->addAttribute('type', 'role_all');
                $rule->addAttribute('value', implode(',', $rolesAll));
            }
        } else {
            $condNode = $xml->addChild('conditions');
            $condNode->addAttribute('enabled', '0');
            $condNode->addAttribute('op', 'ALL');
        }

        return $xml->asXML() ?: '<element></element>';
    }

    private function parseConfigXml(string $xml): array
    {
        $config = [
            'classes' => '',
            'conditions' => [
                'enabled' => 0,
                'op' => 'ALL',
                'login' => '',
                'roles_any' => [],
                'roles_all' => [],
            ],
            'settings' => [],
        ];
        if ($xml === '') {
            return $config;
        }
        $data = @simplexml_load_string($xml);
        if ($data === false) {
            return $config;
        }
        if (isset($data->style)) {
            $config['classes'] = (string)($data->style['classes'] ?? '');
        }
        if (isset($data->conditions)) {
            $config['conditions']['enabled'] = (int)($data->conditions['enabled'] ?? 0);
            $config['conditions']['op'] = (string)($data->conditions['op'] ?? 'ALL');
            foreach ($data->conditions->rule as $rule) {
                $type = (string)($rule['type'] ?? '');
                $value = (string)($rule['value'] ?? '');
                if ($type === 'user_logged_in') {
                    $config['conditions']['login'] = $value;
                } elseif ($type === 'role_any') {
                    $config['conditions']['roles_any'] = array_filter(array_map('intval', explode(',', $value)));
                } elseif ($type === 'role_all') {
                    $config['conditions']['roles_all'] = array_filter(array_map('intval', explode(',', $value)));
                }
            }
        }
        if (isset($data->settings)) {
            foreach ($data->settings->children() as $child) {
                $name = $child->getName();
                if ($name === 'menu') {
                    $config['settings']['menu_key'] = (string)($child['key'] ?? '');
                    $config['settings']['menu_style'] = (string)($child['style'] ?? 'navbar');
                    $config['settings']['menu_depth'] = (string)($child['depth'] ?? '2');
                } elseif ($name === 'pages') {
                    $config['settings']['page_mode'] = (string)($child['mode'] ?? 'all');
                    $config['settings']['page_id'] = (int)($child['page_id'] ?? 0);
                } elseif ($name === 'posts') {
                    $config['settings']['post_mode'] = (string)($child['mode'] ?? 'all');
                    $config['settings']['post_id'] = (int)($child['post_id'] ?? 0);
                } elseif ($name === 'categories') {
                    $config['settings']['category_mode'] = (string)($child['mode'] ?? 'all');
                    $config['settings']['category_id'] = (int)($child['category_id'] ?? 0);
                } elseif ($name === 'embed') {
                    $config['settings']['embed_url'] = (string)($child['url'] ?? '');
                } elseif ($name === 'url') {
                    $config['settings']['url'] = (string)($child['url'] ?? '');
                    $config['settings']['url_label'] = (string)($child['label'] ?? '');
                } elseif ($name === 'image') {
                    $config['settings']['image_src'] = (string)($child['src'] ?? '');
                    $config['settings']['image_alt'] = (string)($child['alt'] ?? '');
                    $config['settings']['image_width'] = (int)($child['width'] ?? 0);
                    $config['settings']['image_height'] = (int)($child['height'] ?? 0);
                } elseif ($name === 'text') {
                    $config['settings']['text_content'] = (string)$child;
                } elseif ($name === 'button') {
                    $config['settings']['button_label'] = (string)($child['label'] ?? '');
                    $config['settings']['button_url'] = (string)($child['url'] ?? '');
                    $config['settings']['button_style'] = (string)($child['style'] ?? 'primary');
                } elseif ($name === 'html') {
                    $config['settings']['html'] = (string)$child;
                }
            }
        }
        return $config;
    }

    private function typeKeyFromId(int $id): string
    {
        $type = ElementTypesModel::find($id);
        return $type['type_key'] ?? '';
    }

    private function sanitizeClasses(string $classes): string
    {
        if ($classes === '') {
            return '';
        }
        $tokens = preg_split('/\\s+/', $classes);
        $allowed = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (preg_match('/^ff-[a-z0-9-]+$/', $token)) {
                $allowed[] = $token;
                continue;
            }
            if (in_array($token, ['container', 'container-fluid', 'rounded', 'rounded-0', 'rounded-1', 'rounded-2', 'rounded-3', 'rounded-pill', 'shadow', 'shadow-sm', 'shadow-lg'], true)) {
                $allowed[] = $token;
                continue;
            }
            if (preg_match('/^(text|bg)-(primary|secondary|success|danger|warning|info|light|dark|muted|white)$/', $token)) {
                $allowed[] = $token;
                continue;
            }
            if (preg_match('/^(m|mt|mb|ms|me|mx|my|p|pt|pb|ps|pe|px|py)-[0-5]$/', $token)) {
                $allowed[] = $token;
                continue;
            }
            if (preg_match('/^w-(25|50|75|100)$/', $token)) {
                $allowed[] = $token;
            }
        }
        return implode(' ', array_unique($allowed));
    }

    private function canUseHtml(): bool
    {
        return \Core\ACL::can('use_block_html');
    }

    private function canUseEmbed(): bool
    {
        return \Core\ACL::can('use_block_embed');
    }
}
