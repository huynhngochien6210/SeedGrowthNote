/* ==========================================
   ACCOUNT PROFILE
========================================== */

(function () {
    const editBtn = document.getElementById('editProfileBtn');
    const accountForm = document.getElementById('accountForm');

    if (!editBtn || !accountForm) return;

    const emailInput = document.getElementById('profileEmail');
    const dobInput = document.getElementById('profileDob');
    const nameInput = document.getElementById('profileName');
    const genderInput = document.getElementById('profileGender');
    const usernameDisplay = document.getElementById('usernameDisplay');
    const message = document.getElementById('profileMessage');

    let editing = false;
    let saving = false;

    function setEditing(enabled) {
        editing = enabled;

        nameInput.readOnly = !enabled;
        dobInput.readOnly = !enabled;
        genderInput.disabled = !enabled;

        // Email không được chỉnh sửa trực tiếp.
        emailInput.readOnly = true;

        editBtn.textContent = enabled
            ? 'Save profile'
            : 'Edit profile';
    }

    async function loadProfile() {
        try {
            const response = await fetch(
                '/api/account/get_profile.php',
                { credentials: 'same-origin' }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Unable to load profile.'
                );
            }

            const user = result.user;

            emailInput.value = user.email || '';
            nameInput.value = user.display_name || '';
            dobInput.value = user.date_of_birth || '';
            genderInput.value = user.gender || '';

            usernameDisplay.textContent =
                user.display_name || 'USERNAME';

            // Đồng bộ tên ở sidebar nếu có.
            document.querySelectorAll(
                '.account-side-username'
            ).forEach(function (element) {
                element.textContent =
                    user.display_name || 'USERNAME';
            });

        } catch (error) {
            console.error('Load profile error:', error);

            message.textContent = error.message;
            message.style.color = '#b42318';
        }
    }

    editBtn.addEventListener('click', async function () {
        if (saving) return;

        if (!editing) {
            setEditing(true);
            message.textContent = '';
            nameInput.focus();
            return;
        }

        const displayName = nameInput.value.trim();

        if (!displayName) {
            message.textContent = 'Fullname cannot be empty.';
            message.style.color = '#b42318';
            return;
        }

        saving = true;
        editBtn.disabled = true;
        editBtn.textContent = 'Saving...';

        try {
            const sessionResponse = await fetch(
                '/api/auth/session.php',
                { credentials: 'same-origin' }
            );

            if (!sessionResponse.ok) {
                throw new Error('Cannot verify session.');
            }

            const session = await sessionResponse.json();

            if (!session.authenticated || !session.csrf_token) {
                throw new Error('Please log in again.');
            }

            const response = await fetch(
                '/api/account/update_profile.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': session.csrf_token
                    },
                    body: JSON.stringify({
                        display_name: displayName,
                        date_of_birth: dobInput.value || null,
                        gender: genderInput.value || null
                    })
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Unable to update profile.'
                );
            }

            setEditing(false);

            usernameDisplay.textContent = displayName;

            document.querySelectorAll(
                '.account-side-username'
            ).forEach(function (element) {
                element.textContent = displayName;
            });

            message.textContent = 'Profile updated successfully.';
            message.style.color = '#24734d';

        } catch (error) {
            console.error('Update profile error:', error);

            message.textContent = error.message;
            message.style.color = '#b42318';

        } finally {
            saving = false;
            editBtn.disabled = false;

            editBtn.textContent = editing
                ? 'Save profile'
                : 'Edit profile';
        }
    });

    setEditing(false);
    loadProfile();
})();


/* ==========================================
   CHANGE PASSWORD
========================================== */

const passwordForm = document.getElementById('passwordForm');

passwordForm?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const currentPassword =
        this.elements.namedItem('current_password')?.value || '';

    const newPassword =
        this.elements.namedItem('new_password')?.value || '';

    const confirmPassword =
        this.elements.namedItem('retype_password')?.value || '';

    const message = document.getElementById('changePasswordMessage');
    const button = this.querySelector('button[type="submit"]');

    if (!message) return;

    message.textContent = '';
    message.style.color = '';

    if (!currentPassword || !newPassword || !confirmPassword) {
        message.textContent = 'Please fill in all password fields.';
        message.style.color = '#b42318';
        return;
    }

    if (newPassword.length < 8) {
        message.textContent =
            'New password must contain at least 8 characters.';
        message.style.color = '#b42318';
        return;
    }

    if (newPassword !== confirmPassword) {
        message.textContent = 'New passwords do not match.';
        message.style.color = '#b42318';
        return;
    }

    if (currentPassword === newPassword) {
        message.textContent =
            'New password must differ from current password.';
        message.style.color = '#b42318';
        return;
    }

    if (button) {
        button.disabled = true;
        button.textContent = 'Updating...';
    }

    try {
        const sessionResponse = await fetch(
            '/api/auth/session.php',
            { credentials: 'same-origin' }
        );

        if (!sessionResponse.ok) {
            throw new Error('Cannot verify session.');
        }

        const session = await sessionResponse.json();

        if (!session.authenticated || !session.csrf_token) {
            throw new Error('Please log in again.');
        }

        const response = await fetch(
            '/api/auth/change_password.php',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': session.csrf_token
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Password change failed.'
            );
        }

        this.reset();

        message.textContent = 'Password changed successfully.';
        message.style.color = '#24734d';

    } catch (error) {
        console.error('Change password error:', error);

        message.textContent =
            error.message || 'Unable to change password.';
        message.style.color = '#b42318';

    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = 'Update password';
        }
    }
});


/* ==========================================
   CHANGE AVATAR
========================================== */

(function () {
    const fileInput = document.getElementById('avatarFileInput');
    const avatarButtons = document.querySelectorAll(
        '.account-page-wrap .avatar-edit-btn, #changeAvatarText'
    );
    const avatarImages = document.querySelectorAll(
        '.account-page-wrap .account-avatar-img'
    );
    const message = document.getElementById('avatarMessage');

    if (!fileInput || !avatarButtons.length) return;

    function showMessage(text, isError = false) {
        if (!message) return;

        message.textContent = text;
        message.style.color = isError ? '#ffb4b4' : '#ffffff';
    }

    function displayAvatar(path) {
        avatarImages.forEach(function (img) {
            const circle = img.closest('.avatar-circle');
            const placeholder = circle?.querySelector(
                '.avatar-placeholder'
            );

            if (!path) {
                img.hidden = true;
                img.removeAttribute('src');
                if (placeholder) placeholder.hidden = false;
                return;
            }

            img.onload = function () {
                img.hidden = false;
                if (placeholder) placeholder.hidden = true;
            };

            img.onerror = function () {
                img.hidden = true;
                if (placeholder) placeholder.hidden = false;
            };

            const separator = path.includes('?') ? '&' : '?';

            img.src = '/' + path.replace(/^\/+/, '') +
                separator + 'v=' + Date.now();
        });
    }

    async function loadAvatar() {
        try {
            const response = await fetch(
                '/api/account/get_profile.php',
                { credentials: 'same-origin' }
            );

            if (!response.ok) return;

            const result = await response.json();

            if (result.success) {
                displayAvatar(result.user.avatar_path || '');
            }

        } catch (error) {
            console.error('Load avatar error:', error);
        }
    }

    avatarButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            fileInput.click();
        });
    });

    fileInput.addEventListener('change', async function () {
        const file = this.files?.[0];
        if (!file) return;

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {
            showMessage('Only JPG, PNG and WebP are allowed.', true);
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showMessage('Image must be smaller than 2 MB.', true);
            this.value = '';
            return;
        }

        avatarButtons.forEach(function (button) {
            button.disabled = true;
        });

        showMessage('Uploading avatar...');

        try {
            const sessionResponse = await fetch(
                '/api/auth/session.php',
                { credentials: 'same-origin' }
            );

            if (!sessionResponse.ok) {
                throw new Error('Cannot verify session.');
            }

            const session = await sessionResponse.json();

            if (!session.authenticated || !session.csrf_token) {
                throw new Error('Please log in again.');
            }

            const formData = new FormData();
            formData.append('avatar', file);

            const response = await fetch(
                '/api/account/upload_avatar.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': session.csrf_token
                    },
                    body: formData
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Avatar upload failed.'
                );
            }

            displayAvatar(result.avatar_path);
            showMessage('Avatar updated successfully.');

        } catch (error) {
            console.error('Avatar upload error:', error);

            showMessage(
                error.message || 'Unable to upload avatar.',
                true
            );

        } finally {
            avatarButtons.forEach(function (button) {
                button.disabled = false;
            });

            fileInput.value = '';
        }
    });

    loadAvatar();
})();


/* ==========================================
   LOGOUT
========================================== */

(function () {
    const logoutLinks = document.querySelectorAll(
        '.account-menu-item.logout a'
    );

    logoutLinks.forEach(function (link) {
        link.addEventListener('click', async function (event) {
            event.preventDefault();

            const confirmed = window.confirm(
                'Are you sure you want to log out?'
            );

            if (!confirmed) return;

            logoutLinks.forEach(function (item) {
                item.style.pointerEvents = 'none';
                item.style.opacity = '0.6';
            });

            try {
                const sessionResponse = await fetch(
                    '/api/auth/session.php',
                    {
                        credentials: 'same-origin',
                        cache: 'no-store'
                    }
                );

                if (!sessionResponse.ok) {
                    throw new Error('Cannot verify session.');
                }

                const session = await sessionResponse.json();

                if (!session.csrf_token) {
                    throw new Error('Invalid session.');
                }

                const response = await fetch(
                    '/api/auth/logout.php',
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-Token': session.csrf_token
                        }
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Logout failed.'
                    );
                }

                // Chuyển về Login.
                window.location.replace('/?page=login');

            } catch (error) {
                console.error('Logout error:', error);

                alert(
                    error.message || 'Unable to log out.'
                );

                logoutLinks.forEach(function (item) {
                    item.style.pointerEvents = '';
                    item.style.opacity = '';
                });
            }
        });
    });
})();


/* ==========================================
   ACCOUNT SIDEBAR TOGGLE
========================================== */

(function () {
    const accountPanel = document.querySelector(
        '.app-panel[data-panel-name="account"]'
    );

    const accountButton = document.querySelector(
        '.app-sidebar .nav-panel-btn[data-panel="account"]'
    ) || document.querySelector(
        '.app-sidebar [data-panel="account"]'
    );

    if (!accountPanel || !accountButton) return;

    accountButton.addEventListener('click', function (event) {
        // Khi Account chưa mở, để logic điều hướng cũ xử lý.
        if (!accountPanel.classList.contains('active')) {
            return;
        }

        // Khi Account đã mở, chặn xử lý click cũ.
        event.preventDefault();
        event.stopImmediatePropagation();

        // Ưu tiên dùng nút Back có sẵn để trở về Notes.
        const backButton = accountPanel.querySelector(
            '.panel-back-btn[data-panel="notes"]'
        );

        if (backButton) {
            backButton.click();
        }
    }, true);
})();

