<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Przechwyć komunikat z auth.php jeśli wrócono z błędem
$login_error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);

// Wczytaj listę użytkowników i ich hasła oraz SID
$users = [
    "admin" => ["password" => "admin1", "sid" => 52702],
    "admin2" => ["password" => "admin2", "sid" => 52702],	
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Sprawdź login i hasło
    if (!isset($users[$username]) || $users[$username]['password'] !== $password) {
        $login_error = "Nieprawidłowy login lub hasło.";
    } else {
        // Pobierz dane z API
        $json = @file_get_contents("https://info.fivelife.pl/gracze");
        if ($json !== false) {
            file_put_contents(__DIR__ . '/sids.json', $json);
        }

        $data = json_decode($json, true);
        $lista = $data['lista'] ?? [];

        $expected_sid = $users[$username]['sid'];
        $found = false;

        foreach ($lista as $entry) {
            if (isset($entry['sid']) && $entry['sid'] == $expected_sid) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $sessionsFile = __DIR__ . '/secure/sessions.json';
            $sessions = [];

            if (file_exists($sessionsFile)) {
                $sessions = json_decode(file_get_contents($sessionsFile), true);
                if (!is_array($sessions)) $sessions = [];
            }

            // Timeout sesji: 60 minut (3600 sekund)
            $sessionTimeout = 60 * 60;
            $now = time();

            if (isset($sessions[$username])) {
                $existing_session = $sessions[$username];
                $existing_session_id = $existing_session['session_id'] ?? '';
                $last_active = $existing_session['last_active'] ?? 0;

                if ($existing_session_id && $existing_session_id !== session_id()) {
                    if (($now - $last_active) < $sessionTimeout) {
                        // Sesja aktywna - blokuj logowanie na innym urządzeniu
                        $login_error = "Jesteś już zalogowany na innym urządzeniu.";
                        goto render_form;
                    } else {
                        // Sesja wygasła - usuń ją
                        unset($sessions[$username]);
                    }
                }
            }

            // Zapisz nową sesję
            $sessions[$username] = [
                'session_id' => session_id(),
                'sid' => $expected_sid,
                'last_active' => $now
            ];

            file_put_contents($sessionsFile, json_encode($sessions));
            file_put_contents(__DIR__ . "/secure/logi.log", date('Y-m-d H:i:s') . " | USER: $username\n", FILE_APPEND);

            $_SESSION['user'] = $username;
            $_SESSION['last_active'] = $now;

            header("Location: index.php");
            exit;
        } else {
            $login_error = "Zaloguj się na FiveLife.pl";
        }
    }
}

render_form:
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <title>Logowanie - Ukryty diler by dzwq</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
  body, html {
    margin: 0;
    padding: 0;
    background-color: #2c343e;
    overflow: hidden;
    font-family: Arial, sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
  }

  body::before {
    content: "";
    position: fixed;
    top: 50%;
    left: 50%;
    width: 1500px;
    height: 1500px;
    max-width: 95vw;
    max-height: 95vh;
    transform: translate(-50%, -50%);
    background-image: url('img/mapa.jpg');
    background-size: 100% auto;
    background-repeat: no-repeat;
    background-position: center center;
    filter: grayscale(70%) brightness(80%) contrast(90%);
    opacity: 0.5;
    z-index: 0;
    pointer-events: none;
  }

  #login-form {
    position: relative;
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgb(0 0 0 / 0.15);
    width: 320px;
    z-index: 1;
  }

  #login-form h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-weight: 700;
    text-align: center;
    color: #333;
  }

  #login-form input[type="text"],
  #login-form input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
  }

  #login-form input[type="submit"] {
    width: 100%;
    padding: 12px;
    background-color: #4a90e2;
    border: none;
    color: white;
    font-weight: 600;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  #login-form input[type="submit"]:hover {
    background-color: #357ab8;
  }

  #error-message {
    color: #d9534f;
    text-align: center;
    margin-bottom: 10px;
    font-weight: 600;
  }
</style>

</head>
<body>
  <form id="login-form" method="post" autocomplete="off" novalidate>
    <h2>Zaloguj się</h2>

    <?php if ($login_error): ?>
      <div id="error-message"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <input type="text" name="username" placeholder="Login" required autofocus />
    <input type="password" name="password" placeholder="Hasło" required />
    <input type="submit" value="Zaloguj" />
  </form>
</body>
</html>
