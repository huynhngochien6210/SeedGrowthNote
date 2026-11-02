
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const form = document.getElementById("signupForm");

    if (!form) {
        return;
    }

    const usernameInput =
        document.getElementById("signupUsername");

    const emailInput =
        document.getElementById("signupEmail");

    const passwordInput =
        document.getElementById("signupPassword");

    const confirmPasswordInput =
        document.getElementById("signupConfirmPassword");

    const button =
        document.getElementById("signupButton");

    const message =
        document.getElementById("signupMessage");

    if (
        !usernameInput ||
        !emailInput ||
        !passwordInput ||
        !confirmPasswordInput ||
        !button ||
        !message
    ) {
        console.error("Signup form elements not found.");
        return;
    }

    function showMessage(text, isSuccess = false) {
        message.textContent = text;

        message.className = isSuccess
            ? "alert alert-success mb-3"
            : "alert alert-danger mb-3";
    }

    function clearMessage() {
        message.textContent = "";
        message.className = "mb-3";
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const displayName = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        clearMessage();

        // Validate username
        if (!displayName) {
            showMessage("Please enter your username.");
            return;
        }

        // Validate email
        if (!email) {
            showMessage("Please enter your email.");
            return;
        }

        // Validate password
        if (password.length < 8) {
            showMessage(
                "Password must be at least 8 characters."
            );
            return;
        }

        // Confirm password
        if (password !== confirmPassword) {
            showMessage("Passwords do not match.");
            return;
        }

        button.disabled = true;
        button.textContent = "Creating account...";

        try {
            // Step 1: Get CSRF token
            const sessionResponse = await fetch(
                "/api/auth/session.php",
                {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

            if (!sessionResponse.ok) {
                throw new Error(
                    "Cannot initialize session."
                );
            }

            const sessionData =
                await sessionResponse.json();

            if (!sessionData.csrf_token) {
                throw new Error(
                    "CSRF token is missing."
                );
            }

            // Step 2: Register account
            const response = await fetch(
                "/api/auth/register.php",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-Token": sessionData.csrf_token
                    },
                    body: JSON.stringify({
                        display_name: displayName,
                        email: email,
                        password: password,
                        confirm_password: confirmPassword
                    })
                }
            );

            const result = await response.json();

            // Handle registration errors
            if (!response.ok || !result.success) {
                if (result.verification_required === true) {
                    sessionStorage.setItem(
                        "verificationEmail",
                        email
                    );

                    window.location.href = "?page=verify";
                    return;
                }

                showMessage(
                    result.message || "Registration failed."
                );
                return;
            }

            // Registration successful
            sessionStorage.setItem(
                "verificationEmail",
                email
            );

            // Redirect to verification page
            window.location.href = "?page=verify";

        } catch (error) {
            console.error("Register error:", error);

            showMessage(
                error.message ||
                "Unable to connect to the server."
            );

        } finally {
            button.disabled = false;
            button.textContent = "Create an account";
        }
    });
});
