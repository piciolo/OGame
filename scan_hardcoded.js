const fs = require('fs');
const path = require('path');

// Recursively find all blade.php files
function findFiles(dir, ext, results = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) findFiles(full, ext, results);
    else if (entry.name.endsWith(ext)) results.push(full);
  }
  return results;
}

// Patterns that indicate translated text (should be skipped)
const transPatterns = [
  /__\s*\(/,          // __('key')
  /trans\s*\(/,        // trans('key')
  /@lang\s*\(/,        // @lang('key')
  /\{\{\s*__\s*\(/,    // {{ __('key') }}
];

// Matches visible text content in HTML (outside of PHP/Blade tags)
// We look for text between > and < that contains letters
const results = {};
let totalHardcoded = 0;

const bladeFiles = findFiles('D:/ogame/resources/views', '.blade.php');

for (const file of bladeFiles) {
  const content = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
  const lines = content.split('\n');
  const fileResults = [];
  const relPath = file.replace(/\\/g, '/').replace('D:/ogame/', '');

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimmed = line.trim();

    // Skip empty lines, comments, pure PHP lines, pure blade directives
    if (!trimmed) continue;
    if (trimmed.startsWith('{{--')) continue;
    if (trimmed.startsWith('<?php') && trimmed.endsWith('?>')) continue;
    if (trimmed.startsWith('@if') || trimmed.startsWith('@else') || 
        trimmed.startsWith('@end') || trimmed.startsWith('@for') ||
        trimmed.startsWith('@php') || trimmed.startsWith('@section') ||
        trimmed.startsWith('@extends') || trimmed.startsWith('@include') ||
        trimmed.startsWith('@push') || trimmed.startsWith('@yield') ||
        trimmed.startsWith('@component') || trimmed.startsWith('@slot') ||
        trimmed.startsWith('@unless') || trimmed.startsWith('@isset') ||
        trimmed.startsWith('@empty') || trimmed.startsWith('@switch') ||
        trimmed.startsWith('@case') || trimmed.startsWith('@break') ||
        trimmed.startsWith('@default') || trimmed === '@endif' ||
        trimmed === '@endforeach' || trimmed === '@endfor' ||
        trimmed === '@endwhile' || trimmed === '@endisset' ||
        trimmed === '@endempty' || trimmed === '@endpush' ||
        trimmed === '@endsection' || trimmed === '@endcomponent') continue;

    // Skip lines that are purely JS/CSS
    if (trimmed.startsWith('var ') || trimmed.startsWith('let ') || 
        trimmed.startsWith('const ') || trimmed.startsWith('function ') ||
        trimmed.startsWith('//') || trimmed.startsWith('/*') || 
        trimmed.startsWith('*') || trimmed.startsWith('$.') ||
        trimmed.startsWith('$(') || trimmed.startsWith('axios.') ||
        trimmed.startsWith('console.') || trimmed.startsWith('window.') ||
        trimmed.startsWith('document.')) continue;

    // Remove Blade echo tags {{ ... }} and {!! ... !!} — these are translated
    let cleaned = line.replace(/\{\{.*?\}\}/g, '').replace(/\{!!.*?!!\}/g, '');
    // Remove PHP tags
    cleaned = cleaned.replace(/<\?php.*?\?>/g, '');
    // Remove HTML tags but keep inner text
    // Actually, let's look for text between > and < 
    
    // Find text content in HTML elements: >visible text<
    const textMatches = cleaned.match(/>\s*([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s',;:.!?()\-\/]{2,})\s*</g);
    if (textMatches) {
      for (const match of textMatches) {
        let text = match.replace(/^>\s*/, '').replace(/\s*<$/, '').trim();
        // Skip if it's just a single short word, number, or technical term
        if (text.length < 4) continue;
        // Skip CSS class-like, camelCase, or code-like strings
        if (/^[a-z]+[A-Z]/.test(text)) continue;
        if (/^(div|span|img|btn|col|row|href|src|alt|class|type|id|name|value|data)/.test(text)) continue;

        fileResults.push({ line: i + 1, text });
      }
    }

    // Also check for hardcoded title="..." and placeholder="..." attributes with text
    const attrMatches = cleaned.match(/(title|placeholder|alt|data-tooltip)\s*=\s*"([^"]*[A-Za-zÀ-ÿ]{3,}[^"]*)"/g);
    if (attrMatches) {
      for (const match of attrMatches) {
        // Skip if it contains {{ (already translated)
        if (match.includes('{{') || match.includes("__('")) continue;
        const textM = match.match(/=\s*"(.+)"/);
        if (textM) {
          const text = textM[1].trim();
          if (text.length < 4) continue;
          // Skip URLs and technical values
          if (text.startsWith('http') || text.startsWith('/') || text.startsWith('#')) continue;
          if (/^[a-z_]+$/.test(text)) continue; // CSS/ID identifiers
          fileResults.push({ line: i + 1, text: `[attr] ${match.substring(0, 60)}`, type: 'attr' });
        }
      }
    }
  }

  if (fileResults.length > 0) {
    results[relPath] = fileResults;
    totalHardcoded += fileResults.length;
  }
}

// Output report
console.log('=== HARDCODED TEXT SCAN REPORT ===');
console.log('Files scanned:', bladeFiles.length);
console.log('Files with hardcoded text:', Object.keys(results).length);
console.log('Total hardcoded strings found:', totalHardcoded);
console.log('');

// Sort by count descending
const sorted = Object.entries(results).sort((a, b) => b[1].length - a[1].length);

for (const [file, items] of sorted) {
  console.log(`\n--- ${file} (${items.length} strings) ---`);
  for (const item of items.slice(0, 15)) {
    console.log(`  L${item.line}: ${item.text.substring(0, 100)}`);
  }
  if (items.length > 15) console.log(`  ... and ${items.length - 15} more`);
}
