import os
import json
import urllib.request
import sys

def get_api_key():
    key_path = os.path.join(os.path.dirname(__file__), '..', '..', '..', '..', '.secrets', 'openrouter.key')
    try:
        with open(key_path, 'r') as f:
            return f.read().strip()
    except Exception as e:
        print(f"Error reading API key: {e}")
        sys.exit(1)

def generate_geo_data():
    api_key = get_api_key()
    url = "https://openrouter.ai/api/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json"
    }

    prompt = """
Eres un asistente experto en la geografía política de Chile.
Genera un archivo JSON estricto con las 16 regiones de Chile y todas sus comunas, incluyendo la municipalidad correspondiente.
El formato debe ser EXACTAMENTE este (sin bloques markdown, solo texto JSON parseable):
{
  "regiones": [
    {
      "nombre": "Región Metropolitana",
      "comunas": [
        {
          "nombre": "Santiago",
          "municipalidad": {
            "nombre": "Municipalidad de Santiago",
            "sitio_web": "https://www.municipalidaddesantiago.cl"
          }
        }
      ]
    }
  ]
}
Asegúrate de incluir TODAS las 16 regiones y todas las comunas de Chile de forma exhaustiva (aprox 346 comunas).
NO incluyas bloques ```json. Empieza directamente con el carácter { y termina con }.
No respondas nada más, tu salida será parseada por json.loads() directamente en Python.
"""

    payload = {
        "model": "deepseek/deepseek-chat",
        "messages": [
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.0,
        "max_tokens": 8000
    }

    print("Solicitando datos a OpenRouter (deepseek-chat)...", file=sys.stderr)
    
    data_bytes = json.dumps(payload).encode('utf-8')
    req = urllib.request.Request(url, data=data_bytes, headers=headers)
    
    try:
        with urllib.request.urlopen(req) as response:
            resp_data = json.loads(response.read().decode('utf-8'))
    except Exception as e:
        print(f"Error en la API: {e}")
        sys.exit(1)

    content = resp_data['choices'][0]['message']['content'].strip()
    
    # Clean up if it returned markdown anyway
    if content.startswith("```json"):
        content = content[7:]
    if content.endswith("```"):
        content = content[:-3]
    content = content.strip()

    try:
        parsed = json.loads(content)
        with open("data/chile_geo_data.json", "w", encoding="utf-8") as f:
            json.dump(parsed, f, ensure_ascii=False, indent=2)
        print("JSON generado exitosamente en data/chile_geo_data.json")
    except Exception as e:
        print(f"Error parseando JSON: {e}")
        with open("error_output.txt", "w", encoding="utf-8") as f:
            f.write(content)
        sys.exit(1)

if __name__ == "__main__":
    generate_geo_data()
