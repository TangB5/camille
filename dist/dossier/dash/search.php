<?php
session_start();
require_once 'db.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Gestion des paramètres de recherche
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at DESC';

// Construction de la requête SQL
$sql = "SELECT * FROM archives WHERE user_id = ? AND name LIKE ?";
$params = [$user_id, "%$search_query%"];

if (!empty($status) && $status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $sql .= " AND created_at >= ?";
    $params[] = date('Y-m-d', strtotime($date_from));
}

if (!empty($date_to)) {
    $sql .= " AND created_at <= ?";
    $params[] = date('Y-m-d', strtotime($date_to . ' +1 day'));
}

$sql .= " ORDER BY " . $sort;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Messages (aucun pour cette page, mais conservés pour cohérence)
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche d'archives - AGC Archives</title>
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
        .search-container {
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
            .search-container {
                padding: 1rem;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
            .search-form {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="output.css">
</head>
<body class="relative flex items-center justify-center w-screen h-screen overflow-hidden bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('image.webp'); opacity: 0.1;"></div>

    <!-- Search Container -->
    <div class="relative z-10 flex flex-col items-center justify-center w-full">
        <div class="search-container w-[90%] p-6 space-y-6 bg-white shadow-2xl rounded-xl backdrop-blur-sm">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Recherche d'archives</h2>
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

            <!-- Formulaire de recherche -->
            <div class="p-4 bg-gray-100 rounded-lg">
                <h3 class="mb-4 text-xl font-semibold text-gray-700">Recherche avancée</h3>
                <form method="GET" class="flex gap-4 search-form">
                    <div class="flex-1">
                        <label class="text-sm text-gray-600">Rechercher par nom</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Statut</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                            <option value="all">Tous</option>
                            <option value="En attente" <?= $status === 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="Sécurisé" <?= $status === 'Sécurisé' ? 'selected' : '' ?>>Sécurisé</option>
                            <option value="Archivé" <?= $status === 'Archivé' ? 'selected' : '' ?>>Archivé</option>
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
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 btn">Rechercher</button>
                    </div>
                </form>
                <div class="mt-4">
                    <label class="text-sm text-gray-600">Trier par</label>
                    <select name="sort" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
                        <option value="created_at DESC" <?= $sort === 'created_at DESC' ? 'selected' : '' ?>>Date (récent en premier)</option>
                        <option value="created_at ASC" <?= $sort === 'created_at ASC' ? 'selected' : '' ?>>Date (ancien en premier)</option>
                        <option value="status ASC" <?= $sort === 'status ASC' ? 'selected' : '' ?>>Statut (A-Z)</option>
                        <option value="status DESC" <?= $sort === 'status DESC' ? 'selected' : '' ?>>Statut (Z-A)</option>
                    </select>
                </form>
            </div>
            </div>

            <!-- Résultats de la recherche -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold text-gray-700">Résultats</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-3 text-gray-700">Nom</th>
                                <th class="p-3 text-gray-700">Description</th>
                                <th class="p-3 text-gray-700">Statut</th>
                                <th class="p-3 text-gray-700">Date d'ajout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($archives)): ?>
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-gray-600">Aucune archive trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($archives as $archive): ?>
                                    <tr class="border-b">
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['name']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['description'] ?: 'Aucune description') ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($archive['status']) ?></td>
                                        <td class="p-3 text-gray-600"><?= date('d/m/Y', strtotime($archive['created_at'])) ?></td>
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