let photoInput = document.getElementById("photoInput");
let photoPreview = document.getElementById("photoPreview");

photoInput.addEventListener("change", function () {

    alert("Are you conformed");
    let file = this.files[0];

    if (file) {

        if (!file.type.startsWith("image/")) {
            alert("Please select an image file.");
            this.value = "";
            return;
        }

        let reader = new FileReader();

        reader.onload = function (event) {

            photoPreview.innerHTML = "";

            let img = document.createElement("img");

            img.src = event.target.result;

            img.style.width = "100%";
            img.style.height = "100%";
            img.style.objectFit = "cover";

            photoPreview.appendChild(img);
        };

        reader.readAsDataURL(file);
    }
});

function clearPhoto() {

    if (photoInput.files.length === 0) {
        alert("No photo selected!");
        return;
    }

    photoInput.value = "";
    alert("Are you confirmed?");
    photoPreview.innerHTML = "<span>No Photo</span>";
}