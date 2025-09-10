import bibtexparser
from pathlib import Path
import re

def ok(c):
    value = ord(c)
    return (value == 0x09 or value == 0x0A or value == 0x0D or
            (value >= 0x20 and value <= 0x7E) or
            (value == 0x85))

def make_ok(s):
    return "".join(filter(ok, s))

def fix(s):
    purged = make_ok(s)
    # Most of the replacements from the Scala script
    replacements = {
        '\\"{o}': '&ouml;', '\\"{a}': '&auml;', "\\'e": '&eacute;',
        "\\'{e}": '&eacute;', '\\`e': '&egrave;', '\\"o': '&ouml;',
        '\\`a': '&agrave;', "\\'a": '&aacute;', '\\`o': '&ograve;',
        "\\'o": '&oacute;', '\\`O': '&Ograve;', "\\'O": '&Oacute;',
        '\\"u': '&uuml;', "\\'E": '&Eacute;', '\\`E': '&Egrave;',
        '\\"O': '&Ouml;', '\\"A': '&Auml;', '\\"a': '&auml;',
        '\\"\\i': '&iuml;', '\\c': '&ccedil;', '\\`A': '&Agrave;',
        "\\'A": '&Aacute;', '\\"U': '&Uuml;', '\\o ': '&oslash;',
        '\\v s': '&#353;', '\\v c': '&#263;', '\\v d': '&#273;',
        '\\~n': '&ntilde;', '\\`{e}': '&egrave;', '\\"{o}': '&ouml;',
        '\\`{a}': '&agrave;', "\\'{a}": '&aacute;', '\\`{o}': '&ograve;',
        "\\'{o}": '&oacute;', '\\`{O}': '&Ograve;', "\\'{O}": '&Oacute;',
        '\\"{u}': '&uuml;', "\\'{E}": '&Eacute;', '\\`{E}': '&Egrave;',
        '\\"{O}': '&Ouml;', '\\"{A}': '&Auml;', '\\"{a}': '&auml;',
        '\\"\\i': '&iuml;', '\\c': '&ccedil;', '\\`{A}': '&Agrave;',
        "\\'{A}": '&Aacute;', '\\"{U}': '&Uuml;', '\\o ': '&oslash;',
        '\\v{s}': '&#353;', '\\v{c}': '&#263;', '\\v{d}': '&#273;',
        '\\v{z}': '&#382;', '\\v z': '&#382;',
        '--': '-', "\\'": '&#39;', "'": '&#39;',
        '\\ssf': '\\mathrm', '\\Cal': '\\mathcal', '\\bold': '\\boldsymbol',
        '\\H{o}': '&#337;', '\\H{a}': '&#225;', '\\H{A}': '&#193;',
        '\\H{e}': '&#233;', '\\H{E}': '&#201;', '\\H{u}': '&#250;',
        '\\H{U}': '&#218;', 'Szeg\\H o{}': 'Szeg&#337;'
    }
    for old, new in replacements.items():
        purged = purged.replace(old, new)

    parts = purged.split('$')
    debraced_parts = []
    for i, part in enumerate(parts):
        if i % 2 == 0:
            debraced_parts.append(part.replace("{", "").replace("}", ""))
        else:
            debraced_parts.append(part)
    return "$".join(debraced_parts)

def quick_fix(s):
    purged = make_ok(s)
    parts = purged.split('$')
    debraced_parts = []
    for i, part in enumerate(parts):
        if i % 2 == 0:
            debraced_parts.append(part.replace("{", "").replace("}", ""))
        else:
            debraced_parts.append(part)
    return "$".join(debraced_parts)

def main():
    data_dir = Path("_data")
    bib_file = data_dir / "publications.bib"
    pubs_yaml_file = data_dir / "pubs.yaml"
    publ_yaml_file = data_dir / "publ.yaml"
    extra_pubs_file = data_dir / "extrapubs.yaml"

    with open(bib_file, 'r', encoding='utf-8') as f:
        bib_database = bibtexparser.load(f)

    pubs_out = []
    simple_out = []

    for entry in bib_database.entries:
        pubs_entry_lines = []
        simple_entry_lines = []

        # Ensure there is an ID, otherwise skip
        if 'ID' not in entry:
            continue

        for key, value in entry.items():
            key_lower = key.lower()
            if key_lower in ["year", "url"]:
                pubs_entry_lines.append(f"  {key_lower}: '{value}'")
                simple_entry_lines.append(f"  {key_lower}: '{value}'")
            else:
                fixed_value = fix(value.replace("\n", " "))
                pubs_entry_lines.append(f"  {key_lower}: '{fixed_value}'")

                quick_fixed_value = quick_fix(value.replace("\n", " ").replace("'", "''"))
                simple_entry_lines.append(f"  {key_lower}: '{quick_fixed_value}'")

        pubs_out.append("- " + "\n  ".join(pubs_entry_lines))
        simple_out.append("- " + "\n  ".join(simple_entry_lines))

    with open(pubs_yaml_file, 'w', encoding='utf-8') as f:
        f.write("\n".join(pubs_out))
        f.write("\n")

    with open(publ_yaml_file, 'w', encoding='utf-8') as f:
        f.write("\n".join(simple_out))
        f.write("\n")

    if extra_pubs_file.exists():
        with open(extra_pubs_file, 'r', encoding='utf-8') as f:
            extra_content = f.read()

        with open(pubs_yaml_file, 'a', encoding='utf-8') as f:
            f.write("\n" + extra_content)

        with open(publ_yaml_file, 'a', encoding='utf-8') as f:
            f.write("\n" + extra_content)

if __name__ == "__main__":
    main()
