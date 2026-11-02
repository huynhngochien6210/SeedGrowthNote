
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

            const row = document.createElement("div");
            row.className = "ne-file";

            const icon = document.createElement("i");
            icon.className = "bi " + getFileIcon(entry.file);

            const link = document.createElement("a");

            link.className = "ne-file-name";
            link.textContent = entry.file.name;

            link.href = entry.url;
            link.target = "_blank";
            link.rel = "noopener noreferrer";

            link.title = entry.file.name;

            const size = document.createElement("small");
            size.className = "ne-file-size";
            size.textContent = formatFileSize(entry.file.size);

            const removeButton = document.createElement("button");

            removeButton.type = "button";
            removeButton.className = "ne-file-remove";

            removeButton.textContent = "✕";

            removeButton.setAttribute(
                "aria-label",
                "Remove " + entry.file.name
            );

            removeButton.addEventListener("click", function () {

                const items = getFiles();
                const index = items.indexOf(entry);

                if (index !== -1) {

                    items.splice(index, 1);

                    URL.revokeObjectURL(entry.url);
                    temporaryUrls.delete(entry.url);
                }

                updateNoteTime();
                renderFiles();
            });

            row.appendChild(icon);
            row.appendChild(link);
            row.appendChild(size);
            row.appendChild(removeButton);

            fileList.appendChild(row);
        });
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

    function addFiles(fileCollection) {

        if (!currentNote || !fileCollection?.length) {
            return;
        }

        const items = getFiles();

        Array.from(fileCollection).forEach(function (file) {

            const url = URL.createObjectURL(file);

            items.push({
                file: file,
                url: url
            });

            temporaryUrls.add(url);
        });

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

        fileInput.addEventListener("change", function () {

            addFiles(fileInput.files);

            // Allow choosing the same file again
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

        dropzone.addEventListener("drop", function (event) {

            event.preventDefault();

            dropzone.classList.remove("dragging");

            addFiles(event.dataTransfer?.files);
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

    function saveEditorData() {

        if (!currentNote) return;

        if (noteName) {

            const name = noteName.value.trim();

            if (name) {

                const cardTitle = currentNote.querySelector(
                    ".note-card-title"
                );

                if (cardTitle) {
                    cardTitle.textContent = name;
                }
            }
        }

        if (noteTitle) {
            currentNote.dataset.editorTitle = noteTitle.value;
        }

        currentNote.dataset.editorContent = noteContent.innerHTML;

        currentNote.dataset.pinned = String(
            pinToggle?.checked || false
        );

        updateNoteTime();
    }

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

        // Set note title
        if (noteTitle) {
            noteTitle.value = note.dataset.editorTitle || "";
        }

        // Set note content
        noteContent.innerHTML =
            note.dataset.editorContent || "";

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
        renderFiles();
    }

    /* ==========================================
       19. CLOSE NOTE EDITOR
    ========================================== */

    function closeEditor() {

        if (!isEditorOpen()) {
            return;
        }

        // Ask before leaving editor
        const confirmed = window.confirm(
            "Leave the note editor?"
        );

        if (!confirmed) {
            return;
        }

        saveEditorData();

        workspace.classList.remove("ne-open");
        editorPanel?.classList.remove("active");

        // Return to Note Board
        board?.classList.remove("d-none");

        currentNote = null;
    }

    // Only ONE Back button event
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
