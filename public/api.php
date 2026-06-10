<?php
/**
 * API JSON del chat.
 * Endpoints (GET/POST con ?action=...):
 *   register  POST  username, password
 *   login     POST  username, password
 *   logout    POST
 *   me        GET
 *   messages  GET   since (id opcional)
 *   send      POST  body
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Forzar HTTPS igual que en index.php
$proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['HTTPS'] ?? '');
if ($proto !== 'https' && $proto !== 'on') {
    json_out(['error' => 'Se requiere HTTPS'], 403);
}

require __DIR__ . '/config.php';

// Rate limiting por sesión: máximo $max acciones de tipo $key en $window segundos.
function rate_limit(string $key, int $max, int $window): void
{
    $now = time();
    $bucket = '_rl_' . $key;
    if (!isset($_SESSION[$bucket])) {
        $_SESSION[$bucket] = ['count' => 0, 'reset' => $now + $window];
    }
    if ($now > $_SESSION[$bucket]['reset']) {
        $_SESSION[$bucket] = ['count' => 0, 'reset' => $now + $window];
    }
    $_SESSION[$bucket]['count']++;
    if ($_SESSION[$bucket]['count'] > $max) {
        json_out(['error' => 'Demasiadas peticiones, espera un momento'], 429);
    }
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function current_user_id(): ?int
{
    return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
}

function require_auth(): int
{
    $uid = current_user_id();
    if ($uid === null) {
        json_out(['error' => 'No autenticado'], 401);
    }
    return $uid;
}

$action = $_GET['action'] ?? '';

// El manifest no necesita DB ni sesión — se sirve antes del try/catch.
if ($action === 'manifest') {
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode([
        'name'             => 'Chat PWA',
        'short_name'       => 'Chat',
        'description'      => 'Chat de mensajes en tiempo casi real con PHP, SQL, JS y CSS.',
        'start_url'        => '/index.php',
        'scope'            => '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'background_color' => '#0f172a',
        'theme_color'      => '#4f46e5',
        'icons'            => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();

    switch ($action) {

        case 'register': {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
                json_out(['error' => 'El usuario debe tener entre 3 y 30 caracteres'], 422);
            }
            if ($password === '') {
                json_out(['error' => 'La contraseña no puede estar vacía'], 422);
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                json_out(['error' => 'Ese usuario ya existe'], 409);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            $_SESSION['uid'] = (int) $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            json_out(['ok' => true, 'user' => ['id' => $_SESSION['uid'], 'username' => $username]]);
        }

        case 'login': {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                json_out(['error' => 'Usuario o contraseña incorrectos'], 401);
            }

            $_SESSION['uid'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            json_out(['ok' => true, 'user' => ['id' => (int) $user['id'], 'username' => $user['username']]]);
        }

        case 'logout': {
            $_SESSION = [];
            session_destroy();
            json_out(['ok' => true]);
        }

        case 'me': {
            $uid = current_user_id();
            if ($uid === null) {
                json_out(['user' => null]);
            }
            json_out(['user' => ['id' => $uid, 'username' => $_SESSION['username'] ?? '']]);
        }

        case 'messages': {
            $uid = require_auth();
            rate_limit('messages', 20, 10); // máx 20 polls cada 10s por sesión
            $since = (int) ($_GET['since'] ?? 0);

            $stmt = $pdo->prepare("
                SELECT m.id, m.body, m.created_at, m.user_id, u.username
                FROM messages m
                JOIN users u ON u.id = m.user_id
                WHERE m.id > ?
                ORDER BY m.id ASC
                LIMIT 200
            ");
            $stmt->execute([$since]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$r) {
                $r['id']      = (int) $r['id'];
                $r['user_id'] = (int) $r['user_id'];
                $r['mine']    = ((int) $r['user_id'] === $uid);
            }
            json_out(['messages' => $rows]);
        }

        case 'send': {
            $uid = require_auth();
            rate_limit('send', 10, 10); // máx 10 mensajes cada 10s por sesión
            $body = trim((string) ($_POST['body'] ?? ''));

            if ($body === '') {
                json_out(['error' => 'Mensaje vacío'], 422);
            }
            if (mb_strlen($body) > 2000) {
                json_out(['error' => 'Mensaje demasiado largo'], 422);
            }

            $stmt = $pdo->prepare('INSERT INTO messages (user_id, body) VALUES (?, ?)');
            $stmt->execute([$uid, $body]);
            json_out(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
        }

        case 'clear': {
            require_auth();
            // Borra todos los mensajes del chat (común a todos los usuarios).
            $pdo->exec('DELETE FROM messages');
            json_out(['ok' => true]);
        }

        default:
            json_out(['error' => 'Acción desconocida'], 404);
    }
} catch (Throwable $e) {
    json_out(['error' => 'Error del servidor', 'detail' => $e->getMessage()], 500);
}
