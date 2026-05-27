import sys, re

for path in sys.argv[1:]:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except (FileNotFoundError, UnicodeDecodeError, IsADirectoryError, PermissionError):
        continue
    orig = content

    # Find protected $fillable = [...] block (multiline)
    m = re.search(r'(\s*)protected\s+\$fillable\s*=\s*(\[[^\]]+\])\s*;', content, re.DOTALL)
    if not m:
        continue
    indent = m.group(1).strip('\n')
    fillable_array = m.group(2)
    full_match = m.group(0)

    # Add use Illuminate\Database\Eloquent\Attributes\Fillable; if missing
    if 'use Illuminate\\Database\\Eloquent\\Attributes\\Fillable;' not in content:
        # Add after first 'use Illuminate' line
        content = re.sub(
            r'(use Illuminate\\Database\\Eloquent\\Model;)',
            r'use Illuminate\\Database\\Eloquent\\Attributes\\Fillable;\n\1',
            content,
            count=1
        )

    # Remove the protected $fillable = [...]; block
    content = content.replace(full_match, '')

    # Add #[Fillable([...])] attribute right before the class declaration
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
