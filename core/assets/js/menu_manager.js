document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.querySelector('[data-menu-item-form] [data-menu-type="1"]');
    if (typeSelect) {
        var updateSections = function () {
            var type = typeSelect.value;
            document.querySelectorAll('[data-menu-type-section]').forEach(function (section) {
                var match = section.getAttribute('data-menu-type-section') === type;
                section.classList.toggle('d-none', !match);
            });
        };
        typeSelect.addEventListener('change', updateSections);
        updateSections();
    }

    var treeRoot = document.querySelector('[data-menu-tree-root]');
    if (!treeRoot) {
        return;
    }

    var dragged = null;

    treeRoot.addEventListener('dragstart', function (event) {
        var item = event.target.closest('[data-menu-item]');
        if (!item) {
            return;
        }
        dragged = item;
        event.dataTransfer.effectAllowed = 'move';
    });

    treeRoot.addEventListener('dragover', function (event) {
        var list = event.target.closest('[data-menu-list]');
        var item = event.target.closest('[data-menu-item]');
        if (!list && !item) {
            return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    });

    treeRoot.addEventListener('drop', function (event) {
        if (!dragged) {
            return;
        }
        var listTarget = event.target.closest('[data-menu-list]');
        var itemTarget = event.target.closest('[data-menu-item]');
        if (!listTarget && !itemTarget) {
            return;
        }
        event.preventDefault();

        var newParentId = 0;
        var newDepth = 1;
        var targetList = listTarget;

        if (itemTarget && itemTarget !== dragged) {
            if (dragged.contains(itemTarget)) {
                return;
            }
            newParentId = parseInt(itemTarget.getAttribute('data-item-id') || '0', 10);
            newDepth = parseInt(itemTarget.getAttribute('data-depth') || '1', 10) + 1;
            targetList = getChildList(itemTarget, newDepth);
        } else if (listTarget) {
            newDepth = parseInt(listTarget.getAttribute('data-depth') || '1', 10);
        }

        var subtreeDepth = getSubtreeDepth(dragged);
        if (newDepth + subtreeDepth - 1 > 3) {
            return;
        }

        targetList.appendChild(dragged);
        updateDepths(dragged, newDepth);
        submitOrdering();
    });

    function getChildList(item, depth) {
        var wrapper = item.querySelector('.ff-menu-children');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'mt-2 ms-4 ff-menu-children';
            var list = document.createElement('ul');
            list.className = 'list-group ff-menu-tree';
            list.setAttribute('data-menu-list', '1');
            list.setAttribute('data-depth', String(depth));
            wrapper.appendChild(list);
            item.appendChild(wrapper);
            return list;
        }
        var list = wrapper.querySelector('[data-menu-list]');
        if (!list) {
            list = document.createElement('ul');
            list.className = 'list-group ff-menu-tree';
            list.setAttribute('data-menu-list', '1');
            list.setAttribute('data-depth', String(depth));
            wrapper.appendChild(list);
        }
        return list;
    }

    function getSubtreeDepth(item) {
        var max = 1;
        item.querySelectorAll('[data-menu-item]').forEach(function (child) {
            var depth = parseInt(child.getAttribute('data-depth') || '1', 10);
            var base = parseInt(item.getAttribute('data-depth') || '1', 10);
            max = Math.max(max, depth - base + 1);
        });
        return max;
    }

    function updateDepths(item, depth) {
        item.setAttribute('data-depth', String(depth));
        var list = item.querySelector('[data-menu-list]');
        if (list) {
            list.setAttribute('data-depth', String(depth + 1));
        }
        item.querySelectorAll(':scope [data-menu-item]').forEach(function (child) {
            var parent = child.parentElement.closest('[data-menu-item]');
            var parentDepth = parseInt(parent.getAttribute('data-depth') || '1', 10);
            child.setAttribute('data-depth', String(parentDepth + 1));
        });
    }

    function submitOrdering() {
        var reorderUrl = treeRoot.getAttribute('data-reorder-url');
        var csrf = treeRoot.getAttribute('data-reorder-csrf');
        if (!reorderUrl || !csrf) {
            return;
        }
        var formData = new FormData();
        formData.append('csrf_token', csrf);
        var rootList = treeRoot.querySelector('[data-menu-list]');
        if (!rootList) {
            return;
        }
        walkList(rootList, 0, formData);
        fetch(reorderUrl, { method: 'POST', body: formData })
            .then(function (response) { return response.text(); })
            .then(function (text) { return text; })
            .catch(function () { return; });
    }

    function walkList(list, parentId, formData) {
        var items = list.querySelectorAll(':scope > [data-menu-item]');
        items.forEach(function (item, index) {
            var itemId = item.getAttribute('data-item-id');
            formData.append('item_id[]', itemId);
            formData.append('parent_id[]', String(parentId));
            formData.append('ordering[]', String(index + 1));
            var childList = item.querySelector(':scope > .ff-menu-children > [data-menu-list]');
            if (childList) {
                walkList(childList, parseInt(itemId || '0', 10), formData);
            }
        });
    }
});
