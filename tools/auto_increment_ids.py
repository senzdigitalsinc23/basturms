#!/usr/bin/env python3
"""Make `id` columns AUTO_INCREMENT PRIMARY KEY in a SQL dump.

Usage:
  python tools/auto_increment_ids.py input.sql [--in-place]

If --in-place is given the file will be overwritten (a backup with .bak is created).
Otherwise the modified SQL is printed to stdout.
"""
import argparse
import re
from pathlib import Path


def transform(sql: str) -> str:
    # Robust line-based parser: iterate lines and only modify within CREATE TABLE blocks
    out_lines = []
    inside_create = False
    paren_depth = 0
    for line in sql.splitlines(keepends=True):
        lstrip = line.lstrip()
        if not inside_create:
            # detect start of CREATE TABLE
            if re.match(r"^CREATE\s+TABLE\b", lstrip, re.IGNORECASE):
                inside_create = True
                # count parentheses on this line
                paren_depth += line.count("(") - line.count(")")
                out_lines.append(line)
                continue
            out_lines.append(line)
        else:
            # update paren depth
            paren_depth += line.count("(") - line.count(")")

            # If this looks like an id column definition, update it
            m = re.match(r"^(\s*`?id`?\s+[^,\n]*)(,?\s*)(--.*)?$", line, re.IGNORECASE)
            if m:
                col_def = m.group(1)
                comma_space = m.group(2) or ""
                comment = m.group(3) or ""
                new_col = col_def
                if "AUTO_INCREMENT" not in new_col.upper():
                    new_col = new_col.rstrip() + " AUTO_INCREMENT"
                if "PRIMARY KEY" not in new_col.upper():
                    new_col = new_col.rstrip() + " PRIMARY KEY"
                new_line = new_col + comma_space + (" " + comment if comment else "")
                out_lines.append(new_line + ("\n" if not new_line.endswith("\n") else ""))
            else:
                out_lines.append(line)

            # end of CREATE TABLE when paren depth <= 0 and line contains ");"
            if paren_depth <= 0 and re.search(r"\)\s*;", line):
                inside_create = False
                paren_depth = 0

    result = "".join(out_lines)

    # Remove separate PRIMARY KEY (`id`) definitions to avoid duplicates
    result = re.sub(r",?\s*PRIMARY\s+KEY\s*\(\s*`?id`?\s*\)\s*,?", ",", result, flags=re.IGNORECASE)
    # Clean up possible leftover double-commas
    result = re.sub(r",\s*,", ",", result)
    return result


def main():
    p = argparse.ArgumentParser(description="Add AUTO_INCREMENT PRIMARY KEY to id columns in SQL dump")
    p.add_argument("input", help="Input SQL file")
    p.add_argument("--in-place", action="store_true", help="Overwrite input file (creates .bak)")
    args = p.parse_args()

    path = Path(args.input)
    sql = path.read_text(encoding="utf-8")
    out = transform(sql)

    if args.in_place:
        bak = path.with_suffix(path.suffix + ".bak")
        bak.write_text(sql, encoding="utf-8")
        path.write_text(out, encoding="utf-8")
        print(f"Updated in place. Backup written to {bak}")
    else:
        print(out)


if __name__ == "__main__":
    main()
