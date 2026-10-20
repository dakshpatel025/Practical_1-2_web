const fatherPhoto = document.getElementById("fatherPhoto");
if (fatherPhoto) {
    fatherPhoto.addEventListener("change", function () {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                const source = new Image();
                source.onload = function () {
                    const scale = Math.min(1, 512 / Math.max(source.width, source.height));
                    const canvas = document.createElement("canvas");
                    canvas.width = Math.round(source.width * scale);
                    canvas.height = Math.round(source.height * scale);
                    canvas.getContext("2d").drawImage(source, 0, 0, canvas.width, canvas.height);
                    const img = document.getElementById("fatherPreview");
                    const text = document.getElementById("fatherText");
                    if (img) {
                        img.src = canvas.toDataURL("image/jpeg", 0.7);
                        img.style.display = "block";
                    }
                    if (text) text.style.display = "none";
                };
                source.src = e.target.result;
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
                const source = new Image();
                source.onload = function () {
                    const scale = Math.min(1, 512 / Math.max(source.width, source.height));
                    const canvas = document.createElement("canvas");
                    canvas.width = Math.round(source.width * scale);
                    canvas.height = Math.round(source.height * scale);
                    canvas.getContext("2d").drawImage(source, 0, 0, canvas.width, canvas.height);
                    const img = document.getElementById("motherPreview");
                    const text = document.getElementById("motherText");
                    if (img) {
                        img.src = canvas.toDataURL("image/jpeg", 0.7);
                        img.style.display = "block";
                    }
                    if (text) text.style.display = "none";
                };
                source.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}
