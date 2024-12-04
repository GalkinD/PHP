<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $captcha_token = $_POST['smartcaptcha-token'];

    // Проверка капчи
    $secret_key = 'YOUR_SECRET_KEY'; // Замените на ваш реальный секретный ключ
    $verify_url = "https://captcha-api.yandex.ru/captcha/verify";

    $data = [
        'secret' => $secret_key,
        'response' => $captcha_token
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($verify_url, false, $context);

    if ($result !== false) {
        $result = json_decode($result, true);
        if ($result['success'] == 'true') {
            // Проверка капчи прошла успешно
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: profile.php");
                    exit();
                } else {
                    echo "Неверный пароль.";
                }
            } else {
                echo "Пользователь не найден.";
            }

            $stmt->close();
        } else {
            echo "Ошибка капчи.";
        }
    } else {
        echo "Ошибка проверки капчи.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Авторизация</title>
    <script src="https://captcha-api.yandex.ru/captcha.js?render=explicit" async></script>
</head>
<body>
    <h2>Авторизация</h2>
    <form method="post" action="">
        <label for="login">Email или Телефон:</label><br>
        <input type="text" id="login" name="login" required><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password" required><br>
        <div id="smartcaptcha-container"></div>
        <input type="submit" value="Войти">
    </form>
    <script>
        window.onload = function() {
            var widget = new window.Ya.Captcha('smartcaptcha-container', {
                sitekey: 'YOUR_SITE_KEY', // Замените на ваш реальный ключ сайта
                callback: function(token) {
                    document.getElementById('smartcaptcha-token').value = token;
                }
            });
        };
    </script>
    <input type="hidden" id="smartcaptcha-token" name="smartcaptcha-token">
</body>
</html>
