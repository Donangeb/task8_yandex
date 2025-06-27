<?php
$config = require 'user_yandex_disk/config/config.php';

?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Авторизация через Яндекс</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f5f5f6;
        }
        .auth-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .error-message {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .yandex-auth-btn {
            background: #FFDB4D;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .yandex-auth-btn:hover {
            background: #FFCC00;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Вход через Яндекс</h1>
        <button id="yandexAuthBtn" class="yandex-auth-btn">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 20C15.5228 20 20 15.5228 20 10C20 4.47715 15.5228 0 10 0C4.47715 0 0 4.47715 0 10C0 15.5228 4.47715 20 10 20Z" fill="#FC3F1D"/>
                <path d="M8.57143 14.2857H11.4286V5.71429H8.57143V14.2857Z" fill="white"/>
                <path d="M8.57143 5.71429H11.4286V9.04762L14.2857 5.71429H18.0952L13.8095 10L18.0952 14.2857H14.2857L11.4286 10.9524V14.2857H8.57143V5.71429Z" fill="white"/>
            </svg>
            Войти с Яндекс ID
        </button>
        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>

    <script>
        document.getElementById('yandexAuthBtn').addEventListener('click', function() {
            const clientId = 'ca7f36fb769b4cf1849d5a82c4875374';
            const redirectUri = encodeURIComponent('<?=$config['yandex']['redirect_uri']?> http://localhost/user_yandex_disk/handle_token.php');
            const authUrl = `https://oauth.yandex.ru/authorize?response_type=token&client_id=${clientId}&redirect_uri=${redirectUri}`;
            
            window.location.href = authUrl;
        });

        // Проверка ошибок в URL
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        
        if (error) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = 'Ошибка авторизации: ' + error;
            errorMessage.style.display = 'block';
        }
    </script>
</body>
</html>