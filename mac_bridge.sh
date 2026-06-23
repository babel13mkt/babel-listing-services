#!/bin/bash
# Bridge Script Local (Mac) -> Mordor -> AR1

WATCH_DIR="/home/andy/workspace/instituciones"
DONE_DIR="$WATCH_DIR/procesados"
REMOTE_AR1="soydechile@ar1"
REMOTE_PATH="/home/soydechile/public_html"

echo "Revisando nuevos JSONs de Owl Alpha en Mordor ($WATCH_DIR)..."

# Listar archivos JSON en Mordor
FILES=$(ssh mordor "find $WATCH_DIR -maxdepth 1 -name 'instituciones_*.json' -o -name 'comercios_*.json' 2>/dev/null")

if [ -z "$FILES" ]; then
    echo "No hay nuevos JSONs."
    exit 0
fi

ssh mordor "mkdir -p $DONE_DIR"

for file in $FILES; do
    filename=$(basename "$file")
    echo "=========================================="
    echo "Nuevo JSON detectado: $filename"
    echo "Descargando desde Mordor..."
    
    # Bajar a Mac
    scp -q "mordor:$file" "/tmp/$filename"
    
    if [ $? -eq 0 ]; then
        echo "Subiendo a AR1..."
        # Subir a AR1
        scp -q "/tmp/$filename" "$REMOTE_AR1:$REMOTE_PATH/"
        
        if [ $? -eq 0 ]; then
            echo "Ejecutando inyección en WordPress AR1..."
            ssh "$REMOTE_AR1" "cd $REMOTE_PATH && wp eval-file babel-auto-importer.php $filename"
            
            if [ $? -eq 0 ]; then
                echo "Inyección exitosa. Moviendo a procesados en Mordor."
                ssh mordor "mv $file $DONE_DIR/"
                rm "/tmp/$filename"
            else
                echo "Error ejecutando el importador."
            fi
        else
            echo "Error subiendo a AR1."
        fi
    else
        echo "Error bajando desde Mordor."
    fi
done
