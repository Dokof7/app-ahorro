---
name: grupoahorro-backend
description: Convenciones del backend Laravel + AdminLTE del sistema GrupoAhorro (app.ahorro.fundacionjabes.org) hospedado en HostGator. Usa esta skill SIEMPRE que se trabaje en el lado servidor del proyecto GrupoAhorro: al crear o modificar controladores de API, agregar rutas en routes/api.php, configurar Sanctum, ajustar modelos Eloquent que serán expuestos al móvil, o cuando se mencione la API que consume la app Flutter de Fundación Jabes. Úsala también para decidir cómo nombrar endpoints, dónde ubicar controladores y cómo desplegar cambios al hosting compartido.
---

# GrupoAhorro Backend — Skill de proyecto (Laravel)

Sistema **Laravel + AdminLTE** sobre **MySQL**, en `app.ahorro.fundacionjabes.org`,
hospedado en **HostGator shared hosting (cPanel)**. Expone una API REST que es
consumida por la app móvil Flutter del mismo proyecto.

## Reglas de oro del despliegue

- **Editar local en VS Code → push a GitHub → SSH pull en servidor → limpiar caché.**
  Repo: `github.com/Dokof7/app-ahorro`.
- **NUNCA correr migraciones destructivas en producción.** Para cambios de esquema,
  usar phpMyAdmin desde cPanel.
- Después de cualquier deploy: `php artisan optimize:clear` en el servidor.
- SSH: usuario `funda682`, puerto `2222`, llave en `C:\Users\admin\.ssh\getsemanilte.key`.

## Autenticación de la API

- **Laravel Sanctum** con tokens (no SPA, no cookies).
- El modelo `User` debe tener el trait `HasApiTokens`.
- Login en `POST /api/login` devuelve `{ token, user }`.
- Rutas protegidas usan el middleware `auth:sanctum`.
- En Laravel 11+, las rutas de API se habilitan con `php artisan install:api`
  (ese comando crea `routes/api.php` y registra Sanctum).

## Convenciones de código

- **Controladores de API en `app/Http/Controllers/Api/`.** Nombres con sufijo
  `ApiController` para distinguirlos de los controladores web del panel AdminLTE
  (ej. `AhorroApiController`, no reusar el del panel).
- **Rutas en `routes/api.php`** agrupadas por middleware:
  - Públicas (login): fuera del grupo.
  - Privadas: dentro de `Route::middleware('auth:sanctum')->group(...)`.
- **Prefijo `/api/`** lo agrega Laravel automáticamente; no escribirlo en las rutas.
- **Responses JSON consistentes.** Cuando devuelvas listas, envuelve en `{ "data": [...] }`
  para que la app pueda paginar después sin romper.
- **Filtrar por usuario autenticado:** en endpoints de datos personales, siempre
  `->where('user_id', $request->user()->id)` o equivalente. No exponer datos de
  otros usuarios.
- **Seleccionar columnas explícitas** en las consultas (`->get(['id', 'concepto', ...])`)
  para no filtrar campos sensibles al móvil.

## Contrato actual de la API móvil

| Método | Ruta           | Controlador                            | Notas                           |
|--------|----------------|----------------------------------------|---------------------------------|
| POST   | `/api/login`   | `Api\ApiAuthController@login`          | Pública                         |
| POST   | `/api/logout`  | `Api\ApiAuthController@logout`         | Revoca el token actual          |
| GET    | `/api/user`    | closure devuelve `$request->user()`    | Verifica sesión                 |
| GET    | `/api/ahorros` | `Api\AhorroApiController@index`        | Hoy devuelve datos de prueba    |

> Cuando se agregue un endpoint nuevo, actualizar esta tabla y avisar al lado Flutter
> para crear el `*_service.dart` correspondiente.

## Cosas a tener en cuenta en HostGator

- **No hay WebSockets persistentes.** Reverb / Soketi no son viables. Si se necesita
  push real, usar **Pusher** o **Ably** (servicios externos con plan gratuito).
- **Tareas programadas:** vía Cron Jobs de cPanel apuntando a `php artisan schedule:run`.
- **Almacenamiento:** el `storage/` debe tener permisos correctos tras cada deploy
  (suele bastar con `chmod -R 775 storage bootstrap/cache`).
- **CORS:** la app móvil no es un navegador, así que **no necesita CORS**. Solo
  configurarlo si además se consume la API desde un dominio web distinto.

## Cómo probar la API antes de tocar Flutter

```bash
# Login
curl -X POST https://app.ahorro.fundacionjabes.org/api/login \
  -H "Accept: application/json" \
  -d "email=...&password=..."

# Endpoint protegido
curl https://app.ahorro.fundacionjabes.org/api/ahorros \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

Si `curl` no devuelve lo esperado, el problema **no** es de la app: arreglar acá primero.
