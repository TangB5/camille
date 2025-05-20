<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Utilisateur';
$role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Inconnu';
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM documents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_documents = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM documents WHERE user_id = ? GROUP BY category");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT title, category, created_at FROM documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $recent_documents = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT DISTINCT category FROM documents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $category_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Erreur : " . $e->getMessage());
    $total_documents = 0;
    $categories = [];
    $recent_documents = [];
    $category_list = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Déconnexion', 'Utilisateur déconnecté']);
    } catch (PDOException $e) {
        error_log("Erreur log : " . $e->getMessage());
    }
    session_destroy();
    setcookie('remember_email', '', time() - 3600, '/');
    header('Location: ../../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Tableau de bord - AGC Archives</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="../output.css">
    <style>
        :root {
            --blue: #1E3A8A;
            --white: #FFFFFF;
            --red: #DC2626;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-600: #4B5563;
            --gray-800: #1F2937;
            --blue-light: #3B82F6;
            --red-light: #F87171;
        }

        .wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 120px;
            background: linear-gradient(180deg, var(--blue) 0%, var(--blue-light) 100%);
            border-bottom-left-radius: 50% 60px;
            border-bottom-right-radius: 50% 60px;
            z-index: 0;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(90deg, var(--blue) 0%, var(--blue-light) 100%);
            color: var(--white);
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100%;
            background: var(--gray-800);
            color: var(--white);
            transform: translateX(0);
            transition: transform 0.3s ease;
            z-index: 999;
            padding-top: 80px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
        }
        .sidebar-hidden {
            transform: translateX(-260px);
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 1.25rem 1rem;
            color: #d1d5db;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar a:hover {
            background: #374151;
            color: var(--white);
            border-left-color: var(--red);
        }
        .sidebar a.active {
            background: var(--blue);
            color: var(--white);
            border-left-color: var(--red);
        }

        .main-content {
            margin-left: 260px;
            padding: 6rem 1.5rem 1.5rem;
            width: calc(100% - 260px);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        .main-content-full {
            margin-left: 0;
            width: 100%;
        }
        .dashboard-card {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 1400px;
            width: 100%;
            position: relative;
        }
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--blue), var(--red));
        }
        .stat-card {
            border: 2px solid var(--blue);
            background: linear-gradient(145deg, var(--white), var(--gray-100));
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .action-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .action-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }
        .action-btn:hover::after {
            width: 200px;
            height: 200px;
        }
        .action-btn:hover {
            transform: scale(1.08);
        }
        .timeline-item {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 2rem;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(var(--blue), var(--red));
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            width: 14px;
            height: 14px;
            background: var(--red);
            border: 2px solid var(--white);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
        }
        .badge {
            padding: 0.35rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .badge:hover {
            transform: scale(1.1);
        }
        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--blue), var(--blue-light));
            transition: width 0.5s ease;
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
        }
        .spinner {
            border: 3px solid var(--gray-200);
            border-top: 3px solid var(--blue);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-260px);
            }
            .sidebar-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 5rem 1rem 1rem;
            }
            .wave {
                height: 80px;
                border-bottom-left-radius: 50% 40px;
                border-bottom-right-radius: 50% 40px;
            }
            .dashboard-card {
                padding: 1.5rem;
            }
            .grid-cols-3, .grid-cols-4 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="relative bg-gray-50">
    <div class="wave"></div>
    <div class="absolute inset-0 h-screen bg-center bg-cover" style="background-image: url('../image.webp'); opacity: 0.1;"></div>

    <nav class="flex items-center justify-between p-4 navbar">
        <div class="flex items-center">
            <button id="toggleSidebar" class="mr-4 text-white md:hidden">
                <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
            </button>
            <div class="flex items-center">
                <svg class="w-8 h-8 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 2c-1.104 0-2 .896-2 2v3h4v-3c0-1.104-.896-2-2-2zm-7-5h2v2H5V8zm0 4h2v2H5v-2zm0 4h2v2H5v-2zm14-4h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8l4 4-4 4" />
                </svg>
                <span class="text-xl font-bold">AGC Archives</span>
            </div>
        </div>
        <div class="flex items-center">
            <span class="hidden mr-4 text-sm font-medium md:block"><?= $email ?> (<span class="text-white badge bg-red"><?= $role ?></span>)</span>
            <form method="POST" action="" id="logout-form">
                <button type="submit" name="logout" class="flex items-center text-white hover:text-gray-200">
                    <ion-icon name="log-out-outline" class="mr-1 text-xl"></ion-icon>
                    <span class="hidden md:inline">Déconnexion</span>
                </button>
            </form>
        </div>
    </nav>

    <div id="sidebar" class="sidebar">
        <a href="dashboard.php" class="active" data-page="dashboard">
            <ion-icon name="home-outline" class="mr-2 text-xl"></ion-icon>
            Tableau de bord
        </a>
        <a href="archives.php" data-page="archives">
            <ion-icon name="archive-outline" class="mr-2 text-xl"></ion-icon>
            Gestion des archives
        </a>
        <a href="search.php" data-page="search">
            <ion-icon name="search-outline" class="mr-2 text-xl"></ion-icon>
            Recherche
        </a>
        <?php if ($role === 'admin'): ?>
            <a href="users.php" data-page="users">
                <ion-icon name="people-outline" class="mr-2 text-xl"></ion-icon>
                Gestion des utilisateurs
            </a>
            <a href="logs.php" data-page="logs">
                <ion-icon name="journal-outline" class="mr-2 text-xl"></ion-icon>
                Journal des actions
            </a>
        <?php endif; ?>
    </div>

    <div id="mainContent" class="relative z-10 flex justify-center main-content">
        <div class="mx-auto dashboard-card fade-in">
            <div class="flex items-center mb-8">
                <ion-icon name="person-circle-outline" class="mr-4 text-5xl text-blue"></ion-icon>
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Bienvenue, <?= $email ?> !</h2>
                    <p class="text-base text-gray-600">Rôle : <span class="text-white badge bg-blue"><?= $role ?></span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 mb-10 md:grid-cols-3">
                <div class="p-6 stat-card rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <ion-icon name="document-outline" class="text-4xl text-blue"></ion-icon>
                        <span class="text-white badge bg-blue">Total</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Documents</h3>
                    <p class="text-4xl font-bold text-blue"><?= $total_documents ?></p>
                    <div class="mt-4 progress-bar">
                        <div class="progress-bar-fill" style="width: <?= min($total_documents * 10, 100) ?>%;"></div>
                    </div>
                </div>
                <div class="p-6 stat-card rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <ion-icon name="folder-outline" class="text-4xl text-blue"></ion-icon>
                        <span class="text-white badge bg-blue">Diversité</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Catégories</h3>
                    <p class="text-4xl font-bold text-blue"><?= count($categories) ?></p>
                    <div class="mt-4 progress-bar">
                        <div class="progress-bar-fill" style="width: <?= min(count($categories) * 20, 100) ?>%;"></div>
                    </div>
                </div>
                <div class="p-6 stat-card rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <ion-icon name="time-outline" class="text-4xl text-blue"></ion-icon>
                        <span class="text-white badge bg-red">Récent</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Activité</h3>
                    <p class="text-4xl font-bold text-blue"><?= count($recent_documents) ?></p>
                    <div class="mt-4 progress-bar">
                        <div class="progress-bar-fill" style="width: <?= min(count($recent_documents) * 33, 100) ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="mb-10">
                <h3 class="mb-6 text-2xl font-semibold text-gray-800">Actions rapides</h3>
                <div class="flex flex-wrap gap-6">
                    <a href="archives.php" data-page="archives" class="flex items-center px-6 py-3 text-white action-btn bg-blue rounded-xl hover:bg-blue-900">
                        <ion-icon name="add-outline" class="mr-2 text-xl"></ion-icon>
                        Ajouter un document
                    </a>
                    <a href="search.php" data-page="search" class="flex items-center px-6 py-3 bg-white border-2 action-btn text-blue rounded-xl border-blue hover:bg-blue hover:text-white">
                        <ion-icon name="search-outline" class="mr-2 text-xl"></ion-icon>
                        Rechercher
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="users.php" data-page="users" class="flex items-center px-6 py-3 text-white action-btn bg-red rounded-xl hover:bg-red-700">
                            <ion-icon name="people-outline" class="mr-2 text-xl"></ion-icon>
                            Gérer utilisateurs
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-semibold text-gray-800">Activité récente</h3>
                    <select id="activityFilter" class="px-4 py-2 text-base bg-white border-2 border-gray-200 rounded-xl input-focus">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($category_list as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($recent_documents)): ?>
                    <p class="text-lg text-gray-500">Aucun document récent.</p>
                <?php else: ?>
                    <div id="activityList" class="space-y-6">
                        <?php foreach ($recent_documents as $doc): ?>
                            <div class="timeline-item">
                                <div class="p-6 transition-shadow bg-white shadow-lg rounded-xl hover:shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <ion-icon name="document-text-outline" class="mr-4 text-3xl text-blue"></ion-icon>
                                            <div>
                                                <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($doc['title']) ?></p>
                                                <p class="text-sm text-gray-600">Catégorie : <span class="text-white badge bg-blue"><?= htmlspecialchars($doc['category']) ?></span></p>
                                            </div>
                                        </div>
                                        <p class="text-sm font-medium text-gray-600"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const Swal = window.Swal;
            const logoutForm = document.getElementById('logout-form');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarLinks = document.querySelectorAll('.sidebar a[data-page]');
            const actionLinks = document.querySelectorAll('.action-btn[data-page]');

            if (logoutForm) {
                logoutForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'question',
                        title: 'Déconnexion',
                        text: 'Voulez-vous vraiment vous déconnecter ?',
                        showCancelButton: true,
                        confirmButtonColor: '#1E3A8A',
                        cancelButtonColor: '#DC2626',
                        confirmButtonText: 'Oui, déconnexion',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                });
            }

            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function () {
                    sidebar.classList.toggle('sidebar-open');
                    mainContent.classList.toggle('main-content-full');
                });
            }

           function loadPage(page, link) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner mx-auto';
    spinner.style.display = 'block';
    mainContent.innerHTML = '';
    mainContent.appendChild(spinner);

    fetch(page + '.php', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Page non trouvée');
            return response.text();
        })
        .then(data => {
            mainContent.innerHTML = data;
            const container = mainContent.querySelector('.dashboard-card, .container');
            if (container) {
                container.classList.add('fade-in');
            } else {
                console.warn('Aucun élément .dashboard-card ou .container trouvé dans la page chargée');
            }
            sidebarLinks.forEach(l => l.classList.remove('active'));
            if (link) link.classList.add('active');
            attachFormHandlers();
            attachFilterHandlers();
        })
        .catch(error => {
            mainContent.innerHTML = `<div class="container p-6 mx-auto"><p class="text-lg font-semibold text-red-600">Erreur : ${error.message}</p></div>`;
        });
}

            function attachFormHandlers() {
                const forms = mainContent.querySelectorAll('form[data-ajax]');
                forms.forEach(form => {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        const submitButton = form.querySelector('button[type="submit"]');
                        const originalText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<div class="inline-block spinner"></div>';

                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(response => response.json())
                            .then(data => {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Succès',
                                        text: data.message,
                                        confirmButtonColor: '#1E3A8A'
                                    }).then(() => {
                                        loadPage(form.dataset.page);
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erreur',
                                        text: data.message,
                                        confirmButtonColor: '#DC2626'
                                    });
                                }
                            })
                            .catch(error => {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erreur',
                                    text: 'Une erreur est survenue.',
                                    confirmButtonColor: '#DC2626'
                                });
                            });
                    });
                });
            }

            function attachFilterHandlers() {
                const filter = mainContent.querySelector('#activityFilter');
                const list = mainContent.querySelector('#activityList');
                if (filter && list) {
                    filter.addEventListener('change', function () {
                        const selectedCategory = this.value;
                        const items = list.querySelectorAll('.timeline-item');
                        items.forEach(item => {
                            const category = item.querySelector('.badge').textContent;
                            item.style.display = selectedCategory === '' || category === selectedCategory ? 'block' : 'none';
                        });
                    });
                }
            }

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadPage(page, this);
                });
            });

            actionLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadPage(page);
                });
            });

            attachFilterHandlers();
        });
    </script>
</body>
</html>