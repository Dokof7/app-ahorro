---
name: grupoahorro-app
description: Contexto y convenciones del proyecto "GrupoAhorro App" — una app Flutter (Android 14) que consume la API REST de un sistema Laravel + AdminLTE en https://app.ahorro.fundacionjabes.org. Usa SIEMPRE esta skill al trabajar en este proyecto: al agregar pantallas, modelos o servicios; al conectar nuevos endpoints; al tocar autenticación, el cliente HTTP (Dio) o el polling; o cuando se mencione GrupoAhorro, Fundación Jabes, ahorros, Sanctum, login por token, o la integración Flutter + Laravel. Úsala también para decidir cómo estructurar código nuevo de forma consistente con lo ya escrito.
---

# GrupoAhorro App — Skill de proyecto

App móvil en **Flutter** (objetivo: **Android 14 / API 34**) que consulta el sistema
**GrupoAhorro** de Fundación Jabes. El backend es **Laravel + AdminLTE** sobre
**MySQL**, hospedado en **HostGator (shared hosting con cPanel)**.

La app **nunca** se conecta a MySQL directamente. El flujo siempre es:

```
Flutter  ⇄  API REST de Laravel (con token Sanctum)  ⇄  MySQL
```

## Stack y decisiones ya tomadas

- **HTTP:** `dio` con un único cliente (`ApiClient`, patrón singleton) que inyecta
  el token `Authorization: Bearer ...` en cada petición vía interceptor.
- **Auth:** Laravel **Sanctum** (tokens). El login es `POST /api/login` y devuelve
  `{ token, user }`. El token se guarda con `flutter_secure_storage`.
- **Estado:** `provider` (`ChangeNotifier`). `AuthProvider` maneja la sesión.
- **"Tiempo real":** **polling** con `Timer.periodic` (intervalo en `AppConfig`).
  No hay WebSockets: el hosting compartido no permite procesos persistentes.
  Si se necesita push real en el futuro, la opción es **Pusher** (externo), no Reverb.
- **Formato:** se evita la dependencia `intl`; fechas y montos se formatean a mano.

## Estructura de carpetas (respétala)

```
lib/
├── main.dart            # Arranque + ruteo inicial (login vs home)
├── config/app_config.dart
├── models/              # Modelos planos con factory fromJson
├── services/            # api_client (Dio), auth_service, *_service (un service por recurso)
├── providers/           # ChangeNotifier por dominio
└── screens/             # Una pantalla por archivo
```

## Convenciones al escribir código nuevo

- **Nuevo endpoint → nuevo service.** Crea `lib/services/<recurso>_service.dart` que
  use `ApiClient.instance.dio`. No instancies Dio en otro lado.
- **Nuevo modelo → `fromJson` tolerante.** Acepta nombres de campo alternativos y
  convierte tipos de forma segura (ver `Ahorro._toDouble`), porque la forma exacta
  del JSON depende de las tablas reales del sistema.
- **Manejo de errores en la UI:** patrón `loading / error / vacío / datos`
  (ver `HomeScreen._buildBody`). Siempre ofrece "Reintentar".
- **Async + setState:** revisa `if (!mounted) return;` después de cada `await`
  antes de llamar a `setState` o navegar.
- **UI:** Material 3, color semilla `0xFF1B3A6B` (azul de la marca). Texto en español.
- **Polling:** cancela el `Timer` en `dispose()` y al cerrar sesión.

## Contrato de la API (estado actual)

| Método | Ruta           | Auth     | Devuelve                          |
|--------|----------------|----------|-----------------------------------|
| POST   | `/api/login`   | pública  | `{ token, user{id,name,email} }`  |
| POST   | `/api/logout`  | sanctum  | `{ message }`                     |
| GET    | `/api/user`    | sanctum  | usuario autenticado               |
| GET    | `/api/ahorros` | sanctum  | lista de movimientos (o `{data:[]}`) |

> El endpoint `/api/ahorros` hoy devuelve datos de prueba. Falta conectarlo a la
> tabla real (`AhorroApiController@index`).

## Trabajo pendiente (orden sugerido)

1. Conectar `AhorroApiController` a la tabla real de ahorros (filtrar por usuario).
2. Mostrar **saldo total** sobre la lista.
3. Pantalla de **detalle** de un movimiento.
4. Filtros (por fecha / grupo).
5. Pull-to-refresh ya existe; revisar estados de error de red.

## Recordatorios de despliegue (HostGator)

- Backend: trabajar local → subir por **Git/SSH** → `php artisan optimize:clear`.
- En Laravel 11+, `routes/api.php` se crea con `php artisan install:api`.
- App release: confirmar permiso `INTERNET` en el `AndroidManifest.xml` principal
  y `minSdk = 23` en `build.gradle.kts`.
