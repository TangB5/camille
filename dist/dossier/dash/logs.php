<?php
session_start();
require_once 'db.php';

// Vérifie si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . (isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
    exit;
}

// Gestion des paramètres de filtrage
$user_filter = isset($_GET['user']) ? trim($_GET['user']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at DESC';

// Construction de la requête SQL
$sql = "SELECT logs.*, users.name AS user_name FROM logs JOIN users ON logs.user_id = users.id WHERE 1=1";
$params = [];

if (!empty($user_filter) && $user_filter !== 'all') {
    $sql .= " AND logs.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($action_filter) && $action_filter !== 'all') {
    $sql .= " AND logs.action = ?";
    $params[] = $action_filter;
}

if (!empty($date_from)) {
    $sql .= " AND logs.created_at >= ?";
    $params[] = date('Y-m-d', strtotime($date_from));
}

if (!empty($date_to)) {
    $sql .= " AND logs.created_at <= ?";
    $params[] = date('Y-m-d', strtotime($date_to . ' +1 day'));
}

$sql .= " ORDER BY " . $sort;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs pour le filtre
$users_stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Journal des activités - AGC Archives</title>
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
        .logs-container {
            max-width: 1200px;
        }
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .error-message, .success-message {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .logs-container {
                padding: 1rem;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
            .filter-form {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="relative flex items-center justify-center w-screen h-screen overflow-hidden bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('image.webp'); opacity: 0.1;"></div>

    <!-- Logs Container -->
    <div class="relative z-10 flex flex-col items-center justify-center w-full">
        <div class="logs-container w-[90%] p-6 space-y-6 bg-white shadow-2xl rounded-xl backdrop-blur-sm">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Journal des activités</h2>
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

            <!-- Formulaire de filtrage -->
            <div class="p-4 bg-gray-100 rounded-lg">
                <h3 class="mb-4 text-xl font-semibold text-gray-700">Filtrer les activités</h3>
                <form method="GET" class="flex gap-4 filter-form">
                    <div>
                        <label class="text-sm text-gray-600">Utilisateur</label>
                        <select name="user" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                            <option value="all">Tous</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Action</label>
                        <select name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                            <option value="all">Toutes</option>
                            <option value="login" <?= $action_filter === 'login' ? 'selected' : '' ?>>Connexion</option>
                            <option value="create_archive" <?= $action_filter === 'create_archive' ? 'selected' : '' ?>>Création d'archive</option>
                            <option value="delete_user" <?= $action_filter === 'delete_user' ? 'selected' : '' ?>>Suppression d'utilisateur</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Date de début</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Date de fin</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 btn">Filtrer</button>
                    </div>
                </form>
                <div class="mt-4">
                    <label class="text-sm text-gray-600">Trier par</label>
                    <select name="sort" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                        <option value="created_at DESC" <?= $sort === 'created_at DESC' ? 'selected' : '' ?>>Date (récent en premier)</option>
                        <option value="created_at ASC" <?= $sort === 'created_at ASC' ? 'selected' : '' ?>>Date (ancien en premier)</option>
                        <option value="action ASC" <?= $sort === 'action ASC' ? 'selected' : '' ?>>Action (A-Z)</option>
                        <option value="action DESC" <?= $sort === 'action DESC' ? 'selected' : '' ?>>Action (Z-A)</option>
                    </select>
                </div>
            </div>

            <!-- Liste des logs -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold text-gray-700">Journal des activités</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-3 text-gray-700">Utilisateur</th>
                                <th class="p-3 text-gray-700">Action</th>
                                <th class="p-3 text-gray-700">Détails</th>
                                <th class="p-3 text-gray-700">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-gray-600">Aucun log trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="border-b">
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($log['user_name']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($log['action']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($log['details'] ?: 'Aucun détail') ?></td>
                                        <td class="p-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>