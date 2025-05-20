<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter.']);
    exit;
}

$user_id = $_SESSION['user_id'];
define('UPLOAD_DIR', '/opt/lampp/htdocs/projetCamille/Uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $file = $_FILES['file'];

    if ($file['size'] > MAX_FILE_SIZE || !in_array($file['type'], ALLOWED_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Fichier invalide (taille max 5MB, PDF/image).']);
        exit;
    }

    $file_path = UPLOAD_DIR . uniqid() . '-' . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, category, description, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $category, $description, $file_path]);
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, 'Ajout document', "Document: $title"]);
            echo json_encode(['success' => true, 'message' => 'Document ajouté avec succès.']);
        } catch (PDOException $e) {
            error_log("Erreur ajout : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_SANITIZE_NUMBER_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    try {
        $stmt = $pdo->prepare("UPDATE documents SET title = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $category, $description, $edit_id, $user_id]);
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Modification document', "Document ID: $edit_id"]);
        echo json_encode(['success' => true, 'message' => 'Document modifié.']);
    } catch (PDOException $e) {
        error_log("Erreur modification : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("SELECT title FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        $title = $stmt->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Suppression document', "Document: $title"]);
        echo json_encode(['success' => true, 'message' => 'Document supprimé.']);
    } catch (PDOException $e) {
        error_log("Erreur suppression : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
    }
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, title, category, description, file_path, created_at FROM documents WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $documents = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM documents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $documents = [];
    $categories = [];
}
?>

<div class="mx-auto dashboard-card fade-in">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Gestion des archives</h2>
        <select id="categoryFilter" class="px-4 py-2 text-base bg-white border-2 border-gray-200 rounded-xl input-focus">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-10">
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Ajouter un document</h3>
        <form method="POST" enctype="multipart/form-data" data-ajax data-page="archives" action="archives.php" class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <input type="text" name="title" placeholder="Titre" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus" required>
            <input type="text" name="category" placeholder="Catégorie" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus" required>
            <textarea name="description" placeholder="Description" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus" rows="3"></textarea>
            <input type="file" name="file" accept=".pdf,.jpg,.png" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus" required>
            <button type="submit" class="flex items-center justify-center px-6 py-3 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                <ion-icon name="cloud-upload-outline" class="mr-2 text-xl"></ion-icon>
                Ajouter
            </button>
        </form>
    </div>
    <div>
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Liste des documents</h3>
        <?php if (empty($documents)): ?>
            <p class="text-lg text-gray-500">Aucun document trouvé.</p>
        <?php else: ?>
            <div id="documentList" class="space-y-6">
                <?php foreach ($documents as $doc): ?>
                    <div class="p-6 transition-shadow bg-white shadow-lg rounded-xl hover:shadow-xl document-item" data-category="<?= htmlspecialchars($doc['category']) ?>">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <ion-icon name="document-text-outline" class="mr-4 text-3xl text-blue"></ion-icon>
                                <div>
                                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($doc['title']) ?></p>
                                    <p class="text-sm text-gray-600">Catégorie : <span class="text-white badge bg-blue"><?= htmlspecialchars($doc['category']) ?></span></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($doc['description'] ?: 'Sans description') ?></p>
                                </div>
                            </div>
                            <p class="text-sm font-medium text-gray-600"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>
                        </div>
                        <div class="flex gap-4">
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="flex items-center px-4 py-2 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                                <ion-icon name="eye-outline" class="mr-2"></ion-icon>
                                Voir
                            </a>
                            <button onclick="editDocument(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['title']) ?>', '<?= htmlspecialchars($doc['category']) ?>', '<?= htmlspecialchars($doc['description'] ?? '') ?>')" class="flex items-center px-4 py-2 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                                <ion-icon name="create-outline" class="mr-2"></ion-icon>
                                Modifier
                            </button>
                            <form method="POST" data-ajax data-page="archives" action="archives.php" onsubmit="return confirmDelete()">
                                <input type="hidden" name="delete_id" value="<?= $doc['id'] ?>">
                                <button type="submit" class="flex items-center px-4 py-2 text-white action-btn bg-red rounded-xl hover:bg-red-700">
                                    <ion-icon name="trash-outline" class="mr-2"></ion-icon>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filter = document.getElementById('categoryFilter');
        const list = document.getElementById('documentList');
        if (filter && list) {
            filter.addEventListener('change', function () {
                const selectedCategory = this.value;
                const items = list.querySelectorAll('.document-item');
                items.forEach(item => {
                    const category = item.dataset.category;
                    item.style.display = selectedCategory === '' || category === selectedCategory ? 'block' : 'none';
                });
            });
        }
    });

    function confirmDelete() {
        return new Promise(resolve => {
            Swal.fire({
                icon: 'warning',
                title: 'Confirmer',
                text: 'Voulez-vous vraiment supprimer ce document ?',
                showCancelButton: true,
                confirmButtonColor: '#1E3A8A',
                cancelButtonColor: '#DC2626',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then(result => resolve(result.isConfirmed));
        });
    }

    function editDocument(id, title, category, description) {
        Swal.fire({
            title: 'Modifier le document',
            html: `
                <input id="edit-title" type="text" value="${title}" class="w-full px-4 py-3 mb-4 border-2 border-gray-200 rounded-xl input-focus" required>
                <input id="edit-category" type="text" value="${category}" class="w-full px-4 py-3 mb-4 border-2 border-gray-200 rounded-xl input-focus" required>
                <textarea id="edit-description" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-focus">${description}</textarea>
            `,
            showCancelButton: true,
            confirmButtonColor: '#1E3A8A',
            cancelButtonColor: '#DC2626',
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
            preConfirm: () => {
                return {
                    title: document.getElementById('edit-title').value,
                    category: document.getElementById('edit-category').value,
                    description: document.getElementById('edit-description').value
                };
            }
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('edit_id', id);
                formData.append('title', result.value.title);
                formData.append('category', result.value.category);
                formData.append('description', result.value.description);
                fetch('archives.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: data.message,
                                confirmButtonColor: '#1E3A8A'
                            }).then(() => loadPage('archives'));
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: data.message,
                                confirmButtonColor: '#DC2626'
                            });
                        }
                    });
            }
        });
    }
</script>