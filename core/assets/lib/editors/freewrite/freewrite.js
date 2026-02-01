(function () {
    'use strict';

    var root = document.querySelector('[data-ff-freewrite]');
    if (!root) {
        return;
    }

    var form = root.closest('[data-ff-freewrite-form]');
    var blocksRoot = root.querySelector('[data-ff-blocks]');
    var xmlField = root.querySelector('[data-ff-xml]');
    var emptyNote = root.querySelector('[data-ff-empty]');

    var canHtml = root.getAttribute('data-can-html') === '1';
    var canEmbed = root.getAttribute('data-can-embed') === '1';
    var allowMailContent = root.getAttribute('data-ff-mail-content') === '1';
    var mediaUrl = root.getAttribute('data-media-url') || '';
    var docUrl = root.getAttribute('data-doc-url') || '';

    var msgSelectText = root.getAttribute('data-msg-select-text') || '';
    var msgUrlRequired = root.getAttribute('data-msg-url-required') || '';
    var msgUrlInvalid = root.getAttribute('data-msg-url-invalid') || '';
    var msgGridLimit = root.getAttribute('data-msg-grid-limit') || '';
    var msgBlockRestricted = root.getAttribute('data-msg-block-restricted') || '';
    var msgEmpty = root.getAttribute('data-msg-no-blocks') || '';

    var labels = {
        moveUp: root.getAttribute('data-label-move-up') || '',
        moveDown: root.getAttribute('data-label-move-down') || '',
        duplicate: root.getAttribute('data-label-duplicate') || '',
        remove: root.getAttribute('data-label-delete') || '',
        text: root.getAttribute('data-label-text') || '',
        title: root.getAttribute('data-label-title') || '',
        url: root.getAttribute('data-label-url') || '',
        description: root.getAttribute('data-label-description') || '',
        label: root.getAttribute('data-label-label') || '',
        alt: root.getAttribute('data-label-alt') || '',
        caption: root.getAttribute('data-label-caption') || '',
        alignment: root.getAttribute('data-label-alignment') || '',
        size: root.getAttribute('data-label-size') || '',
        width: root.getAttribute('data-label-width') || '',
        height: root.getAttribute('data-label-height') || '',
        level: root.getAttribute('data-label-level') || '',
        style: root.getAttribute('data-label-style') || '',
        icon: root.getAttribute('data-label-icon') || '',
        columns: root.getAttribute('data-label-columns') || '',
        rows: root.getAttribute('data-label-rows') || '',
        cols: root.getAttribute('data-label-cols') || '',
        file: root.getAttribute('data-label-file') || '',
        addItem: root.getAttribute('data-label-add-item') || '',
        removeItem: root.getAttribute('data-label-remove-item') || '',
        addTab: root.getAttribute('data-label-add-tab') || '',
        addPanel: root.getAttribute('data-label-add-panel') || '',
        addRow: root.getAttribute('data-label-add-row') || '',
        addCol: root.getAttribute('data-label-add-col') || '',
        addBlock: root.getAttribute('data-label-add-block') || '',
        addImage: root.getAttribute('data-label-add-image') || '',
        changeImage: root.getAttribute('data-label-change-image') || '',
        addGallery: root.getAttribute('data-label-add-gallery') || '',
        removeGallery: root.getAttribute('data-label-remove-gallery') || '',
        selectFile: root.getAttribute('data-label-select-file') || '',
        changeFile: root.getAttribute('data-label-change-file') || '',
        alignLeft: root.getAttribute('data-label-align-left') || '',
        alignCenter: root.getAttribute('data-label-align-center') || '',
        alignRight: root.getAttribute('data-label-align-right') || '',
        sizeSmall: root.getAttribute('data-label-size-small') || '',
        sizeMedium: root.getAttribute('data-label-size-medium') || '',
        sizeLarge: root.getAttribute('data-label-size-large') || '',
        sizeFull: root.getAttribute('data-label-size-full') || '',
        stylePrimary: root.getAttribute('data-label-style-primary') || '',
        styleSecondary: root.getAttribute('data-label-style-secondary') || '',
        styleOutline: root.getAttribute('data-label-style-outline') || '',
        iconSolid: root.getAttribute('data-label-icon-solid') || '',
        iconRegular: root.getAttribute('data-label-icon-regular') || '',
        iconBrands: root.getAttribute('data-label-icon-brands') || '',
        mailContent: root.getAttribute('data-label-mail-content') || ''
    };

    var blockLabelMap = {};
    var blockIconMap = {};
    var blockButtons = root.querySelectorAll('[data-ff-add-block]');
    blockButtons.forEach(function (button) {
        var type = button.getAttribute('data-ff-add-block');
        var label = button.querySelector('span') ? button.querySelector('span').textContent : '';
        var iconEl = button.querySelector('i');
        if (type) {
            blockLabelMap[type] = label ? label.trim() : '';
            blockIconMap[type] = iconEl ? iconEl.className.replace('fa-solid', '').trim() : '';
        }
        button.addEventListener('click', function () {
            addBlock(type, {}, getActiveContainer());
        });
    });

    if (labels.mailContent) {
        blockLabelMap.mailcontent = labels.mailContent;
        blockIconMap.mailcontent = 'fa-envelope-open-text';
    }

    var formatButtons = root.querySelectorAll('[data-ff-format]');
    formatButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var cmd = button.getAttribute('data-ff-format');
            if (!cmd) {
                return;
            }
            if (cmd === 'link') {
                handleLink();
                return;
            }
            if (cmd === 'insertCode') {
                document.execCommand('insertHTML', false, '<code>' + getSelectionText() + '</code>');
                return;
            }
            document.execCommand(cmd, false, null);
        });
    });

    var mailContentButton = root.querySelector('[data-ff-mailcontent]');

    function findMailContentBlock() {
        return blocksRoot ? blocksRoot.querySelector('[data-ff-type="mailcontent"]') : null;
    }

    function updateMailContentButton() {
        if (!mailContentButton) {
            return;
        }
        var hasBlock = !!findMailContentBlock();
        mailContentButton.setAttribute('data-ff-mailcontent-present', hasBlock ? '1' : '0');
    }

    if (mailContentButton) {
        mailContentButton.addEventListener('click', function () {
            if (!allowMailContent) {
                showMessage(msgBlockRestricted);
                return;
            }
            var existing = findMailContentBlock();
            if (existing) {
                setActiveBlock(existing);
                existing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            addBlock('mailcontent', {}, getActiveContainer());
        });
    }

    var colorTools = root.querySelectorAll('[data-ff-color-tool]');
    var colorInputs = root.querySelectorAll('[data-ff-color-input]');

    function closeColorPanels(except) {
        colorTools.forEach(function (tool) {
            var panel = tool.querySelector('[data-ff-color-panel]');
            if (!panel || panel === except) {
                return;
            }
            panel.classList.remove('is-open');
        });
    }

    colorTools.forEach(function (tool) {
        var toggle = tool.querySelector('[data-ff-color-toggle]');
        var panel = tool.querySelector('[data-ff-color-panel]');
        if (!toggle || !panel) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var isOpen = panel.classList.contains('is-open');
            closeColorPanels();
            panel.classList.toggle('is-open', !isOpen);
        });
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        var tool = target && target.closest ? target.closest('[data-ff-color-tool]') : null;
        if (!tool) {
            closeColorPanels();
        }
    });

    function applyColor(command, value) {
        if (!command) {
            return;
        }
        document.execCommand(command, false, value);
    }

    function initColorPickers() {
        if (typeof window.iro === 'undefined') {
            return;
        }
        colorInputs.forEach(function (input) {
            var panel = input.closest('[data-ff-color-panel]');
            if (!panel) {
                return;
            }
            var pickerEl = panel.querySelector('[data-ff-color-picker]');
            if (!pickerEl) {
                return;
            }
            var command = input.getAttribute('data-ff-color-command') || '';
            var picker = new window.iro.ColorPicker(pickerEl, {
                width: 180,
                color: input.value || '#000000'
            });

            picker.on('color:change', function (color) {
                input.value = color.rgbaString;
                applyColor(command, input.value);
            });

            input.addEventListener('input', function () {
                try {
                    picker.color.set(input.value);
                    applyColor(command, input.value);
                } catch (err) {
                    // Ignore invalid manual input while typing.
                }
            });
        });
    }

    initColorPickers();

    var wrapButtons = root.querySelectorAll('[data-ff-wrap]');
    wrapButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var type = button.getAttribute('data-ff-wrap');
            if (type) {
                wrapSelection(type);
            }
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            xmlField.value = serializeBlocks(blocksRoot, 0);
        });
    }

    if (xmlField && xmlField.value) {
        loadFromXml(xmlField.value);
    }

    updateEmptyState();

    window.mediaPickerCallback = function (item, options) {
        if (!item || !item.url) {
            return;
        }
        var target = root.querySelector('[data-ff-media-target="1"]');
        if (!target) {
            addBlock('image', { src: item.url, alt: item.title || '' }, getActiveContainer());
            return;
        }
        var mode = target.getAttribute('data-ff-media-mode') || 'image';
        target.removeAttribute('data-ff-media-target');
        target.removeAttribute('data-ff-media-mode');
        if (mode === 'gallery') {
            addGalleryItem(target, {
                src: item.url,
                alt: options && options.altText ? options.altText : item.title || ''
            });
            return;
        }
        setImageBlockData(target, {
            src: item.url,
            alt: options && options.altText ? options.altText : item.title || '',
            alignment: options && options.alignment ? options.alignment : 'left',
            size: options && options.size ? options.size : 'medium',
            width: options && options.width ? options.width : '',
            height: options && options.height ? options.height : ''
        });
    };

    window.freewriteDocumentCallback = function (path, title) {
        var target = root.querySelector('[data-ff-doc-target="1"]');
        if (!target) {
            addBlock('file', { url: path, label: title || '' }, getActiveContainer());
            return;
        }
        target.removeAttribute('data-ff-doc-target');
        setFileBlockData(target, { url: path, label: title || '' });
    };

    function addBlock(type, data, container) {
        if (!container || !type) {
            return;
        }
        if (type === 'mailcontent' && !allowMailContent) {
            showMessage(msgBlockRestricted);
            return;
        }
        if (type === 'embed' && !canEmbed) {
            showMessage(msgBlockRestricted);
            return;
        }
        if (type === 'html' && !canHtml) {
            showMessage(msgBlockRestricted);
            return;
        }
        var depth = getContainerDepth(container);
        if (type === 'grid' && depth >= 3) {
            showMessage(msgGridLimit);
            return;
        }
        var block = buildBlock(type, data, depth);
        container.appendChild(block);
        setActiveBlock(block);
        updateEmptyState();
        updateMailContentButton();
    }

    function buildBlock(type, data, depth) {
        var block = document.createElement('div');
        block.className = 'ff-freewrite-block';
        block.setAttribute('data-ff-type', type);

        var header = document.createElement('div');
        header.className = 'ff-freewrite-block-header';

        var title = document.createElement('div');
        title.className = 'ff-freewrite-block-title';
        var titleIcon = document.createElement('i');
        titleIcon.className = 'fa-solid ' + (blockIconMap[type] || 'fa-square');
        titleIcon.setAttribute('aria-hidden', 'true');
        var titleText = document.createElement('span');
        titleText.textContent = blockLabelMap[type] || type;
        title.appendChild(titleIcon);
        title.appendChild(titleText);
        header.appendChild(title);

        var actions = document.createElement('div');
        actions.className = 'ff-freewrite-block-actions';
        actions.appendChild(createActionButton('move-up', 'fa-arrow-up', labels.moveUp));
        actions.appendChild(createActionButton('move-down', 'fa-arrow-down', labels.moveDown));
        actions.appendChild(createActionButton('duplicate', 'fa-clone', labels.duplicate));
        actions.appendChild(createActionButton('remove', 'fa-trash', labels.remove));
        header.appendChild(actions);
        block.appendChild(header);

        var body = document.createElement('div');
        body.className = 'ff-freewrite-block-body';
        block.appendChild(body);

        var locked = (type === 'html' && !canHtml) || (type === 'embed' && !canEmbed);
        if (locked) {
            var note = document.createElement('div');
            note.className = 'ff-freewrite-block-note';
            note.textContent = msgBlockRestricted;
            body.appendChild(note);
        }

        switch (type) {
            case 'richtext':
                buildRichtext(body, data, locked);
                break;
            case 'heading':
                buildHeading(body, data, locked);
                break;
            case 'paragraph':
            case 'lead':
            case 'small':
            case 'quote':
            case 'pullquote':
            case 'code':
            case 'preformatted':
            case 'callout':
            case 'shortcode':
                buildTextarea(body, data, locked, labels.text);
                break;
            case 'divider':
            case 'spacer':
                buildStatic(body, type);
                break;
            case 'mailcontent':
                buildMailContent(body);
                break;
            case 'list_bulleted':
            case 'list_numbered':
                buildList(body, data, locked);
                break;
            case 'list_checklist':
                buildChecklist(body, data, locked);
                break;
            case 'list_definition':
            case 'key_value':
                buildPairs(body, data, locked);
                break;
            case 'grid':
                buildGrid(body, data, depth, locked);
                break;
            case 'section':
            case 'group':
            case 'container':
            case 'card':
                buildContainer(body, data, depth, locked);
                break;
            case 'tabs':
                buildTabs(body, data, depth, locked);
                break;
            case 'accordion':
                buildAccordion(body, data, depth, locked);
                break;
            case 'image':
            case 'figure':
                buildImage(body, data, locked);
                break;
            case 'gallery':
                buildGallery(body, data, locked);
                break;
            case 'icon':
                buildIcon(body, data, locked);
                break;
            case 'table':
                buildTable(body, data, locked);
                break;
            case 'button':
                buildButton(body, data, locked);
                break;
            case 'file':
                buildFile(body, data, locked);
                break;
            case 'link_card':
                buildLinkCard(body, data, locked);
                break;
            case 'embed':
                buildEmbed(body, data, locked);
                break;
            case 'html':
                buildHtml(body, data, locked);
                break;
        }

        block.addEventListener('click', function (event) {
            if (event.target && event.target.closest('[data-ff-action]')) {
                handleBlockAction(event, block);
                return;
            }
            setActiveBlock(block);
        });

        return block;
    }

    function createActionButton(action, icon, label) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-light btn-sm';
        button.setAttribute('data-ff-action', action);
        var iconEl = document.createElement('i');
        iconEl.className = 'fa-solid ' + icon;
        iconEl.setAttribute('aria-hidden', 'true');
        var text = document.createElement('span');
        text.textContent = label;
        button.appendChild(iconEl);
        button.appendChild(text);
        return button;
    }

    function handleBlockAction(event, block) {
        var action = event.target.closest('[data-ff-action]').getAttribute('data-ff-action');
        if (!action) {
            return;
        }
        var container = block.parentElement;
        if (!container) {
            return;
        }
        if (action === 'remove') {
            block.remove();
            updateEmptyState();
            updateMailContentButton();
            return;
        }
        if (action === 'duplicate') {
            var type = block.getAttribute('data-ff-type') || '';
            var data = extractBlockData(block);
            var depth = getContainerDepth(container);
            var copy = buildBlock(type, data, depth);
            container.insertBefore(copy, block.nextSibling);
            updateEmptyState();
            updateMailContentButton();
            return;
        }
        if (action === 'move-up') {
            var prev = block.previousElementSibling;
            if (prev) {
                container.insertBefore(block, prev);
            }
            return;
        }
        if (action === 'move-down') {
            var next = block.nextElementSibling;
            if (next) {
                container.insertBefore(next, block);
            }
        }
    }

    function buildRichtext(body, data, locked) {
        var editor = document.createElement('div');
        editor.className = 'ff-freewrite-richtext';
        editor.setAttribute('data-ff-richtext', '1');
        if (!locked) {
            editor.setAttribute('contenteditable', 'true');
        }
        editor.innerHTML = data.html || '';
        body.appendChild(editor);
    }

    function buildHeading(body, data, locked) {
        var wrap = document.createElement('div');
        wrap.className = 'd-flex gap-2';
        var input = createInput('text', data.text || '', locked);
        var select = document.createElement('select');
        select.className = 'form-select ff-freewrite-select';
        select.disabled = locked;
        [1, 2, 3, 4, 5, 6].forEach(function (level) {
            var opt = document.createElement('option');
            opt.value = String(level);
            opt.textContent = labels.level + ' ' + level;
            if (String(data.level || 2) === String(level)) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
        wrap.appendChild(wrapField(labels.text, input));
        wrap.appendChild(wrapField(labels.level, select));
        body.appendChild(wrap);
    }

    function buildTextarea(body, data, locked, label) {
        var textarea = document.createElement('textarea');
        textarea.className = 'form-control ff-freewrite-textarea';
        textarea.rows = 4;
        textarea.value = data.text || '';
        textarea.readOnly = locked;
        body.appendChild(wrapField(label, textarea));
    }

    function buildStatic(body, type) {
        var note = document.createElement('div');
        note.className = 'ff-freewrite-block-note';
        note.textContent = blockLabelMap[type] || type;
        body.appendChild(note);
    }

    function buildMailContent(body) {
        var note = document.createElement('div');
        note.className = 'ff-freewrite-block-note';
        note.textContent = (blockLabelMap.mailcontent || 'Mail content') + ' [mailcontent]';
        body.appendChild(note);
    }

    function buildList(body, data, locked) {
        var textarea = document.createElement('textarea');
        textarea.className = 'form-control ff-freewrite-textarea';
        textarea.rows = 4;
        textarea.value = (data.items || []).join('\n');
        textarea.readOnly = locked;
        body.appendChild(wrapField(labels.text, textarea));
    }

    function buildChecklist(body, data, locked) {
        var list = document.createElement('div');
        list.className = 'd-flex flex-column gap-2';
        var items = data.items || [];
        items.forEach(function (item) {
            list.appendChild(buildChecklistRow(item.text || '', !!item.checked, locked));
        });
        if (!locked) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-outline-secondary btn-sm';
            addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addItem + '</span>';
            addBtn.addEventListener('click', function () {
                list.insertBefore(buildChecklistRow('', false, locked), addBtn);
            });
            list.appendChild(addBtn);
        }
        body.appendChild(list);
    }

    function buildChecklistRow(text, checked, locked) {
        var row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2';
        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input';
        checkbox.checked = checked;
        checkbox.disabled = locked;
        var input = createInput('text', text, locked);
        input.classList.add('flex-grow-1');
        row.appendChild(checkbox);
        row.appendChild(input);
        if (!locked) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-light btn-sm';
            removeBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i><span>' + labels.removeItem + '</span>';
            removeBtn.addEventListener('click', function () {
                row.remove();
            });
            row.appendChild(removeBtn);
        }
        row.setAttribute('data-ff-check-item', '1');
        return row;
    }

    function buildPairs(body, data, locked) {
        var list = document.createElement('div');
        list.className = 'd-flex flex-column gap-2';
        var items = data.items || [];
        items.forEach(function (item) {
            list.appendChild(buildPairRow(item.key || '', item.value || '', locked));
        });
        if (!locked) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-outline-secondary btn-sm';
            addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addItem + '</span>';
            addBtn.addEventListener('click', function () {
                list.insertBefore(buildPairRow('', '', locked), addBtn);
            });
            list.appendChild(addBtn);
        }
        body.appendChild(list);
    }

    function buildPairRow(key, value, locked) {
        var row = document.createElement('div');
        row.className = 'd-flex flex-wrap gap-2 align-items-center';
        var keyInput = createInput('text', key, locked);
        keyInput.classList.add('flex-grow-1');
        var valueInput = createInput('text', value, locked);
        valueInput.classList.add('flex-grow-1');
        row.appendChild(wrapField(labels.title, keyInput));
        row.appendChild(wrapField(labels.text, valueInput));
        if (!locked) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-light btn-sm';
            removeBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i><span>' + labels.removeItem + '</span>';
            removeBtn.addEventListener('click', function () {
                row.remove();
            });
            row.appendChild(removeBtn);
        }
        row.setAttribute('data-ff-pair', '1');
        return row;
    }

    function buildGrid(body, data, depth, locked) {
        var wrapper = document.createElement('div');
        wrapper.className = 'ff-freewrite-grid-editor';

        var select = document.createElement('select');
        select.className = 'form-select ff-freewrite-select';
        select.disabled = locked;
        [2, 3, 4].forEach(function (count) {
            var opt = document.createElement('option');
            opt.value = String(count);
            opt.textContent = String(count);
            if (String(data.columns || 2) === String(count)) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
        wrapper.appendChild(wrapField(labels.columns, select));

        var columnsWrap = document.createElement('div');
        columnsWrap.className = 'ff-freewrite-grid-row';

        var columns = data.columns || 2;
        for (var i = 0; i < columns; i++) {
            columnsWrap.appendChild(buildGridColumn((data.items && data.items[i]) ? data.items[i] : [], depth + 1, locked));
        }

        select.addEventListener('change', function () {
            if (locked) {
                return;
            }
            var newCount = parseInt(select.value, 10);
            columnsWrap.innerHTML = '';
            for (var idx = 0; idx < newCount; idx++) {
                columnsWrap.appendChild(buildGridColumn([], depth + 1, locked));
            }
        });

        wrapper.appendChild(columnsWrap);
        body.appendChild(wrapper);
    }

    function buildGridColumn(items, depth, locked) {
        var col = document.createElement('div');
        col.className = 'ff-freewrite-grid-col';
        var inner = createBlocksContainer(depth);
        var addHint = document.createElement('div');
        addHint.className = 'ff-freewrite-block-note';
        addHint.textContent = labels.addBlock;
        inner.appendChild(addHint);
        col.appendChild(inner);
        (items || []).forEach(function (item) {
            inner.appendChild(buildBlock(item.type, item.data || {}, depth));
        });
        return col;
    }

    function buildContainer(body, data, depth, locked) {
        var container = createBlocksContainer(depth + 1);
        var note = document.createElement('div');
        note.className = 'ff-freewrite-block-note';
        note.textContent = labels.addBlock;
        container.appendChild(note);
        body.appendChild(container);
        (data.items || []).forEach(function (item) {
            container.appendChild(buildBlock(item.type, item.data || {}, depth + 1));
        });
        if (locked) {
            container.setAttribute('data-ff-locked', '1');
        }
    }

    function buildTabs(body, data, depth, locked) {
        var tabs = document.createElement('div');
        tabs.className = 'd-flex flex-column gap-3';
        var items = data.items || [];
        items.forEach(function (item) {
            tabs.appendChild(buildTabItem(item, depth + 1, locked));
        });
        if (!locked) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-outline-secondary btn-sm';
            addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addTab + '</span>';
            addBtn.addEventListener('click', function () {
                tabs.insertBefore(buildTabItem({ title: '', items: [] }, depth + 1, locked), addBtn);
            });
            tabs.appendChild(addBtn);
        }
        body.appendChild(tabs);
    }

    function buildTabItem(item, depth, locked) {
        var card = document.createElement('div');
        card.className = 'card';
        var cardBody = document.createElement('div');
        cardBody.className = 'card-body';
        var titleInput = createInput('text', item.title || '', locked);
        cardBody.appendChild(wrapField(labels.title, titleInput));
        var container = createBlocksContainer(depth);
        var note = document.createElement('div');
        note.className = 'ff-freewrite-block-note';
        note.textContent = labels.addBlock;
        container.appendChild(note);
        cardBody.appendChild(container);
        (item.items || []).forEach(function (child) {
            container.appendChild(buildBlock(child.type, child.data || {}, depth));
        });
        card.appendChild(cardBody);
        return card;
    }

    function buildAccordion(body, data, depth, locked) {
        var items = document.createElement('div');
        items.className = 'd-flex flex-column gap-3';
        (data.items || []).forEach(function (item) {
            items.appendChild(buildAccordionItem(item, depth + 1, locked));
        });
        if (!locked) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-outline-secondary btn-sm';
            addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addPanel + '</span>';
            addBtn.addEventListener('click', function () {
                items.insertBefore(buildAccordionItem({ title: '', items: [] }, depth + 1, locked), addBtn);
            });
            items.appendChild(addBtn);
        }
        body.appendChild(items);
    }

    function buildAccordionItem(item, depth, locked) {
        var card = document.createElement('div');
        card.className = 'card';
        var cardBody = document.createElement('div');
        cardBody.className = 'card-body';
        var titleInput = createInput('text', item.title || '', locked);
        cardBody.appendChild(wrapField(labels.title, titleInput));
        var container = createBlocksContainer(depth);
        var note = document.createElement('div');
        note.className = 'ff-freewrite-block-note';
        note.textContent = labels.addBlock;
        container.appendChild(note);
        cardBody.appendChild(container);
        (item.items || []).forEach(function (child) {
            container.appendChild(buildBlock(child.type, child.data || {}, depth));
        });
        card.appendChild(cardBody);
        return card;
    }

    function buildImage(body, data, locked) {
        var preview = document.createElement('div');
        preview.className = 'ff-freewrite-block-note';
        if (data.src) {
            var img = document.createElement('img');
            img.src = data.src;
            img.alt = data.alt || '';
            img.className = 'img-fluid';
            applyImageSizing(img, data);
            preview.textContent = '';
            preview.appendChild(img);
        } else {
            preview.textContent = labels.addImage;
        }
        body.appendChild(preview);

        var buttonRow = document.createElement('div');
        buttonRow.className = 'd-flex gap-2';
        if (!locked) {
            var selectBtn = document.createElement('button');
            selectBtn.type = 'button';
            selectBtn.className = 'btn btn-outline-primary btn-sm';
            selectBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + (data.src ? labels.changeImage : labels.addImage) + '</span>';
            selectBtn.addEventListener('click', function () {
                if (!mediaUrl) {
                    return;
                }
                var block = body.closest('.ff-freewrite-block');
                block.setAttribute('data-ff-media-target', '1');
                block.setAttribute('data-ff-media-mode', 'image');
                window.open(mediaUrl, 'ffMediaPicker', 'width=1100,height=760');
            });
            buttonRow.appendChild(selectBtn);
        }
        body.appendChild(buttonRow);

        var altInput = createInput('text', data.alt || '', locked);
        var captionInput = createInput('text', data.caption || '', locked);
        var widthInput = createInput('number', data.width || '', locked);
        widthInput.setAttribute('min', '0');
        widthInput.setAttribute('step', '1');
        widthInput.setAttribute('data-ff-image-width', '1');
        widthInput.placeholder = 'px';
        var heightInput = createInput('number', data.height || '', locked);
        heightInput.setAttribute('min', '0');
        heightInput.setAttribute('step', '1');
        heightInput.setAttribute('data-ff-image-height', '1');
        heightInput.placeholder = 'px';

        var alignSelect = document.createElement('select');
        alignSelect.className = 'form-select ff-freewrite-select';
        alignSelect.disabled = locked;
        var alignLabels = {
            left: labels.alignLeft,
            center: labels.alignCenter,
            right: labels.alignRight
        };
        ['left', 'center', 'right'].forEach(function (value) {
            var opt = document.createElement('option');
            opt.value = value;
            opt.textContent = alignLabels[value] || value;
            if ((data.alignment || 'left') === value) {
                opt.selected = true;
            }
            alignSelect.appendChild(opt);
        });

        var sizeSelect = document.createElement('select');
        sizeSelect.className = 'form-select ff-freewrite-select';
        sizeSelect.disabled = locked;
        var sizeLabels = {
            small: labels.sizeSmall,
            medium: labels.sizeMedium,
            large: labels.sizeLarge,
            full: labels.sizeFull
        };
        ['small', 'medium', 'large', 'full'].forEach(function (value) {
            var optSize = document.createElement('option');
            optSize.value = value;
            optSize.textContent = sizeLabels[value] || value;
            if ((data.size || 'medium') === value) {
                optSize.selected = true;
            }
            sizeSelect.appendChild(optSize);
        });

        body.appendChild(wrapField(labels.alt, altInput));
        body.appendChild(wrapField(labels.caption, captionInput));
        body.appendChild(wrapField(labels.alignment, alignSelect));
        body.appendChild(wrapField(labels.size, sizeSelect));
        body.appendChild(wrapField(labels.width, widthInput));
        body.appendChild(wrapField(labels.height, heightInput));

        var updatePreview = function () {
            var targetImg = preview.querySelector('img');
            if (!targetImg) {
                return;
            }
            applyImageSizing(targetImg, {
                width: widthInput.value,
                height: heightInput.value
            });
        };
        widthInput.addEventListener('input', updatePreview);
        heightInput.addEventListener('input', updatePreview);
    }

    function applyImageSizing(img, data) {
        if (!img) {
            return;
        }
        var widthValue = data && data.width ? parseInt(data.width, 10) : 0;
        var heightValue = data && data.height ? parseInt(data.height, 10) : 0;
        if (isNaN(widthValue)) {
            widthValue = 0;
        }
        if (isNaN(heightValue)) {
            heightValue = 0;
        }
        img.style.width = widthValue > 0 ? widthValue + 'px' : '';
        img.style.height = heightValue > 0 ? heightValue + 'px' : '';
    }

    function setImageBlockData(block, data) {
        var body = block.querySelector('.ff-freewrite-block-body');
        if (!body) {
            return;
        }
        body.innerHTML = '';
        buildImage(body, data || {}, false);
    }

    function buildGallery(body, data, locked) {
        var list = document.createElement('div');
        list.className = 'd-flex flex-column gap-2';
        list.setAttribute('data-ff-gallery-list', '1');
        (data.items || []).forEach(function (item) {
            list.appendChild(buildGalleryRow(item, locked));
        });

        if (!locked) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-outline-primary btn-sm';
            addBtn.setAttribute('data-ff-gallery-add', '1');
            addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addGallery + '</span>';
            addBtn.addEventListener('click', function () {
                if (!mediaUrl) {
                    return;
                }
                var block = body.closest('.ff-freewrite-block');
                block.setAttribute('data-ff-media-target', '1');
                block.setAttribute('data-ff-media-mode', 'gallery');
                window.open(mediaUrl, 'ffMediaPicker', 'width=1100,height=760');
            });
            list.appendChild(addBtn);
        }
        body.appendChild(list);
    }

    function buildGalleryRow(item, locked) {
        var row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2';
        var img = document.createElement('img');
        img.src = item.src || '';
        img.alt = item.alt || '';
        img.className = 'img-fluid ff-freewrite-gallery-thumb';
        row.appendChild(img);
        var caption = createInput('text', item.alt || '', locked);
        caption.classList.add('flex-grow-1');
        row.appendChild(caption);
        if (!locked) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-light btn-sm';
            removeBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i><span>' + labels.removeGallery + '</span>';
            removeBtn.addEventListener('click', function () {
                row.remove();
            });
            row.appendChild(removeBtn);
        }
        row.setAttribute('data-ff-gallery-item', '1');
        return row;
    }

    function addGalleryItem(block, data) {
        var list = block.querySelector('[data-ff-gallery-list]');
        if (!list) {
            return;
        }
        var row = buildGalleryRow(data, false);
        var addBtn = list.querySelector('[data-ff-gallery-add]');
        if (addBtn) {
            list.insertBefore(row, addBtn);
        } else {
            list.appendChild(row);
        }
    }

    function buildIcon(body, data, locked) {
        var styleSelect = document.createElement('select');
        styleSelect.className = 'form-select ff-freewrite-select';
        styleSelect.disabled = locked;
        var iconLabels = {
            solid: labels.iconSolid,
            regular: labels.iconRegular,
            brands: labels.iconBrands
        };
        ['solid', 'regular', 'brands'].forEach(function (value) {
            var opt = document.createElement('option');
            opt.value = value;
            opt.textContent = iconLabels[value] || value;
            if ((data.style || 'solid') === value) {
                opt.selected = true;
            }
            styleSelect.appendChild(opt);
        });
        var nameInput = createInput('text', data.name || '', locked);
        body.appendChild(wrapField(labels.style, styleSelect));
        body.appendChild(wrapField(labels.icon, nameInput));
    }

    function buildTable(body, data, locked) {
        var table = document.createElement('table');
        table.className = 'table table-bordered';
        var tbody = document.createElement('tbody');
        table.appendChild(tbody);
        (data.rows || [['']]).forEach(function (row) {
            tbody.appendChild(buildTableRow(row, locked));
        });
        body.appendChild(table);
        if (!locked) {
            var actions = document.createElement('div');
            actions.className = 'd-flex gap-2';
            var addRow = document.createElement('button');
            addRow.type = 'button';
            addRow.className = 'btn btn-outline-secondary btn-sm';
            addRow.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addRow + '</span>';
            addRow.addEventListener('click', function () {
                tbody.appendChild(buildTableRow([''], locked));
            });
            var addCol = document.createElement('button');
            addCol.type = 'button';
            addCol.className = 'btn btn-outline-secondary btn-sm';
            addCol.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + labels.addCol + '</span>';
            addCol.addEventListener('click', function () {
                var rows = tbody.querySelectorAll('tr');
                rows.forEach(function (row) {
                    var cell = document.createElement('td');
                    cell.appendChild(createInput('text', '', locked));
                    row.appendChild(cell);
                });
            });
            actions.appendChild(addRow);
            actions.appendChild(addCol);
            body.appendChild(actions);
        }
    }

    function buildTableRow(values, locked) {
        var row = document.createElement('tr');
        (values || ['']).forEach(function (value) {
            var cell = document.createElement('td');
            cell.appendChild(createInput('text', value, locked));
            row.appendChild(cell);
        });
        row.setAttribute('data-ff-table-row', '1');
        return row;
    }

    function buildButton(body, data, locked) {
        var labelInput = createInput('text', data.label || '', locked);
        var urlInput = createInput('text', data.url || '', locked);
        var styleSelect = document.createElement('select');
        styleSelect.className = 'form-select ff-freewrite-select';
        styleSelect.disabled = locked;
        var styleLabels = {
            primary: labels.stylePrimary,
            secondary: labels.styleSecondary,
            outline: labels.styleOutline
        };
        ['primary', 'secondary', 'outline'].forEach(function (value) {
            var opt = document.createElement('option');
            opt.value = value;
            opt.textContent = styleLabels[value] || value;
            if ((data.style || 'primary') === value) {
                opt.selected = true;
            }
            styleSelect.appendChild(opt);
        });
        body.appendChild(wrapField(labels.label, labelInput));
        body.appendChild(wrapField(labels.url, urlInput));
        body.appendChild(wrapField(labels.style, styleSelect));
    }

    function buildFile(body, data, locked) {
        var urlInput = createInput('text', data.url || '', true);
        urlInput.classList.add('ff-freewrite-input');
        var labelInput = createInput('text', data.label || '', locked);
        body.appendChild(wrapField(labels.file, urlInput));
        body.appendChild(wrapField(labels.label, labelInput));
        if (!locked) {
            var pickBtn = document.createElement('button');
            pickBtn.type = 'button';
            pickBtn.className = 'btn btn-outline-primary btn-sm';
            pickBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i><span>' + (data.url ? labels.changeFile : labels.selectFile) + '</span>';
            pickBtn.addEventListener('click', function () {
                if (!docUrl) {
                    return;
                }
                body.closest('.ff-freewrite-block').setAttribute('data-ff-doc-target', '1');
                window.open(docUrl, 'ffDocPicker', 'width=960,height=720');
            });
            body.appendChild(pickBtn);
        }
    }

    function setFileBlockData(block, data) {
        var body = block.querySelector('.ff-freewrite-block-body');
        if (!body) {
            return;
        }
        body.innerHTML = '';
        buildFile(body, data || {}, false);
    }

    function buildLinkCard(body, data, locked) {
        var titleInput = createInput('text', data.title || '', locked);
        var descInput = document.createElement('textarea');
        descInput.className = 'form-control ff-freewrite-textarea';
        descInput.rows = 3;
        descInput.value = data.description || '';
        descInput.readOnly = locked;
        var urlInput = createInput('text', data.url || '', locked);
        var labelInput = createInput('text', data.label || '', locked);
        body.appendChild(wrapField(labels.title, titleInput));
        body.appendChild(wrapField(labels.description, descInput));
        body.appendChild(wrapField(labels.url, urlInput));
        body.appendChild(wrapField(labels.label, labelInput));
    }

    function buildEmbed(body, data, locked) {
        var urlInput = createInput('text', data.url || '', locked);
        body.appendChild(wrapField(labels.url, urlInput));
    }

    function buildHtml(body, data, locked) {
        var textarea = document.createElement('textarea');
        textarea.className = 'form-control ff-freewrite-textarea';
        textarea.rows = 6;
        textarea.value = data.html || '';
        textarea.readOnly = locked;
        body.appendChild(wrapField(labels.text, textarea));
    }

    function wrapField(label, control) {
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-3';
        if (label) {
            var labelEl = document.createElement('label');
            labelEl.className = 'form-label';
            labelEl.textContent = label;
            wrapper.appendChild(labelEl);
        }
        wrapper.appendChild(control);
        return wrapper;
    }

    function createInput(type, value, locked) {
        var input = document.createElement('input');
        input.type = type;
        input.className = 'form-control ff-freewrite-input';
        input.value = value || '';
        input.readOnly = locked;
        return input;
    }

    function createBlocksContainer(depth) {
        var container = document.createElement('div');
        container.className = 'ff-freewrite-blocks';
        container.setAttribute('data-ff-blocks', '1');
        container.setAttribute('data-ff-depth', String(depth));
        container.addEventListener('click', function (event) {
            if (event.target && event.target.closest('.ff-freewrite-block')) {
                return;
            }
            setActiveContainer(container);
        });
        return container;
    }

    function getActiveContainer() {
        var active = root.querySelector('[data-ff-container-active="1"]');
        if (active) {
            return active;
        }
        return blocksRoot;
    }

    function setActiveContainer(container) {
        var active = root.querySelector('[data-ff-container-active="1"]');
        if (active) {
            active.removeAttribute('data-ff-container-active');
        }
        container.setAttribute('data-ff-container-active', '1');
    }

    function setActiveBlock(block) {
        var active = root.querySelector('[data-ff-block-active="1"]');
        if (active) {
            active.removeAttribute('data-ff-block-active');
        }
        block.setAttribute('data-ff-block-active', '1');
        var container = block.closest('[data-ff-blocks]');
        if (container) {
            setActiveContainer(container);
        }
    }

    function getContainerDepth(container) {
        var depth = parseInt(container.getAttribute('data-ff-depth') || '0', 10);
        if (isNaN(depth)) {
            return 0;
        }
        return depth;
    }

    function updateEmptyState() {
        var hasBlocks = blocksRoot.querySelectorAll('.ff-freewrite-block').length > 0;
        if (emptyNote) {
            emptyNote.classList.toggle('is-hidden', hasBlocks);
            emptyNote.querySelector('span').textContent = msgEmpty;
        }
        updateMailContentButton();
    }

    function showMessage(message) {
        if (!message) {
            return;
        }
        window.alert(message);
    }

    function handleLink() {
        var url = window.prompt(msgUrlRequired);
        if (!url) {
            showMessage(msgUrlRequired);
            return;
        }
        if (!isSafeUrl(url)) {
            showMessage(msgUrlInvalid);
            return;
        }
        document.execCommand('createLink', false, url);
    }

    function getSelectionText() {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return '';
        }
        return selection.toString();
    }

    function wrapSelection(type) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
            showMessage(msgSelectText);
            return;
        }
        var range = selection.getRangeAt(0);
        var rich = range.commonAncestorContainer;
        var richEl = rich.nodeType === 1 ? rich.closest('[data-ff-richtext]') : rich.parentElement.closest('[data-ff-richtext]');
        if (!richEl) {
            showMessage(msgSelectText);
            return;
        }

        var beforeRange = range.cloneRange();
        beforeRange.selectNodeContents(richEl);
        beforeRange.setEnd(range.startContainer, range.startOffset);

        var afterRange = range.cloneRange();
        afterRange.selectNodeContents(richEl);
        afterRange.setStart(range.endContainer, range.endOffset);

        var beforeHtml = rangeToHtml(beforeRange);
        var selectedHtml = rangeToHtml(range);
        var afterHtml = rangeToHtml(afterRange);

        richEl.innerHTML = beforeHtml;

        var blockEl = richEl.closest('.ff-freewrite-block');
        if (!blockEl) {
            return;
        }
        var container = blockEl.parentElement;
        if (!container) {
            return;
        }

        var insertIndex = Array.prototype.indexOf.call(container.children, blockEl) + 1;
        var newBlockData = buildWrapData(type, selectedHtml);
        var newBlock = buildBlock(type, newBlockData, getContainerDepth(container));

        container.insertBefore(newBlock, container.children[insertIndex] || null);
        if (afterHtml && afterHtml.trim() !== '') {
            var afterBlock = buildBlock('richtext', { html: afterHtml }, getContainerDepth(container));
            container.insertBefore(afterBlock, newBlock.nextSibling);
        }
        setActiveBlock(newBlock);
    }

    function buildWrapData(type, html) {
        var text = stripHtml(html).trim();
        if (type === 'list_bulleted') {
            return { items: text.split(/\n+/).filter(Boolean) };
        }
        if (type === 'heading') {
            return { text: text, level: 2 };
        }
        if (type === 'code') {
            return { text: text };
        }
        if (type === 'callout') {
            return { text: text };
        }
        return { text: text };
    }

    function rangeToHtml(range) {
        var fragment = range.cloneContents();
        var wrap = document.createElement('div');
        wrap.appendChild(fragment);
        return wrap.innerHTML;
    }

    function stripHtml(html) {
        var div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    }

    function isSafeUrl(url) {
        if (!url) {
            return false;
        }
        if (url.indexOf('/') === 0) {
            return true;
        }
        return /^https?:\/\//i.test(url);
    }

    function serializeBlocks(container, depth) {
        var xml = '<freewrite version=\"1\">';
        Array.prototype.slice.call(container.children).forEach(function (child) {
            if (child.classList && child.classList.contains('ff-freewrite-block')) {
                xml += serializeBlock(child, depth);
            }
        });
        xml += '</freewrite>';
        return xml;
    }

    function serializeBlock(block, depth) {
        var type = block.getAttribute('data-ff-type');
        var body = block.querySelector('.ff-freewrite-block-body');
        if (!type || !body) {
            return '';
        }

        if (type === 'richtext') {
            var rich = body.querySelector('[data-ff-richtext]');
            var html = rich ? rich.innerHTML : '';
            html = sanitizeRichtext(html);
            return '<block type="richtext"><![CDATA[' + cdataWrap(html) + ']]></block>';
        }

        if (type === 'heading') {
            var headingText = getFieldValue(body, 'input');
            var level = body.querySelector('select') ? body.querySelector('select').value : '2';
            return '<block type="heading" level="' + escapeXml(level) + '">' + escapeXml(headingText) + '</block>';
        }

        if (type === 'paragraph' || type === 'lead' || type === 'small' || type === 'quote' || type === 'pullquote' || type === 'code' || type === 'preformatted' || type === 'callout' || type === 'shortcode') {
            var textValue = getFieldValue(body, 'textarea') || getFieldValue(body, 'input');
            return '<block type="' + escapeXml(type) + '">' + escapeXml(textValue) + '</block>';
        }

        if (type === 'divider' || type === 'spacer') {
            return '<block type="' + escapeXml(type) + '"></block>';
        }

        if (type === 'mailcontent') {
            return '<block type="mailcontent"></block>';
        }

        if (type === 'list_bulleted' || type === 'list_numbered') {
            var listText = getFieldValue(body, 'textarea');
            var items = listText.split(/\n+/).map(function (item) {
                return item.trim();
            }).filter(Boolean);
            var xml = '<block type="' + escapeXml(type) + '">';
            items.forEach(function (item) {
                xml += '<item>' + escapeXml(item) + '</item>';
            });
            xml += '</block>';
            return xml;
        }

        if (type === 'list_checklist') {
            var itemsXml = '';
            var items = body.querySelectorAll('[data-ff-check-item]');
            items.forEach(function (row) {
                var input = row.querySelector('input[type="text"]');
                var checkbox = row.querySelector('input[type="checkbox"]');
                if (!input || input.value.trim() === '') {
                    return;
                }
                itemsXml += '<item checked="' + (checkbox && checkbox.checked ? '1' : '0') + '">' + escapeXml(input.value.trim()) + '</item>';
            });
            return '<block type="list_checklist">' + itemsXml + '</block>';
        }

        if (type === 'list_definition' || type === 'key_value') {
            var pairs = body.querySelectorAll('[data-ff-pair]');
            var pairsXml = '';
            pairs.forEach(function (row) {
                var inputs = row.querySelectorAll('input');
                if (inputs.length < 2) {
                    return;
                }
                var key = inputs[0].value.trim();
                var value = inputs[1].value.trim();
                if (key === '' && value === '') {
                    return;
                }
                pairsXml += '<pair key="' + escapeXml(key) + '" value="' + escapeXml(value) + '"></pair>';
            });
            return '<block type="' + escapeXml(type) + '">' + pairsXml + '</block>';
        }

        if (type === 'grid') {
            var select = body.querySelector('select');
            var columns = select ? select.value : '2';
            var gridXml = '<block type="grid" columns="' + escapeXml(columns) + '">';
            var cols = body.querySelectorAll('.ff-freewrite-grid-col');
            cols.forEach(function (col) {
                var colContainer = col.querySelector('[data-ff-blocks]');
                var nestedXml = serializeNestedBlocks(colContainer, depth + 1);
                gridXml += '<column width="' + escapeXml(String(Math.floor(12 / parseInt(columns, 10)))) + '">' + nestedXml + '</column>';
            });
            gridXml += '</block>';
            return gridXml;
        }

        if (type === 'section' || type === 'group' || type === 'container' || type === 'card') {
            var container = body.querySelector('[data-ff-blocks]');
            var nested = serializeNestedBlocks(container, depth + 1);
            return '<block type="' + escapeXml(type) + '">' + nested + '</block>';
        }

        if (type === 'tabs') {
            var tabsXml = '<block type="tabs">';
            var tabs = body.querySelectorAll('.card');
            tabs.forEach(function (card) {
                var titleInput = card.querySelector('input');
                var title = titleInput ? titleInput.value.trim() : '';
                var tabContainer = card.querySelector('[data-ff-blocks]');
                var tabBlocks = serializeNestedBlocks(tabContainer, depth + 1);
                tabsXml += '<tab title="' + escapeXml(title) + '">' + tabBlocks + '</tab>';
            });
            tabsXml += '</block>';
            return tabsXml;
        }

        if (type === 'accordion') {
            var accXml = '<block type="accordion">';
            var items = body.querySelectorAll('.card');
            items.forEach(function (card) {
                var titleInput = card.querySelector('input');
                var title = titleInput ? titleInput.value.trim() : '';
                var itemContainer = card.querySelector('[data-ff-blocks]');
                var itemBlocks = serializeNestedBlocks(itemContainer, depth + 1);
                accXml += '<item title="' + escapeXml(title) + '">' + itemBlocks + '</item>';
            });
            accXml += '</block>';
            return accXml;
        }

        if (type === 'image' || type === 'figure') {
            var inputs = body.querySelectorAll('input');
            var alt = inputs.length > 0 ? inputs[0].value.trim() : '';
            var caption = inputs.length > 1 ? inputs[1].value.trim() : '';
            var selects = body.querySelectorAll('select');
            var alignment = selects.length > 0 ? selects[0].value : '';
            var size = selects.length > 1 ? selects[1].value : '';
            var widthInput = body.querySelector('[data-ff-image-width]');
            var heightInput = body.querySelector('[data-ff-image-height]');
            var width = widthInput ? widthInput.value.trim() : '';
            var height = heightInput ? heightInput.value.trim() : '';
            var preview = body.querySelector('img');
            var src = preview ? preview.getAttribute('src') : '';
            return '<block type="' + escapeXml(type) + '" src="' + escapeXml(src) + '" alt="' + escapeXml(alt) + '" caption="' + escapeXml(caption) + '" align="' + escapeXml(alignment) + '" size="' + escapeXml(size) + '" width="' + escapeXml(width) + '" height="' + escapeXml(height) + '"></block>';
        }

        if (type === 'gallery') {
            var galleryXml = '<block type="gallery">';
            var rows = body.querySelectorAll('[data-ff-gallery-item]');
            rows.forEach(function (row) {
                var img = row.querySelector('img');
                var captionInput = row.querySelector('input');
                var src = img ? img.getAttribute('src') : '';
                if (!src) {
                    return;
                }
                galleryXml += '<image src="' + escapeXml(src) + '" alt="' + escapeXml(captionInput ? captionInput.value.trim() : '') + '"></image>';
            });
            galleryXml += '</block>';
            return galleryXml;
        }

        if (type === 'icon') {
            var iconInputs = body.querySelectorAll('input');
            var name = iconInputs.length > 0 ? iconInputs[0].value.trim() : '';
            var styleSelect = body.querySelector('select');
            var style = styleSelect ? styleSelect.value : 'solid';
            return '<block type="icon" style="' + escapeXml(style) + '" name="' + escapeXml(name) + '"></block>';
        }

        if (type === 'table') {
            var tableXml = '<block type="table">';
            var tableRows = body.querySelectorAll('[data-ff-table-row]');
            tableRows.forEach(function (row) {
                tableXml += '<row>';
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function (input) {
                    tableXml += '<cell>' + escapeXml(input.value.trim()) + '</cell>';
                });
                tableXml += '</row>';
            });
            tableXml += '</block>';
            return tableXml;
        }

        if (type === 'button') {
            var buttonInputs = body.querySelectorAll('input');
            var label = buttonInputs.length > 0 ? buttonInputs[0].value.trim() : '';
            var url = buttonInputs.length > 1 ? buttonInputs[1].value.trim() : '';
            var styleSelect = body.querySelector('select');
            var style = styleSelect ? styleSelect.value : 'primary';
            return '<block type="button" url="' + escapeXml(url) + '" label="' + escapeXml(label) + '" style="' + escapeXml(style) + '"></block>';
        }

        if (type === 'file') {
            var fileInputs = body.querySelectorAll('input');
            var fileUrl = fileInputs.length > 0 ? fileInputs[0].value.trim() : '';
            var fileLabel = fileInputs.length > 1 ? fileInputs[1].value.trim() : '';
            return '<block type="file" url="' + escapeXml(fileUrl) + '" label="' + escapeXml(fileLabel) + '"></block>';
        }

        if (type === 'link_card') {
            var linkInputs = body.querySelectorAll('input');
            var title = linkInputs.length > 0 ? linkInputs[0].value.trim() : '';
            var url = linkInputs.length > 1 ? linkInputs[1].value.trim() : '';
            var label = linkInputs.length > 2 ? linkInputs[2].value.trim() : '';
            var desc = body.querySelector('textarea') ? body.querySelector('textarea').value.trim() : '';
            return '<block type="link_card" url="' + escapeXml(url) + '" title="' + escapeXml(title) + '" description="' + escapeXml(desc) + '" label="' + escapeXml(label) + '"></block>';
        }

        if (type === 'embed') {
            var embedUrl = getFieldValue(body, 'input');
            return '<block type="embed" url="' + escapeXml(embedUrl) + '"></block>';
        }

        if (type === 'html') {
            var htmlSource = getFieldValue(body, 'textarea');
            return '<block type="html"><![CDATA[' + cdataWrap(htmlSource) + ']]></block>';
        }

        return '';
    }

    function serializeNestedBlocks(container, depth) {
        var xml = '';
        if (!container) {
            return xml;
        }
        Array.prototype.slice.call(container.children).forEach(function (child) {
            if (child.classList && child.classList.contains('ff-freewrite-block')) {
                xml += serializeBlock(child, depth + 1);
            }
        });
        return xml;
    }

    function getFieldValue(body, selector) {
        var field = body.querySelector(selector);
        if (!field) {
            return '';
        }
        return field.value || '';
    }

    function escapeXml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function cdataWrap(value) {
        return String(value || '').replace(/\]\]>/g, ']]]]><![CDATA[>');
    }

    function sanitizeRichtext(html) {
        var container = document.createElement('div');
        container.innerHTML = html;
        var allowed = ['P', 'BR', 'STRONG', 'EM', 'U', 'S', 'A', 'CODE', 'KBD', 'SUB', 'SUP', 'SPAN'];
        var walker = document.createTreeWalker(container, NodeFilter.SHOW_ELEMENT, null);
        var nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }
        nodes.forEach(function (node) {
            if (node.nodeName === 'B' || node.nodeName === 'I') {
                var tag = node.nodeName === 'B' ? 'STRONG' : 'EM';
                var replacement = document.createElement(tag.toLowerCase());
                while (node.firstChild) {
                    replacement.appendChild(node.firstChild);
                }
                node.parentNode.replaceChild(replacement, node);
                node = replacement;
            }
            if (allowed.indexOf(node.nodeName) === -1) {
                node.replaceWith(document.createTextNode(node.textContent || ''));
                return;
            }
            Array.prototype.slice.call(node.attributes).forEach(function (attr) {
                var name = attr.name.toLowerCase();
                if (name === 'style' || name.indexOf('on') === 0) {
                    node.removeAttribute(attr.name);
                    return;
                }
                if (node.nodeName === 'A' && name === 'href') {
                    if (!isSafeUrl(attr.value)) {
                        node.removeAttribute('href');
                    }
                }
                if (node.nodeName !== 'A' && node.nodeName !== 'SPAN') {
                    node.removeAttribute(attr.name);
                }
                if (node.nodeName === 'SPAN' && name !== 'class') {
                    node.removeAttribute(attr.name);
                }
            });
        });
        return container.innerHTML;
    }

    function loadFromXml(xml) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(xml, 'text/xml');
        var rootNode = doc.getElementsByTagName('freewrite')[0];
        if (!rootNode) {
            return;
        }
        blocksRoot.innerHTML = '';
        Array.prototype.slice.call(rootNode.childNodes).forEach(function (node) {
            if (node.nodeName !== 'block') {
                return;
            }
            var block = buildBlock(node.getAttribute('type'), parseBlockData(node), 0);
            blocksRoot.appendChild(block);
        });
        updateMailContentButton();
    }

    function extractBlockData(block) {
        var type = block.getAttribute('data-ff-type');
        var body = block.querySelector('.ff-freewrite-block-body');
        if (!type || !body) {
            return {};
        }
        if (type === 'mailcontent') {
            return {};
        }
        if (type === 'richtext') {
            var rich = body.querySelector('[data-ff-richtext]');
            return { html: rich ? rich.innerHTML : '' };
        }
        if (type === 'heading') {
            var headingText = getFieldValue(body, 'input');
            var level = body.querySelector('select') ? body.querySelector('select').value : '2';
            return { text: headingText, level: level };
        }
        if (type === 'paragraph' || type === 'lead' || type === 'small' || type === 'quote' || type === 'pullquote' || type === 'code' || type === 'preformatted' || type === 'callout' || type === 'shortcode') {
            return { text: getFieldValue(body, 'textarea') || getFieldValue(body, 'input') };
        }
        if (type === 'list_bulleted' || type === 'list_numbered') {
            var listText = getFieldValue(body, 'textarea');
            return { items: listText.split(/\n+/).map(function (item) { return item.trim(); }).filter(Boolean) };
        }
        if (type === 'list_checklist') {
            var items = [];
            var rows = body.querySelectorAll('[data-ff-check-item]');
            rows.forEach(function (row) {
                var input = row.querySelector('input[type="text"]');
                var checkbox = row.querySelector('input[type="checkbox"]');
                if (!input) {
                    return;
                }
                items.push({ text: input.value, checked: checkbox && checkbox.checked });
            });
            return { items: items };
        }
        if (type === 'list_definition' || type === 'key_value') {
            var pairs = [];
            var pairRows = body.querySelectorAll('[data-ff-pair]');
            pairRows.forEach(function (row) {
                var inputs = row.querySelectorAll('input');
                if (inputs.length < 2) {
                    return;
                }
                pairs.push({ key: inputs[0].value, value: inputs[1].value });
            });
            return { items: pairs };
        }
        if (type === 'grid') {
            var select = body.querySelector('select');
            var columns = select ? parseInt(select.value, 10) : 2;
            var cols = [];
            var colEls = body.querySelectorAll('.ff-freewrite-grid-col');
            colEls.forEach(function (col) {
                var colContainer = col.querySelector('[data-ff-blocks]');
                cols.push(getBlocksFromContainer(colContainer));
            });
            return { columns: columns, items: cols };
        }
        if (type === 'section' || type === 'group' || type === 'container' || type === 'card') {
            var container = body.querySelector('[data-ff-blocks]');
            return { items: getBlocksFromContainer(container) };
        }
        if (type === 'tabs') {
            var tabs = [];
            var cards = body.querySelectorAll('.card');
            cards.forEach(function (card) {
                var titleInput = card.querySelector('input');
                var tabContainer = card.querySelector('[data-ff-blocks]');
                tabs.push({ title: titleInput ? titleInput.value : '', items: getBlocksFromContainer(tabContainer) });
            });
            return { items: tabs };
        }
        if (type === 'accordion') {
            var itemsAcc = [];
            var accCards = body.querySelectorAll('.card');
            accCards.forEach(function (card) {
                var titleInput = card.querySelector('input');
                var accContainer = card.querySelector('[data-ff-blocks]');
                itemsAcc.push({ title: titleInput ? titleInput.value : '', items: getBlocksFromContainer(accContainer) });
            });
            return { items: itemsAcc };
        }
        if (type === 'image' || type === 'figure') {
            var preview = body.querySelector('img');
            var inputs = body.querySelectorAll('input');
            var selects = body.querySelectorAll('select');
            var widthInput = body.querySelector('[data-ff-image-width]');
            var heightInput = body.querySelector('[data-ff-image-height]');
            return {
                src: preview ? preview.getAttribute('src') : '',
                alt: inputs.length > 0 ? inputs[0].value : '',
                caption: inputs.length > 1 ? inputs[1].value : '',
                alignment: selects.length > 0 ? selects[0].value : 'left',
                size: selects.length > 1 ? selects[1].value : 'medium',
                width: widthInput ? widthInput.value : '',
                height: heightInput ? heightInput.value : ''
            };
        }
        if (type === 'gallery') {
            var gallery = [];
            var rowsGal = body.querySelectorAll('[data-ff-gallery-item]');
            rowsGal.forEach(function (row) {
                var img = row.querySelector('img');
                var input = row.querySelector('input');
                if (img) {
                    gallery.push({ src: img.getAttribute('src') || '', alt: input ? input.value : '' });
                }
            });
            return { items: gallery };
        }
        if (type === 'icon') {
            var iconInputs = body.querySelectorAll('input');
            var selectStyle = body.querySelector('select');
            return { name: iconInputs.length > 0 ? iconInputs[0].value : '', style: selectStyle ? selectStyle.value : 'solid' };
        }
        if (type === 'table') {
            var tableRows = [];
            var rowEls = body.querySelectorAll('[data-ff-table-row]');
            rowEls.forEach(function (rowEl) {
                var cells = [];
                var inputs = rowEl.querySelectorAll('input');
                inputs.forEach(function (input) { cells.push(input.value); });
                tableRows.push(cells);
            });
            return { rows: tableRows };
        }
        if (type === 'button') {
            var buttonInputs = body.querySelectorAll('input');
            var styleSelect = body.querySelector('select');
            return {
                label: buttonInputs.length > 0 ? buttonInputs[0].value : '',
                url: buttonInputs.length > 1 ? buttonInputs[1].value : '',
                style: styleSelect ? styleSelect.value : 'primary'
            };
        }
        if (type === 'file') {
            var fileInputs = body.querySelectorAll('input');
            return { url: fileInputs.length > 0 ? fileInputs[0].value : '', label: fileInputs.length > 1 ? fileInputs[1].value : '' };
        }
        if (type === 'link_card') {
            var linkInputs = body.querySelectorAll('input');
            var desc = body.querySelector('textarea') ? body.querySelector('textarea').value : '';
            return {
                title: linkInputs.length > 0 ? linkInputs[0].value : '',
                url: linkInputs.length > 1 ? linkInputs[1].value : '',
                label: linkInputs.length > 2 ? linkInputs[2].value : '',
                description: desc
            };
        }
        if (type === 'embed') {
            return { url: getFieldValue(body, 'input') };
        }
        if (type === 'html') {
            return { html: getFieldValue(body, 'textarea') };
        }
        return {};
    }

    function getBlocksFromContainer(container) {
        var items = [];
        if (!container) {
            return items;
        }
        Array.prototype.slice.call(container.children).forEach(function (child) {
            if (child.classList && child.classList.contains('ff-freewrite-block')) {
                items.push({ type: child.getAttribute('data-ff-type'), data: extractBlockData(child) });
            }
        });
        return items;
    }

    function parseBlockData(node) {
        var type = node.getAttribute('type');
        if (type === 'richtext') {
            return { html: node.textContent || '' };
        }
        if (type === 'heading') {
            return { text: node.textContent || '', level: node.getAttribute('level') || '2' };
        }
        if (type === 'paragraph' || type === 'lead' || type === 'small' || type === 'quote' || type === 'pullquote' || type === 'code' || type === 'preformatted' || type === 'callout' || type === 'shortcode') {
            return { text: node.textContent || '' };
        }
        if (type === 'list_bulleted' || type === 'list_numbered') {
            return { items: getItemTexts(node) };
        }
        if (type === 'list_checklist') {
            return { items: getChecklistItems(node) };
        }
        if (type === 'list_definition' || type === 'key_value') {
            return { items: getPairs(node) };
        }
        if (type === 'grid') {
            return { columns: parseInt(node.getAttribute('columns') || '2', 10), items: getGridColumns(node) };
        }
        if (type === 'section' || type === 'group' || type === 'container' || type === 'card') {
            return { items: getNestedBlocks(node) };
        }
        if (type === 'tabs') {
            return { items: getTabs(node) };
        }
        if (type === 'accordion') {
            return { items: getAccordions(node) };
        }
        if (type === 'image' || type === 'figure') {
            return {
                src: node.getAttribute('src') || '',
                alt: node.getAttribute('alt') || '',
                caption: node.getAttribute('caption') || '',
                alignment: node.getAttribute('align') || 'left',
                size: node.getAttribute('size') || 'medium',
                width: node.getAttribute('width') || '',
                height: node.getAttribute('height') || ''
            };
        }
        if (type === 'gallery') {
            return { items: getGalleryItems(node) };
        }
        if (type === 'icon') {
            return { name: node.getAttribute('name') || '', style: node.getAttribute('style') || 'solid' };
        }
        if (type === 'table') {
            return { rows: getTableRows(node) };
        }
        if (type === 'button') {
            return { label: node.getAttribute('label') || '', url: node.getAttribute('url') || '', style: node.getAttribute('style') || 'primary' };
        }
        if (type === 'file') {
            return { url: node.getAttribute('url') || '', label: node.getAttribute('label') || '' };
        }
        if (type === 'link_card') {
            return {
                title: node.getAttribute('title') || '',
                description: node.getAttribute('description') || '',
                url: node.getAttribute('url') || '',
                label: node.getAttribute('label') || ''
            };
        }
        if (type === 'embed') {
            return { url: node.getAttribute('url') || '' };
        }
        if (type === 'html') {
            return { html: node.textContent || '' };
        }
        if (type === 'mailcontent') {
            return {};
        }
        return {};
    }

    function getItemTexts(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'item') {
                items.push(child.textContent || '');
            }
        });
        return items;
    }

    function getChecklistItems(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'item') {
                items.push({ text: child.textContent || '', checked: child.getAttribute('checked') === '1' });
            }
        });
        return items;
    }

    function getPairs(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'pair') {
                items.push({ key: child.getAttribute('key') || '', value: child.getAttribute('value') || '' });
            }
        });
        return items;
    }

    function getGridColumns(node) {
        var cols = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'column') {
                cols.push(getNestedBlocks(child));
            }
        });
        return cols;
    }

    function getNestedBlocks(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'block') {
                items.push({ type: child.getAttribute('type') || '', data: parseBlockData(child) });
            }
        });
        return items;
    }

    function getTabs(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'tab') {
                items.push({ title: child.getAttribute('title') || '', items: getNestedBlocks(child) });
            }
        });
        return items;
    }

    function getAccordions(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'item') {
                items.push({ title: child.getAttribute('title') || '', items: getNestedBlocks(child) });
            }
        });
        return items;
    }

    function getGalleryItems(node) {
        var items = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
            if (child.nodeName === 'image') {
                items.push({ src: child.getAttribute('src') || '', alt: child.getAttribute('alt') || '' });
            }
        });
        return items;
    }

    function getTableRows(node) {
        var rows = [];
        Array.prototype.slice.call(node.childNodes).forEach(function (row) {
            if (row.nodeName !== 'row') {
                return;
            }
            var cells = [];
            Array.prototype.slice.call(row.childNodes).forEach(function (cell) {
                if (cell.nodeName === 'cell') {
                    cells.push(cell.textContent || '');
                }
            });
            rows.push(cells);
        });
        return rows;
    }
})();
