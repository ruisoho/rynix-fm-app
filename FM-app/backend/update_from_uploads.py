import json
import os
import re
import html

# Configuration
UPLOADS_DIR = r'c:\Users\ruiso\Documents\trae_projects\FM\FM-app\uploads'
JSON_PATH = os.path.join(UPLOADS_DIR, 'germanlaws.json')

def clean_html(raw_html):
    """Remove HTML tags and clean up whitespace."""
    # Replace <br> with newline
    text = re.sub(r'<br\s*/?>', '\n', raw_html, flags=re.IGNORECASE)
    # Remove all other tags
    text = re.sub(r'<[^>]+>', '', text)
    # Unescape HTML entities
    text = html.unescape(text)
    # Normalize whitespace (but keep newlines)
    lines = [line.strip() for line in text.split('\n')]
    text = '\n'.join(line for line in lines if line)
    return text

def parse_law_html(file_path):
    """Parse the specific HTML format from gesetze-im-internet exports."""
    try:
        with open(file_path, 'r', encoding='cp1252', errors='replace') as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return []

    # Split into norms/sections
    # Format: <div class="jnnorm" ...>
    chunks = content.split('<div class="jnnorm"')
    
    sections = []
    
    for chunk in chunks[1:]: # Skip preamble
        # Extract Title/Section Number
        # <span class="jnenbez">§ 1</span>
        match_bez = re.search(r'class="jnenbez">([^<]+)</span>', chunk)
        # <span class="jnentitel">Zielsetzung und Anwendungsbereich</span>
        match_titel = re.search(r'class="jnentitel">([^<]+)</span>', chunk)
        
        if match_bez:
            sec_num = clean_html(match_bez.group(1))
            sec_title = clean_html(match_titel.group(1)) if match_titel else ""
            
            # Extract Content
            # <div class="jurAbsatz">...</div>
            # There can be multiple paragraphs
            paragraphs = re.findall(r'class="jurAbsatz">(.*?)</div>', chunk, re.DOTALL)
            
            full_content = "\n".join([clean_html(p) for p in paragraphs])
            
            if full_content.strip():
                sections.append({
                    "section": sec_num,
                    "title": sec_title,
                    "content": full_content
                })
                
    return sections

def main():
    print("Loading germanlaws.json...")
    with open(JSON_PATH, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    laws_map = {law.get('abbreviation', '').lower(): law for law in data['laws']}
    
    files = os.listdir(UPLOADS_DIR)
    html_files = [f for f in files if f.endswith('.htm') or f.endswith('.html')]
    
    updated_count = 0
    
    print(f"Found {len(html_files)} HTML files to process.")
    
    for filename in html_files:
        # Extract abbreviation from filename
        # Format: "Abbreviation - Full Name.htm"
        # e.g., "ArbSchG - Gesetz....htm"
        parts = filename.split(' - ')
        if len(parts) > 0:
            abbr = parts[0].strip()
            # Handle special cases if any (e.g. "GefStoffV" might be "GefStoffV - ...")
            
            # Normalize for matching
            abbr_key = abbr.lower()
            
            if abbr_key in laws_map:
                print(f"Processing {abbr}...")
                sections = parse_law_html(os.path.join(UPLOADS_DIR, filename))
                
                if sections:
                    law = laws_map[abbr_key]
                    law['full_text'] = sections
                    # Also update PDF link if we want to point to local HTML? No, user asked for PDF extraction.
                    # But we can update the source status
                    updated_count += 1
                    print(f"  -> Updated {len(sections)} sections for {abbr}")
                else:
                    print(f"  -> No sections found for {abbr} (Check parser)")
            else:
                print(f"Skipping {filename}: Abbreviation '{abbr}' not found in JSON.")
                
    if updated_count > 0:
        print("Saving updated germanlaws.json...")
        with open(JSON_PATH, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=4, ensure_ascii=False)
        print("Done.")
    else:
        print("No laws updated.")

if __name__ == "__main__":
    main()
