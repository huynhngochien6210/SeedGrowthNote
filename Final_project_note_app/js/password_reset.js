document.addEventListener("DOMContentLoaded", () => {
    const forgotForm = document.getElementById("forgotPasswordForm");
    const resetForm = document.getElementById("resetPasswordForm");

    async function postJSON(url, data) {
        const response = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Request failed");
        }

        return result;
    }

    if (forgotForm) {
        forgotForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const message = document.getElementById(
                "forgotPasswordMessage"
            );

            const button = forgotForm.querySelector(
                'button[type="submit"]'
            );

            button.disabled = true;
            message.textContent = "Sending request...";

            try {
                const email = document.getElementById(
                    "resetEmail"
                ).value.trim();

                const result = await postJSON(
                    "/api/auth/forgot_password.php",
                    { email }
                );

                message.textContent = result.message;
            } catch (error) {
                message.textContent = error.message;
            } finally {
                button.disabled = false;
            }
        });
    }

    if (resetForm) {
        const params = new URLSearchParams(window.location.search);
        const token = params.get("token");

        const message = document.getElementById(
            "resetPasswordMessage"
        );

        if (!token) {
            message.textContent = "Missing reset token";
            resetForm.querySelector("button").disabled = true;
            return;
        }

        // Xóa token khỏi thanh địa chỉ sau khi đã đọc.
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname + "?page=reset_password"
        );

        resetForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const password = document.getElementById(
                "newPassword"
            ).value;

            const confirmPassword = document.getElementById(
                "confirmNewPassword"
            ).value;

            if (password !== confirmPassword) {
                message.textContent = "Passwords do not match";
                return;
            }

            const button = resetForm.querySelector(
                'button[type="submit"]'
            );

            button.disabled = true;
            message.textContent = "Resetting password...";

            try {
                const result = await postJSON(
                    "/api/auth/reset_password.php",
                    {
                        token,
                        password,
                        confirm_password: confirmPassword
                    }
                );

                message.textContent = result.message;
                resetForm.reset();

                // Người dùng có thể đăng nhập bằng mật khẩu mới.
            } catch (error) {
                message.textContent = error.message;
                button.disabled = false;
            }
        });
    }
});