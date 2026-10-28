#!/bin/bash
# Se ejecuta UNA SOLA VEZ: la primera vez que arranca la base con el disco vacío.
# Si están las variables ORIGEN_*, copia aquí toda la base de Clever Cloud
# (o de cualquier otro MySQL). Si no, la base queda vacía y el sistema crea
# sus tablas nuevas solo, pero las básicas hay que importarlas (ver README).
#
# La imagen oficial de MySQL "incluye" (source) este archivo, por eso no usa
# "exit": terminaría el arranque completo de MySQL.

importar_desde_origen() {
    if [ -z "${ORIGEN_HOST:-}" ] || [ -z "${ORIGEN_DB:-}" ] || [ -z "${ORIGEN_USER:-}" ]; then
        echo "[planeacion] No hay ORIGEN_HOST / ORIGEN_DB / ORIGEN_USER: la base queda vacía."
        return 0
    fi

    # En /tmp: la raíz del disco pertenece a root y la importación corre como usuario mysql.
    local respaldo=/tmp/origen_inicial.sql
    echo "[planeacion] Copiando la base '$ORIGEN_DB' desde $ORIGEN_HOST:${ORIGEN_PORT:-3306} ..."
    if ! MYSQL_PWD="${ORIGEN_PASS:-}" mysqldump \
            -h "$ORIGEN_HOST" -P "${ORIGEN_PORT:-3306}" -u "$ORIGEN_USER" \
            --single-transaction --no-tablespaces --set-gtid-purged=OFF --skip-lock-tables \
            --default-character-set=utf8mb4 "$ORIGEN_DB" > "$respaldo"; then
        echo "[planeacion] ERROR: no se pudo leer la base de origen. Revisa ORIGEN_HOST, ORIGEN_PORT, ORIGEN_USER, ORIGEN_PASS y ORIGEN_DB."
        echo "[planeacion] La base queda vacía. Para reintentar, sigue la sección 'Volver a copiar los datos' del README."
        return 0
    fi

    if MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=socket -uroot --default-character-set=utf8mb4 "$MYSQL_DATABASE" < "$respaldo"; then
        local tablas
        tablas=$(MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=socket -uroot -N -e \
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$MYSQL_DATABASE'")
        echo "[planeacion] Datos copiados: $tablas tablas en '$MYSQL_DATABASE'."
        rm -f "$respaldo"

    else
        echo "[planeacion] ERROR: el respaldo se descargó pero no se pudo cargar. Revisa el mensaje de arriba."
    fi
}

importar_desde_origen
