let photoInput = document.getElementById("photoInput");
let photoPreview = document.getElementById("photoPreview");

function getLoggedInStudent() {
    const storedStudent = sessionStorage.getItem("studenthub_logged_in_student");
    if (!storedStudent) {
        window.location.href = "login.html";
        return null;
    }

    try {
        return JSON.parse(storedStudent);
    } catch (error) {
        console.error("Invalid student session data", error);
        window.location.href = "login.html";
        return null;
    }
}

function mapCourseToOption(courseName) {
    const courseMap = {
        "B.Tech Computer Science": "B.Tech CSE",
        "B.Tech Information Technology": "B.Tech IT",
        "BCA": "BCA",
        "BBA": "BBA",
        "BSc.IT": "BSc.IT",
        "B.Tech Computer Engineering": "B.Tech CSE"
    };

    return courseMap[courseName] || "B.Tech CSE";
}

function populateStudentProfile(student) {
    if (!student) return;

    const stdIdField = document.getElementById("stdId");
    const fullNameField = document.getElementById("fullName");
    const dispNameField = document.getElementById("dispName");
    const uniNameField = document.getElementById("uniName");
    const instSelectField = document.getElementById("instSelect");
    const progSelectField = document.getElementById("progSelect");
    const bPlaceField = document.getElementById("bPlace");
    const secondTitle = document.getElementById("second_title");

    if (stdIdField) stdIdField.value = student.id || "";
    if (fullNameField) fullNameField.value = student.name || "";
    if (dispNameField) dispNameField.value = student.name || "";
    if (uniNameField) uniNameField.value = "CHARUSAT";
    if (instSelectField && student.institute) instSelectField.value = student.institute;
    if (progSelectField) progSelectField.value = mapCourseToOption(student.course || "B.Tech Computer Science");
    if (bPlaceField) bPlaceField.value = student.city || "";
    if (secondTitle) secondTitle.textContent = "General Information & Admission Records for " + (student.name || "Student");
}

const loggedStudent = getLoggedInStudent();
if (loggedStudent) {
    populateStudentProfile(loggedStudent);
}

if (photoInput && photoPreview) {
    photoInput.addEventListener("change", function () {
        let file = this.files[0];
        if (file) {
            if (!file.type.startsWith("image/")) {
                alert("Please select a valid image file (PNG, JPG, JPEG).");
                this.value = "";
                return;
            }
            let reader = new FileReader();
            reader.onload = function (event) {
                const source = new Image();
                source.onload = function () {
                    const scale = Math.min(1, 512 / Math.max(source.width, source.height));
                    const canvas = document.createElement("canvas");
                    canvas.width = Math.round(source.width * scale);
                    canvas.height = Math.round(source.height * scale);
                    canvas.getContext("2d").drawImage(source, 0, 0, canvas.width, canvas.height);
                    photoPreview.replaceChildren();
                    let img = document.createElement("img");
                    img.src = canvas.toDataURL("image/jpeg", 0.7);
                    img.alt = "Uploaded Student Photo";
                    img.style.width = "100%";
                    img.style.height = "100%";
                    img.style.objectFit = "cover";
                    photoPreview.appendChild(img);
                };
                source.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}

function clearPhoto() {
    if (!photoInput || !photoPreview) {
        alert("No photo currently selected!");
        return;
    }

    if (confirm("Are you sure you want to remove the photo?")) {
        photoInput.value = "";
        photoPreview.innerHTML = "<span>No Photo</span>";
    }
}
