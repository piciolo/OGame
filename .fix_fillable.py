import sys, re

BS = chr(92)

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    m = re.search(r'(\s*)protected\s+\$fillable\s*=\s*(\[[^\]]+\])\s*;', content, re.DOTALL)
    if not m:
        continue
    fillable_array = m.group(2)
    full_match = m.group(0)

    use_fillable = 'use Illuminate' + BS + 'Database' + BS + 'Eloquent' + BS + 'Attributes' + BS + 'Fillable;'
    use_model = 'use Illuminate' + BS + 'Database' + BS + 'Eloquent' + BS + 'Model;'

    if use_fillable not in content:
        if use_model in content:
            content = content.replace(use_model, use_fillable + '\n' + use_model, 1)
        else:
            ns_match = re.search(r'^namespace\s+[^;]+;', content, re.MULTILINE)
            if ns_match:
                end = ns_match.end()
                content = content[:end] + '\n\n' + use_fillable + content[end:]

    content = content.replace(full_match, '')

    content = re.sub(
        r'(\nclass\s+\w+\s+extends\s+\w+)',
        f'\n#[Fillable({fillable_array})]\\1',
        content,
        count=1
    )

    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
