let form = document.getElementById("loginForm");
form.addEventListener("submit", function(event) {
    event.preventDefault();
    let usernameInput = document.getElementById("username");
    let passwordInput = document.getElementById("password");
    let username = usernameInput.value.trim();
    let password = passwordInput.value;
    let usernameError = document.getElementById("usernameError");
    let passwordError = document.getElementById("passwordError");
    if (usernameError) { usernameError.style.display = 'none'; usernameError.textContent = ''; }
    if (passwordError) { passwordError.style.display = 'none'; passwordError.textContent = ''; }
    let usernameRegex = /^[A-Za-z0-9]+$/;
    let passwordRegex = /^[A-Za-z0-9@#$!%]+$/;
    if (username === "") {
        showError(usernameInput, usernameError, "Please enter your username or Student ID");
        return;
    }
    if (!usernameRegex.test(username)) {
        showError(usernameInput, usernameError, "Username can contain only alphanumeric characters");
        return;
    }
    if (password === "") {
        showError(passwordInput, passwordError, "Please enter your password");
        return;
    }
    if (!passwordRegex.test(password)) {
        showError(passwordInput, passwordError, "Password contains invalid characters");
        return;
    }
    sessionStorage.setItem("studenthub_logged_in_user", username);
    window.location.href = "main_page.html";
});
function showError(input, errorEl, message) {
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = "block";
    } else {
        alert(message);
    }
    input.focus();
}
