<?php
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
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
