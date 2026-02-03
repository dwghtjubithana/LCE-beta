import google.genai as genai
import os

# ------------------------------------------------------------------
# CONFIGURATIE
# LET OP: Zorg dat je API KEY hier weer in staat!
# ------------------------------------------------------------------
import os

API_KEY = os.getenv("GEMINI_API_KEY", "")
if not API_KEY:
    raise SystemExit("Missing GEMINI_API_KEY environment variable.")

def run_official_test():
    print("--- STAP 1: API Configureren ---")
    genai.configure(api_key=API_KEY)

    print("--- STAP 2: Beschikbare modellen ophalen... ---")
    valid_models = []
    try:
        # We vragen Google welke modellen deze sleutel mag gebruiken
        for m in genai.list_models():
            if 'generateContent' in m.supported_generation_methods:
                print(f"Beschikbaar: {m.name}")
                valid_models.append(m.name)
    except Exception as e:
        print(f"Fout bij ophalen modellen: {e}")
        return

    if not valid_models:
        print("CRITISCH: Geen enkel generatief model gevonden voor deze API Key.")
        return

    # We kiezen slim het beste model dat beschikbaar is
    # Voorkeur: Flash -> Pro -> De eerste die we vinden
    target_model = valid_models[0] # Fallback
    
    if 'models/gemini-1.5-flash' in valid_models:
        target_model = 'models/gemini-1.5-flash'
    elif 'models/gemini-pro' in valid_models:
        target_model = 'models/gemini-pro'

    print(f"\n--- STAP 3: Testen met model '{target_model}' ---")
    
    try:
        model = genai.GenerativeModel(target_model)
        response = model.generate_content("Reageer uitsluitend met het woord: CONNECTED")

        print("\n--- SUCCES! API RESPONSE ---")
        print(response.text)
        print("----------------------------")

    except Exception as e:
        print("\n--- CRITICAL ERROR ---")
        print(e)
        print("----------------------")

if __name__ == "__main__":
    run_official_test()
