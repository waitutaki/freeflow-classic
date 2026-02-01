<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$theme = $theme ?? [];
$definition = $definition ?? ['regions' => [], 'tokens' => []];
$overrides = $overrides ?? [];
$themeKey = (string)($theme['theme_key'] ?? '');
$context = (string)($theme['context'] ?? 'site');
$previewUrl = $context === 'admin' ? '/admin/' : '/';
?>
<div class="container-fluid ff-customizer" data-theme-key="<?php echo Template::escape($themeKey); ?>" data-context="<?php echo Template::escape($context); ?>" data-save-url="/admin/themes/customize/save" data-save-all-url="/admin/themes/customize/save-all" data-preview-url="<?php echo Template::escape($previewUrl); ?>" style="height: 100vh; display: flex; flex-direction: column;">
    <div class="zulu-toolbar-row">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group">
                <button class="btn btn-primary" form="theme-customizer-form" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                    <?php echo Lang::get('FFCMS_SAVE'); ?>
                </button>
                <a class="btn btn-outline-secondary" href="/admin/themes">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                    <?php echo Lang::get('FFCMS_CLOSE'); ?>
                </a>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="ff-autosave-toggle" checked>
                <label class="form-check-label" for="ff-autosave-toggle"><?php echo Lang::get('FFCMS_AUTOSAVE'); ?></label>
            </div>
        </div>
    </div>

    <div class="row g-0 align-items-stretch" style="flex: 1; overflow: hidden;">
        <div class="col-md-5 col-lg-4" style="background: #f1f1f1; border-right: 1px solid #ddd; overflow-y: auto; height: 100%;">
            <form id="theme-customizer-form" method="post" action="/admin/themes/customize/save-all" class="ff-customizer-panel" style="padding: 20px; height: 100%; overflow-y: auto;">
                <?php echo Csrf::input(); ?>
                <input type="hidden" name="theme_key" value="<?php echo Template::escape($themeKey); ?>">
                <input type="hidden" name="context" value="<?php echo Template::escape($context); ?>">
                <div class="accordion" id="ff-customizer-accordion" style="box-shadow: none;">
                    <?php foreach ($definition['regions'] as $index => $region) : ?>
                        <?php
                        $regionId = 'ff-region-' . $index;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="<?php echo Template::escape($regionId); ?>">
                                <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo Template::escape($regionId); ?>-panel">
                                    <?php echo Template::escape($region['label'] ?? $region['key']); ?>
                                </button>
                            </h2>
                            <div id="<?php echo Template::escape($regionId); ?>-panel" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#ff-customizer-accordion">
                                <div class="accordion-body">
                                    <?php if (!empty($region['help'])) : ?>
                                        <p class="text-muted"><?php echo Template::escape($region['help']); ?></p>
                                    <?php endif; ?>
                                    <?php foreach ($region['tokens'] as $token) : ?>
                                        <?php
                                        $tokenKey = $token['key'];
                                        $overrideValue = $overrides[$tokenKey]['value'] ?? '';
                                        $value = $overrideValue !== '' ? $overrideValue : ($token['default'] ?? '');
                                        $label = $token['label'] ?? $tokenKey;
                                        $help = $token['help'] ?? '';
                                        $type = $token['type'] ?? 'text';
                                        $min = $token['min'] ?? '';
                                        $max = $token['max'] ?? '';
                                        $step = $token['step'] ?? '';
                                        $options = $token['options'] ?? [];
                                        ?>
                                        <div class="mb-3">
                                            <label class="form-label" for="token_<?php echo Template::escape($tokenKey); ?>"><?php echo Template::escape($label); ?></label>
                                            <?php if ($type === 'select' && !empty($options)) : ?>
                                                <select class="form-select ff-customizer-input" id="token_<?php echo Template::escape($tokenKey); ?>" name="token_<?php echo Template::escape($tokenKey); ?>" data-token="<?php echo Template::escape($tokenKey); ?>" data-token-type="<?php echo Template::escape($type); ?>">
                                                    <?php foreach ($options as $option) : ?>
                                                        <option value="<?php echo Template::escape($option['value']); ?>" <?php echo $value === $option['value'] ? 'selected' : ''; ?>><?php echo Template::escape($option['label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($type === 'range') : ?>
                                                <input class="form-range ff-customizer-input" type="range" id="token_<?php echo Template::escape($tokenKey); ?>" name="token_<?php echo Template::escape($tokenKey); ?>" value="<?php echo Template::escape($value); ?>" data-token="<?php echo Template::escape($tokenKey); ?>" data-token-type="<?php echo Template::escape($type); ?>" <?php echo $min !== '' ? 'min="' . Template::escape($min) . '"' : ''; ?> <?php echo $max !== '' ? 'max="' . Template::escape($max) . '"' : ''; ?> <?php echo $step !== '' ? 'step="' . Template::escape($step) . '"' : ''; ?>>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted"><?php echo $min !== '' ? Template::escape($min) : '0'; ?></small>
                                                    <small class="text-muted"><?php echo $max !== '' ? Template::escape($max) : '100'; ?></small>
                                                </div>
                                            <?php elseif ($type === 'color') : ?>
                                                <div class="input-group">
                                                    <input class="form-control ff-customizer-input" type="text" id="token_<?php echo Template::escape($tokenKey); ?>" name="token_<?php echo Template::escape($tokenKey); ?>" value="<?php echo Template::escape($value); ?>" data-token="<?php echo Template::escape($tokenKey); ?>" data-token-type="<?php echo Template::escape($type); ?>" data-token-format="<?php echo Template::escape($token['format'] ?? ''); ?>" data-ff-color-input="1">
                                                    <div class="ff-customizer-color-picker input-group-text" data-ff-color-picker="1" style="cursor: pointer; width: 40px; height: 38px; background-color: <?php echo Template::escape($value); ?>;"></div>
                                                </div>
                                            <?php else : ?>
                                                <input class="form-control ff-customizer-input" type="text" id="token_<?php echo Template::escape($tokenKey); ?>" name="token_<?php echo Template::escape($tokenKey); ?>" value="<?php echo Template::escape($value); ?>" data-token="<?php echo Template::escape($tokenKey); ?>" data-token-type="<?php echo Template::escape($type); ?>" data-token-format="<?php echo Template::escape($token['format'] ?? ''); ?>" <?php echo $type === 'color' ? 'data-ff-color-input="1"' : ''; ?>>
                                                <?php if ($type === 'color') : ?>
                                                    <div class="ff-customizer-color-picker" data-ff-color-picker="1"></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($help) : ?>
                                                <div class="form-text"><?php echo Template::escape($help); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <div class="col-md-7 col-lg-8" style="position: relative;">
            <div class="ff-customizer-preview" style="height: 100%; position: relative;">
                <iframe class="ff-customizer-frame" title="<?php echo Template::escape(Lang::get('FFCMS_PREVIEW')); ?>" src="<?php echo Template::escape($previewUrl); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">Preview</div>
            </div>
        </div>
    </div>
</div>
