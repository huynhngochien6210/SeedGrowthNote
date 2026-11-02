
<div class="signup-card">

    <!-- Tiêu đề -->
    <h2 class="signup-title">
        SIGN UP
    </h2>

    <!-- Form đăng ký -->
    <form id="signupForm">

        <!-- Username -->
        <div class="mb-3">
            <input
                id="signupUsername"
                name="display_name"
                type="text"
                class="form-control signup-input"
                placeholder="Username"
                autocomplete="name"
                required
            >
        </div>

        <!-- Email -->
        <div class="mb-3">
            <input
                id="signupEmail"
                name="email"
                type="email"
                class="form-control signup-input"
                placeholder="Email"
                autocomplete="email"
                required
            >
        </div>

        <!-- Password -->
        <div class="mb-3">
            <input
                id="signupPassword"
                name="password"
                type="password"
                class="form-control signup-input"
                placeholder="Password"
                autocomplete="new-password"
                minlength="8"
                required
            >
        </div>

        <!-- Confirm password -->
        <div class="mb-4">
            <input
                id="signupConfirmPassword"
                name="confirm_password"
                type="password"
                class="form-control signup-input"
                placeholder="Retype password"
                autocomplete="new-password"
                minlength="8"
                required
            >
        </div>

        <!-- Thông báo -->
        <div
            id="signupMessage"
            class="mb-3"
            role="alert"
            aria-live="polite"
        ></div>

        <!-- Nút tạo tài khoản -->
        <button
            id="signupButton"
            type="submit"
            class="btn signup-button w-100"
        >
            Create an account
        </button>

        <!-- Chuyển sang đăng nhập -->
        <p class="login-text">
            Already have an account?
            <a href="?page=login">
                Sign in
            </a>
        </p>

    </form>

</div>
