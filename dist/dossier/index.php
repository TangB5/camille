<?php
session_start();
require_once 'db.php';

function isBlocked($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$email]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];
    return $count >= 5;
}

function logAttempt($pdo, $email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Vérification des tentatives
    if (isBlocked($pdo, $email)) {
        $_SESSION['error'] = "Trop de tentatives. Réessayez dans 15 minutes.";
        $_SESSION['attempts'] = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
        header("Location: index.php");
        exit;
    }

    // Validation des entrées
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        logAttempt($pdo, $email);
        header("Location: index.php");
        exit;
    }

    // Vérification de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Option "Se souvenir de moi"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', true, true);
            // En production, stocker $token dans la base de données avec un lien à l'utilisateur
        }

        header("Location:dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        logAttempt($pdo, $email);
        $_SESSION['attempts'] = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
        header("Location:index.php");
        exit;
    }
}
?>
<?php
session_start();
require_once 'db.php';

// Si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Récupération des erreurs et tentatives
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] : 0;
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="output.css">
    <title>Connexion - AGC Archives</title>
    <link rel="stylesheet" href="css/stylelogin.css">

</head>
<body class="relative flex bg-gray-50">
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
                    <?php if ($attempts >= 3): ?>
                        <span class="block">Tentatives restantes : <?= 5 - $attempts ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="index.php" class="space-y-4" id="login-form">
                <div class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field">
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" name="email" id="email" required class="w-full mt-1 bg-transparent outline-none">
                </div>
                <div class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field">
                    <label class="text-sm text-gray-600">Mot de passe</label>
                    <input type="password" name="password" id="password" required class="w-full mt-1 bg-transparent outline-none">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="mr-1 text-blue-600 border-gray-300 rounded focus:ring-blue-600">
                        <span class="text-gray-600">Se souvenir de moi</span>
                    </label>
                    <a href="#" class="text-blue-600 hover:underline">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="w-full py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    Login
                </button>
            </form>

            <!-- Divider -->
            <div class="text-sm text-center text-gray-400">ou se connecter avec</div>

            <!-- Social Buttons -->
            <div class="flex justify-center space-x-4">
                <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Google">
                    <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" alt="Google" class="w-4 h-4 mr-2">
                    Google
                </button>
                <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Facebook">
                    <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" class="w-4 h-4 mr-2">
                    Facebook
                </button>
            </div>

            <p class="mt-4 text-sm text-center text-gray-500">Pas encore inscrit ? <a href="register.php" class="text-blue-600 hover:underline">Créer un compte</a></p>
        </div>

       
    </div>
     </div>
     
    

    <script src="js/script.js">
    </script>
</body>
</html>