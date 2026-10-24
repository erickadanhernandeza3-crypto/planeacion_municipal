# Sistema de Planeación Municipal — Tamazunchale

Sistema web del H. Ayuntamiento de Tamazunchale, S.L.P. para registrar el Plan Municipal (ejes, programas y metas), dar seguimiento a los avances, capturar los indicadores MIR y generar reportes.

Está hecho en PHP 8 y MySQL 8, con Bootstrap, DataTables, Chart.js y SweetAlert2 cargados desde CDN.

## Roles

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Usuarios, ejes, programas, indicadores MIR, comparativo trimestral y bitácora. |
| **Planeación** | Metas, seguimiento y avances, alertas, calendario, físico vs financiero, reportes y observaciones. |
| **Público (con clave)** | `avance.php?clave=...`: resumen de solo lectura del avance por programa. |

## Instalación local (Laragon o XAMPP)

1. Copia el proyecto a `C:\laragon\www\planeacion_municipal` (o a `htdocs` en XAMPP).
2. Crea la base de datos e importa la estructura:
   ```sql
   CREATE DATABASE planeacion_municipal_adan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Después importa `sql/instalacion.sql` en esa base (phpMyAdmin > Importar, o `mysql -u root planeacion_municipal_adan < sql/instalacion.sql`).
3. Crea `Php/config.local.php` con los datos de tu MySQL local. Este archivo no se sube a git:
   ```php
   <?php
   return [
       'DB_HOST' => 'localhost',
       'DB_PORT' => 3306,
       'DB_NAME' => 'planeacion_municipal_adan',
       'DB_USER' => 'root',
       'DB_PASS' => '',
       'CLAVE_AVANCE' => 'clave-para-avance-php',
   ];
   ```
4. Entra a `http://planeacion_municipal.test` con el usuario `admin` y la contraseña `Cambiar.2026`. **Cámbiala en ese momento** desde Gestión de Usuarios > Editar.

## Producción (Render + Clever Cloud)

El `Dockerfile` arma la imagen con PHP 8.2 y Apache. En el Web Service de Render configura estas variables en **Environment** (ver `.env.example`):

| Variable | Para qué |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión a la base de MySQL (Clever Cloud). |
| `CLAVE_AVANCE` | Clave de `avance.php`. Si no se define, esa página queda cerrada. |

En producción la imagen ya trae:
- Los errores de PHP se guardan en el log y nunca se muestran en pantalla.
- La cookie de sesión es solo HTTPS, `HttpOnly` y `SameSite`.
- Cabeceras de seguridad activas: anti-iframe, `nosniff` y HSTS.
- Las carpetas `sql/` y `docker/` no se pueden abrir desde el navegador.

> El plan gratuito de Render "se duerme" sin uso y la primera carga tarda cerca de un minuto. Para uso diario conviene un plan de pago.

## Respaldos

**Hazlo antes de salir a producción.** Revisa en Clever Cloud que el add-on de MySQL tenga respaldos automáticos activos. Además, saca un respaldo manual cada semana y antes de cualquier cambio grande:

```bash
mysqldump -h HOST -P PUERTO -u USUARIO -p --single-transaction --routines NOMBRE_BD > respaldo_AAAA-MM-DD.sql
```

Para restaurar: `mysql -h HOST -P PUERTO -u USUARIO -p NOMBRE_BD < respaldo_AAAA-MM-DD.sql`.

Guarda los respaldos fuera del servidor (por ejemplo, en una unidad compartida del Ayuntamiento).

## Seguridad (cómo está hecho)

- **Contraseñas:** se guardan cifradas con bcrypt y piden mínimo 8 caracteres.
- **Login:** bloquea 15 minutos después de 5 intentos fallidos desde la misma IP con el mismo usuario.
- **Sesión:** se cierra sola tras 30 minutos sin actividad.
- **Permisos:** `Php/controlador.php` es la única entrada. Revisa sesión, rol y token CSRF antes de cualquier operación (`protegerControlador()` en `Php/comunes_extra.php`).
- **Consultas:** todas las que llevan datos del usuario son preparadas (`consultarPreparado()` / `ejecutarPreparado()`).
- **Validación:** en el servidor (largo de textos, fechas, números, roles). Los errores regresan como texto y el navegador los muestra en una alerta.
- **Texto en pantalla:** se escapa al mostrarse, con `e()` en PHP y `escapar()` en JavaScript.
- **Administradores:** siempre queda al menos uno activo, y nadie puede eliminarse ni quitarse el acceso a sí mismo.
- **Bitácora:** registra inicios y cierres de sesión, intentos fallidos y cada alta, edición o eliminación.

## Estructura

```
index.php            Login
administrador.php    Panel del administrador
planeacion.php       Panel de planeación
avance.php           Vista pública con clave
Php/controlador.php  Entrada única de todas las peticiones (idopc=...)
Php/comunes_extra.php  Seguridad, validaciones y funciones compartidas
Php/operaciones.php  CRUD de ejes, programas, metas, usuarios e indicadores
Php/vistas.php       Formularios y tablas
Php/planeacion_extra.php / admin_extra.php  Módulos de cada panel
js/                  funciones.js (base), extras_ui.js (CSRF, sesión, menú), módulos
sql/instalacion.sql  Base de datos completa desde cero
sql/planeacion_extra.sql  Solo para actualizar bases antiguas
```
