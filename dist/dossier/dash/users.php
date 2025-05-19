<?php
session_start();
require_once 'db.php';

// Vérifie si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . (isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
    exit;
 }

$user_id = $_SESSION['user_id'];

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Modification d'un utilisateur
        if ($action === 'edit') {
            $user_id_to_edit = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
            $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $role = in_array($_POST['role'], ['standard', 'admin']) ? $_POST['role'] : 'standard';

            if (empty($name) || empty($email)) {
                $_SESSION['error'] = "Le nom et l'email sont requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Veuillez entrer un email valide.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                if ($stmt->execute([$name, $email, $role, $user_id_to_edit])) {
                    $_SESSION['success'] = "Utilisateur mis à jour avec succès.";
                } else {
                    $_SESSION['error'] = "Erreur lors de la mise à jour de l'utilisateur.";
                }
            }
        }

        // Suppression d'un utilisateur
        if ($action === 'delete') {
            $user_id_to_delete = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
            if ($user_id_to_delete == $user_id) {
                $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id_to_delete])) {
                    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
                } else {
                    $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur.";
                }
            }
        }
    }
    header("Location: users.php");
    exit;
}

// Récupération de tous les utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Gestion des utilisateurs - AGC Archives</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="output.css">

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
        .users-container {
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
            .users-container {
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

    <!-- Users Container -->
    <div class="relative z-10 flex flex-col items-center justify-center w-full">
        <div class="users-container w-[90%] p-6 space-y-6 bg-white shadow-2xl rounded-xl backdrop-blur-sm">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Gestion des utilisateurs</h2>
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

            <!-- Liste des utilisateurs -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold text-gray-700">Liste des utilisateurs</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-3 text-gray-700">Nom</th>
                                <th class="p-3 text-gray-700">Email</th>
                                <th class="p-3 text-gray-700">Rôle</th>
                                <th class="p-3 text-gray-700">Date d'inscription</th>
                                <th class="p-3 text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="p-3 text-center text-gray-600">Aucun utilisateur trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="border-b">
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($user['name']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($user['role']) ?></td>
                                        <td class="p-3 text-gray-600"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                        <td class="p-3 space-x-2">
                                            <button onclick="openEditModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', '<?= $user['role'] ?>')" class="px-2 py-1 text-white bg-green-600 rounded hover:bg-green-700 btn">Modifier</button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')" class="px-2 py-1 text-white bg-red-600 rounded hover:bg-red-700 btn">Supprimer</button>
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
            <h3 class="mb-4 text-xl font-semibold text-gray-700">Modifier l'utilisateur</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div>
                    <label class="text-sm text-gray-600">Nom</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="text-sm text-gray-600">Rôle</label>
                    <select name="role" id="edit_role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                        <option value="standard">Standard</option>
                        <option value="admin">Administrateur</option>
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
        function openEditModal(id, name, email, role) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
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