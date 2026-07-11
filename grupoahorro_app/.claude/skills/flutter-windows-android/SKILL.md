---
name: flutter-windows-android
description: Solución de problemas del entorno de desarrollo Flutter en Windows 11 con destino Android 14. Usa esta skill cuando aparezcan errores de `flutter doctor`, fallos de `adb` (puerto 5037, daemon que no arranca), errores al descargar el Android Emulator o imágenes del SDK ("Not in GZIP format"), problemas para que el teléfono Android aparezca en `flutter devices`, conflictos con otros emuladores (BlueStacks, LDPlayer, Nox), o configuración del cliente HTTP de la app (URL de API, permisos de red, minSdk). Aplica al setup de Dan en Windows 11 con SDK en C:\\Users\\admin\\AppData\\Local\\Android\\sdk.
---

# Flutter en Windows 11 → Android 14 — Skill de troubleshooting

Atajos para los problemas que ya hemos resuelto, para no repetir el ciclo.

## Rutas conocidas del entorno

- Flutter SDK: `C:\Users\admin\develop\flutter`
- Android SDK: `C:\Users\admin\AppData\Local\Android\sdk`
- `adb`: `C:\Users\admin\AppData\Local\Android\sdk\platform-tools\adb.exe`
- JDK (de Android Studio): `C:\Program Files\Android\Android Studio\jbr`

## Problema: `adb` no levanta el daemon (puerto 5037)

Síntoma: `could not read ok from ADB Server`, `failed to start daemon`.

**Orden de ataque:**

1. Matar adb colgado y reintentar:
   ```
   taskkill /F /IM adb.exe
   adb start-server
   ```
2. Ver quién ocupa el puerto:
   ```
   netstat -ano | findstr 5037
   tasklist | findstr <PID>
   ```
3. **Sospechoso #1: otro emulador instalado** (BlueStacks, LDPlayer, Nox, MEmu).
   Cada uno trae su propio `adb`. Cerrarlo, incluido el servicio en segundo plano.
4. Diagnóstico verboso en primer plano:
   ```
   adb nodaemon server
   ```
5. Antivirus / VPN pueden bloquear el socket local; revisar cuarentena.

## Problema: "Not in GZIP format" al instalar componentes del SDK

Síntoma: el SDK Manager o `flutter doctor --android-licenses` descarga el zip y se
queja del formato. Significa **descarga corrupta** o **interceptada** (proxy / AV).

**Solución:**

1. Borrar la descarga corrupta en `C:\Users\admin\AppData\Local\Android\sdk\.temp\`
   y los temporales (`%TEMP%`).
2. Android Studio → Settings → System Settings → HTTP Proxy → **No proxy**.
3. Desactivar antivirus durante la descarga.
4. Si insiste, **descarga manual** desde el navegador con la URL exacta de
   `dl.google.com/android/repository/...` y descomprime en la carpeta que toque
   (ej. `sdk\emulator\`).

## Problema: el teléfono Android no aparece en `flutter devices`

Diagnóstico directo:
```
adb devices
```

| Lo que devuelve | Causa y arreglo                                                   |
|-----------------|-------------------------------------------------------------------|
| (vacío)         | Depuración USB apagada, modo USB en "solo carga", cable solo de carga, o falta driver del fabricante. |
| `unauthorized`  | Falta aceptar el aviso de huella RSA en el teléfono. Marcar "Permitir siempre". |
| `offline`       | `adb kill-server && adb start-server` y reconectar.              |
| `device`        | Todo OK; volver a correr `flutter devices`.                       |

Notas:
- El modo USB debe estar en **"Transferencia de archivos"** (MTP), no "Solo carga".
- Activar Depuración USB: tocar 7 veces *Número de compilación* en *Acerca del teléfono*.

## Problema: solo aparecen Windows / Chrome / Edge como dispositivos

Eso es lo normal cuando no hay Android conectado **y** no hay emulador corriendo.
Las opciones son:

- **Teléfono físico** (ver sección anterior). Es lo más rápido para probar contra
  la API real de GrupoAhorro.
- **Emulador:**
  ```
  flutter emulators
  flutter emulators --launch <id>
  ```
  Si la lista está vacía, crear un AVD en Android Studio → Device Manager → Create
  Device (Pixel + imagen Android 14 / API 34).

## Configuración Android del proyecto Flutter

- `android/app/src/main/AndroidManifest.xml` debe tener (justo arriba de `<application>`):
  ```xml
  <uses-permission android:name="android.permission.INTERNET"/>
  ```
- `android/app/build.gradle.kts` (o `.gradle`): `minSdk = 23` (lo exige
  `flutter_secure_storage 10.x`). `targetSdk = 34` para Android 14.
- La API usa HTTPS, así que **no** hace falta tocar `usesCleartextTraffic`.

## Pista para apuntar la app a otra URL durante pruebas

En `lib/config/app_config.dart` cambiar `apiBaseUrl`. Para apuntar a un Laravel local
desde el **emulador**, la dirección de la máquina anfitriona es `http://10.0.2.2:8000`
(no `localhost`). Desde un **teléfono físico**, usar la IP LAN del PC.
