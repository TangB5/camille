<?php
session_start();
require_once 'includes/config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location:../dist/pages/dashboard.php');
    exit;
}

$error = '';
$attempts = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0;
$lockout_time = isset($_SESSION['lockout_time']) ? $_SESSION['lockout_time'] : 0;
$lockout_duration = 10; // 10 secondes
$is_locked = $attempts >= 3 && time() < $lockout_time + $lockout_duration;

// Pré-remplir l'email si cookie existe
$remembered_email = isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '';

// Vérifier si l'utilisateur est bloqué
if ($is_locked) {
    $remaining = ($lockout_time + $lockout_duration) - time();
    $error = "Trop de tentatives. Réessayez dans $remaining secondes.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Réinitialiser le blocage si le temps est écoulé
    if ($attempts >= 3 && time() >= $lockout_time + $lockout_duration) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
        $attempts = 0;
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;

                // Gérer "Se souvenir de moi"
                if ($remember) {
                    setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 jours
                } else {
                    setcookie('remember_email', '', time() - 3600, '/'); // Supprimer le cookie
                }

                header('Location:../dist/pages/dashboard.php');
                exit;
            } else {
                // Échec de la connexion
                $_SESSION['login_attempts'] = $attempts + 1;
                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['lockout_time'] = time();
                    $error = 'Trop de tentatives. Compte bloqué pour 10 secondes.';
                } else {
                    $error = 'Email ou mot de passe incorrect.';
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="output.css">
    <title>Connexion - AGC Archives</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
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
        .input-field:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
        }
        .error-message {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .social-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                padding: 1rem;
            }
            .login-card {
                width: 100%;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
        }
        .input-icon {
            position: relative;
        }
        .input-icon ion-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
        }
        .input-icon input {
            padding-left: 40px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
            cursor: pointer;
        }
    </style>
</head>
<body class="relative flex bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('image.webp'); opacity: 0.1;"></div>

    <!-- Login Container -->
    <div class="flex items-center justify-center w-full h-screen">
        <div class="relative z-10 flex w-[50%] p-2 bg-white login-container">
            <!-- Image Container -->
            <div class="hidden object-cover w-1/2 bg-center bg-cover rounded-md md:block" style="background-image: url('assur.webp');">
                <div class="flex flex-col justify-center w-full h-full p-6 text-center text-white bg-black bg-opacity-50">
                    <h2 class="text-3xl font-bold">Vos archives, protégées avec soin</h2>
                    <p class="mt-2 text-gray-300">Rejoignez-nous et accédez à nos services exclusifs.</p>
                </div>
            </div>

            <!-- Login Card -->
            <div class="w-1/2 p-6 space-y-6 shadow-2xl login-card rounded-xl backdrop-blur-sm">
                <!-- Logo or Icon -->
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 2c-1.104 0-2 .896-2 2v3h4v-3c0-1.104-.896-2-2-2zm-7-5h2v2H5V8zm0 4h2v2H5v-2zm0 4h2v2H5v-2zm14-4h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8l4 4-4 4" />
                    </svg>
                </div>

                <h2 class="text-3xl font-bold text-center text-gray-800">Connexion à AGC</h2>

                <!-- Tabs -->
                <div class="flex justify-between p-1 text-sm bg-gray-200 rounded-full">
                    <button class="w-1/2 py-2 font-semibold text-gray-700 bg-white rounded-full">Login</button>
                    <a href="register.php" class="w-1/2 py-2 text-center text-gray-600 hover:text-gray-800">Sign Up</a>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="p-2 text-sm text-red-700 bg-red-100 rounded error-message" id="error-message">
                        <?= htmlspecialchars($error); ?>
                        <?php if ($attempts >= 1 && !$is_locked): ?>
                            <span class="block">Tentatives restantes : <?= 3 - $attempts ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="index.php" class="space-y-4" id="login-form">
                    <div class="relative input-icon">
                        <ion-icon name="mail-outline" class="absolute right-0"></ion-icon>
                        <input type="email" name="email" id="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Email" value="<?= $remembered_email ?>" <?= $is_locked ? 'disabled' : '' ?>>
                    </div>
                    <div class="relative input-icon" >
                        
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Mot de passe" <?= $is_locked ? 'disabled' : '' ?>>
                        <ion-icon name="eye-outline" id="toggle-password" class="absolute right-0 password-toggle"></ion-icon>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="mr-1 text-blue-600 border-gray-300 rounded focus:ring-blue-600" <?= $is_locked ? 'disabled' : '' ?> <?= $remembered_email ? 'checked' : '' ?>>
                            <span class="text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-blue-600 hover:underline">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="w-full py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700" <?= $is_locked ? 'disabled' : '' ?>>Login</button>
                </form>

                <!-- Divider -->
                <div class="text-sm text-center text-gray-400">ou se connecter avec</div>

                <!-- Social Buttons -->
                <div class="flex justify-center space-x-4">
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Google" <?= $is_locked ? 'disabled' : '' ?>>
                        <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" alt="Google" class="w-4 h-4 mr-2">
                        Google
                    </button>
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Facebook" <?= $is_locked ? 'disabled' : '' ?>>
                        <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" class="w-4 h-4 mr-2">
                        Facebook
                    </button>
                </div>

                <p class="mt-4 text-sm text-center text-gray-500">Pas encore inscrit ? <a href="register.php" class="text-blue-600 hover:underline">Créer un compte</a></p>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>