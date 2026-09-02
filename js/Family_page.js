document.getElementById("fatherPhoto").addEventListener("change", function () {
        alert("Photo selected!");

        let file = this.files[0];

        if (file) {
            let reader = new FileReader();

                reader.onload = function (e) {
                document.getElementById("fatherPreview").src = e.target.result;
                document.getElementById("fatherPreview").style.display = "block";
                document.getElementById("fatherText").style.display = "none";
            };

            reader.readAsDataURL(file);
        }
    });


document.getElementById("motherPhoto").addEventListener("change", function () {
        alert("Photo selected!");
        let file = this.files[0];

        if (file) {
            let reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById("motherPreview").src = e.target.result;
                document.getElementById("motherPreview").style.display = "block";
                document.getElementById("motherText").style.display = "none";
            };

            reader.readAsDataURL(file);
        }
    });