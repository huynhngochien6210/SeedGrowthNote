
<div class="note-editor-panel">

    <!-- Header của khung soạn thảo -->
    <div class="note-editor-header">
        <button type="button" class="note-back-btn" id="noteBackBtn">
            <i class="bi bi-arrow-left"></i>
            <span>Back</span>
        </button>

        <div class="note-editor-heading">
            <div class="note-editor-leaf">
                <i class="bi bi-feather"></i>
            </div>

            <input
                type="text"
                class="note-editor-name"
                id="noteName"
                value="New Note"
                aria-label="Note name"
            >

            <button
                type="button"
                class="note-rename-btn"
                id="renameNoteBtn"
                title="Rename note"
            >
                <i class="bi bi-pencil"></i>
            </button>
        </div>
    </div>

    <!-- Tiêu đề ghi chú -->
    <input
        type="text"
        class="note-title-input"
        id="noteTitle"
        placeholder="Title..."
        aria-label="Note title"
    >

    <!-- Khu vực soạn thảo -->
    <div class="note-writing-area">

        <!-- Thanh công cụ -->
        <div class="note-toolbar">

            <button type="button" data-command="undo" title="Undo">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>

            <button type="button" data-command="redo" title="Redo">
                <i class="bi bi-arrow-clockwise"></i>
            </button>

            <span class="note-toolbar-divider"></span>

            <button type="button" data-command="bold" title="Bold">
                <i class="bi bi-type-bold"></i>
            </button>

            <button type="button" data-command="italic" title="Italic">
                <i class="bi bi-type-italic"></i>
            </button>

            <button type="button" data-command="underline" title="Underline">
                <i class="bi bi-type-underline"></i>
            </button>

            <span class="note-toolbar-divider"></span>

            <button
                type="button"
                data-command="insertUnorderedList"
                title="Bullet list"
            >
                <i class="bi bi-list-ul"></i>
            </button>

            <button
                type="button"
                data-command="insertOrderedList"
                title="Numbered list"
            >
                <i class="bi bi-list-ol"></i>
            </button>

            <button type="button" id="noteLinkBtn" title="Insert link">
                <i class="bi bi-link-45deg"></i>
            </button>

        </div>

        <!-- Nội dung ghi chú -->
        <div
            class="note-content"
            id="noteContent"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            data-placeholder="Write your note here..."
        ></div>

    </div>

    <!-- Thanh chức năng dưới -->
    <div class="note-editor-footer">

        <label class="note-toggle-option">
            <i class="bi bi-tag"></i>
            <span>Pin note</span>
            <input type="checkbox" id="pinNoteToggle">
            <span class="note-toggle-switch"></span>
        </label>

        <label class="note-toggle-option">
            <i class="bi bi-lock"></i>
            <span>Password</span>
            <input type="checkbox" id="passwordNoteToggle">
            <span class="note-toggle-switch"></span>
        </label>

        
    </div>

    <!-- Khu vực nhập mật khẩu, ẩn mặc định -->
    <div class="note-password-area" id="notePasswordArea" hidden>
        <input
            type="password"
            id="notePassword"
            placeholder="Enter note password..."
        >
    </div>

</div>
