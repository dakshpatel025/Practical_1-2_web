let generatedCaptcha = "";
function generateCaptcha() {
    let canvas = document.getElementById("captchaCanvas");
    if (!canvas) return;
    let ctx = canvas.getContext("2d");
    let chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    generatedCaptcha = "";
    for (let i = 0; i < 5; i++) {
        generatedCaptcha += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    ctx.fillStyle = "#e2e8f0";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.font = "bold 20px Arial";
    ctx.fillStyle = "#10115d";
    ctx.fillText(generatedCaptcha, 25, 26);
}
function checkPasswordStrength(password) {
    let text = document.getElementById("strengthText");
    if (!text) return;
    if (password.length === 0) {
        text.innerText = "";
        return;
    }
    let hasUpper = /[A-Z]/.test(password);
    let hasLower = /[a-z]/.test(password);
    let hasNumber = /[0-9]/.test(password);
    let hasSpecial = /[@$!%*?&]/.test(password);
    if (password.length >= 8 && hasUpper && hasLower && hasNumber && hasSpecial) {
        text.innerText = "Strength: Strong";
        text.style.color = "green";
    } else if (password.length >= 6) {
        text.innerText = "Strength: Medium";
        text.style.color = "orange";
    } else {
        text.innerText = "Strength: Weak";
        text.style.color = "red";
    }
}
document.addEventListener("DOMContentLoaded", function () {
    generateCaptcha();
    let passInput = document.getElementById("regPass");
    if (passInput) {
        passInput.addEventListener("input", function () {
            checkPasswordStrength(this.value);
        });
    }
    let form = document.getElementById("regForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            let name = document.getElementById("regName").value.trim();
            let email = document.getElementById("regEmail").value.trim();
            let mobile = document.getElementById("regMobile").value.trim();
            let password = document.getElementById("regPass").value;
            let confirmPass = document.getElementById("regConfirmPass").value;
            let course = document.getElementById("regCourse").value;
            let year = document.getElementById("regYear").value;
            let genderSelected = document.querySelector('input[name="gender"]:checked');
            let captchaVal = document.getElementById("captchaInput").value.trim();
            let terms = document.getElementById("regTerms").checked;
            document.querySelectorAll(".error-text").forEach(el => {
                el.style.display = "none";
                el.innerText = "";
            });
            let isValid = true;
            let nameRegex = /^[A-Za-z\s]{3,50}$/;
            if (!nameRegex.test(name)) {
                showErr("nameErr", "Enter a valid name (letters only, min 3 chars)");
                isValid = false;
            }
            let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email)) {
                showErr("emailErr", "Enter a valid email address (e.g. name@charusat.edu.in)");
                isValid = false;
            }
            let mobileRegex = /^[6-9]\d{9}$/;
            if (!mobileRegex.test(mobile)) {
                showErr("mobileErr", "Enter a valid 10-digit mobile number");
                isValid = false;
            }
            let passRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passRegex.test(password)) {
                showErr("passErr", "Password must have min 8 chars, 1 uppercase, 1 lowercase, 1 number & 1 symbol");
                isValid = false;
            }
            if (password !== confirmPass) {
                showErr("confirmErr", "Passwords do not match");
                isValid = false;
            }
            if (course === "") {
                showErr("courseErr", "Please select a course");
                isValid = false;
            }
            if (year === "") {
                showErr("yearErr", "Please select an academic year");
                isValid = false;
            }
            if (!genderSelected) {
                showErr("genderErr", "Please select gender");
                isValid = false;
            }
            if (captchaVal.toUpperCase() !== generatedCaptcha.toUpperCase()) {
                showErr("captchaErr", "Incorrect CAPTCHA code. Try again");
                generateCaptcha();
                isValid = false;
            }
            if (!terms) {
                showErr("termsErr", "You must agree to the terms");
                isValid = false;
            }
            if (isValid) {
                alert("Registration Successful!\nWelcome " + name + " to CHARUSAT StudentHub.");
                form.reset();
                generateCaptcha();
                document.getElementById("strengthText").innerText = "";
            }
        });
    }
});
function showErr(id, msg) {
    let el = document.getElementById(id);
    if (el) {
        el.innerText = msg;
        el.style.display = "block";
    }
}
