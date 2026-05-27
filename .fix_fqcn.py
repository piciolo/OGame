import sys, re

FQCN_PATTERN = re.compile(r'\\((?:OGame|Illuminate|Carbon)\\(?:[A-Z]\w*\\)*[A-Z]\w*)\b')
BUILTIN_CLASSES = ['Throwable', 'Exception', 'DateTime', 'Closure', 'ArrayAccess', 'Iterator', 'Countable',
                   'RuntimeException', 'LogicException', 'InvalidArgumentException', 'TypeError', 'ValueError',
                   'BadMethodCallException', 'OutOfBoundsException', 'OutOfRangeException', 'UnexpectedValueException',
                   'DomainException', 'DateTimeImmutable', 'DateTimeZone', 'DateInterval', 'DatePeriod']
BUILTIN_PATTERN = re.compile(r'\\(' + '|'.join(BUILTIN_CLASSES) + r')\b')

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    fqcns = set(FQCN_PATTERN.findall(content)) | set(BUILTIN_PATTERN.findall(content))
    if not fqcns:
        continue

    existing_uses = set()
    for m in re.finditer(r'^use\s+([\w\\]+)(?:\s+as\s+\w+)?;', content, re.MULTILINE):
        existing_uses.add(m.group(1))

    ns_match = re.search(r'^namespace\s+[\w\\]+;', content, re.MULTILINE)
    if not ns_match:
        continue

    new_uses = []
    for fqcn in sorted(fqcns):
        if fqcn in existing_uses:
            continue
        new_uses.append(fqcn)
        existing_uses.add(fqcn)

    for fqcn in fqcns:
        short = fqcn.rsplit('\\', 1)[-1]
        same_short = [u for u in existing_uses if u.rsplit('\\', 1)[-1] == short and u != fqcn]
        if same_short:
            continue
        content = re.sub(r'\\' + re.escape(fqcn) + r'\b', short, content)

    if new_uses:
        ns_end = ns_match.end()
        rest = content[ns_end:]
        m_first_use = re.search(r'\nuse\s+', rest)
        if m_first_use:
            insert_pos = ns_end + m_first_use.start() + 1
        else:
            insert_pos = ns_end + 1
        use_block = ''.join('use ' + u + ';\n' for u in sorted(new_uses))
        content = content[:insert_pos] + '\n' + use_block + content[insert_pos:]

    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print('Updated:', path)
