import re
from pathlib import Path
import bib2yaml

def get_entries(s):
    # A simple split by '@' is not robust for all BibTeX formats,
    # but it matches the logic in the Scala script.
    return ["@" + x for x in s.split("@")[1:]]

def get_mr_num(entry_string):
    match = re.search(r"MR[0-9]+", entry_string)
    if match:
        return int(match.group(0)[2:])
    return None

def get_entry_map(s):
    entries_list = get_entries(s)
    entry_map = {}
    for entry in entries_list:
        mr_num = get_mr_num(entry)
        if mr_num:
            entry_map[mr_num] = entry
    return entry_map

def view(entry_map):
    # Sort by MR number descending and return a single string
    sorted_entries = sorted(entry_map.items(), key=lambda item: item[0], reverse=True)
    return "\n".join([entry for _, entry in sorted_entries])

def main():
    data_dir = Path("_data")
    pub_file = data_dir / "publications.bib"
    new_pubs_file = Path("tmp") / "test.bib"

    if pub_file.exists():
        with open(pub_file, 'r', encoding='utf-8') as f:
            emap = get_entry_map(f.read())
    else:
        emap = {}

    if new_pubs_file.exists():
        with open(new_pubs_file, 'r', encoding='utf-8') as f:
            new_emap = get_entry_map(f.read())
        emap.update(new_emap)

    with open(pub_file, 'w', encoding='utf-8') as f:
        f.write(view(emap))

    # Run the bib2yaml script
    bib2yaml.main()

if __name__ == "__main__":
    main()
