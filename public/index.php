<?php
// Forzar HTTPS: el Service Worker y la instalación PWA solo funcionan en HTTPS.
// Local reenvía el protocolo original en X-Forwarded-Proto a través de su router.
$proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['HTTPS'] ?? '');
if ($proto !== 'https' && $proto !== 'on') {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 302);
    exit;
}

session_start();
$loggedIn = isset($_SESSION['uid']);
$username  = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title>Chat PWA</title>
    <link rel="manifest" href="api.php?action=manifest" crossorigin="use-credentials">
    <link rel="icon" href="icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div id="app" data-logged="<?= $loggedIn ? '1' : '0' ?>" data-username="<?= htmlspecialchars($username, ENT_QUOTES) ?>">

    <!-- Pantalla de autenticación -->
    <section id="auth" class="screen<?= $loggedIn ? ' hidden' : '' ?>">
        <div class="auth-card">
            <div class="brand">
                <img src="icons/icon.svg" alt="" width="48" height="48">
                <h1>Chat PWA</h1>
                <p class="muted">Inicia sesión o crea una cuenta</p>
            </div>

            <div class="tabs">
                <button class="tab active" data-tab="login">Entrar</button>
                <button class="tab" data-tab="register">Registrarse</button>
            </div>

            <form id="auth-form" autocomplete="off">
                <input type="text" name="username" placeholder="Usuario" required
                       minlength="3" maxlength="30" autocomplete="username">
                <input type="password" name="password" placeholder="Contraseña" required
                       autocomplete="current-password">
                <p id="auth-error" class="error" role="alert"></p>
                <button type="submit" class="btn-primary" id="auth-submit">Entrar</button>
            </form>
        </div>
    </section>

    <!-- Pantalla del chat -->
    <section id="chat" class="screen<?= $loggedIn ? '' : ' hidden' ?>">
        <header class="chat-header">
            <div class="title">
                <img src="icons/icon.svg" alt="" width="28" height="28">
                <span>Chat general</span>
            </div>
            <div class="user-box">
                <span id="who" class="muted"></span>
                <a href="clear.php" id="clear-chat" class="btn-ghost" title="Borrar todos los mensajes">Vaciar</a>
                <button id="logout" class="btn-ghost" title="Salir">Salir</button>
            </div>
        </header>

        <main id="messages" class="messages" aria-live="polite"></main>

        <form id="send-form" class="composer">
            <input type="text" id="msg-input" placeholder="Escribe un mensaje…"
                   maxlength="2000" autocomplete="off">
            <button type="submit" class="btn-send" aria-label="Enviar">➤</button>
        </form>
    </section>

</div>

<script src="assets/app.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.php', { scope: '/' }).catch(console.error);
        });
    }
</script>
</body>
</html>
