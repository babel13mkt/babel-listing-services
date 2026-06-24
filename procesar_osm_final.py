import json
import glob
import traceback

AMENITY_MAP = {
    'police': 'Seguridad Pública',
    'fire_station': 'Seguridad Pública',
    'hospital': 'Salud',
    'clinic': 'Salud',
    'courthouse': 'Justicia',
    'townhall': 'Gobierno',
    'library': 'Cultura',
    'prison': 'Seguridad Pública'
}

files = glob.glob('/home/andy/workspace/instituciones/osm_batch*.json')
resultados = []

for f in files:
    try:
        data = json.load(open(f))
        
        # Iterate over the top level keys (e.g. 'carabineros', 'bomberos')
        for category_name, category_data in data.items():
            if isinstance(category_data, dict):
                items = category_data.get('elements', [])
            elif isinstance(category_data, list):
                items = category_data
            else:
                items = []

            for item in items:
                if type(item) is dict and 'tags' in item:
                    tags = item['tags']
                    amenity = tags.get('amenity', '')
                    if amenity not in AMENITY_MAP:
                        continue
                    
                    nombre = tags.get('name', tags.get('alt_name', ''))
                    if not nombre:
                        continue
                    
                    comuna = tags.get('addr:city', tags.get('addr:suburb', ''))
                    street = tags.get('addr:street', '')
                    num = tags.get('addr:housenumber', '')
                    direccion = f'{street} {num}'.strip()
                    
                    telefono = tags.get('phone', tags.get('contact:phone', ''))
                    website = tags.get('website', tags.get('contact:website', ''))
                    
                    lat = ''
                    lng = ''
                    if 'center' in item:
                        lat = str(item['center'].get('lat', ''))
                        lng = str(item['center'].get('lon', ''))
                    elif 'lat' in item and 'lon' in item:
                        lat = str(item['lat'])
                        lng = str(item['lon'])
                    
                    record = {
                        'nombre': nombre,
                        'categoria': AMENITY_MAP[amenity],
                        'comuna': comuna,
                        'region': '',
                        'telefono': telefono,
                        'direccion': direccion,
                        'website': website,
                        'descripcion': f'Institución: {nombre}',
                        'imagen_destacada': '',
                        'galeria': [],
                        'lat': lat,
                        'lng': lng
                    }
                    resultados.append(record)
    except Exception as e:
        print(f'Error processing {f}: {e}')
        traceback.print_exc()

# Deduplicate by name and lat/lon
unique_results = []
seen = set()
for r in resultados:
    key = f"{r['nombre']}-{r['lat']}-{r['lng']}"
    if key not in seen:
        seen.add(key)
        unique_results.append(r)

out_file = '/home/andy/workspace/instituciones/instituciones_osm_final.json'
with open(out_file, 'w', encoding='utf-8') as out:
    json.dump(unique_results, out, ensure_ascii=False, indent=2)

print(f'Procesados exitosamente {len(unique_results)} registros únicos desde OpenStreetMap.')
