<div class="password-reset-page">
    <div class="password-reset-card">

        <h2 class="password-reset-title">
            Reset Password
        </h2>

        <p class="password-reset-description">
            Enter your new password to regain access
            to your SeedGrowthNote account.
        </p>

        <form id="resetPasswordForm">

            <div class="mb-3">
                <label for="newPassword" class="form-label">
                    New password
                </label>

                <input
                    type="password"
                    id="newPassword"
                    name="password"
                    class="form-control password-reset-input"
                    placeholder="Enter new password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="confirmNewPassword" class="form-label">
                    Confirm new password
                </label>

                <input
                    type="password"
                    id="confirmNewPassword"
                    name="confirm_password"
                    class="form-control password-reset-input"
                    placeholder="Confirm new password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn password-reset-button w-100"
            >
                Reset password
            </button>

        </form>

        <p
            id="resetPasswordMessage"
            class="password-reset-message"
            role="status"
            aria-live="polite"
        ></p>

        <div class="password-reset-footer">
            <a href="?page=login">
                Back to login
            </a>
        </div>

    </div>
</div>
