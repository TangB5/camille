<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword = filter_input(INPUT_POST, 'keyword', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $date_from = filter_input(INPUT_POST, 'date_from', FILTER_SANITIZE_STRING);
    $file_type = filter_input(INPUT_POST, 'file_type', FILTER_SANITIZE_STRING);

    $query = "SELECT id, title, category, description, file_path, created_at FROM documents WHERE user_id = ?";
    $params = [$user_id];
    if ($keyword) {
        $query .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    if ($date_from) {
        $query .= " AND created_at >= ?";
        $params[] = $date_from;
    }
    if ($file_type) {
        $query .= " AND file_path LIKE ?";
        $params[] = "%.$file_type";
    }
    $query .= " ORDER BY created_at DESC LIMIT 10";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Recherche', "Mots-clés: $keyword, Catégorie: $category"]);
        echo json_encode(['success' => true, 'message' => 'Recherche effectuée.', 'results' => $results]);
    } catch (PDOException $e) {
        error_log("Erreur recherche : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche.']);
    }
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM documents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}
?>

<div class="mx-auto dashboard-card fade-in">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Recherche de documents</h2>
        <div class="relative">
            <input type="text" id="searchInput" placeholder="Rechercher..." class="w-64 px-4 py-3 pr-10 border-2 border-gray-200 rounded-xl input-focus" autocomplete="off">
            <ion-icon name="search-outline" class="absolute text-gray-600 transform -translate-y-1/2 right-3 top-1/2"></ion-icon>
        </div>
    </div>
    <div class="mb-10">
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Filtres de recherche</h3>
        <form id="searchForm" data-ajax data-page="search" action="search.php" class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <input type="text" name="keyword" placeholder="Mots-clés" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus">
            <select name="category" class="px-4 py-3 bg-white border-2 border-gray-200 rounded-xl input-focus">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date_from" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus">
            <select name="file_type" class="px-4 py-3 bg-white border-2 border-gray-200 rounded-xl input-focus">
                <option value="">Tous les types</option>
                <option value="pdf">PDF</option>
                <option value="jpg">JPG</option>
                <option value="png">PNG</option>
            </select>
            <button type="submit" class="flex items-center justify-center px-6 py-3 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                <ion-icon name="search-outline" class="mr-2 text-xl"></ion-icon>
                Rechercher
            </button>
        </form>
    </div>
    <div>
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Résultats</h3>
        <div id="searchResults" class="space-y-6"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchForm = document.getElementById('searchForm');
        const searchResults = document.getElementById('searchResults');

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<div class="inline-block spinner"></div>';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    searchResults.innerHTML = '';
                    if (data.success && data.results.length > 0) {
                        data.results.forEach(doc => {
                            const div = document.createElement('div');
                            div.className = 'bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow';
                            div.innerHTML = `
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <ion-icon name="document-text-outline" class="mr-4 text-3xl text-blue"></ion-icon>
                                        <div>
                                            <p class="text-lg font-semibold text-gray-800">${doc.title}</p>
                                            <p class="text-sm text-gray-600">Catégorie : <span class="text-white badge bg-blue">${doc.category}</span></p>
                                            <p class="text-sm text-gray-600">${doc.description || 'Sans description'}</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-medium text-gray-600">${new Date(doc.created_at).toLocaleString('fr-FR')}</p>
                                </div>
                                <div class="flex gap-4">
                                    <a href="${doc.file_path}" target="_blank" class="flex items-center px-4 py-2 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                                        <ion-icon name="eye-outline" class="mr-2"></ion-icon>
                                        Voir
                                    </a>
                                </div>
                            `;
                            searchResults.appendChild(div);
                        });
                    } else {
                        searchResults.innerHTML = '<p class="text-lg text-gray-500">Aucun résultat trouvé.</p>';
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    searchResults.innerHTML = '<p class="text-lg font-semibold text-red-600">Erreur lors de la recherche.</p>';
                });
        });
    });
</script>