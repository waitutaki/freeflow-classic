<?php
use Core\Lang;
use Core\Template;

$contentXml = $content_xml ?? '';
$fieldName = $field_name ?? 'content_xml';
$canHtml = !empty($can_html);
$canEmbed = !empty($can_embed);
$mediaUrl = $media_url ?? '/admin/media/picker?bucket=users';
$docUrl = $doc_url ?? '/admin/documents/picker';
$allowMailContent = !empty($allow_mailcontent);
?>
<div class="ff-freewrite"
    data-ff-freewrite="1"
    data-can-html="<?php echo $canHtml ? '1' : '0'; ?>"
    data-can-embed="<?php echo $canEmbed ? '1' : '0'; ?>"
    data-ff-mail-content="<?php echo $allowMailContent ? '1' : '0'; ?>"
    data-media-url="<?php echo Template::escape($mediaUrl); ?>"
    data-doc-url="<?php echo Template::escape($docUrl); ?>"
    data-msg-select-text="<?php echo Template::escape(Lang::get('FFCMS_FW_SELECT_TEXT')); ?>"
    data-msg-url-required="<?php echo Template::escape(Lang::get('FFCMS_FW_URL_REQUIRED')); ?>"
    data-msg-url-invalid="<?php echo Template::escape(Lang::get('FFCMS_FW_URL_INVALID')); ?>"
    data-msg-grid-limit="<?php echo Template::escape(Lang::get('FFCMS_FW_GRID_LIMIT')); ?>"
    data-msg-block-restricted="<?php echo Template::escape(Lang::get('FFCMS_FW_BLOCK_RESTRICTED')); ?>"
    data-msg-no-blocks="<?php echo Template::escape(Lang::get('FFCMS_FW_EMPTY')); ?>"
    data-label-move-up="<?php echo Template::escape(Lang::get('FFCMS_MOVE_UP')); ?>"
    data-label-move-down="<?php echo Template::escape(Lang::get('FFCMS_MOVE_DOWN')); ?>"
    data-label-duplicate="<?php echo Template::escape(Lang::get('FFCMS_DUPLICATE')); ?>"
    data-label-delete="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>"
    data-label-text="<?php echo Template::escape(Lang::get('FFCMS_TEXT')); ?>"
    data-label-title="<?php echo Template::escape(Lang::get('FFCMS_TITLE')); ?>"
    data-label-url="<?php echo Template::escape(Lang::get('FFCMS_URL')); ?>"
    data-label-description="<?php echo Template::escape(Lang::get('FFCMS_DESCRIPTION')); ?>"
    data-label-label="<?php echo Template::escape(Lang::get('FFCMS_LABEL')); ?>"
    data-label-alt="<?php echo Template::escape(Lang::get('FFCMS_ALT_TEXT')); ?>"
    data-label-caption="<?php echo Template::escape(Lang::get('FFCMS_CAPTION')); ?>"
    data-label-alignment="<?php echo Template::escape(Lang::get('FFCMS_ALIGNMENT')); ?>"
    data-label-size="<?php echo Template::escape(Lang::get('FFCMS_SIZE')); ?>"
    data-label-width="<?php echo Template::escape(Lang::get('FFCMS_WIDTH')); ?>"
    data-label-height="<?php echo Template::escape(Lang::get('FFCMS_HEIGHT')); ?>"
    data-label-level="<?php echo Template::escape(Lang::get('FFCMS_LEVEL')); ?>"
    data-label-style="<?php echo Template::escape(Lang::get('FFCMS_STYLE')); ?>"
    data-label-icon="<?php echo Template::escape(Lang::get('FFCMS_ICON')); ?>"
    data-label-columns="<?php echo Template::escape(Lang::get('FFCMS_COLUMNS')); ?>"
    data-label-rows="<?php echo Template::escape(Lang::get('FFCMS_ROWS')); ?>"
    data-label-cols="<?php echo Template::escape(Lang::get('FFCMS_COLUMNS')); ?>"
    data-label-file="<?php echo Template::escape(Lang::get('FFCMS_FILE')); ?>"
    data-label-add-item="<?php echo Template::escape(Lang::get('FFCMS_ADD_ITEM')); ?>"
    data-label-remove-item="<?php echo Template::escape(Lang::get('FFCMS_REMOVE_ITEM')); ?>"
    data-label-add-tab="<?php echo Template::escape(Lang::get('FFCMS_ADD_TAB')); ?>"
    data-label-add-panel="<?php echo Template::escape(Lang::get('FFCMS_ADD_PANEL')); ?>"
    data-label-add-row="<?php echo Template::escape(Lang::get('FFCMS_ADD_ROW')); ?>"
    data-label-add-col="<?php echo Template::escape(Lang::get('FFCMS_ADD_COLUMN')); ?>"
    data-label-add-block="<?php echo Template::escape(Lang::get('FFCMS_ADD_BLOCK')); ?>"
    data-label-add-image="<?php echo Template::escape(Lang::get('FFCMS_ADD_IMAGE')); ?>"
    data-label-change-image="<?php echo Template::escape(Lang::get('FFCMS_CHANGE_IMAGE')); ?>"
    data-label-add-gallery="<?php echo Template::escape(Lang::get('FFCMS_ADD_GALLERY')); ?>"
    data-label-remove-gallery="<?php echo Template::escape(Lang::get('FFCMS_REMOVE_GALLERY')); ?>"
    data-label-select-file="<?php echo Template::escape(Lang::get('FFCMS_SELECT_FILE')); ?>"
    data-label-change-file="<?php echo Template::escape(Lang::get('FFCMS_CHANGE_FILE')); ?>"
    data-label-align-left="<?php echo Template::escape(Lang::get('FFCMS_ALIGN_LEFT')); ?>"
    data-label-align-center="<?php echo Template::escape(Lang::get('FFCMS_ALIGN_CENTER')); ?>"
    data-label-align-right="<?php echo Template::escape(Lang::get('FFCMS_ALIGN_RIGHT')); ?>"
    data-label-size-small="<?php echo Template::escape(Lang::get('FFCMS_SIZE_SMALL')); ?>"
    data-label-size-medium="<?php echo Template::escape(Lang::get('FFCMS_SIZE_MEDIUM')); ?>"
    data-label-size-large="<?php echo Template::escape(Lang::get('FFCMS_SIZE_LARGE')); ?>"
    data-label-size-full="<?php echo Template::escape(Lang::get('FFCMS_SIZE_FULL')); ?>"
    data-label-style-primary="<?php echo Template::escape(Lang::get('FFCMS_STYLE_PRIMARY')); ?>"
    data-label-style-secondary="<?php echo Template::escape(Lang::get('FFCMS_STYLE_SECONDARY')); ?>"
    data-label-style-outline="<?php echo Template::escape(Lang::get('FFCMS_STYLE_OUTLINE')); ?>"
    data-label-icon-solid="<?php echo Template::escape(Lang::get('FFCMS_ICON_SOLID')); ?>"
    data-label-icon-regular="<?php echo Template::escape(Lang::get('FFCMS_ICON_REGULAR')); ?>"
    data-label-icon-brands="<?php echo Template::escape(Lang::get('FFCMS_ICON_BRANDS')); ?>"
    data-label-mail-content="<?php echo Template::escape(Lang::get('FFCMS_MAIL_CONTENT')); ?>">
    <textarea class="visually-hidden" name="<?php echo Template::escape($fieldName); ?>" data-ff-xml><?php echo Template::escape($contentXml); ?></textarea>
    <div class="ff-freewrite-toolbar">
        <div class="ff-freewrite-toolbar-group">
            <button class="btn btn-light btn-sm" type="button" data-ff-format="bold">
                <i class="fa-solid fa-bold" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_BOLD'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="italic">
                <i class="fa-solid fa-italic" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ITALIC'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="underline">
                <i class="fa-solid fa-underline" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_UNDERLINE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="strikeThrough">
                <i class="fa-solid fa-strikethrough" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_STRIKE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="insertUnorderedList">
                <i class="fa-solid fa-list-ul" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_BULLETED'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="insertOrderedList">
                <i class="fa-solid fa-list-ol" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_NUMBERED'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="justifyLeft">
                <i class="fa-solid fa-align-left" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ALIGN_LEFT'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="justifyCenter">
                <i class="fa-solid fa-align-center" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ALIGN_CENTER'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="justifyRight">
                <i class="fa-solid fa-align-right" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ALIGN_RIGHT'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="link">
                <i class="fa-solid fa-link" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LINK'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-format="insertCode">
                <i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CODE'); ?></span>
            </button>
        </div>
        <div class="ff-freewrite-toolbar-group">
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="heading">
                <i class="fa-solid fa-heading" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_HEADING'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="quote">
                <i class="fa-solid fa-quote-left" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_QUOTE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="list_bulleted">
                <i class="fa-solid fa-list-ul" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_BULLETED'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="list_numbered">
                <i class="fa-solid fa-list-ol" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_NUMBERED'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="code">
                <i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CODE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-wrap="callout">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CALLOUT'); ?></span>
            </button>
        </div>
        <?php if ($allowMailContent) : ?>
            <div class="ff-freewrite-toolbar-group">
                <button class="btn btn-light btn-sm" type="button" data-ff-mailcontent="1">
                    <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_INSERT_MAIL_CONTENT'); ?></span>
                </button>
            </div>
        <?php endif; ?>
        <div class="ff-freewrite-toolbar-group">
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="richtext">
                <i class="fa-solid fa-pen-nib" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_RICHTEXT'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="heading">
                <i class="fa-solid fa-heading" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_HEADING'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="paragraph">
                <i class="fa-solid fa-paragraph" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PARAGRAPH'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="lead">
                <i class="fa-solid fa-text-height" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LEAD'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="small">
                <i class="fa-solid fa-text-width" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SMALL_TEXT'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="quote">
                <i class="fa-solid fa-quote-left" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_QUOTE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="pullquote">
                <i class="fa-solid fa-quote-right" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PULLQUOTE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="code">
                <i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CODE'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="preformatted">
                <i class="fa-solid fa-file-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PREFORMATTED'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="divider">
                <i class="fa-solid fa-minus" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_DIVIDER'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="spacer">
                <i class="fa-solid fa-arrows-up-down" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SPACER'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="callout">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CALLOUT'); ?></span>
            </button>
            <button class="btn btn-light btn-sm" type="button" data-ff-add-block="shortcode">
                <i class="fa-solid fa-brackets-curly" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SHORTCODE'); ?></span>
            </button>
        </div>
    </div>
    <div class="ff-freewrite-shell">
        <aside class="ff-freewrite-library">
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_TEXT'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="richtext"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_RICHTEXT'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="heading"><i class="fa-solid fa-heading" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_HEADING'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="paragraph"><i class="fa-solid fa-paragraph" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PARAGRAPH'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="lead"><i class="fa-solid fa-text-height" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LEAD'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="small"><i class="fa-solid fa-text-width" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SMALL_TEXT'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="quote"><i class="fa-solid fa-quote-left" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_QUOTE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="pullquote"><i class="fa-solid fa-quote-right" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PULLQUOTE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="code"><i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CODE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="preformatted"><i class="fa-solid fa-file-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_PREFORMATTED'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="divider"><i class="fa-solid fa-minus" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_DIVIDER'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="spacer"><i class="fa-solid fa-arrows-up-down" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SPACER'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="callout"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CALLOUT'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="shortcode"><i class="fa-solid fa-brackets-curly" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SHORTCODE'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_LISTS'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="list_bulleted"><i class="fa-solid fa-list-ul" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_BULLETED'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="list_numbered"><i class="fa-solid fa-list-ol" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_NUMBERED'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="list_checklist"><i class="fa-solid fa-list-check" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_CHECKLIST'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="list_definition"><i class="fa-solid fa-list" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LIST_DEFINITION'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="key_value"><i class="fa-solid fa-key" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_KEY_VALUE'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_LAYOUT'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="grid"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_GRID'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="section"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_SECTION'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="group"><i class="fa-solid fa-object-group" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_GROUP'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="container"><i class="fa-solid fa-box" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CONTAINER'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="card"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_CARD'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="tabs"><i class="fa-solid fa-folder" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_TABS'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="accordion"><i class="fa-solid fa-bars-staggered" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ACCORDION'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_MEDIA'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="image"><i class="fa-solid fa-image" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_IMAGE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="figure"><i class="fa-solid fa-image" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_FIGURE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="gallery"><i class="fa-solid fa-images" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_GALLERY'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="icon"><i class="fa-solid fa-icons" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_ICON'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_DATA'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="table"><i class="fa-solid fa-table" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_TABLE'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_LINKS'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="button"><i class="fa-solid fa-square" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_BUTTON'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="file"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_FILE'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="link_card"><i class="fa-solid fa-link" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_LINK_CARD'); ?></span></button>
                </div>
            </div>
            <div class="ff-freewrite-library-group">
                <h6 class="text-uppercase text-muted"><?php echo Lang::get('FFCMS_BLOCKS_ADVANCED'); ?></h6>
                <div class="ff-freewrite-library-grid">
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="embed"><i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_EMBED'); ?></span></button>
                    <button class="ff-freewrite-block-btn" type="button" data-ff-add-block="html"><i class="fa-solid fa-code" aria-hidden="true"></i><span><?php echo Lang::get('FFCMS_HTML'); ?></span></button>
                </div>
            </div>
        </aside>
        <div class="ff-freewrite-editor">
            <div class="ff-freewrite-empty" data-ff-empty="1">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                <span><?php echo Lang::get('FFCMS_FW_EMPTY'); ?></span>
            </div>
            <div class="ff-freewrite-blocks" data-ff-blocks="1" data-ff-depth="0"></div>
        </div>
    </div>
</div>
