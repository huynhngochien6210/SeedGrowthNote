<div class="password-reset-page">
    <div class="password-reset-card">

        <h2 class="password-reset-title">
            Forgot Password
        </h2>

        <p class="password-reset-description">
            Enter your email address and we will send you
            a link to reset your password.
        </p>

        <form id="forgotPasswordForm">

            <div class="mb-3">
                <label for="resetEmail" class="form-label">
                    Email address
                </label>

                <input
                    type="email"
                    id="resetEmail"
                    name="email"
                    class="form-control password-reset-input"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn password-reset-button w-100"
            >
                Send reset link
            </button>

        </form>

        <p
            id="forgotPasswordMessage"
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
