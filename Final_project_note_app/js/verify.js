
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const form = document.getElementById("verifyForm");
    const emailInput = document.getElementById("verifyEmail");
    const message = document.getElementById("verifyMessage");
    const button = document.getElementById("resendVerifyButton");

    if (!form || !emailInput || !message || !button) {
        console.error("Verify form elements not found.");
        return;
    }

    // Restore email from registration
    const savedEmail =
        sessionStorage.getItem("verificationEmail");

    if (savedEmail) {
        emailInput.value = savedEmail;
    }

    function showMessage(text, success = false) {
        message.hidden = false;
        message.textContent = text;
        message.className = success
            ? "alert alert-success mb-3"
            : "alert alert-danger mb-3";
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const email = emailInput.value.trim();

        if (!email || !emailInput.checkValidity()) {
            showMessage("Please enter a valid email address.");
            return;
        }

        button.disabled = true;
        button.textContent = "Sending...";
        message.hidden = true;

        try {
            // Get CSRF token
            const sessionResponse = await fetch(
                "api/auth/session.php",
                {
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

            if (!sessionResponse.ok) {
                throw new Error("Cannot initialize session.");
            }

            const sessionData = await sessionResponse.json();

            if (!sessionData.csrf_token) {
                throw new Error("Missing CSRF token.");
            }

            // Request another verification email
            const response = await fetch(
                "api/auth/resend_verification.php",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-Token": sessionData.csrf_token
                    },
                    body: JSON.stringify({ email })
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                showMessage(
                    result.message ||
                    "Unable to send verification email."
                );
                return;
            }

            sessionStorage.setItem(
                "verificationEmail",
                email
            );

            showMessage(
                result.message ||
                "Please check your email for the link.",
                true
            );

        } catch (error) {
            console.error("Verify email error:", error);

            showMessage(
                "Unable to process your request. Please try again."
            );
        } finally {
            button.disabled = false;
            button.textContent = "Resend Verification Email";
        }
    });
});
