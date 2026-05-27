import sys, re

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    # Remove orphan PHPDoc: /** @var list<string> */ followed by another /** ... */
    # Pattern: /** ... @var list<string> ... */ followed only by whitespace and another /**
    content = re.sub(
        r'\n\s*/\*\*\s*\n\s*\*\s*@var\s+list<string>\s*\n\s*\*/\s*\n(?=\s*/\*\*)',
        '\n',
        content
    )

    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
