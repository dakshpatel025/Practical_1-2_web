let form = document.getElementById("loginForm");

if (form) {
    form.addEventListener("submit", async function(event) {
        event.preventDefault();
        let usernameInput = document.getElementById("username");
        let passwordInput = document.getElementById("password");
        let username = usernameInput.value.trim();
        let password = passwordInput.value.trim();
        let usernameError = document.getElementById("usernameError");
        let passwordError = document.getElementById("passwordError");

        if (usernameError) { usernameError.style.display = 'none'; usernameError.textContent = ''; }
        if (passwordError) { passwordError.style.display = 'none'; passwordError.textContent = ''; }

        let usernameRegex = /^[A-Za-z0-9]+$/;
        if (username === "") {
            showError(usernameInput, usernameError, "Please enter your Student ID");
            return;
        }
        if (!usernameRegex.test(username)) {
            showError(usernameInput, usernameError, "Student ID can contain only alphanumeric characters");
            return;
        }
        if (password === "") {
            showError(passwordInput, passwordError, "Please enter your password");
            return;
        }

        try {
            const response = await fetch("../data/students.json");
            if (!response.ok) {
                throw new Error("Student data not found");
            }

            const students = await response.json();
            const matchedStudent = students.find(student =>
                student.id.toLowerCase() === username.toLowerCase() &&
                String(student.password) === password
            );

            if (!matchedStudent) {
                showError(passwordInput, passwordError, "Invalid Student ID or password");
                return;
            }

            sessionStorage.setItem("studenthub_logged_in_user", matchedStudent.id);
            sessionStorage.setItem("studenthub_logged_in_student", JSON.stringify(matchedStudent));
            window.location.href = "main_page.html";
        } catch (error) {
            console.error(error);
            showError(passwordInput, passwordError, "Unable to load student data. Please try again.");
        }
    });
}

function showError(input, errorEl, message) {
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = "block";
    } else {
        alert(message);
    }
    if (input) {
        input.focus();
    }
}
