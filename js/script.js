document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("inscriptionForm");

    form.addEventListener("submit", function (event) {
        const email = form.email.value;
        const password = form.mot_de_passe.value;

        if (!email.includes("@")) {
            alert("Veuillez entrer une adresse e-mail valide.");
            event.preventDefault();
        }

        if (password.length < 6) {
            alert("Le mot de passe doit contenir au moins 6 caractères.");
            event.preventDefault();
        }
    });
});
