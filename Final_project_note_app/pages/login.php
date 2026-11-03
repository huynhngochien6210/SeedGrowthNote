<div class="login-card">

    <!-- Tiêu đề -->
    <h2 class="login-title">
        HELLO
    </h2>

    <!-- Form đăng nhập -->
    <form id="loginForm">

        <!-- Email -->
        <div class="mb-3">
            <input
                id="loginEmail"
                name="email"
                type="email"
                class="form-control login-input"
                placeholder="Email"
                autocomplete="email"
                required
            >
        </div>

        <!-- Password -->
        <div class="mb-3">
            <input
                id="loginPassword"
                name="password"
                type="password"
                class="form-control login-input"
                placeholder="Password"
                autocomplete="current-password"
                required
            >
        </div>

        <!-- Thông báo lỗi -->
        <div
            id="loginMessage"
            class="alert alert-danger mb-3"
            role="alert"
            aria-live="polite"
            style="display: none;"
        ></div>

        <!-- Nút đăng nhập -->
        <button
            id="loginButton"
            type="submit"
            class="btn login-button w-100"
        >
            Sign in
        </button>

        <!-- Quên mật khẩu -->
        <div class="mt-3">
            <a
                href="?page=forgot_password"
                class="forgot-password-link"
            >
                Forgot password?
            </a>
        </div>

        <!-- Chuyển sang đăng ký -->
        <p class="signup-text">
            Don't have an account?
            <a href="?page=signup">
                Sign up now
            </a>
        </p>

    </form>

</div>
