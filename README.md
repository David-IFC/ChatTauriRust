# Chat PWA — ChatTauriRust

Aplicación de **chat de mensajes** desarrollada como proyecto educativo. Existe en tres formas:

- 🌐 **Web / PWA** — accesible desde el navegador e instalable como app.
- 💬 **Backend** — PHP + MySQL alojado en InfinityFree.
- 🖥️ **App nativa de escritorio (Windows)** — empaquetada con **Tauri** (Rust).

🔗 **Web en producción:** https://chatrusttauri.xo.je

---

## 📥 Instalar la app de escritorio (Windows)

1. Ve a la sección **[Releases](../../releases)** del repositorio.
2. Descarga `Chat PWA_1.0.0_x64-setup.exe`.
3. Ejecútalo y sigue el asistente de instalación.
4. Abre **"Chat PWA"** desde el menú inicio.

### Requisitos para usar la app
- 🪟 **Windows 64 bits**
- 🌐 **Conexión a internet** (la app carga el chat desde el servidor)
- ✅ WebView2 (ya viene incluido en Windows 10/11)

> ⚠️ Como el instalador no está firmado digitalmente, Windows SmartScreen puede mostrar
> *"Windows protegió tu PC"*. Haz clic en **"Más información" → "Ejecutar de todas formas"**.

---

## 🧩 Cómo funciona

La app de escritorio es una **ventana nativa** que carga la web alojada en InfinityFree.
Todo el chat (usuarios y mensajes) vive en el servidor, así que varias personas pueden
chatear entre sí. No guarda datos en local.

```
App nativa (Tauri)  ──►  internet  ──►  InfinityFree (PHP + MySQL)
```

---

## 🛠️ Tecnologías

| Capa | Tecnología |
|---|---|
| Frontend | HTML, CSS, JavaScript (vanilla) |
| Backend | PHP 8.2 (PDO) |
| Base de datos | MySQL (producción) / SQLite (desarrollo local) |
| PWA | Web App Manifest + Service Worker |
| App nativa | Tauri 2 (Rust + WebView2) |

---

## 📂 Estructura del proyecto

```
app/
├── public/            # La web (PHP + JS + CSS) — se sube al hosting
│   ├── index.php      # Página principal (login + chat)
│   ├── api.php        # API JSON (login, mensajes, manifest…)
│   ├── config.php     # Conexión a la base de datos
│   ├── clear.php      # Borrar todos los mensajes
│   ├── sw.php         # Service Worker (PWA)
│   ├── assets/        # CSS y JavaScript
│   └── icons/         # Iconos de la PWA
└── tauri/             # App nativa de escritorio
    └── src-tauri/     # Código Rust + configuración de Tauri
```

---

## 💻 Desarrollo

### Web (PHP)
Servir la carpeta `public/` con cualquier servidor PHP (por ejemplo
[Local by Flywheel](https://localwp.com/)). En `public/config.php` se elige
entre SQLite (local) o MySQL.

### App nativa (Tauri)
Requiere [Rust](https://rustup.rs/), las **VS C++ Build Tools** y el Tauri CLI
(`cargo install tauri-cli --version "^2"`). Desde la carpeta `tauri/`:

```bash
cargo tauri dev      # ventana en vivo (desarrollo)
cargo tauri build    # genera el instalador en src-tauri/target/release/bundle/nsis/
```

La URL que abre la app se configura en `tauri/src-tauri/tauri.conf.json`
(`app.windows[0].url`).

---

## 📄 Licencia

Proyecto educativo de uso libre.
