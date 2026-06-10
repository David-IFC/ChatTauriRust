<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PWA Check</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e5e7eb; padding: 24px; }
  .ok  { color: #4ade80; } .err { color: #f87171; } .warn { color: #facc15; }
  pre  { background: #1e293b; padding: 12px; border-radius: 8px; white-space: pre-wrap; }
</style>
</head>
<body>
<h2>PWA Diagnostic</h2>
<pre id="out">Ejecutando checks...</pre>
<script>
const log = (cls, msg) => {
    document.getElementById('out').innerHTML +=
        `<span class="${cls}">${msg}</span>\n`;
};

(async () => {
    document.getElementById('out').textContent = '';

    // 1. HTTPS
    log(location.protocol === 'https:' ? 'ok' : 'err',
        `[HTTPS] protocol = ${location.protocol} ${location.protocol === 'https:' ? '✓' : '✗ — Chrome requiere HTTPS para instalar'}`);

    // 2. Service Worker API
    log('serviceWorker' in navigator ? 'ok' : 'err',
        `[SW API] serviceWorker in navigator = ${'serviceWorker' in navigator}`);

    // 3. SW registrado
    if ('serviceWorker' in navigator) {
        try {
            const regs = await navigator.serviceWorker.getRegistrations();
            if (regs.length === 0) {
                log('err', '[SW] No hay ningún Service Worker registrado ✗');
            } else {
                regs.forEach(r => {
                    const state = r.active?.state ?? r.installing?.state ?? r.waiting?.state ?? 'ninguno';
                    log('ok', `[SW] scope=${r.scope}  estado=${state} ✓`);
                });
            }
        } catch(e) { log('err', '[SW] Error consultando registros: ' + e); }
    }

    // 4. Manifest accesible
    try {
        const r = await fetch('/manifest.json');
        const ct = r.headers.get('content-type') || '';
        log(r.ok ? 'ok' : 'err',
            `[Manifest] HTTP ${r.status}  Content-Type: ${ct} ${r.ok ? '✓' : '✗'}`);
        if (r.ok) {
            const j = await r.json();
            log('ok', `[Manifest] name="${j.name}" start_url="${j.start_url}" display="${j.display}" ✓`);
            log(j.icons?.length >= 2 ? 'ok' : 'warn',
                `[Manifest] icons: ${j.icons?.length ?? 0} (se necesitan al menos 2)`);
        }
    } catch(e) { log('err', '[Manifest] Falló la carga: ' + e); }

    // 5. Iconos
    for (const src of ['/icons/icon-192.png', '/icons/icon-512.png']) {
        try {
            const r = await fetch(src);
            log(r.ok ? 'ok' : 'err', `[Icono] ${src}  HTTP ${r.status} ${r.ok ? '✓' : '✗'}`);
        } catch(e) { log('err', `[Icono] ${src} error: ${e}`); }
    }

    // 6. beforeinstallprompt
    log('warn', '[Install prompt] Si ves este mensaje, el evento aún no se disparó en esta carga.');
    window.addEventListener('beforeinstallprompt', (e) => {
        log('ok', '[Install prompt] beforeinstallprompt recibido ✓ — el sitio es instalable');
    });
    window.addEventListener('appinstalled', () => {
        log('ok', '[Install] appinstalled recibido — ¡instalado correctamente! ✓');
    });

})();
</script>
</body>
</html>
