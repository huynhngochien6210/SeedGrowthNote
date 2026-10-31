
document.addEventListener("DOMContentLoaded", function () {
    const editor = document.getElementById("noteContent");
    const noteName = document.getElementById("noteName");
    const renameButton = document.getElementById("renameNoteBtn");
    const backButton = document.getElementById("noteBackBtn");
    const linkButton = document.getElementById("noteLinkBtn");
    const pinToggle = document.getElementById("pinNoteToggle");
    const passwordToggle = document.getElementById("passwordNoteToggle");
    const passwordArea = document.getElementById("notePasswordArea");
    const aiButton = document.getElementById("noteAiBtn");

    if (!editor) return;

    // Dinh dang van ban
    document.querySelectorAll(".note-toolbar button[data-command]")
        .forEach(function (button) {
            button.addEventListener("mousedown", function (event) {
                // Giu vung chon van ban khi bam toolbar
                event.preventDefault();
            });

            button.addEventListener("click", function () {
                const command = button.dataset.command;

                editor.focus();
                document.execCommand(command, false, null);
            });
        });

    // Chen lien ket
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

                editor.focus();
                document.execCommand("createLink", false, parsedUrl.href);
            } catch {
                alert("Please enter a valid URL, such as https://example.com");
            }
        });
    }

    // Doi ten ghi chu
    if (renameButton && noteName) {
        renameButton.addEventListener("click", function () {
            noteName.focus();
            noteName.select();
        });
    }

    // Hien thi truong mat khau
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

    // Nut Back: thong bao de tich hop voi routing cua team
    if (backButton) {
        backButton.addEventListener("click", function () {
            const confirmed = confirm("Leave the note editor?");

            if (confirmed) {
                window.history.back();
            }
        });
    }

    // AI chua duoc ket noi backend
    if (aiButton) {
        aiButton.addEventListener("click", function () {
            alert("AI Assist will be connected when the AI feature is implemented.");
        });
    }
});
