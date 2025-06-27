<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_token'])) {
    $_SESSION['access_token'] = $_POST['access_token'];
    header('Location: /user_yandex_disk/public/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Авторизация Яндекс</title>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.hash.substring(1));
            const token = params.get('access_token');

            if (token) {
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    credentials: 'same-origin',
                    body: 'access_token=' + encodeURIComponent(token)
                }).then(() => {
                    window.location.href = '/user_yandex_disk/public/index.php';
                });
            } else {
                document.body.innerHTML = '<p>Токен не найден</p>';
            }
        });
    </script>
</head>
<body>
    <p>Обработка токена...</p>
</body>
</html>
