<?php
session_start();
require_once 'db.php';

// Si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Veuillez entrer un email valide.";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérification du doublon d'email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
        } else {
            // Hachage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insertion dans la base de données
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'standard', NOW())");
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $_SESSION['success'] = "Inscription réussie ! Connectez-vous.";
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
    header("Location: register.php");
    exit;
}

// Récupération des erreurs
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - AGC Archives</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="relative overflow-hidden bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('image.webp'); opacity: 0.1;"></div>


    



<!-- Login Container -->
     <div class="flex items-center justify-center w-full h-screen">
        <div class="relative z-10 flex  w-[50%] p-2  bg-white login-container ">
            
         <!-- Image Container -->
<div class="hidden object-cover w-1/2 bg-center bg-cover rounded-md md:block" style="background-image: url('assur.webp');">  
    <div class="flex flex-col justify-center w-full h-full p-6 text-center text-white bg-black bg-opacity-50" >
            <h2 class="text-3xl font-bold">Vos archives, protégées avec soin</h2>
            <p class="mt-2 text-gray-300">Rejoignez-nous et accédez à nos services exclusifs.</p>          
    </div>
</div>    
        <!-- Registration Card -->
            <div class="w-1/2 p-6 space-y-6 shadow-2xl login-card rounded-xl backdrop-blur-sm">
                <!-- Logo or Icon -->
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 2c-1.104 0-2 .896-2 2v3h4v-3c0-1.104-.896-2-2-2zm-7-5h2v2H5V8zm0 4h2v2H5v-2zm0 4h2v2H5v-2zm14-4h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8l4 4-4 4" />
                    </svg>
                </div>

                <h2 class="text-3xl font-bold text-center text-gray-800">Inscription à AGC</h2>

                <!-- Tabs -->
                <div class="flex justify-between p-1 text-sm bg-gray-200 rounded-full">
                    <a href="index.php" class="w-1/2 py-2 text-center text-gray-600 hover:text-gray-800">Login</a>
                    <button class="w-1/2 py-2 font-semibold text-gray-700 bg-white rounded-full">Sign Up</button>
                </div>

                <!-- Messages -->
                <?php if ($error): ?>
                    <div class="p-2 text-sm text-red-700 bg-red-100 rounded error-message" id="error-message">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="p-2 text-sm text-green-700 bg-green-100 rounded success-message">
                        <?= htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" action="register.php" class="space-y-4" id="register-form">
                    <div class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field">
                        <label class="text-sm text-gray-600">Nom</label>
                        <input type="text" name="name" id="name" required class="w-full mt-1 bg-transparent bg-blue-300 outline-none" >
                    </div>
                    <div class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field">
                        <label class="text-sm text-gray-600">Email</label>
                        <input type="email" name="email" id="email" required class="w-full mt-1 bg-transparent bg-blue-300 outline-none">
                    </div>
                    <div class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field">
                        <label class="text-sm text-gray-600">Mot de passe</label>
                        <input type="password" name="password" id="password" required class="w-full mt-1 bg-transparent bg-blue-300 outline-none">
                    </div>

                    <button type="submit" class="w-full py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        S'inscrire
                    </button>
                </form>

                <p class="mt-4 text-sm text-center text-gray-500">Déjà inscrit ? <a href="login.php" class="text-blue-600 hover:underline">Se connecter</a></p>
            </div>
</div>    

           
</div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('#register-form');
            const nameInput = document.querySelector('#name');
            const emailInput = document.querySelector('#email');
            const passwordInput = document.querySelector('#password');
            const errorDiv = document.querySelector('#error-message');

            form.addEventListener('submit', (e) => {
                let error = '';

                // Vérification du nom
                if (nameInput.value.length < 2) {
                    error = 'Le nom doit contenir au moins 2 caractères.';
                }
                // Vérification de l'email
                else if (!emailInput.value.includes('@') || emailInput.value.length < 5) {
                    error = 'Veuillez entrer un email valide.';
                }
                // Vérification du mot de passe
                else if (passwordInput.value.length < 6) {
                    error = 'Le mot de passe doit contenir au moins 6 caractères.';
                }

                if (error) {
                    e.preventDefault();
                    if (errorDiv) {
                        errorDiv.textContent = error;
                        errorDiv.classList.remove('hidden');
                    }
                }
            });
        });
    </script>
</body>
</html>