// Filter tags (frontend-only): replace localStorage adapter with backend API later.
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('filterTagsPanel');
    const toggle = document.getElementById('filterTagsBtn');
    const items = document.getElementById('filterTagItems');
    const allButton = document.getElementById('filterAllTags');
    const board = document.getElementById('noteBoardView');
    const input = document.getElementById('filterTagInput');
    const error = document.getElementById('filterTagError');
    const form = document.getElementById('filterTagForm');
    const modalTitle = document.getElementById('filterTagModalTitle');
    const message = document.getElementById('filterTagsMessage');
    if (!panel || !toggle || !board || !window.bootstrap) return;

    const storageKey = 'sgn.filter-tags.v1';
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('filterTagModal'));
    const deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteFilterTagModal'));
    let tags = [];
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (Array.isArray(saved)) tags = saved.filter(t => t && typeof t.id === 'string' && typeof t.name === 'string');
    } catch { /* Empty local cache */ }
    let selectedId = null;
    let editingId = null;
    let deletingId = null;
    const save = () => {
        try { localStorage.setItem(storageKey, JSON.stringify(tags)); message.textContent = ''; }
        catch { message.textContent = 'Cannot save tags in this browser.'; }
    };
    const normalize = value => value.trim().replace(/\s+/g, ' ');

    function filterNotes() {
        // Notes supplied by backend should expose their tag IDs in data-tag-ids, e.g. "id1,id2".
        // Untagged demo notes remain visible under All tags.
        board.querySelectorAll('.note-card').forEach(note => {
            const ids = (note.dataset.tagIds || '').split(',').filter(Boolean);
            note.classList.toggle('d-none', selectedId !== null && !ids.includes(selectedId));
        });
        allButton.classList.toggle('is-selected', selectedId === null);
        items.querySelectorAll('.filter-tag-item').forEach(row => row.classList.toggle('is-selected', row.dataset.id === selectedId));
    }
    function render() {
        items.replaceChildren();
        tags.forEach(tag => {
            const row = document.createElement('div');
            row.className = 'filter-tag-item';
            row.dataset.id = tag.id;
            const name = document.createElement('button');
            name.type = 'button'; name.className = 'filter-tag-name'; name.textContent = tag.name;
            name.title = `Filter by ${tag.name}`;
            name.addEventListener('click', () => { selectedId = tag.id; filterNotes(); });
            const edit = document.createElement('button');
            edit.type = 'button'; edit.className = 'filter-tag-action'; edit.setAttribute('aria-label', `Rename ${tag.name}`);
            edit.innerHTML = '<i class="bi bi-pencil-fill"></i>';
            edit.addEventListener('click', () => openEditor(tag.id));
            const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'filter-tag-action'; remove.setAttribute('aria-label', `Delete ${tag.name}`);
            remove.innerHTML = '<i class="bi bi-trash"></i>';
            remove.addEventListener('click', () => { deletingId = tag.id; deleteModal.show(); });
            row.append(name, edit, remove); items.append(row);
        });
        filterNotes();
    }
    function openEditor(id = null) {
        editingId = id;
        const tag = tags.find(item => item.id === id);
        modalTitle.textContent = tag ? 'Rename tag' : 'Add tag';
        input.value = tag ? tag.name : '';
        input.classList.remove('is-invalid'); error.textContent = '';
        modal.show();
    }
    form.addEventListener('submit', event => {
        event.preventDefault();
        const name = normalize(input.value);
        const duplicate = tags.some(tag => tag.id !== editingId && tag.name.toLocaleLowerCase() === name.toLocaleLowerCase());
        if (!name || duplicate) {
            error.textContent = !name ? 'Please enter a tag name.' : 'This tag already exists.';
            input.classList.add('is-invalid'); return;
        }
        if (editingId) tags.find(tag => tag.id === editingId).name = name;
        else tags.push({ id: crypto.randomUUID(), name });
        save(); render(); modal.hide();
    });
    document.getElementById('addFilterTag').addEventListener('click', () => openEditor());
    document.getElementById('confirmDeleteFilterTag').addEventListener('click', () => {
        tags = tags.filter(tag => tag.id !== deletingId);
        if (selectedId === deletingId) selectedId = null;
        save(); render(); deleteModal.hide(); deletingId = null;
    });
    allButton.addEventListener('click', () => { selectedId = null; filterNotes(); });
    function setPanel(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) document.getElementById('sharedBoardView')?.classList.add('d-none');
        if (open) board.classList.remove('d-none');
        document.querySelector('.app-sidebar')?.classList.remove('show');
    }
    toggle.addEventListener('click', () => setPanel(panel.hidden));
    toggle.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setPanel(panel.hidden); } });
    document.getElementById('shareWithBtn')?.addEventListener('click', () => setPanel(false));
    // Refilter any notes newly rendered by the existing note creation script.
    const observer = new MutationObserver(() => filterNotes());
    observer.observe(document.getElementById('noteGrid'), { childList: true });
    render();
});
