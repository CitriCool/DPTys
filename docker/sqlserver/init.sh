#!/bin/bash
# Espera a SQL Server y crea la base de datos si no existe.
# Las tablas las crean las migraciones de Laravel al arrancar la aplicación.
set -e
SQLCMD="/opt/mssql-tools18/bin/sqlcmd -S db -U sa -P $MSSQL_SA_PASSWORD -C -b"

for i in $(seq 1 60); do
    if $SQLCMD -Q "SELECT 1" >/dev/null 2>&1; then break; fi
    echo "Esperando a SQL Server ($i)..."
    sleep 2
done

$SQLCMD -Q "IF DB_ID('$DB_NAME') IS NULL CREATE DATABASE [$DB_NAME]"
echo "Base de datos $DB_NAME lista."
