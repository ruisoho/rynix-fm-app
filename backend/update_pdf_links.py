import json
import os
import re

# Configuration
UPLOADS_DIR = r'c:\Users\ruiso\Documents\trae_projects\FM\FM-app\uploads'
JSON_PATH = os.path.join(UPLOADS_DIR, 'germanlaws.json')

def main():
    print("Loading germanlaws.json...")
    with open(JSON_PATH, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    laws = data['laws']
    files = os.listdir(UPLOADS_DIR)
    pdf_files = [f for f in files if f.lower().endswith('.pdf')]
    
    updated_count = 0
    
    # Better matching logic
    for law in laws:
        # Get identifier candidates
        abbr = law.get('abbreviation', '').strip().lower()
        name = law.get('name', '').strip().lower()
        
        # Create normalized versions for matching
        # e.g. "ASR A1.2" -> "asr-a1-2"
        name_norm = re.sub(r'[\s\.]+', '-', name)
        abbr_norm = re.sub(r'[\s\.]+', '-', abbr)
        
        match = None
        
        for pdf in pdf_files:
            pdf_lower = pdf.lower()
            
            # 1. Exact Abbreviation Match (e.g. MBO.pdf -> MBO)
            if abbr and pdf_lower == f"{abbr}.pdf":
                match = pdf
                break
                
            # 2. Starts with Abbreviation (e.g. DGUV vorschrift 2.pdf -> DGUV...)
            # Only if abbr is substantial (>2 chars) to avoid false positives
            if abbr and len(abbr) > 2 and pdf_lower.startswith(abbr):
                 # Verify it's followed by space or separator to avoid prefix matches like "Arb" matching "ArbSchG"
                 if pdf_lower.startswith(f"{abbr} ") or pdf_lower.startswith(f"{abbr}-") or pdf_lower.startswith(f"{abbr}_"):
                     match = pdf
                     break
            
            # 3. Name Match in Filename
            # e.g. "ASR A1.2" in "ASR-A1-2-Aenderungen..."
            # Check original name
            if name and name in pdf_lower:
                match = pdf
                break
                
            # Check normalized name (replace dots/spaces with hyphens)
            if name and name_norm in pdf_lower:
                match = pdf
                break
                
        if match:
            # Update the link
            new_link = f"../uploads/{match}"
            
            if law.get('pdf_download') != new_link:
                law['pdf_download'] = new_link
                law_id = law.get('abbreviation') or law.get('name') or law.get('id')
                print(f"Updated PDF link for {law_id} -> {match}")
                updated_count += 1
        else:
            # If no match found, but current link points to uploads, clear it (likely invalid from previous run)
            current_link = law.get('pdf_download', '')
            if current_link and current_link.startswith('../uploads/'):
                print(f"Removing invalid local PDF link for {law.get('name', 'Unknown')}: {current_link}")
                law['pdf_download'] = ""
                updated_count += 1
                
    if updated_count > 0:
        print("Saving updated germanlaws.json...")
        with open(JSON_PATH, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=4, ensure_ascii=False)
        print("Done.")
    else:
        print("No PDF links updated.")

if __name__ == "__main__":
    main()
