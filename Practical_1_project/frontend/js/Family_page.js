const fatherPhoto = document.getElementById("fatherPhoto");
if (fatherPhoto) {
    fatherPhoto.addEventListener("change", function () {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById("fatherPreview");
                const text = document.getElementById("fatherText");
                if (img) {
                    img.src = e.target.result;
                    img.style.display = "block";
                }
                if (text) text.style.display = "none";
            };
            reader.readAsDataURL(file);
        }
    });
}
const motherPhoto = document.getElementById("motherPhoto");
if (motherPhoto) {
    motherPhoto.addEventListener("change", function () {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById("motherPreview");
                const text = document.getElementById("motherText");
                if (img) {
                    img.src = e.target.result;
                    img.style.display = "block";
                }
                if (text) text.style.display = "none";
            };
            reader.readAsDataURL(file);
        }
    });
}
