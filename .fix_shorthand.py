import sys, re

TYPE_RE = r'(?:\\)?\w+(?:\\\w+)*'

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content
    content = re.sub(r'\?(' + TYPE_RE + r')(\s+\$\w)', r'\1|null\2', content)
    content = re.sub(r'(:\s*)\?(' + TYPE_RE + r')(\s*[{\n;])', r'\1\2|null\3', content)
    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
