import wikipedia
import json

regions = {
    25: "Región de Arica y Parinacota",
    26: "Región de Tarapacá",
    27: "Región de Antofagasta",
    20: "Región de Atacama",
    28: "Región de Coquimbo",
    19: "Región de Valparaíso",
    18: "Región Metropolitana de Santiago",
    29: "Región de O'Higgins",
    30: "Región del Maule",
    31: "Región de Ñuble",
    32: "Región del Biobío",
    33: "Región de la Araucanía",
    34: "Región de Los Ríos",
    35: "Región de Los Lagos",
    36: "Región de Aysén",
    37: "Región de Magallanes y de la Antártica Chilena"
}

wikipedia.set_lang("es")

urls = {}
for term_id, query in regions.items():
    try:
        page = wikipedia.page(query)
        # Buscar una imagen que sea un paisaje, evitar mapas o escudos
        img_url = None
        for img in page.images:
            if img.lower().endswith(('.jpg', '.jpeg', '.png')) and 'map' not in img.lower() and 'escudo' not in img.lower() and 'flag' not in img.lower() and 'bandera' not in img.lower():
                img_url = img
                break
        urls[term_id] = img_url
        print(f"Found {query}: {img_url}")
    except Exception as e:
        print(f"Error {query}: {e}")

with open('region_urls.json', 'w') as f:
    json.dump(urls, f)
