# Base de datos MySQL del sistema, para correr en Render como "Private Service"
# junto al servicio web (misma región = consultas de ~1 ms en lugar de ~150 ms).
# En Render:  Dockerfile Path = ./docker/mysql/Dockerfile
#             Docker Build Context Directory = ./docker/mysql
#             Disco montado en /var/lib/mysql
# Instrucciones completas en el README (sección "Base de datos en Render").
FROM mysql:8.4

COPY planeacion.cnf /etc/mysql/conf.d/planeacion.cnf
COPY importar-datos.sh /docker-entrypoint-initdb.d/10-importar-datos.sh

# MySQL ignora en silencio un archivo de configuración que cualquiera puede modificar;
# se fijan los permisos para no depender de cómo llegaron los archivos.
RUN chmod 644 /etc/mysql/conf.d/planeacion.cnf \
 && chmod 755 /docker-entrypoint-initdb.d/10-importar-datos.sh
