<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT l.id, l.action, l.details, l.created_at, u.email FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT id, email FROM users");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur : " . $e->getMessage());
    $logs = [];
    $users = [];
}
?>

<div class="mx-auto dashboard-card fade-in">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Journal des actions</h2>
        <div class="flex gap-4">
            <input type="date" id="dateFilter" class="px-4 py-3 border-2 border-gray-200 rounded-xl input-focus">
            <select id="userFilter" class="px-4 py-3 bg-white border-2 border-gray-200 rounded-xl input-focus">
                <option value="">Tous les utilisateurs</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div>
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Historique des actions</h3>
        <?php if (empty($logs)): ?>
            <p class="text-lg text-gray-500">Aucun log trouvé.</p>
        <?php else: ?>
            <div id="logList" class="space-y-6">
                <?php foreach ($logs as $log): ?>
                    <div class="p-6 transition-shadow bg-white shadow-lg rounded-xl hover:shadow-xl log-item" data-date="<?= date('Y-m-d', strtotime($log['created_at'])) ?>" data-user="<?= htmlspecialchars($log['email']) ?>">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <ion-icon name="journal-outline" class="mr-4 text-3xl text-blue"></ion-icon>
                                <div>
                                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($log['action']) ?></p>
                                    <p class="text-sm text-gray-600">Utilisateur : <span class="text-white badge bg-blue"><?= htmlspecialchars($log['email']) ?></span></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($log['details'] ?: 'Sans détails') ?></p>
                                </div>
                            </div>
                            <p class="text-sm font-medium text-gray-600"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateFilter = document.getElementById('dateFilter');
        const userFilter = document.getElementById('userFilter');
        const logList = document.getElementById('logList');

        function filterLogs() {
            const selectedDate = dateFilter.value;
            const selectedUser = userFilter.value;
            const items = logList.querySelectorAll('.log-item');
            items.forEach(item => {
                const date = item.dataset.date;
                const user = item.dataset.user;
                const showDate = !selectedDate || date === selectedDate;
                const showUser = !selectedUser || user === selectedUser;
                item.style.display = showDate && showUser ? 'block' : 'none';
            });
        }

        dateFilter.addEventListener('change', filterLogs);
        userFilter.addEventListener('change', filterLogs);
    });
</script>