import sys, re

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    if '\\Illuminate\\Support\\Carbon' in content:
        if 'use Illuminate\\Support\\Carbon;' not in content:
            content = re.sub(
                r'(namespace\s+[^\n;]+;\s*\n\s*\n?)',
                r'\1use Illuminate\\Support\\Carbon;\n',
                content,
                count=1
            )
        content = content.replace('\\Illuminate\\Support\\Carbon', 'Carbon')

    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
