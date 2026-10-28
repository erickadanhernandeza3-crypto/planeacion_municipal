# Sistema de Planeación Municipal — Tamazunchale

Sistema web del H. Ayuntamiento de Tamazunchale, S.L.P. para registrar el Plan Municipal (ejes, programas y metas), dar seguimiento a los avances, capturar los indicadores MIR y generar reportes.

Está hecho en PHP 8 y MySQL 8, con Bootstrap, DataTables, Chart.js y SweetAlert2 cargados desde CDN.

## Roles

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Usuarios, ejes, programas, indicadores MIR, comparativo trimestral y bitácora. |
| **Planeación** | Metas, seguimiento y avances, **revisión de los avances de los enlaces**, alertas, calendario, físico vs financiero, reportes, observaciones y fichas técnicas MIR. |
| **Enlace de dependencia** | Solo ve las metas de los programas cuya *Área responsable* es su dependencia. Registra avances con evidencias (fotos y PDF). Sus avances quedan **En revisión** hasta que Planeación los aprueba o los devuelve con un comentario. |
| **Público (con clave)** | `avance.php?clave=...`: resumen de solo lectura del avance por programa. |

### Flujo de un avance de un enlace

1. El enlace registra el avance y adjunta sus evidencias. El avance queda **En revisión** y todavía no se suma a la meta.
2. Planeación lo ve en **Revisión de Avances**, con el contador en el menú, junto con sus fotos y PDF.
3. **Aprobar:** se suma a la meta y se recalcula su estado, todo en una sola transacción.
   **Devolver:** el enlace ve el motivo en "Mis avances enviados" y registra el avance corregido.

Los avances que registra Planeación quedan aprobados directamente, como antes. El comparativo trimestral solo cuenta los avances aprobados.

### Indicadores MIR

Cada indicador tiene su **ficha técnica**:
- nombre del indicador y fórmula;
- dimensión, sentido y frecuencia;
- línea base, año de la línea base y meta anual;
- medios de verificación y supuestos.

Desde "Indicadores (MIR)" se imprime la ficha técnica de cada indicador, con su semáforo de cumplimiento (verde 90% o más, amarillo de 70% a 89%, rojo menos de 70%), y la **Matriz de Indicadores para Resultados** completa del programa.

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

## Producción en Render

Todo corre en Render, en la **misma región**: el sistema (Web Service) y su base de datos MySQL (Private Service con disco). Así cada consulta tarda ~1 ms. Con la base en otro proveedor y otro continente tardaba ~150 ms.

| Servicio | Qué es | Plan |
|---|---|---|
| Web Service (`Dockerfile` de la raíz) | PHP 8.2 + Apache | Gratis o de pago |
| Private Service (`docker/mysql/Dockerfile`) | MySQL 8.4 con disco de 1 GB | Starter (~7 USD/mes) + disco (~0.25 USD/mes) |

Variables del **Web Service** (ver `.env.example`):

| Variable | Para qué |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión a MySQL. `DB_HOST` es la dirección interna del Private Service. |
| `CLAVE_AVANCE` | Clave de `avance.php`. Si no se define, esa página queda cerrada. |
| `CLOUDINARY_URL` | Cuenta de Cloudinary para las evidencias: `cloudinary://API_KEY:API_SECRET@CLOUD_NAME`. Se copia del Dashboard de Cloudinary ("API environment variable"). Sin ella, el sistema avisa que las evidencias no están configuradas. |

### Base de datos en Render (paso a paso)

1. **Ver la región del sistema.** En Render abre el Web Service y anota su **Region** (por ejemplo, *Oregon*). La base debe ir en la misma.
2. **Crear la base.** *New > Private Service* > conecta este repositorio de GitHub y llena:

   | Campo | Valor |
   |---|---|
   | Name | `planeacion-mysql` |
   | Region | **la misma del paso 1** |
   | Runtime / Language | Docker |
   | Dockerfile Path | `./docker/mysql/Dockerfile` |
   | Docker Build Context Directory | `./docker/mysql` |
   | Instance Type | Starter |
   | Disk | Mount Path `/var/lib/mysql`, tamaño 1 GB |

3. **Variables del Private Service**, antes de crearlo:

   | Variable | Valor |
   |---|---|
   | `MYSQL_DATABASE` | `planeacion_municipal` |
   | `MYSQL_USER` | `planeacion` |
   | `MYSQL_PASSWORD` | una contraseña larga (la usará el sistema) |
   | `MYSQL_ROOT_PASSWORD` | otra contraseña larga, distinta |
   | `ORIGEN_HOST`, `ORIGEN_PORT`, `ORIGEN_DB`, `ORIGEN_USER`, `ORIGEN_PASS` | los datos de la base actual de Clever Cloud, que son los mismos que hoy tiene el Web Service en `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` |

   La primera vez que arranca, la base **copia sola todos los datos** de Clever Cloud. En *Logs* debe aparecer `[planeacion] Datos copiados: N tablas`. Después ya no vuelve a copiar, aunque se reinicie.
4. **Conectar el sistema a la base nueva.** En el Web Service cambia las variables y guarda (se reinicia solo):
   - `DB_HOST` = la **Internal Address** del Private Service, sin el `:3306` (por ejemplo, `planeacion-mysql-abcd`)
   - `DB_PORT` = `3306`
   - `DB_NAME` = `planeacion_municipal`
   - `DB_USER` = `planeacion`
   - `DB_PASS` = la `MYSQL_PASSWORD` del paso 3
5. **Comprobar.** Abre `https://<tu-sistema>.onrender.com/salud.php`. Debe responder `"ok":true` con `consulta_ms` de 0 a 2. Entra al sistema y revisa que estén tus datos.
6. Cuando todo esté bien por unos días, puedes quitar las variables `ORIGEN_*` y dar de baja la base de Clever Cloud.

**Volver a copiar los datos** (si la copia inicial falló): en el Private Service abre *Shell* y ejecuta, con los datos de Clever Cloud:

```bash
MYSQL_PWD='CLAVE_CLEVER' mysqldump -h HOST_CLEVER -P PUERTO -u USUARIO_CLEVER --single-transaction --no-tablespaces --set-gtid-purged=OFF BD_CLEVER > /tmp/origen.sql
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot planeacion_municipal < /tmp/origen.sql
```

La configuración de MySQL (`docker/mysql/planeacion.cnf`) está pensada para los 512 MB del plan Starter (usa ~135 MB):
- usa la hora del centro de México;
- no guarda registro binario, para que el disco no se llene solo;
- guarda los datos en una subcarpeta del disco, porque la raíz trae `lost+found` y MySQL no arranca ahí.

### Lo que el sistema hace solo

**Base de datos sin acceso directo.** Al arrancar, el sistema crea solo las tablas y columnas nuevas (`Php/migraciones.php`, tabla `esquema_version`). Después de cada despliegue, la primera petición aplica lo que falte. No hay que correr scripts a mano.

**Evidencias.** Render borra los archivos del servidor en cada despliegue, por eso las fotos y los PDF se guardan en Cloudinary y en la base solo queda el enlace:
- Las fotos se reducen en el navegador antes de subirse (máximo 1600 px).
- El servidor revisa el tipo real del archivo (JPG, PNG, WEBP o PDF) y que pese máximo 5 MB.
- La subida se firma en el servidor; las claves nunca llegan al navegador.
- Al borrar una meta, un programa o un eje, también se borran sus archivos en Cloudinary.

**Siempre despierto en horario de oficina.** El plan gratis del Web Service se duerme a los 15 minutos sin visitas. La tarea `.github/workflows/mantener-activo.yml` visita `salud.php` cada 10 minutos de 7:00 a 21:00 (hora de México).

En producción la imagen ya trae:
- Los errores de PHP se guardan en el log y nunca se muestran en pantalla.
- La cookie de sesión es solo HTTPS, `HttpOnly` y `SameSite`.
- Cabeceras de seguridad activas: anti-iframe, `nosniff` y HSTS.
- Las carpetas `sql/` y `docker/` no se pueden abrir desde el navegador.
- Caché del navegador para CSS y JS, y descarga diferida de las librerías de Excel/PDF.

## Respaldos

**Hazlo antes de salir a producción.** Saca un respaldo cada semana y antes de cualquier cambio grande. En el Private Service abre *Shell*:

```bash
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction planeacion_municipal > /tmp/respaldo.sql
```

Para pasarlo a tu computadora, Render permite conectarse por SSH a los servicios de pago (botón *Connect > SSH* del Private Service); con eso puedes copiar el archivo con `scp`.

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
enlace.php           Panel del enlace de dependencia
avance.php           Vista pública con clave
Php/controlador.php  Entrada única de todas las peticiones (idopc=...)
Php/comunes_extra.php  Seguridad, roles, validaciones y funciones compartidas
Php/migraciones.php  Actualiza la base de datos sola al desplegar
Php/evidencias.php   Subida y borrado de evidencias en Cloudinary
docker/mysql/        Base de datos MySQL para Render (Private Service)
salud.php            Verificación de salud (la usa la tarea de GitHub)
Php/operaciones.php  CRUD de ejes, programas, metas, usuarios e indicadores
Php/vistas.php       Formularios y tablas
Php/planeacion_extra.php / admin_extra.php  Módulos de cada panel
js/                  funciones.js (base), extras_ui.js (CSRF, sesión, menú), módulos
sql/instalacion.sql  Base de datos completa desde cero
sql/planeacion_extra.sql  Solo para actualizar bases antiguas
```
