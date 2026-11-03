// SeedGrowthNote: MySQL-backed label management, note assignment and filtering.
(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', init, { once: true });

    function init() {
        const panel = document.getElementById('filterTagsPanel');
        const toggle = document.getElementById('filterTagsBtn');
        const close = document.getElementById('closeFilterTagsBtn');
        const items = document.getElementById('filterTagItems');
        const all = document.getElementById('filterAllTags');
        const add = document.getElementById('addFilterTag');
        const message = document.getElementById('filterTagsMessage');
        const grid = document.getElementById('noteGrid');
        const board = document.getElementById('noteBoardView');
        const sharedBoard = document.getElementById('sharedBoardView');
        const form = document.getElementById('filterTagForm');
        const input = document.getElementById('filterTagInput');
        const title = document.getElementById('filterTagModalTitle');
        const error = document.getElementById('filterTagError');
        const confirmDelete = document.getElementById('confirmDeleteFilterTag');
        if (!panel || !toggle || !items || !grid) return;

        const modal = id => {
            const el = document.getElementById(id);
            return el && window.bootstrap?.Modal ? bootstrap.Modal.getOrCreateInstance(el) : null;
        };
        const editModal = modal('filterTagModal');
        const deleteModal = modal('deleteFilterTagModal');
        let labels = [];
        let associations = [];
        let selectedId = null;
        let editingId = null;
        let deletingId = null;
        let filteredIds = null;
        let busy = false;

        // Assignment modal: separate from read-only Labels area of note editor.
        const assignment = document.createElement('div');
        assignment.className = 'modal fade';
        assignment.id = 'noteLabelsModal';
        assignment.tabIndex = -1;
        assignment.setAttribute('aria-hidden', 'true');
        assignment.innerHTML = `
          <div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="noteLabelsForm">
            <div class="modal-header"><h5 class="modal-title">Labels for note</h5>
              <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><div id="noteLabelsChoices"></div></div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-success" type="submit">Save labels</button></div>
          </form></div>`;
        document.body.appendChild(assignment);
        const assignmentModal = modal('noteLabelsModal');
        const choices = assignment.querySelector('#noteLabelsChoices');
        let assignmentNoteId = null;

        function setMessage(text) { if (message) message.textContent = text; }
        async function api(url, options = {}) {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) },
                ...options
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'API error');
            return result.data;
        }
        function post(url, body) { return api(url, { method: 'POST', body: JSON.stringify(body) }); }
        function normalize(s) { return s.trim().replace(/\s+/g, ' '); }
        function setPanel(open) {
            panel.hidden = !open;
            panel.classList.toggle('d-none', !open);
            toggle.classList.toggle('is-active', open);
            toggle.setAttribute('aria-expanded', String(open));
            if (open) {
                sharedBoard?.classList.add('d-none');
                board?.classList.remove('d-none');
                document.querySelectorAll('.app-panel.active').forEach(el => el.classList.remove('active'));
                document.querySelector('.app-sidebar')?.classList.remove('show');
            }
        }
        toggle.addEventListener('click', e => { e.preventDefault(); setPanel(panel.hidden); });
        toggle.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setPanel(panel.hidden); }
        });
        close?.addEventListener('click', () => setPanel(false));
        document.addEventListener('click', e => {
            if (panel.hidden || panel.contains(e.target) || toggle.contains(e.target)) return;
            if (e.target.closest('.modal, .modal-backdrop') || document.querySelector('.modal.show')) return;
            setPanel(false);
        });
        document.getElementById('shareWithBtn')?.addEventListener('click', () => setPanel(false));
        document.querySelectorAll('.nav-panel-btn').forEach(btn => btn.addEventListener('click', () => setPanel(false)));

        
        function syncCards() {
            grid.querySelectorAll('.note-card[data-note-id]').forEach(card => {
                const noteId = String(card.dataset.noteId);

                // Lấy tất cả labels đã gắn cho note
                const ownLabels = associations.filter(
                    a => String(a.note_id) === noteId
                );

                card.dataset.tagIds = ownLabels
                    .map(a => a.label_id)
                    .join(',');

                card.dataset.labels = ownLabels
                    .map(a => a.name)
                    .join(',');

                // Giữ nguyên chức năng lọc notes
                card.classList.toggle(
                    'd-none',
                    filteredIds !== null && !filteredIds.has(noteId)
                );

                let btn = card.querySelector('.sgn-card-label-button');

                // Tạo nút nếu chưa tồn tại
                if (!btn) {
                    btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'sgn-card-label-button';

                    btn.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        openAssignment(card.dataset.noteId);
                    });

                    card.appendChild(btn);
                }

                // Hiển thị tên labels thay vì chữ Labels cố định
                const labelNames = ownLabels.map(a => a.name);

                const displayText = labelNames.length > 0
                    ? labelNames.join(', ')
                    : 'Labels';

                // Không sử dụng innerHTML với tên label từ database
                let icon = btn.querySelector('i');
                if (!icon) {
                    icon = document.createElement('i');
                    icon.className = 'bi bi-tags';
                    btn.appendChild(icon);
                }

                let labelText = btn.querySelector('.sgn-label-text');
                if (!labelText) {
                    labelText = document.createElement('span');
                    labelText.className = 'sgn-label-text';
                    btn.appendChild(labelText);
                }

                if (labelText.textContent !== displayText) {
                    labelText.textContent = displayText;
                }

                btn.title = labelNames.length
                    ? 'Labels: ' + displayText
                    : 'Nhấn để gắn label';

                btn.setAttribute('aria-label', btn.title);
            });
        }
        
        // Khi note_board.js tải lại danh sách notes từ MySQL,
        // tự động hiển thị labels trên các note card mới.
        const labelsCardObserver = new MutationObserver(() => {
            syncCards();
        });

        labelsCardObserver.observe(grid, {
            childList: true
        });


        function highlight() {
            all?.classList.toggle('is-selected', selectedId === null);
            items.querySelectorAll('.filter-tag-item').forEach(row => {
                row.classList.toggle('is-selected', row.dataset.id === String(selectedId));
            });
        }
        async function applyFilter() {
            if (selectedId === null) {
                filteredIds = null;
            } else {
                const ids = await api('api/labels/filter_notes.php?label_id=' + encodeURIComponent(selectedId));
                filteredIds = new Set(ids.map(String));
            }
            syncCards();
            highlight();
        }
        async function selectFilter(id) {
            const previous = selectedId;
            selectedId = id;
            try { await applyFilter(); setMessage(''); }
            catch (e) { selectedId = previous; setMessage(e.message); }
        }
        function render() {
            items.replaceChildren();
            for (const label of labels) {
                const row = document.createElement('div');
                row.className = 'filter-tag-item';
                row.dataset.id = label.id;
                const name = document.createElement('button');
                name.type = 'button'; name.className = 'filter-tag-name'; name.textContent = label.name;
                name.addEventListener('click', () => selectFilter(String(label.id)));
                const edit = document.createElement('button');
                edit.type = 'button'; edit.className = 'filter-tag-action';
                edit.setAttribute('aria-label', 'Rename ' + label.name);
                edit.innerHTML = '<i class="bi bi-pencil-fill"></i>';
                edit.addEventListener('click', () => openEditor(label));
                const remove = document.createElement('button');
                remove.type = 'button'; remove.className = 'filter-tag-action';
                remove.setAttribute('aria-label', 'Delete ' + label.name);
                remove.innerHTML = '<i class="bi bi-trash"></i>';
                remove.addEventListener('click', () => openDelete(label));
                row.append(name, edit, remove);
                items.appendChild(row);
            }
            highlight(); syncCards();
        }
        async function refresh() {
            const [freshLabels, freshAssociations] = await Promise.all([
                api('api/labels/labels.php'), api('api/labels/note_labels.php')
            ]);
            labels = freshLabels;
            associations = freshAssociations;
            if (selectedId !== null && !labels.some(l => String(l.id) === selectedId)) selectedId = null;
            render();
            await applyFilter();
            // Update read-only labels in an editor that is already open.
            document.querySelectorAll('.note-card[data-note-id]').forEach(card => {
                document.dispatchEvent(new CustomEvent('seedgrowth:note-labels-changed', { detail: { note: card } }));
            });
        }
        async function runWrite(work) {
            if (busy) return;
            busy = true;
            try { await work(); await refresh(); setMessage(''); }
            catch (e) { setMessage(e.message); window.alert(e.message); }
            finally { busy = false; }
        }
        function openEditor(label = null) {
            editingId = label ? Number(label.id) : null;
            if (title) title.textContent = label ? 'Rename tag' : 'Add tag';
            if (input) { input.value = label?.name || ''; input.classList.remove('is-invalid'); }
            if (error) error.textContent = '';
            if (editModal) editModal.show();
            else {
                const name = window.prompt(label ? 'Đổi tên label' : 'Tên label mới', label?.name || '');
                if (name !== null) saveName(name);
            }
        }
        function saveName(raw) {
            const name = normalize(raw);
            if (!name || name.length > 40) {
                if (error) error.textContent = 'Tên label phải có 1-40 ký tự.';
                input?.classList.add('is-invalid');
                return;
            }
            return runWrite(async () => {
                await post('api/labels/labels.php', {
                    action: editingId ? 'rename' : 'create',
                    ...(editingId ? { label_id: editingId } : {}), name
                });
                editModal?.hide();
            });
        }
        function openDelete(label) {
            deletingId = Number(label.id);
            if (deleteModal) deleteModal.show();
            else if (confirm('Xóa label? Ghi chú sẽ không bị xóa.')) doDelete();
        }
        function doDelete() {
            return runWrite(async () => {
                await post('api/labels/labels.php', { action: 'delete', label_id: deletingId });
                deleteModal?.hide();
            });
        }
        function openAssignment(noteId) {
            assignmentNoteId = Number(noteId);
            if (!assignmentNoteId) return;
            choices.replaceChildren();
            const checked = new Set(associations.filter(a => Number(a.note_id) === assignmentNoteId)
                .map(a => String(a.label_id)));
            if (!labels.length) choices.textContent = 'Hãy tạo label trong Filter Tags trước.';
            for (const label of labels) {
                const item = document.createElement('label');
                item.className = 'sgn-label-choice';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox'; checkbox.value = label.id;
                checkbox.checked = checked.has(String(label.id));
                item.append(checkbox, document.createTextNode(' ' + label.name));
                choices.appendChild(item);
            }
            assignmentModal?.show();
        }
        assignment.querySelector('form').addEventListener('submit', e => {
            e.preventDefault();
            const ids = [...choices.querySelectorAll('input:checked')].map(el => Number(el.value));
            runWrite(async () => {
                await post('api/labels/note_labels.php', { note_id: assignmentNoteId, label_ids: ids });
                assignmentModal?.hide();
            });
        });
        form?.addEventListener('submit', e => { e.preventDefault(); saveName(input?.value || ''); });
        confirmDelete?.addEventListener('click', doDelete);
        add?.addEventListener('click', () => openEditor());
        all?.addEventListener('click', () => selectFilter(null));

        // Existing note_board.js adds cards asynchronously after fetching notes.
        new MutationObserver(syncCards).observe(grid, { childList: true });
        refresh().catch(e => setMessage('Không tải được labels: ' + e.message));
    }
})();
