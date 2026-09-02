let form = document.getElementById("loginForm");

form.addEventListener("submit", function(event) {

    event.preventDefault();

    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;

    let usernameRegex = /^[A-Za-z0-9]+$/;
    let passwordRegex = /^[A-Za-z0-9@#$!%]+$/;

    if (username == "") {
        alert("Please enter username");
        return;
    }

    if (!usernameRegex.test(username)) {
        alert("Username can contain only letters and numbers");
        return;
    }

    if (password == "") {
        alert("Please enter password");
        return;
    }

    if (!passwordRegex.test(password)) {
        alert("Password contains invalid characters");
        return;
    }

    alert("Login successful!");

    window.location.href = "main_page.html";
});