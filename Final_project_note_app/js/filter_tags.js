
(() => {
    'use strict';

    function initFilterTags() {
        const panel =
            document.getElementById('filterTagsPanel') ||
            document.querySelector('.filter-tags-panel');

        const sidebar = document.querySelector('.app-sidebar');

        const toggle =
            document.getElementById('filterTagsBtn') ||
            [...(sidebar?.querySelectorAll('.sidebar-icon-item') || [])]
                .find(el =>
                    el.textContent.trim().toLowerCase() === 'filter tags'
                );

        if (!toggle || !panel) {
            console.warn(
                '[Filter Tags] Missing panel or sidebar button.'
            );
            return;
        }

        toggle.id = 'filterTagsBtn';
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('tabindex', '0');
        toggle.setAttribute('aria-controls', 'filterTagsPanel');

        panel.id = 'filterTagsPanel';

        const closeButton =
            document.getElementById('closeFilterTagsBtn');

        const board =
            document.getElementById('noteBoardView');

        const sharedBoard =
            document.getElementById('sharedBoardView');

        function isPanelOpen() {
            return !panel.hidden &&
                !panel.classList.contains('d-none');
        }

        function setPanel(open) {
            panel.hidden = !open;
            panel.classList.toggle('d-none', !open);

            toggle.setAttribute(
                'aria-expanded',
                String(open)
            );

            toggle.classList.toggle('is-active', open);

            if (open) {
                sharedBoard?.classList.add('d-none');
                board?.classList.remove('d-none');

                document.querySelectorAll('.app-panel.active')
                    .forEach(p => p.classList.remove('active'));

                sidebar?.classList.remove('show');
            }
        }

        // Toggle Filter Tags
        toggle.addEventListener('click', event => {
            event.preventDefault();
            setPanel(!isPanelOpen());
        });

        // Close using X
        closeButton?.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            setPanel(false);
            toggle.focus();
        });

        // Close when clicking outside panel
        document.addEventListener('click', event => {
            if (!isPanelOpen()) return;

            if (
                panel.contains(event.target) ||
                toggle.contains(event.target)
            ) {
                return;
            }

            // Do not close while using Bootstrap modals
            if (
                event.target.closest(
                    '#filterTagModal, #deleteFilterTagModal, .modal-backdrop'
                )
            ) {
                return;
            }

            if (
                document.querySelector(
                    '#filterTagModal.show, #deleteFilterTagModal.show'
                )
            ) {
                return;
            }

            setPanel(false);
        });

        // Keyboard support
        toggle.addEventListener('keydown', event => {
            if (
                event.key === 'Enter' ||
                event.key === ' '
            ) {
                event.preventDefault();
                setPanel(!isPanelOpen());
            }
        });

        // Close when opening Shared
        document.getElementById('shareWithBtn')
            ?.addEventListener('click', () => setPanel(false));

        // Close when navigating to another panel
        document.querySelectorAll('.nav-panel-btn')
            .forEach(button => {
                button.addEventListener(
                    'click',
                    () => setPanel(false)
                );
            });

        setPanel(isPanelOpen());
        initTagManager(panel, board);
    }


    /* ===== Tag Management ===== */

    function initTagManager(panel, board) {
        const items = panel.querySelector('#filterTagItems');
        const allButton = panel.querySelector('#filterAllTags');
        const addButton = panel.querySelector('#addFilterTag');
        const message = panel.querySelector('#filterTagsMessage');

        if (!items || !allButton || !addButton) {
            console.warn('[Filter Tags] Missing tag controls.');
            return;
        }

        const input =
            document.getElementById('filterTagInput');

        const form =
            document.getElementById('filterTagForm');

        const modalTitle =
            document.getElementById('filterTagModalTitle');

        const error =
            document.getElementById('filterTagError');

        const confirmDeleteButton =
            document.getElementById('confirmDeleteFilterTag');

        function getModal(id) {
            const element = document.getElementById(id);

            return element && window.bootstrap?.Modal
                ? bootstrap.Modal.getOrCreateInstance(element)
                : null;
        }

        const editorModal = getModal('filterTagModal');
        const deleteModal = getModal('deleteFilterTagModal');

        const storageKey = 'sgn.filter-tags.v1';

        let tags = [];

        // Load stored tags
        try {
            const data = JSON.parse(
                localStorage.getItem(storageKey) || '[]'
            );

            if (Array.isArray(data)) {
                tags = data.filter(tag =>
                    tag &&
                    typeof tag.id === 'string' &&
                    typeof tag.name === 'string'
                );
            }
        } catch (err) {
            console.warn(
                '[Filter Tags] Could not read localStorage:',
                err
            );
        }

        let selectedId = null;
        let editingId = null;
        let deletingId = null;

        function setMessage(text) {
            if (message) {
                message.textContent = text;
            }
        }

        function save() {
            try {
                localStorage.setItem(
                    storageKey,
                    JSON.stringify(tags)
                );

                setMessage('');
            } catch (err) {
                console.warn(
                    '[Filter Tags] Could not save tags:',
                    err
                );

                setMessage(
                    'Không lưu được tag trên trình duyệt này.'
                );
            }
        }

        function normalized(text) {
            return text.trim().replace(/\s+/g, ' ');
        }

        function hasDuplicate(name, excludeId) {
            return tags.some(tag =>
                tag.id !== excludeId &&
                tag.name.toLocaleLowerCase() ===
                    name.toLocaleLowerCase()
            );
        }

        function makeId() {
            return window.crypto?.randomUUID?.() ||
                `tag-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        }


        /* ===== Filter Notes ===== */

        function filterNotes() {
            if (board) {
                board.querySelectorAll('.note-card')
                    .forEach(note => {
                        const ids = (
                            note.dataset.tagIds || ''
                        )
                            .split(',')
                            .map(v => v.trim())
                            .filter(Boolean);

                        note.classList.toggle(
                            'd-none',
                            selectedId !== null &&
                                !ids.includes(selectedId)
                        );
                    });
            }

            allButton.classList.toggle(
                'is-selected',
                selectedId === null
            );

            items.querySelectorAll('.filter-tag-item')
                .forEach(row => {
                    row.classList.toggle(
                        'is-selected',
                        row.dataset.id === selectedId
                    );
                });
        }


        /* ===== Add / Rename Tag ===== */

        function insertTag(name) {
            const value = normalized(name);

            if (
                !value ||
                value.length > 40 ||
                hasDuplicate(value, editingId)
            ) {
                return !value
                    ? 'Vui lòng nhập tên tag.'
                    : value.length > 40
                        ? 'Tên tag tối đa 40 ký tự.'
                        : 'Tag này đã tồn tại.';
            }

            if (editingId) {
                const tag = tags.find(
                    item => item.id === editingId
                );

                if (tag) {
                    tag.name = value;
                }
            } else {
                tags.push({
                    id: makeId(),
                    name: value
                });
            }

            save();
            render();
            editorModal?.hide();

            return null;
        }

        function openEditor(id = null) {
            editingId = id;

            const tag = tags.find(
                item => item.id === id
            );

            // Fallback without Bootstrap modal
            if (!editorModal || !form || !input) {
                const value = window.prompt(
                    tag ? 'Đổi tên tag:' : 'Tên tag mới:',
                    tag?.name || ''
                );

                if (value === null) {
                    return;
                }

                const problem = insertTag(value);

                if (problem) {
                    window.alert(problem);
                }

                return;
            }

            if (modalTitle) {
                modalTitle.textContent = tag
                    ? 'Rename tag'
                    : 'Add tag';
            }

            input.value = tag?.name || '';
            input.classList.remove('is-invalid');

            if (error) {
                error.textContent = '';
            }

            editorModal.show();
        }


        /* ===== Delete Tag ===== */

        function doDelete() {
            tags = tags.filter(
                tag => tag.id !== deletingId
            );

            if (selectedId === deletingId) {
                selectedId = null;
            }

            deletingId = null;

            save();
            render();

            deleteModal?.hide();
        }

        function openDelete(id) {
            deletingId = id;

            if (deleteModal && confirmDeleteButton) {
                deleteModal.show();
            } else if (
                window.confirm(
                    'Xóa tag này? Ghi chú sẽ không bị xóa.'
                )
            ) {
                doDelete();
            }
        }


        /* ===== Render Tags ===== */

        function render() {
            items.replaceChildren();

            tags.forEach(tag => {
                const row = document.createElement('div');

                row.className = 'filter-tag-item';
                row.dataset.id = tag.id;

                // Tag name
                const name = document.createElement('button');

                name.type = 'button';
                name.className = 'filter-tag-name';
                name.textContent = tag.name;

                name.addEventListener('click', () => {
                    selectedId = tag.id;
                    filterNotes();
                });

                // Edit button
                const edit = document.createElement('button');

                edit.type = 'button';
                edit.className = 'filter-tag-action';

                edit.setAttribute(
                    'aria-label',
                    `Rename ${tag.name}`
                );

                edit.innerHTML =
                    '<i class="bi bi-pencil-fill"></i>';

                edit.addEventListener(
                    'click',
                    () => openEditor(tag.id)
                );

                // Delete button
                const remove = document.createElement('button');

                remove.type = 'button';
                remove.className = 'filter-tag-action';

                remove.setAttribute(
                    'aria-label',
                    `Delete ${tag.name}`
                );

                remove.innerHTML =
                    '<i class="bi bi-trash"></i>';

                remove.addEventListener(
                    'click',
                    () => openDelete(tag.id)
                );

                row.append(name, edit, remove);
                items.append(row);
            });

            filterNotes();
        }


        /* ===== Event Listeners ===== */

        addButton.addEventListener(
            'click',
            () => openEditor()
        );

        allButton.addEventListener('click', () => {
            selectedId = null;
            filterNotes();
        });

        form?.addEventListener('submit', event => {
            event.preventDefault();

            const problem = insertTag(
                input?.value || ''
            );

            if (problem) {
                if (error) {
                    error.textContent = problem;
                }

                input?.classList.add('is-invalid');
            }
        });

        confirmDeleteButton?.addEventListener(
            'click',
            doDelete
        );

        // Refilter when notes are added/removed
        const grid = document.getElementById('noteGrid');

        if (
            grid &&
            typeof MutationObserver !== 'undefined'
        ) {
            new MutationObserver(filterNotes).observe(
                grid,
                { childList: true }
            );
        }

        render();
    }


    /* ===== Initialize ===== */

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initFilterTags,
            { once: true }
        );
    } else {
        initFilterTags();
    }
})();
