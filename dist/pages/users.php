<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = password_hash(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING), PASSWORD_DEFAULT);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Ajout utilisateur', "Utilisateur: $email"]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté.']);
    } catch (PDOException $e) {
        error_log("Erreur ajout : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_SANITIZE_NUMBER_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $edit_id]);
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Modification utilisateur', "Utilisateur: $email"]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur modifié.']);
    } catch (PDOException $e) {
        error_log("Erreur modification : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification.']);
    }
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../output.css">
</head>
<body class="font-sans bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800">AGC Archiv</h2>
            </div>
            <nav class="mt-6">
                <a href="#" class="flex items-center px-6 py-2 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a href="#" class="flex items-center px-6 py-2 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-users"></i> Gestion des utilisateurs
                </a>
                <a href="#" class="flex items-center px-6 py-2 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-archive"></i> Archives
                </a>
                <a href="#" class="flex items-center px-6 py-2 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-search"></i> Recherche
                </a>
                <a href="#" class="flex items-center px-6 py-2 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-clipboard-list"></i> Journal
                </a>
                <a href="#" class="flex items-center px-6 py-2 mt-4 text-gray-600 hover:bg-gray-200">
                    <i class="mr-2 fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white shadow-md">
                <div class="flex items-center space-x-4">
                    <input type="text" id="userSearch" placeholder="Rechercher un utilisateur..." class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Connecté : <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-300 rounded-full">
                        <?php echo strtoupper(substr($_SESSION['email'] ?? '', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 p-6 overflow-x-auto">
                <div class="p-6 bg-white rounded-lg shadow-md">
                    <h1 class="mb-4 text-2xl font-bold text-gray-800">Gestion des utilisateurs</h1>
                    <div class="mb-6">
                        <button id="addUserBtn" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="mr-2 fas fa-plus"></i> Ajouter un utilisateur
                        </button>
                    </div>

                    <!-- Tableau des utilisateurs -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr class="text-left text-gray-600 bg-gray-100">
                                    <th class="px-4 py-2">Nom</th>
                                    <th class="px-4 py-2">Email</th>
                                    <th class="px-4 py-2">Rôle</th>
                                    <th class="px-4 py-2">Date d'ajout</th>
                                    <th class="px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="5" class="py-4 text-center text-gray-500">Aucun utilisateur trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 rounded-full text-sm <?php echo $user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td class="px-4 py-2">
                                                <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'] ?? ''); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')" class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('userSearch');
            const tableRows = document.querySelectorAll('tbody tr');

            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase();
                tableRows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
                });
            });

            document.getElementById('addUserBtn').addEventListener('click', function () {
                Swal.fire({
                    title: 'Ajouter un utilisateur',
                    html: `
                        <input id="swal-name" type="text" class="swal2-input" placeholder="Nom" required>
                        <input id="swal-email" type="email" class="swal2-input" placeholder="Email" required>
                        <input id="swal-password" type="password" class="swal2-input" placeholder="Mot de passe" required>
                        <select id="swal-role" class="swal2-input">
                            <option value="user">Utilisateur</option>
                            <option value="admin">Admin</option>
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ajouter',
                    preConfirm: () => {
                        return {
                            name: document.getElementById('swal-name').value,
                            email: document.getElementById('swal-email').value,
                            password: document.getElementById('swal-password').value,
                            role: document.getElementById('swal-role').value
                        };
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('add_user', true);
                        formData.append('name', result.value.name);
                        formData.append('email', result.value.email);
                        formData.append('password', result.value.password);
                        formData.append('role', result.value.role);
                        fetch('users.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Succès!', data.message, 'success').then(() => {
                                        fetch('users.php').then(r => r.text()).then(html => {
                                            document.getElementById('mainContent').innerHTML = html;
                                        });
                                    });
                                } else {
                                    Swal.fire('Erreur!', data.message, 'error');
                                }
                            });
                    }
                });
            });

            window.editUser = function (id, name, email, role) {
                Swal.fire({
                    title: 'Modifier l\'utilisateur',
                    html: `
                        <input id="edit-name" type="text" value="${name}" class="swal2-input" placeholder="Nom" required>
                        <input id="edit-email" type="email" value="${email}" class="swal2-input" placeholder="Email" required>
                        <select id="edit-role" class="swal2-input">
                            <option value="user" ${role === 'user' ? 'selected' : ''}>Utilisateur</option>
                            <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Enregistrer',
                    preConfirm: () => {
                        return {
                            name: document.getElementById('edit-name').value,
                            email: document.getElementById('edit-email').value,
                            role: document.getElementById('edit-role').value
                        };
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('edit_id', id);
                        formData.append('name', result.value.name);
                        formData.append('email', result.value.email);
                        formData.append('role', result.value.role);
                        fetch('users.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Succès!', data.message, 'success').then(() => {
                                        fetch('users.php').then(r => r.text()).then(html => {
                                            document.getElementById('mainContent').innerHTML = html;
                                        });
                                    });
                                } else {
                                    Swal.fire('Erreur!', data.message, 'error');
                                }
                            });
                    }
                });
            };
        });
    </script>
</body>
</html>