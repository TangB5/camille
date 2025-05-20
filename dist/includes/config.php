<?php


if (!defined('ACCESS')) {
    define('ACCESS', true);
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'Test1');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "Connexion à la base de données réussie !";
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion : " . $e->getMessage()); // Afficher le message d'erreur détaillé
}

// define('UPLOAD_DIR', __DIR__ . '../Uploads/');
// define('MAX_FILE_SIZE', 5 * 1024 * 1024);
// define('ALLOWED_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// try {
//     $pdo = new PDO(
//         "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
//         DB_USER,
//         DB_PASS,
//         [
//             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::ATTR_EMULATE_PREPARES => false
//         ]
//     );
// } catch (PDOException $e) {
//     error_log("Erreur de connexion à la base de données : " . $e->getMessage());
//     die("Une erreur est survenue. Veuillez réessayer plus tard.");
// }

// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);
?>