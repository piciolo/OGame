import sys, re

BS = chr(92)

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    m = re.search(r"\s*protected\s+\$table\s*=\s*'([^']+)'\s*;\s*\n", content)
    if not m:
        continue
    table_name = m.group(1)
    full_match = m.group(0)

    use_table_decl = 'use Illuminate' + BS + 'Database' + BS + 'Eloquent' + BS + 'Attributes' + BS + 'Table;'
    use_model_decl = 'use Illuminate' + BS + 'Database' + BS + 'Eloquent' + BS + 'Model;'

    if use_table_decl not in content:
        if use_model_decl in content:
            content = content.replace(use_model_decl, use_table_decl + '\n' + use_model_decl, 1)
        else:
            ns_match = re.search(r'^namespace\s+[^;]+;', content, re.MULTILINE)
            if ns_match:
                end = ns_match.end()
                content = content[:end] + '\n\n' + use_table_decl + content[end:]

    content = content.replace(full_match, '\n')

    content = re.sub(
        r'(\nclass\s+\w+\s+extends\s+\w+)',
        "\n#[Table(name: '" + table_name + "')]\\1",
        content,
        count=1
    )

    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
