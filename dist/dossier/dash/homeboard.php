   
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-800">Toutes les archives</h1>
                <div>
                    <button class="px-4 py-2 mr-2 text-white rounded btn-bleu">
                        <ion-icon name="cloud-upload-outline" class="mr-1"></ion-icon> Ajouter une archive
                    </button>
                    <button class="px-4 py-2 text-gray-700 bg-gray-200 rounded">
                        <ion-icon name="add-outline" class="mr-1"></ion-icon> Créer
                    </button>
                </div>
            </div>
            <p class="mb-4 text-gray-600">Toutes vos archives sont affichées ici.</p>

            <!-- Recently Modified -->
            <div class="mb-6">
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Récemment modifié</h2>
                <div class="flex space-x-4 recent-files">
                    <?php foreach ($recent_files as $file): ?>
                        <div class="flex items-center p-2 bg-white rounded shadow">
                            <?php
                            $ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                            $icon = 'document-outline';
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'image-outline';
                            elseif (in_array($ext, ['mp4', 'avi', 'mov'])) $icon = 'videocam-outline';
                            elseif (in_array($ext, ['mp3', 'wav'])) $icon = 'musical-notes-outline';
                            elseif ($ext === 'zip') $icon = 'archive-outline';
                            ?>
                            <ion-icon name="<?= $icon ?>" class="mr-2 text-gray-600"></ion-icon>
                            <?= htmlspecialchars($file['file_name']) ?> (<?= round($file['file_size'] / (1024 * 1024), 1) ?>MB)
                        </div>
                    <?php endforeach; ?>
                    <a href="archives.php" class="text-blue-600">Voir tout →</a>
                </div>
            </div>

            <!-- File List -->
            <div class="p-4 rounded shadow content-card">
                <div class="flex justify-between mb-4">
                    <div class="flex space-x-2">
                        <button class="text-blue-600">
                            <ion-icon name="filter-outline" class="mr-1"></ion-icon> Filtrer
                        </button>
                        <button class="text-gray-600">
                            <ion-icon name="list-outline" class="mr-1"></ion-icon> Liste
                        </button>
                    </div>
                    <div class="flex w-64 h-6 bg-gray-200">
                        <div class="bg-blue-500" style="width: <?= ($file_counts['Documents'] / array_sum($file_counts)) * 100 ?>%"></div>
                        <div class="bg-purple-500" style="width: <?= ($file_counts['Image'] / array_sum($file_counts)) * 100 ?>%"></div>
                        <div class="bg-red-500" style="width: <?= ($file_counts['Video'] / array_sum($file_counts)) * 100 ?>%"></div>
                        <div class="bg-yellow-500" style="width: <?= ($file_counts['Audio'] / array_sum($file_counts)) * 100 ?>%"></div>
                        <div class="bg-green-500" style="width: <?= ($file_counts['ZIP'] / array_sum($file_counts)) * 100 ?>%"></div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="p-2">Nom</th>
                                <th class="p-2">Propriétaire</th>
                                <th class="p-2">Taille</th>
                                <th class="p-2">Date de modification</th>
                            </tr>
                        </thead>
                        <tbody id="fileTable">
                            <?php foreach ($all_files as $file): ?>
                                <tr class="border-t">
                                    <td class="p-2">
                                        <?php
                                        $ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                                        $icon = 'document-outline';
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'image-outline';
                                        elseif (in_array($ext, ['mp4', 'avi', 'mov'])) $icon = 'videocam-outline';
                                        elseif (in_array($ext, ['mp3', 'wav'])) $icon = 'musical-notes-outline';
                                        elseif ($ext === 'zip') $icon = 'archive-outline';
                                        ?>
                                        <ion-icon name="<?= $icon ?>" class="mr-2 text-gray-600"></ion-icon>
                                        <?= htmlspecialchars($file['file_name']) ?>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($user_name) ?></td>
                                    <td class="p-2"><?= round($file['file_size'] / (1024 * 1024), 1) ?>MB</td>
                                    <td class="p-2"><?= $file['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Storage Usage -->
            <div class="flex justify-between p-4 mt-6 rounded shadow content-card">
                <div class="flex flex-wrap text-sm storage-stats">
                    <div class="flex-1 min-w-[150px]"><span class="inline-block w-4 h-4 mr-2 bg-blue-500"></span> Documents (<?= $file_counts['Documents'] ?>)</div>
                    <div class="flex-1 min-w-[150px]"><span class="inline-block w-4 h-4 mr-2 bg-purple-500"></span> Images (<?= $file_counts['Image'] ?>)</div>
                    <div class="flex-1 min-w-[150px]"><span class="inline-block w-4 h-4 mr-2 bg-red-500"></span> Vidéos (<?= $file_counts['Video'] ?>)</div>
                    <div class="flex-1 min-w-[150px]"><span class="inline-block w-4 h-4 mr-2 bg-yellow-500"></span> Audios (<?= $file_counts['Audio'] ?>)</div>
                    <div class="flex-1 min-w-[150px]"><span class="inline-block w-4 h-4 mr-2 bg-green-500"></span> ZIP (<?= $file_counts['ZIP'] ?>)</div>
                </div>
                <div class="text-center">
                    <div class="relative w-32 h-32 mx-auto">
                        <canvas id="storageChart"></canvas>
                        <div class="absolute text-center transform -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                            <div class="text-xl font-bold text-blue-600"><?= $storage_used ?>MB</div>
                            <div class="text-sm text-gray-500">de <?= $storage_total / 1024 ?>GB</div>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Besoin de plus d'espace ?</p>
                    <button class="px-4 py-2 mt-2 text-white rounded btn-violet">Passer à Pro</button>
                </div>
            </div>
        </div>