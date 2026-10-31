
/* ===== Render Shared Note ===== */

function renderSharedNote(data) {
    const template =
        document.getElementById('sharedNoteTemplate');

    const clone = template.content.cloneNode(true);
    const card = clone.querySelector('.shared-note-card');

    card.querySelector('.shared-note-title').textContent =
        data.title || 'Untitled';

    const receivedEl =
        card.querySelector('.shared-note-received');

    if (data.receivedDate) {
        receivedEl.textContent =
            `Received: ${data.receivedDate}`;
    } else {
        receivedEl.remove();
    }

    const sharerEl =
        card.querySelector('.shared-note-sharer');

    if (data.sharerEmail) {
        sharerEl.querySelector('.sharer-email').textContent =
            data.sharerEmail;
    } else {
        sharerEl.remove();
    }

    const permissionEl =
        card.querySelector('.shared-note-permission');

    if (data.permission === 'read-only') {
        permissionEl.classList.add('read-only');

        permissionEl.querySelector('i').className =
            'bi bi-eye';

        permissionEl.querySelector('.permission-label')
            .textContent = 'Read-only';

    } else if (data.permission === 'editable') {
        permissionEl.classList.add('editable');

        permissionEl.querySelector('i').className =
            'bi bi-pencil';

        permissionEl.querySelector('.permission-label')
            .textContent = 'Editable';

    } else {
        permissionEl.remove();
    }

    if (
        !data.receivedDate &&
        !data.sharerEmail &&
        !data.permission
    ) {
        card.classList.add('is-minimal');
    }

    return card;
}


/* ===== Load Shared Notes ===== */

async function loadSharedNotes() {
    const grid = document.getElementById('sharedGrid');

    if (!grid) return;

    grid.innerHTML = '';

    // Mock data, replace with API when backend is ready
    const notes = [
        {
            title: 'Machine Learning',
            receivedDate: '19/8/2026',
            sharerEmail: 'a@gmail.com',
            permission: 'read-only'
        },
        {
            title: 'Project Plan',
            sharerEmail: 'b@gmail.com',
            permission: 'editable'
        },
        {
            title: 'Random note'
        }
    ];

    notes.forEach(note => {
        const card = renderSharedNote(note);
        grid.appendChild(card);
    });
}


/* ===== Shared Panel Navigation ===== */

document.addEventListener('DOMContentLoaded', () => {

    const sharedBtn =
        document.getElementById('shareWithBtn');

    const noteBoard =
        document.getElementById('noteBoardView');

    const sharedBoard =
        document.getElementById('sharedBoardView');

    if (!sharedBtn || !noteBoard || !sharedBoard) {
        return;
    }

    function isOpen() {
        return !sharedBoard.classList.contains('d-none');
    }

    function setSharedView(open) {
        sharedBoard.classList.toggle('d-none', !open);
        noteBoard.classList.toggle('d-none', open);

        // Close Account / Password
        document.querySelectorAll('.app-panel.active')
            .forEach(panel => {
                panel.classList.remove('active');
            });

        document.querySelector('.app-sidebar')
            ?.classList.remove('show');

        if (open) {
            loadSharedNotes();
        }
    }

    // Toggle Shared with me
    sharedBtn.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        const opening = !isOpen();

        const filterPanel =
            document.getElementById('filterTagsPanel');

        if (filterPanel) {
            filterPanel.hidden = true;
            filterPanel.classList.add('d-none');

            const filterBtn =
                document.getElementById('filterTagsBtn');

            filterBtn?.setAttribute(
                'aria-expanded',
                'false'
            );

            filterBtn?.classList.remove('is-active');
        }

        setSharedView(opening);
    });

    // Close Shared when clicking outside
    document.addEventListener('click', event => {
        if (
            !isOpen() ||
            sharedBoard.contains(event.target) ||
            sharedBtn.contains(event.target)
        ) {
            return;
        }

        if (
            event.target.closest(
                '.nav-panel-btn, #filterTagsBtn'
            )
        ) {
            return;
        }

        setSharedView(false);
    });
});
