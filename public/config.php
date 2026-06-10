<?php
/**
 * Configuración de la base de datos.
 *
 * PRODUCCIÓN (InfinityFree u otro hosting MySQL):
 *   - DB_DRIVER = 'mysql'
 *   - Rellena los datos de MySQL con los que te da el panel de InfinityFree.
 *
 * LOCAL (Local by Flywheel):
 *   - DB_DRIVER = 'sqlite'  → cero configuración, archivo local.
 *   - O DB_DRIVER = 'mysql' con el puerto que muestra Local en la pestaña Database.
 */

const DB_DRIVER = 'mysql'; // 'sqlite' | 'mysql'

// --- Config SQLite (solo para desarrollo local) ---
const SQLITE_PATH = __DIR__ . '/../chat-data/chat.sqlite';

// --- Config MySQL ---
// InfinityFree: rellena estos datos desde el panel de control (Databases → MySQL).
// Local: host=127.0.0.1, db='local', user='root', pass='root', puerto=el que muestra Local.
const MYSQL_HOST = 'sql209.infinityfree.com'; // <-- cámbialo por tu host de InfinityFree
const MYSQL_PORT = 3306;
const MYSQL_DB   = 'if0_42150598_db_tauri';       // <-- tu nombre de base de datos
const MYSQL_USER = 'if0_42150598';            // <-- tu usuario MySQL
const MYSQL_PASS = 'FqCg6cF2n3M0tzH';           // <-- tu contraseña MySQL

/**
 * Devuelve una conexión PDO ya inicializada (crea las tablas si no existen).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            MYSQL_HOST, MYSQL_PORT, MYSQL_DB
        );
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        init_schema_mysql($pdo);
    } else {
        $dir = dirname(SQLITE_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . SQLITE_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        init_schema_sqlite($pdo);
    }

    return $pdo;
}

function init_schema_sqlite(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL UNIQUE,
            password   TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            body       TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
}

function init_schema_mysql(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            username   VARCHAR(50) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            body       TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_messages_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}
