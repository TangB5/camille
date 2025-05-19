<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Exemple : Créer un nouveau dossier ou document
    $name = htmlspecialchars($_POST['name'] ?? '');
    if ($name) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=agc_archiv_secure", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare("INSERT INTO archives (user_id, file_name, file_size, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->execute([$_SESSION['user_id'], $name]);
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            echo "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer - AGC Archives</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        :root {
            --bleu: #1E40AF;
            --blanc: #FFFFFF;
            --rouge: #DC2626;
            --gris-clair: #F3F4F6;
        }
        body {
            background-color: var(--gris-clair);
            font-family: Arial, sans-serif;
        }
        .btn-bleu {
            background-color: var(--bleu);
            color: var(--blanc);
        }
        .btn-bleu:hover {
            background-color: #1E3A8A;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen font-sans">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
        <h2 class="mb-4 text-2xl font-semibold">Créer un nouvel élément</h2>
        <form method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-gray-700">Nom :</label>
                <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg" required>
            </div>
            <button type="submit" class="w-full px-4 py-2 rounded btn-bleu">Créer</button>
        </form>
        <a href="dashboard.php" class="block mt-4 text-center text-blue-600">Retour</a>
    </div>
</body>
</html>