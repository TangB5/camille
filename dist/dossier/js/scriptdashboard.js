function loadSection(section) {
    fetch(`dash/${section}.php`)
        .then(response => response.text())
        .then(data => {
            const mainContent = document.getElementById("main-content");
            mainContent.style.opacity = 0; // Effet de fade-out
            setTimeout(() => {
                mainContent.innerHTML = data;
                mainContent.style.opacity = 1; // Effet de fade-in
            }, 300);
        })
        .catch(error => console.error("Erreur de chargement :", error));
}
