<?php
// Borra TODOS los mensajes del chat y vuelve a index.php.
// Es una página de navegación normal: el Service Worker no la intercepta.

session_start();
require __DIR__ . '/config.php';

// Solo usuarios logueados pueden vaciar el chat.
if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = db();
    $pdo->exec('DELETE FROM messages');
} catch (Throwable $e) {
    // Si algo falla, mostramos el error en texto plano.
    http_response_code(500);
    echo 'Error al borrar: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Volver al chat.
header('Location: index.php');
exit;
