<!-- Password panel -->
<div
    class="app-panel account-page-wrap"
    data-panel-name="password"
>
    <div class="account-card d-flex flex-column flex-md-row position-relative">

        <!-- Nút quay lại Notes -->
        <button
            type="button"
            class="panel-back-btn nav-panel-btn"
            data-panel="notes"
            aria-label="Back"
        >
            <i class="bi bi-arrow-left"></i>
        </button>

        <!-- LEFT: Avatar + Username + Menu -->
        <div class="account-side d-flex flex-column align-items-center text-center px-4 py-5">

            
            <div class="avatar-circle mb-2">
                <i class="bi bi-person-fill avatar-placeholder"></i>

                <img
                    class="account-avatar-img"
                    alt="Profile avatar"
                    hidden
                >

                <button
                    class="avatar-edit-btn"
                    type="button"
                    aria-label="Change avatar"
                >
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>


            <div class="account-side-username mb-4">
                USERNAME
            </div>

            <ul class="account-menu list-unstyled w-100">

                <li class="account-menu-item">
                    <span class="account-menu-dot"></span>

                    <a
                        href="#"
                        class="nav-panel-btn"
                        data-panel="account"
                    >
                        Account info
                    </a>
                </li>

                <li class="account-menu-item active">
                    <span class="account-menu-dot"></span>

                    <a href="#">
                        Password
                    </a>
                </li>

                <li class="account-menu-item logout">
                    <span class="account-menu-dot"></span>

                    <a href="#">
                        Log out
                    </a>
                </li>

            </ul>
        </div>

        <!-- RIGHT: Change Password -->
        <div class="account-main flex-grow-1 p-4 p-md-5 position-relative">

            <h2 class="account-username mb-4">
                Change password
            </h2>

            <form id="passwordForm">

                <div class="row gy-4">

                    <!-- Current password -->
                    <div class="col-12 col-lg-7">

                        <label
                            class="account-field-label"
                            for="currentPassword"
                        >
                            <i class="bi bi-lock"></i>
                            Current password
                        </label>

                        <div class="password-field-wrap">

                            <input
                                type="password"
                                id="currentPassword"
                                class="account-field-input"
                                name="current_password"
                                placeholder="Enter current password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle-btn"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                        </div>
                    </div>

                    <!-- New password -->
                    <div class="col-12 col-lg-7">

                        <label
                            class="account-field-label"
                            for="newPassword"
                        >
                            <i class="bi bi-lock-fill"></i>
                            New password
                        </label>

                        <div class="password-field-wrap">

                            <input
                                type="password"
                                id="newPassword"
                                class="account-field-input"
                                name="new_password"
                                placeholder="Enter new password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle-btn"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                        </div>
                    </div>

                    <!-- Confirm new password -->
                    <div class="col-12 col-lg-7">

                        <label
                            class="account-field-label"
                            for="confirmNewPassword"
                        >
                            <i class="bi bi-lock-fill"></i>
                            Retype new password
                        </label>

                        <div class="password-field-wrap">

                            <input
                                type="password"
                                id="confirmNewPassword"
                                class="account-field-input"
                                name="retype_password"
                                placeholder="Retype new password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle-btn"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                        </div>
                    </div>

                </div>

                <!-- Action buttons -->
                <div class="mt-4 d-flex flex-column align-items-start gap-3">

                    <button
                        type="submit"
                        class="edit-profile-btn position-static"
                    >
                        Update password
                    </button>

                    <!-- Success / Error message -->
                    <p
                        id="changePasswordMessage"
                        class="mt-2 mb-0"
                        role="status"
                        aria-live="polite"
                    ></p>

                    <a
                        href="?page=forgot_password"
                        class="forgot-password-link"
                    >
                        Forgot your password?
                    </a>

                </div>

            </form>

        </div>

    </div>
</div>
