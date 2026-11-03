
<div class="verify-card">
    <h2 class="verify-title">
        Verify Your Email
    </h2>

    <p class="text-center mb-4">
        We have sent a verification link to your email.
        Please check your inbox and click the link
        to activate your account.
    </p>

    <form id="verifyForm">
        <div class="mb-3">
            <label for="verifyEmail" class="form-label">
                Email address
            </label>

            <input
                id="verifyEmail"
                name="email"
                type="email"
                class="form-control verify-input"
                placeholder="Enter your email"
                autocomplete="email"
                required
            >
        </div>

        <div
            id="verifyMessage"
            class="mb-3"
            role="status"
            aria-live="polite"
            hidden
        ></div>

        <button
            id="resendVerifyButton"
            type="submit"
            class="btn verify-button w-100"
        >
            Resend Verification Email
        </button>
    </form>

    <p class="verify-login-text">
        Already verified?
        <a href="?page=login">Sign in</a>
    </p>
</div>
