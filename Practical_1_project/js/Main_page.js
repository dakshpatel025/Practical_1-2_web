let photoInput = document.getElementById("photoInput");
let photoPreview = document.getElementById("photoPreview");
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
                photoPreview.innerHTML = "";
                let img = document.createElement("img");
                img.src = event.target.result;
                img.alt = "Uploaded Student Photo";
                img.style.width = "100%";
                img.style.height = "100%";
                img.style.objectFit = "cover";
                photoPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
}
function clearPhoto() {
    if (!photoInput || photoInput.files.length === 0) {
        alert("No photo currently selected!");
        return;
    }
    if (confirm("Are you sure you want to remove the photo?")) {
        photoInput.value = "";
        photoPreview.innerHTML = "<span>No Photo</span>";
    }
}
