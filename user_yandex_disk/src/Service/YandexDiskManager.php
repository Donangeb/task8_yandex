<?php

namespace App\Service;

use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Client\Exception\UnauthorizedException;
use Exception;

class YandexDiskManager
{
    private Disk $disk;
    private array $config;
    private string $uploadDir;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->uploadDir = $this->config['upload_dir'];
        
        try {
            $this->disk = new Disk($_SESSION['access_token']);
        } catch (Exception $e) {
            $this->handleError('Ошибка инициализации: ' . $e->getMessage());
        }
    }

    public function process(): void
    {
        try {
            $this->handleLogout();
            $this->handleFileUpload();
            $this->handleFileDownload();
            $this->handleFileDelete();
            $this->handleFolderCreation();
            $this->handleFileEdit();
            $this->displayFiles();
        } catch (Exception $e) {
            $this->handleError('Ошибка: ' . $e->getMessage());
        }
    }

    private function handleLogout(): void
    {
        if (isset($_GET['logout'])) {
            session_unset();
            session_destroy();
            header("Location: {$this->config['base_url']}");
            exit;
        }
    }

    private function handleFileUpload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $uploadedFiles = $_FILES['file'];
            $currentPath = isset($_GET['path']) ? $_GET['path'] : 'disk:/';
            $currentPath = rtrim($currentPath, '/');

            $success = 0;
            $errors = 0;

            for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
                if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                    $tempFilePath = $this->uploadDir . basename($uploadedFiles['name'][$i]);

                    if (!move_uploaded_file($uploadedFiles['tmp_name'][$i], $tempFilePath)) {
                        $errors++;
                        continue;
                    }

                    try {
                        $remotePath = $currentPath . '/' . basename($uploadedFiles['name'][$i]);
                        $resource = $this->disk->getResource($remotePath);
                        $resource->upload($tempFilePath, true);
                        unlink($tempFilePath);
                        $success++;
                    } catch (Exception $e) {
                        $errors++;
                    }
                } else {
                    $errors++;
                }
            }

            if ($success > 0) $_SESSION['successMessage'] = "Загружено файлов: $success";
            if ($errors > 0) $_SESSION['errorMessage'] = "Ошибок загрузки: $errors";

            $this->redirect();
        }
    }

    private function handleFileDownload(): void
    {
        if (isset($_GET['download'])) {
            try {
                $filePath = $_GET['download'];
                $resource = $this->disk->getResource($filePath);

                $temp = tempnam(sys_get_temp_dir(), 'yadisk_');
                $resource->download($temp, true);

                // Отправляем файл пользователю
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Length: ' . filesize($temp));

                readfile($temp);
                unlink($temp);
                exit;
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = 'Ошибка при скачивании: ' . $e->getMessage();
                $this->redirect();
            }
        }
    }

    private function handleFileDelete(): void
    {
        if (isset($_GET['delete'])) {
            try {
                $filePath = $_GET['delete'];
                $resource = $this->disk->getResource($filePath);
                $resource->delete();
                $_SESSION['successMessage'] = 'Файл удалён.';
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = 'Ошибка при удалении: ' . $e->getMessage();
            }
            $this->redirect();
        }
    }

    private function handleFolderCreation(): void
    {
        if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
            try {
                $folderName = trim($_POST['folder_name']);
                $currentPath = isset($_GET['path']) ? $_GET['path'] : 'disk:/';
                $currentPath = rtrim($currentPath, '/');
                $newFolderPath = $currentPath . '/' . $folderName;

                $resource = $this->disk->getResource($newFolderPath);
                $resource->create();
                $_SESSION['successMessage'] = 'Папка создана.';
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = 'Ошибка при создании папки: ' . $e->getMessage();
            }
            $this->redirect();
        }
    }

    private function handleFileEdit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit']) && isset($_POST['edit_path'])) {
            try {
                $savePath = $_POST['edit_path'];
                $content = $_POST['edit_content'];

                $temp = tempnam(sys_get_temp_dir(), 'edit_save_');
                file_put_contents($temp, $content);

                $resource = $this->disk->getResource($savePath);
                $resource->upload($temp, true);
                unlink($temp);

                $_SESSION['successMessage'] = 'Файл успешно сохранён.';
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = 'Ошибка при сохранении файла: ' . $e->getMessage();
            }
            $this->redirect();
        }
    }

    private function displayFiles(): void
    {
        try {
            $path = isset($_GET['path']) ? $_GET['path'] : 'disk:/';
            $resource = $this->disk->getResource($path);
            $files = $resource->items;

            $editFileContent = '';
            $editFilePath = null;

            if (isset($_GET['edit'])) {
                try {
                    $editFilePath = $_GET['edit'];
                    $resource = $this->disk->getResource($editFilePath);

                    $temp = tempnam(sys_get_temp_dir(), 'edit_');
                    $resource->download($temp, true);
                    $editFileContent = file_get_contents($temp);
                    unlink($temp);
                } catch (Exception $e) {
                    $_SESSION['errorMessage'] = 'Ошибка при открытии файла: ' . $e->getMessage();
                }
            }

            // Отображаем файлы через View
            $this->renderFilesView($files, $path, $editFileContent, $editFilePath);

        } catch (UnauthorizedException $e) {
            $this->handleError('Ошибка авторизации: неверный OAuth-токен');
        } catch (Exception $e) {
            $this->handleError('Ошибка: ' . $e->getMessage());
        }
    }

    private function renderFilesView(iterable $files, string $path, string $editFileContent = '', ?string $editFilePath = null): void
    {
        $errorMessage = $_SESSION['errorMessage'] ?? null;
        $successMessage = $_SESSION['successMessage'] ?? null;
        
        // Очищаем сообщения из сессии
        unset($_SESSION['errorMessage'], $_SESSION['successMessage']);

        // Подключаем View
        require dirname(__DIR__) . '/View/files.php';
    }

    private function redirect(): void
    {
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        $redirect .= isset($_GET['path']) ? '?path=' . urlencode($_GET['path']) : '';
        header("Location: $redirect");
        exit;
    }

    private function handleError(string $message): void
    {
        $_SESSION['errorMessage'] = $message;
        header("Location: {$this->config['base_url']}/user_yandex_disk/public/index.php");
        exit;
    }

    public static function isTextFile($file): bool
    {
        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        return in_array($ext, ['txt', 'html', 'csv', 'xml', 'json', 'php', 'md', 'js', 'css', 'cpp', 'cln', 'cs']);
    }
} 