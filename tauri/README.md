# Chat PWA — App nativa con Tauri

App de escritorio (Windows) que abre el chat alojado en
`https://chatrusttauri.xo.je` dentro de una ventana nativa.

## Requisitos (una sola vez)

- **WebView2 Runtime** (ya viene en Windows 10/11).
- **Rust** (rustup) con toolchain `stable-msvc`.
- **Visual Studio C++ Build Tools** (workload "Desarrollo para escritorio con C++").
- **Tauri CLI**: `cargo install tauri-cli --version "^2"`

## Generar iconos (una vez, o al cambiar el icono)

Desde `tauri/`:

```
cargo tauri icon src-tauri/icons/source.png
```

## Desarrollo (ventana en vivo)

```
cargo tauri dev
```

## Compilar el instalador

```
cargo tauri build
```

El instalador `.exe` (NSIS) queda en:

```
src-tauri/target/release/bundle/nsis/Chat PWA_1.0.0_x64-setup.exe
```

## Cambiar la URL del chat

Edita `src-tauri/tauri.conf.json` → `app.windows[0].url`.
