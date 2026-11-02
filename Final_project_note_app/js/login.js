
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    const emailInput = document.getElementById("loginEmail");
    const passwordInput = document.getElementById("loginPassword");
    const message = document.getElementById("loginMessage");
    const button = document.getElementById("loginButton");

    if (!form || !emailInput || !passwordInput) {
        console.error("Login form elements not found.");
        return;
    }

    function showMessage(text) {
        if (message) {
            message.textContent = text;
            message.style.display = "block";
        } else {
            alert(text);
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (button) {
            button.disabled = true;
            button.textContent = "Signing in...";
        }

        try {
            const sessionResponse = await fetch(
                "/api/auth/session.php",
                { credentials: "same-origin" }
            );

            if (!sessionResponse.ok) {
                throw new Error("Cannot initialize session.");
            }

            const sessionData = await sessionResponse.json();

            const response = await fetch("/api/auth/login.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": sessionData.csrf_token
                },
                body: JSON.stringify({ email, password })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (result.verification_required) {
                    sessionStorage.setItem(
                        "verificationEmail",
                        email
                    );
                }

                showMessage(result.message || "Đăng nhập thất bại.");
                return;
            }

            // Change this if your Home route has another name
            window.location.href = "/?page=home";

        } catch (error) {
            console.error("Login error:", error);
            showMessage("Không thể kết nối đến máy chủ.");
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = "Sign in";
            }
        }
    });
});
