<?php
session_start();
require_once 'db.php';



// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Ajout d'une archive
        if ($action === 'add') {
            $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
            $status = in_array($_POST['status'], ['Sécurisé', 'En attente', 'Archivé']) ? $_POST['status'] : 'En attente';

            if (empty($name)) {
                $_SESSION['error'] = "Le nom de l'archive est requis.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO archives (user_id, name, description, status, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$user_id, $name, $description, $status])) {
                    $_SESSION['success'] = "Archive ajoutée avec succès.";
                } else {
                    $_SESSION['error'] = "Erreur lors de l'ajout de l'archive.";
                }
            }
        }

        // Modification d'une archive
        if ($action === 'edit') {
            $archive_id = filter_var($_POST['archive_id'], FILTER_SANITIZE_NUMBER_INT);
            $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
            $status = in_array($_POST['status'], ['Sécurisé', 'En attente', 'Archivé']) ? $_POST['status'] : 'En attente';

            if (empty($name)) {
                $_SESSION['error'] = "Le nom de l'archive est requis.";
            } else {
                $stmt = $pdo->prepare("UPDATE archives SET name = ?, description = ?, status = ? WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$name, $description, $status, $archive_id, $user_id])) {
                    $_SESSION['success'] = "Archive mise à jour avec succès.";
                } else {
                    $_SESSION['error'] = "Erreur lors de la mise à jour de l'archive.";
                }
            }
        }

        // Suppression d'une archive
        if ($action === 'delete') {
            $archive_id = filter_var($_POST['archive_id'], FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("DELETE FROM archives WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$archive_id, $user_id])) {
                $_SESSION['success'] = "Archive supprimée avec succès.";
            } else {
                $_SESSION['error'] = "Erreur lors de la suppression de l'archive.";
            }
        }
    }
    header("Location: archive.php");
    exit;
}

// Récupération des archives de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM archives WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Messages
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des archives - AGC Archives</title>
    <link rel="stylesheet" href="output.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: #3b82f6;
            border-bottom-left-radius: 50% 100px;
            border-bottom-right-radius: 50% 100px;
            z-index: 0;
            overflow: hidden;
        }
        .archives-container {
            max-width: 1200px;
        }
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
        }
        .modal-content {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            margin: 10% auto;
        }
        .error-message, .success-message {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .archives-container {
                padding: 1rem;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
        }
    </style>
</head>
<body class="relative flex items-center justify-center w-screen h-screen overflow-hidden bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('image.webp'); opacity: 0.1;"></div>

    <!-- Archives Container -->
    <div class="relative z-10 flex flex-col items-center justify-center w-full">
        <div class="archives-container w-[90%] p-6 space-y-6 bg-white shadow-2xl rounded-xl backdrop-blur-sm">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Gestion des archives</h2>
                <div class="space-x-4">
                    <a href="dashboard.php" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 btn">Retour au tableau de bord</a>
                    <form method="POST" action="dashboard.php" class="inline">
                        <button type="submit" name="logout" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 btn">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="p-2 text-sm text-red-700 bg-red-100 rounded error-message">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="p-2 text-sm text-green-700 bg-green-100 rounded success-message">
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="p-4 bg-gray-100 rounded-lg">
                <h3 class="mb-4 text-xl font-semibold text-gray-700">Ajouter une archive</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="text-sm text-gray-600">Nom de l'archive</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Description</label>
                        <textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400"></textarea>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Statut</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                            <option value="En attente">En attente</option>
                            <option value="Sécurisé">Sécurisé</option>
                            <option value="Archivé">Archivé</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 btn">
                        Ajouter
                    </button>
                </form>
            </div>

            <!-- Liste des archives -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold text-gray-700">Vos archives</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-3 text-gray-700">Nom</th>
                                <th class="p-3 text-gray-700">Description</th>
                                <th class="p-3 text-gray-700">Statut</th>
                                <th class="p-3 text-gray-700">Date d'ajout</th>
                                <th class="p-3 text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($archives)): ?>
                                <tr>
                                    <td colspan="5" class="p-3 text-center text-gray-600">Aucune archive trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($archives as $archive): ?>
                                    <tr class="border-b">
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['name']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['description'] ?: 'Aucune description') ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['status']) ?></td>
                                        <td class="p-3 text-gray-600"><?= date('d/m/Y', strtotime($archive['created_at'])) ?></td>
                                        <td class="p-3 space-x-2">
                                            <button onclick="openEditModal(<?= $archive['id'] ?>, '<?= htmlspecialchars($archive['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($archive['description'] ?? '', ENT_QUOTES) ?>', '<?= $archive['status'] ?>')" class="px-2 py-1 text-white bg-green-600 rounded hover:bg-green-700 btn">Modifier</button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="archive_id" value="<?= $archive['id'] ?>">
                                                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette archive ?')" class="px-2 py-1 text-white bg-red-600 rounded hover:bg-red-700 btn">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-4 text-xl font-semibold text-gray-700">Modifier l'archive</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="archive_id" id="edit_archive_id">
                <div>
                    <label class="text-sm text-gray-600">Nom de l'archive</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="text-sm text-gray-600">Description</label>
                    <textarea name="description" id="edit_description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400"></textarea>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Statut</label>
                    <select name="status" id="edit_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                        <option value="En attente">En attente</option>
                        <option value="Sécurisé">Sécurisé</option>
                        <option value="Archivé">Archivé</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 btn">Annuler</button>
                    <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 btn">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, description, status) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_archive_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_status').value = status;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>