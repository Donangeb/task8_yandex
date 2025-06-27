<?php
/**
 * @var array $files
 * @var string $path
 * @var string|null $errorMessage
 * @var string|null $successMessage
 * @var string|null $editFileContent
 * @var string|null $editFilePath
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/user_yandex_disk/style/css/style.css">
    <title>Мои файлы на Яндекс.Диске</title>
</head>
<body>
    <div class="container">
        <h1>Мои файлы на Яндекс.Диске</h1>

        <?php if ($path !== 'disk:/'): ?>
            <?php
            $parentPath = dirname($path);
            if ($parentPath === 'disk:') {
                $parentPath = 'disk:/';
            }
            ?>
            <p>
                <a href="?path=<?= urlencode($parentPath) ?>" class="btn_exit" style="text-decoration: none">
                    <i class="icon icon-back"></i>
                </a>
            </p>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="upload-form" style="display: flex; justify-content: space-between; align-items: center;">
            <form id="uploadForm" method="post" enctype="multipart/form-data" 
                  style="flex-grow: 1; display: flex; align-items: center; gap: 10px;">
                <input type="file" name="file[]" id="fileInput" multiple required>
                <button type="submit" class="btn">Загрузить</button>
            </form>
            <div id="dropZone" style="margin-top: 10px; padding: 30px; border: 2px dashed #999; border-radius: 6px; text-align: center; color: #999;">
                Перетащите файлы сюда для загрузки
            </div>
            <div id="progress" style="margin-top: 10px;"></div>
            <form method="get" style="margin-left: 10px;">
                <button type="submit" name="logout" value="1" class="btn_exit">Выход</button>
            </form>
        </div>

        <button type="button" class="btn" onclick="openModal()" style="margin-bottom: 10px;">
            <i class="icon icon-folder-plus"></i>
        </button>

        <?php if (isset($files)): ?>
            <div class="file-list">
                <div class="file-item file-header">
                    <div class="file-name">Имя файла</div>
                    <div class="file-size">Размер</div>
                    <div class="file-date">Дата изменения</div>
                    <div class="file-edit"></div>
                    <div class="file-download"></div>
                    <div class="file-delete"></div>
                </div>

                <?php foreach ($files as $file): ?>
                    <div class="file-item">
                        <div class="file-name">
                            <?php if ($file->isDir()): ?>
                                <a href="?path=<?= urlencode($file->getPath()) ?>" style="text-decoration: none">
                                    <i class="icon icon-folder"></i>
                                    <?= htmlspecialchars($file->name) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($file->name) ?>
                            <?php endif; ?>
                        </div>

                        <div class="file-size">
                            <?php if ($file->isDir()): ?>
                                Папка
                            <?php else: ?>
                                <?= round($file->size / 1024 / 1024, 2) ?> MB
                            <?php endif; ?>
                        </div>

                        <div class="file-date">
                            <?= date('d.m.Y H:i', strtotime($file->modified)) ?>
                        </div>

                        <div class="file-edit">
                            <?php if (!$file->isDir() && \App\Service\YandexDiskManager::isTextFile($file)): ?>
                                <a href="?edit=<?= urlencode($file->getPath()) ?>" title="Редактировать">
                                    <i class="icon icon-edit"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="file-download">
                            <?php if (!$file->isDir()): ?>
                                <a href="?download=<?= urlencode($file->getPath()) ?>" title="Скачать">
                                    <i class="icon icon-download"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="file-delete">
                            <?php if (!$file->isDir()): ?>
                                <a href="?delete=<?= urlencode($file->getPath()) ?>" title="Удалить" onclick="return confirm('Удалить файл?');">
                                    <i class="icon icon-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно для создания папки -->
    <div id="folderModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Новая папка</h2>
            <form method="post">
                <input type="text" name="folder_name" placeholder="Имя папки" required>
                <input type="hidden" name="create_folder" value="1">
                <div style="margin-top: 10px;">
                    <button type="submit" class="btn">Создать</button>
                    <button type="button" class="btn" onclick="closeModal()" style="background-color: #999;">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editFilePath): ?>
        <!-- Модальное окно для редактирования файла -->
        <div id="editModal" class="modal" style="display: flex;">
            <div class="modal-content" style="width: 600px;">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Редактировать: <?= htmlspecialchars(basename($editFilePath)) ?></h2>
                <form method="post">
                    <textarea name="edit_content" rows="20" style="width: 100%;"><?= htmlspecialchars($editFileContent) ?></textarea>
                    <input type="hidden" name="edit_path" value="<?= htmlspecialchars($editFilePath) ?>">
                    <input type="hidden" name="save_edit" value="1">
                    <div style="margin-top: 10px;">
                        <button type="submit" class="btn">Сохранить</button>
                        <button type="button" class="btn" onclick="closeModal()" style="background-color: #999;">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        const progress = document.getElementById('progress');

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.style.borderColor = '#ff3333';
        });

        dropZone.addEventListener('dragleave', e => {
            dropZone.style.borderColor = '#999';
        });

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.borderColor = '#999';

            const files = e.dataTransfer.files;
            fileInput.files = files;
            uploadForm.submit();
        });

        uploadForm.addEventListener('submit', () => {
            progress.innerHTML = 'Загрузка...';
        });

        function openModal() {
            document.getElementById('folderModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('folderModal').style.display = 'none';
            // Если открыто модальное окно редактирования, закрываем его
            if (document.getElementById('editModal')) {
                window.location.href = window.location.pathname + window.location.search.replace(/&?edit=[^&]*/, '');
            }
        }
    </script>
</body>
</html> 