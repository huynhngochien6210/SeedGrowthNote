
document.addEventListener("DOMContentLoaded", () => {
    // ===== 1. DOM ELEMENTS =====
    const modal = document.getElementById("shareNoteModal");
    const emailInput = document.getElementById("shareUserInput");
    const permissionSelect = document.getElementById("shareDefaultPermission");
    const addBtn = document.getElementById("shareAddBtn");
    const confirmBtn = document.getElementById("shareConfirmBtn");
    const peopleList = document.getElementById("sharePeopleList");
    const emptyMessage = document.getElementById("shareEmpty");
    const errorMessage = document.getElementById("shareError");

    if (
        !modal ||
        !emailInput ||
        !permissionSelect ||
        !addBtn ||
        !confirmBtn ||
        !peopleList ||
        !emptyMessage ||
        !errorMessage
    ) {
        console.error("Share Note: Không tìm thấy đủ HTML elements.");
        return;
    }

    // ===== 2. CONFIGURATION =====

    // Đặt note ID vào HTML khi mở modal:
    // <div id="shareNoteModal" data-note-id="123">
    //
    // API dự kiến:
    // POST /api/notes/{noteId}/shares
    //
    // Để trống data-note-id thì giao diện chạy demo,
    // chưa lưu dữ liệu lên server.

    const API_BASE_URL = "/api/notes";

    // Danh sách người nhận đang chuẩn bị chia sẻ
    let recipients = [];

    // ===== 3. HELPER FUNCTIONS =====

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = message ? "block" : "none";
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function updateConfirmButton() {
        confirmBtn.disabled = recipients.length === 0;
    }

    function updateEmptyMessage() {
        emptyMessage.style.display =
            recipients.length === 0 ? "block" : "none";
    }

    // ===== 4. RENDER RECIPIENT LIST =====

    function renderRecipients() {
        peopleList.replaceChildren();

        recipients.forEach((recipient, index) => {
            const row = document.createElement("div");
            row.className = "share-person-item";

            const info = document.createElement("div");
            info.className = "share-person-info";

            const avatar = document.createElement("div");
            avatar.className = "share-person-avatar";
            avatar.textContent = recipient.email.charAt(0).toUpperCase();

            const details = document.createElement("div");
            details.className = "share-person-details";

            const email = document.createElement("div");
            email.className = "share-person-email";
            email.textContent = recipient.email;

            const permission = document.createElement("div");
            permission.className = "share-person-permission";
            permission.textContent =
                recipient.permission === "edit"
                    ? "Can edit"
                    : "Can view";

            details.append(email, permission);
            info.append(avatar, details);

            const actions = document.createElement("div");
            actions.className = "share-person-actions";

            const permissionControl = document.createElement("select");
            permissionControl.className = "form-select share-person-select";
            permissionControl.setAttribute(
                "aria-label",
                `Permission for ${recipient.email}`
            );

            [
                { value: "read", label: "Can view" },
                { value: "edit", label: "Can edit" }
            ].forEach(optionData => {
                const option = document.createElement("option");
                option.value = optionData.value;
                option.textContent = optionData.label;
                permissionControl.appendChild(option);
            });

            permissionControl.value = recipient.permission;

            permissionControl.addEventListener("change", () => {
                recipients[index].permission = permissionControl.value;
                renderRecipients();
            });

            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "share-person-remove";
            removeBtn.setAttribute(
                "aria-label",
                `Remove ${recipient.email}`
            );
            removeBtn.innerHTML = '<i class="bi bi-trash3"></i>';

            removeBtn.addEventListener("click", () => {
                recipients.splice(index, 1);
                showError("");
                renderRecipients();
            });

            actions.append(permissionControl, removeBtn);
            row.append(info, actions);
            peopleList.appendChild(row);
        });

        updateEmptyMessage();
        updateConfirmButton();
    }

    // ===== 5. ADD RECIPIENT =====

    function addRecipient() {
        const email = emailInput.value.trim().toLowerCase();
        const permission = permissionSelect.value;

        showError("");

        if (!email) {
            showError("Please enter an email address.");
            emailInput.focus();
            return;
        }

        if (!isValidEmail(email)) {
            showError("Please enter a valid email address.");
            emailInput.focus();
            return;
        }

        const alreadyExists = recipients.some(
            recipient => recipient.email === email
        );

        if (alreadyExists) {
            showError("This person is already in the list.");
            emailInput.focus();
            return;
        }

        if (!["read", "edit"].includes(permission)) {
            showError("Please select a valid permission.");
            return;
        }

        recipients.push({
            email,
            permission
        });

        emailInput.value = "";
        emailInput.focus();

        renderRecipients();
    }

    addBtn.addEventListener("click", addRecipient);

    // Enter để thêm email nhanh hơn
    emailInput.addEventListener("keydown", event => {
        if (event.key === "Enter") {
            event.preventDefault();
            addRecipient();
        }
    });

    // Xóa thông báo lỗi khi người dùng nhập lại
    emailInput.addEventListener("input", () => {
        showError("");
    });

    // ===== 6. CONFIRM SHARE =====

    confirmBtn.addEventListener("click", async () => {
        if (recipients.length === 0) {
            showError("Please add at least one person.");
            return;
        }

        const noteId = modal.dataset.noteId;

        // Chưa có note ID thì chỉ kiểm tra giao diện.
        if (!noteId) {
            alert(
                "The recipient list is ready.\n" +
                "Backend API is not connected yet, so no data was saved."
            );

            // Dùng dữ liệu này để kiểm tra trong Console.
            console.table(recipients);
            return;
        }

        confirmBtn.disabled = true;
        confirmBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" ' +
            'aria-hidden="true"></span> Sharing...';

        try {
            const response = await fetch(
                `${API_BASE_URL}/${encodeURIComponent(noteId)}/shares`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        shares: recipients.map(recipient => ({
                            email: recipient.email,
                            permission: recipient.permission
                        }))
                    })
                }
            );

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    result.message || "Unable to share this note."
                );
            }

            alert("Note shared successfully!");

            // Phát sự kiện để các thành phần khác cập nhật danh sách.
            modal.dispatchEvent(
                new CustomEvent("note-shared", {
                    bubbles: true,
                    detail: {
                        noteId,
                        recipients: [...recipients]
                    }
                })
            );

            recipients = [];
            renderRecipients();
            showError("");

            // Đóng modal bằng Bootstrap nếu có.
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).hide();
            }
        } catch (error) {
            showError(error.message || "Something went wrong.");
        } finally {
            confirmBtn.innerHTML =
                '<i class="bi bi-send"></i> <span>Share</span>';
            updateConfirmButton();
        }
    });

    // ===== 7. RESET WHEN MODAL CLOSES =====

    modal.addEventListener("hidden.bs.modal", () => {
        recipients = [];
        emailInput.value = "";
        permissionSelect.value = "read";
        showError("");
        renderRecipients();
    });

    // ===== 8. INITIAL STATE =====

    showError("");
    renderRecipients();
});
