
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    /* ==========================================
       1. GET ELEMENTS
    ========================================== */

    // Note Editor
    const noteContent = document.getElementById("noteContent");
    const noteName = document.getElementById("noteName");
    const noteTitle = document.getElementById("noteTitle");

    const renameButton = document.getElementById("renameNoteBtn");
    const backButton = document.getElementById("noteBackBtn");
    const linkButton = document.getElementById("noteLinkBtn");

    const pinToggle = document.getElementById("pinNoteToggle");
    const passwordToggle = document.getElementById("passwordNoteToggle");
    const passwordArea = document.getElementById("notePasswordArea");
    const aiButton = document.getElementById("noteAiBtn");

    // Workspace
    const workspace = document.getElementById("noteEditorWorkspace");
    const editorPanel = document.getElementById("noteEditorAppPanel");

    // Note Board
    const board = document.getElementById("noteBoardView");
    const grid = document.getElementById("noteGrid");
    const createButton = document.getElementById("btnCreateNote");

    // Attachments
    const attachmentsPanel = document.getElementById("noteAttachmentsPanel");
    const closeAttachmentsButton = document.getElementById("neCloseAttachments");
    const dropzone = document.getElementById("neDropzone");
    const fileInput = document.getElementById("neFileInput");
    const fileList = document.getElementById("neAttachmentList");

    // Labels and Note Info
    const labelsContainer = document.getElementById("neLabels");
    const createdDate = document.getElementById("neCreated");
    const updatedDate = document.getElementById("neUpdated");

    if (!noteContent) {
        console.warn("[Note Editor] noteContent not found.");
        return;
    }

    /* ==========================================
       2. NOTE EDITOR STATE
    ========================================== */

    let currentNote = null;
    let autoSaveTimer = null;
    let isSaving = false;
    let saveAgain = false;

    // Temporary files for each note
    const filesByNote = new WeakMap();

    // Keep track of Blob URLs for cleanup
    const temporaryUrls = new Set();

    function getToday() {
        return new Date().toLocaleDateString("en-GB");
    }

    function isEditorOpen() {
        return workspace?.classList.contains("ne-open") || false;
    }

    /* ==========================================
       3. TEXT FORMATTING
       Keep existing team functionality
    ========================================== */

    document.querySelectorAll(".note-toolbar button[data-command]")
        .forEach(function (button) {

            button.addEventListener("mousedown", function (event) {
                // Preserve text selection
                event.preventDefault();
            });

            button.addEventListener("click", function () {
                const command = button.dataset.command;

                if (!command) return;

                noteContent.focus();

                document.execCommand(command, false, null);
            });
        });

    /* ==========================================
       4. INSERT LINK
    ========================================== */

    if (linkButton) {
        linkButton.addEventListener("click", function () {

            const url = prompt("Enter the link URL:");

            if (!url) return;

            try {
                const parsedUrl = new URL(url);

                if (!["http:", "https:", "mailto:"].includes(parsedUrl.protocol)) {
                    alert("Please enter a valid web link.");
                    return;
                }

                noteContent.focus();

                document.execCommand(
                    "createLink",
                    false,
                    parsedUrl.href
                );

            } catch (error) {
                alert("Please enter a valid URL, such as https://example.com");
            }
        });
    }

    /* ==========================================
       5. RENAME NOTE
    ========================================== */

    if (renameButton && noteName) {
        renameButton.addEventListener("click", function () {
            noteName.focus();
            noteName.select();
        });
    }

    /* ==========================================
       6. NOTE PASSWORD
    ========================================== */

    if (passwordToggle && passwordArea) {

        passwordToggle.addEventListener("change", function () {

            passwordArea.hidden = !passwordToggle.checked;

            if (passwordToggle.checked) {

                document.getElementById("notePassword")?.focus();

            } else {

                const passwordInput = document.getElementById("notePassword");

                if (passwordInput) {
                    passwordInput.value = "";
                }
            }
        });
    }

    /* ==========================================
       7. AI ASSIST
       Keep existing placeholder
    ========================================== */

    if (aiButton) {
        aiButton.addEventListener("click", function () {
            alert(
                "AI Assist will be connected when the AI feature is implemented."
            );
        });
    }

    /* ==========================================
       8. ATTACHMENTS REOPEN BUTTON
    ========================================== */

    let reopenButton = null;

    if (editorPanel && attachmentsPanel) {

        reopenButton = document.createElement("button");

        reopenButton.type = "button";
        reopenButton.className = "ne-reopen";
        reopenButton.textContent = "Attachments";
        reopenButton.hidden = true;

        const header = editorPanel.querySelector(".note-editor-header");

        if (header) {
            header.appendChild(reopenButton);
        }

        reopenButton.addEventListener("click", function () {
            openAttachments();
        });
    }

    function openAttachments() {
        if (!attachmentsPanel) return;

        attachmentsPanel.hidden = false;

        if (reopenButton) {
            reopenButton.hidden = true;
        }
    }

    function closeAttachments() {
        if (!attachmentsPanel) return;

        attachmentsPanel.hidden = true;

        if (reopenButton) {
            reopenButton.hidden = false;
        }
    }

    closeAttachmentsButton?.addEventListener(
        "click",
        closeAttachments
    );

    /* ==========================================
       9. GET ATTACHMENT FILES
    ========================================== */

    function getFiles() {

        if (!currentNote) {
            return [];
        }

        if (!filesByNote.has(currentNote)) {
            filesByNote.set(currentNote, []);
        }

        return filesByNote.get(currentNote);
    }

    function getFileIcon(file) {

        if (file.type.startsWith("image/")) {
            return "bi-file-earmark-image";
        }

        if (file.type.startsWith("video/")) {
            return "bi-file-earmark-play";
        }

        if (file.type.startsWith("audio/")) {
            return "bi-file-earmark-music";
        }

        if (file.type === "application/pdf") {
            return "bi-file-earmark-pdf";
        }

        return "bi-file-earmark";
    }

    function formatFileSize(bytes) {

        if (bytes < 1024) {
            return bytes + " B";
        }

        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + " KB";
        }

        return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    }

    /* ==========================================
       10. RENDER ATTACHMENTS
    ========================================== */

    function renderFiles() {

        if (!fileList) return;

        fileList.replaceChildren();

        const files = getFiles();

        files.forEach(function (entry) {

            const row = document.createElement('div');
            row.className = 'ne-file';

            const icon = document.createElement('i');
            icon.className = 'bi ' + getFileIcon({
                type: entry.mime_type || ''
            });

            const link = document.createElement('a');

            link.className = 'ne-file-name';
            link.textContent = entry.file_name;

            link.href = '/' + entry.file_path;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.title = entry.file_name;

            const size = document.createElement('small');
            size.className = 'ne-file-size';
            size.textContent = formatFileSize(
                Number(entry.file_size || 0)
            );

            row.appendChild(icon);
            row.appendChild(link);
            row.appendChild(size);

            fileList.appendChild(row);
        });
    }

    /* ==========================================
    LOAD ATTACHMENTS FROM DATABASE
    ========================================== */

    async function loadAttachments() {

        if (!currentNote) return;

        const noteId = currentNote.dataset.noteId;

        try {
            const response = await fetch(
                `/api/upload_file/get_attachments.php?note_id=${encodeURIComponent(noteId)}`,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Failed to load attachments'
                );
            }

            filesByNote.set(currentNote, result.data);

            renderFiles();

        } catch (error) {
            console.error('Load attachments error:', error);
        }
    }

    /* ==========================================
       11. UPDATE NOTE TIME
    ========================================== */

    function updateNoteTime() {

        if (!currentNote) return;

        const today = getToday();

        currentNote.dataset.updatedAt = today;

        if (updatedDate) {
            updatedDate.textContent = today;
        }
    }

    /* ==========================================
       12. ADD ATTACHMENTS
    ========================================== */

    async function addFiles(fileCollection) {

        if (!currentNote || !fileCollection?.length) {
            return;
        }

        const noteId = currentNote.dataset.noteId;

        if (!noteId) {
            alert('Note ID not found');
            return;
        }

        for (const file of Array.from(fileCollection)) {

            // Frontend size validation
            if (file.size > 10 * 1024 * 1024) {
                alert(`${file.name} exceeds the 10 MB limit.`);
                continue;
            }

            const formData = new FormData();

            formData.append('note_id', noteId);
            formData.append('attachment', file);

            try {
                const response = await fetch(
                    '/api/upload_file/attachments.php',
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Upload failed'
                    );
                }

                // Store uploaded attachment information
                getFiles().push(result.data);

                console.log('Uploaded:', result.data.file_name);

            } catch (error) {
                console.error('Upload error:', error);

                alert(
                    `Could not upload ${file.name}: ${error.message}`
                );
            }
        }

        updateNoteTime();
        renderFiles();
    }

    /* ==========================================
       13. FILE INPUT
    ========================================== */

    if (dropzone && fileInput) {

        dropzone.addEventListener("click", function () {
            fileInput.click();
        });

        dropzone.addEventListener("keydown", function (event) {

            if (event.key === "Enter" || event.key === " ") {

                event.preventDefault();

                fileInput.click();
            }
        });

       fileInput.addEventListener("change", async function () {

            const selectedFiles = Array.from(fileInput.files);

            await addFiles(selectedFiles);

            fileInput.value = "";
        });
    }

    /* ==========================================
       14. DRAG AND DROP
    ========================================== */

    if (dropzone) {

        dropzone.addEventListener("dragover", function (event) {

            event.preventDefault();

            dropzone.classList.add("dragging");
        });

        dropzone.addEventListener("dragleave", function (event) {

            event.preventDefault();

            dropzone.classList.remove("dragging");
        });

        dropzone.addEventListener("drop", async function (event) {

            event.preventDefault();

            dropzone.classList.remove("dragging");

            await addFiles(event.dataTransfer?.files);
        });
    }

    /* ==========================================
       15. LABELS - READ ONLY
    ========================================== */

    function showLabels(note) {

        if (!labelsContainer) return;

        labelsContainer.replaceChildren();

        if (!note) {

            labelsContainer.textContent = "No labels";
            return;
        }

        const values = (
            note.dataset.labels ||
            note.dataset.tagNames ||
            ""
        )
            .split(",")
            .map(function (value) {
                return value.trim();
            })
            .filter(Boolean);

        if (values.length === 0) {

            const empty = document.createElement("span");

            empty.className = "ne-empty";
            empty.textContent = "No labels";

            labelsContainer.appendChild(empty);

            return;
        }

        values.forEach(function (name) {

            const chip = document.createElement("span");

            chip.className = "ne-label";
            chip.textContent = name;

            // Labels are display-only
            labelsContainer.appendChild(chip);
        });
    }

    /* ==========================================
       16. NOTE INFORMATION
    ========================================== */

    function showNoteInfo(note) {

        if (!note) return;

        const today = getToday();

        if (!note.dataset.createdAt) {
            note.dataset.createdAt = today;
        }

        if (createdDate) {
            createdDate.textContent = note.dataset.createdAt;
        }

        if (updatedDate) {
            updatedDate.textContent =
                note.dataset.updatedAt || today;
        }
    }

    /* ==========================================
       17. SAVE EDITOR DATA TEMPORARILY
    ========================================== */

    /* ==========================================
   17. UPDATE NOTE
    ========================================== */

    function getEditorContent() {
        const heading = document.createElement('h1');
        heading.textContent = noteTitle?.value.trim() || '';

        const wrapper = document.createElement('div');
        wrapper.appendChild(heading);

        const contentWrapper = document.createElement('div');
        contentWrapper.innerHTML = noteContent.innerHTML;

        while (contentWrapper.firstChild) {
            wrapper.appendChild(contentWrapper.firstChild);
        }

        return wrapper.innerHTML;
    }

    async function saveEditorData() {

    if (!currentNote) return false;

    const noteId = currentNote.dataset.noteId;
    const name = noteName?.value.trim() || '';
    const content = getEditorContent();

    if (!noteId || !name) {
        console.error('Missing note ID or note name');
        return false;
    }

    // Prevent overlapping requests
    if (isSaving) {
        saveAgain = true;
        return false;
    }

    isSaving = true;

    try {
        const response = await fetch('/api/notes/update_note.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                note_id: Number(noteId),
                title: name,
                content: content
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to update note');
        }

        // Update card title
        const cardTitle = currentNote.querySelector('.note-card-title');

        if (cardTitle) {
            cardTitle.textContent = name;
        }

        // Update local data
        currentNote.dataset.editorTitle = name;
        currentNote.dataset.editorContent = content;

        // Update timestamp from database
        if (result.data?.updated_at) {
            const date = new Date(
                result.data.updated_at.replace(' ', 'T')
            );

            const formattedDate = date.toLocaleDateString('vi-VN');

            currentNote.dataset.updatedAt = formattedDate;

            if (updatedDate) {
                updatedDate.textContent = formattedDate;
            }

            const cardDate = currentNote.querySelector('.note-card-date');

            if (cardDate) {
                cardDate.textContent = formattedDate;
            }
        }

        console.log('Note saved successfully');

        return true;

        } catch (error) {
            console.error('Auto-save error:', error);
            return false;

        } finally {
            isSaving = false;

            if (saveAgain) {
                saveAgain = false;
                scheduleAutoSave();
            }
        }
    }

    function loadEditorContent(html) {

        const parser = new DOMParser();
        const doc = parser.parseFromString(html || '', 'text/html');

        const heading = doc.querySelector('h1');

        if (noteTitle) {
            noteTitle.value = heading?.textContent || '';
        }

        if (heading) {
            heading.remove();
        }

        noteContent.innerHTML = doc.body.innerHTML;
    }

    function scheduleAutoSave() {

        if (!currentNote) return;

        clearTimeout(autoSaveTimer);

        autoSaveTimer = setTimeout(async function () {
            await saveEditorData();
        }, 1000);
    }

    // Auto-save when note name changes
    noteName?.addEventListener('input', scheduleAutoSave);

    // Auto-save when heading changes
    noteTitle?.addEventListener('input', scheduleAutoSave);

    // Auto-save when note content changes
    noteContent.addEventListener('input', scheduleAutoSave);

    /* ==========================================
       18. OPEN NOTE EDITOR
    ========================================== */

    function openEditor(note) {

        if (!note || !workspace || !editorPanel || !board) {
            return;
        }

        // Save current note before switching
        if (currentNote && currentNote !== note) {
            saveEditorData();
        }

        currentNote = note;

        // Hide Note Board
        board.classList.add("d-none");

        // Hide Shared with me
        document.getElementById("sharedBoardView")
            ?.classList.add("d-none");

        // Close Account and other panels
        document.querySelectorAll(".app-panel.active")
            .forEach(function (panel) {
                panel.classList.remove("active");
            });

        // Hide Filter Tags
        const filterPanel = document.getElementById(
            "filterTagsPanel"
        );

        if (filterPanel) {
            filterPanel.hidden = true;
            filterPanel.classList.add("d-none");
        }

        const filterButton = document.getElementById(
            "filterTagsBtn"
        );

        filterButton?.setAttribute("aria-expanded", "false");
        filterButton?.classList.remove("is-active");

        // Show editor
        workspace.classList.add("ne-open");
        editorPanel.classList.add("active");

        // Set note name
        if (noteName) {

            noteName.value =
                note.querySelector(".note-card-title")
                    ?.textContent?.trim() || "New Note";
        }

        // Restore heading and note content
        loadEditorContent(note.dataset.editorContent || '');

        // Restore pin toggle
        if (pinToggle) {
            pinToggle.checked =
                note.dataset.pinned === "true";
        }

        // Reset password visibility when opening
        if (passwordToggle && passwordArea) {
            passwordArea.hidden = !passwordToggle.checked;
        }

        // Show attachments
        openAttachments();

        // Update sidebar information
        showLabels(note);
        showNoteInfo(note);

        // Load attachments from database
        loadAttachments();
    }

    /* ==========================================
       19. CLOSE NOTE EDITOR
    ========================================== */

    async function closeEditor() {

        if (!isEditorOpen()) {
            return;
        }

        const confirmed = window.confirm(
            "Leave the note editor?"
        );

        if (!confirmed) {
            return;
        }

        // Save note before leaving
        const saved = await saveEditorData();

        if (!saved) {
            return;
        }

        workspace.classList.remove("ne-open");
        editorPanel?.classList.remove("active");

        board?.classList.remove("d-none");

        currentNote = null;
    }

    backButton?.addEventListener("click", function (event) {
        event.preventDefault();
        closeEditor();
    });

    /* ==========================================
       20. OPEN AN EXISTING NOTE
    ========================================== */

    
    grid?.addEventListener("click", function (event) {

        const link = event.target.closest(".note-card-link");

        if (!link) return;

        const note = link.closest(".note-card");

        if (!note) return;

        event.preventDefault();

        // Check whether this is an uploaded File Note
        if (note.dataset.noteType === "file") {

            const filePath = note.dataset.filePath;
            const fileName = note.dataset.fileName;

            if (!filePath) {
                alert("File path not found.");
                return;
            }

            // Download file
            const downloadLink = document.createElement("a");

            downloadLink.href = "/" + filePath;
            downloadLink.download = fileName || "download";
            downloadLink.style.display = "none";

            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();

            return;
        }

        // Normal Text Note: open Note Editor
        openEditor(note);
    });


    /* ==========================================
       21. OPEN NEWLY CREATED NOTE
    ========================================== */

    document.addEventListener("seedgrowth:note-created", function (event) {
        const newNote = event.detail?.card;

        if (newNote) {
            openEditor(newNote);
        }
    });

    /* ==========================================
       22. KEEP LABELS UP TO DATE
    ========================================== */

    document.addEventListener(
        "seedgrowth:note-labels-changed",
        function (event) {

            if (event.detail?.note === currentNote) {
                showLabels(currentNote);
            }
        }
    );

    /* ==========================================
       23. CLEAN UP TEMPORARY URLs
    ========================================== */

    window.addEventListener("pagehide", function () {

        temporaryUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });

        temporaryUrls.clear();
    });

});
