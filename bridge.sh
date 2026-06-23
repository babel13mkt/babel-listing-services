#!/bin/bash
# Bridge Script - Mordor -> AR1

# Configuración
WATCH_DIR="/home/andy/hermes-workspace"
DONE_DIR="$WATCH_DIR/procesados"
REMOTE_USER="soydechile"
REMOTE_HOST="ar1"
REMOTE_PATH="/home/soydechile/public_html"

mkdir -p "$DONE_DIR"

echo "Revisando nuevos JSONs de Owl Alpha en $WATCH_DIR..."

# Buscar archivos json generados por Owl Alpha (excluir subcarpetas para evitar logs)
find "$WATCH_DIR" -maxdepth 1 -name "instituciones_*.json" -o -name "comercios_*.json" | while read -r json_file; do
    
    filename=$(basename "$json_file")
    echo "=========================================="
    echo "Nuevo JSON detectado: $filename"
    echo "Transferiendo a $REMOTE_HOST..."
    
    scp -q "$json_file" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
    
    if [ $? -eq 0 ]; then
        echo "Transferencia exitosa. Ejecutando inyección en WordPress..."
        # Ejecutar wp-cli en AR1
        ssh "$REMOTE_USER@$REMOTE_HOST" "cd $REMOTE_PATH && wp eval-file babel-auto-importer.php $filename"
        
        if [ $? -eq 0 ]; then
            echo "Inyección completa. Moviendo JSON local a procesados."
            mv "$json_file" "$DONE_DIR/"
        else
            echo "Error durante la ejecución del importador en AR1."
        fi
    else
        echo "Error al transferir $filename a AR1."
    fi
done

echo "Ciclo terminado."
