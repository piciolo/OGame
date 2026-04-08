/**
 * Scan Missing Translations
 * ==========================
 * Scansiona i file Blade per trovare testi hardcoded e li confronta con le chiavi
 * di traduzione esistenti. Genera un report con:
 * - Stringhe hardcoded che GIA' hanno una chiave di traduzione (da sostituire)
 * - Stringhe hardcoded che NON hanno una chiave (da creare)
 *
 * USO: node scan_missing_translations.js
 */

const fs = require('fs');
const path = require('path');

// ============================================================================
// 1. Carica tutte le chiavi di traduzione EN esistenti
// ============================================================================

const langDir = path.join(__dirname, 'resources', 'lang', 'en');
const translations = {};
let totalKeys = 0;

// Parse PHP translation arrays recursively
function parsePHPArray(content) {
  const result = {};

  // Match 'key' => 'value' patterns
  const singlePattern = /'([^']+)'\s*=>\s*'((?:[^'\\]|\\.|`[^']*)*?)'/g;
  let match;
  while ((match = singlePattern.exec(content)) !== null) {
    result[match[1]] = match[2].replace(/\\'/g, "'");
  }

  // Match 'key' => [...] nested arrays
  const nestedPattern = /'([^']+)'\s*=>\s*\[/g;
  while ((match = nestedPattern.exec(content)) !== null) {
    // Find the matching closing bracket
    let depth = 1;
    let pos = match.index + match[0].length;
    let start = pos;
    while (depth > 0 && pos < content.length) {
      if (content[pos] === '[') depth++;
      if (content[pos] === ']') depth--;
      pos++;
    }
    const nested = content.substring(start, pos - 1);
    const nestedResult = parsePHPArray(nested);
    if (Object.keys(nestedResult).length > 0) {
      result[match[1]] = nestedResult;
    }
  }

  return result;
}

// Flatten nested keys with dot notation
function flattenKeys(obj, prefix = '') {
  const keys = {};
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === 'object' && !Array.isArray(value)) {
      Object.assign(keys, flattenKeys(value, fullKey));
    } else {
      keys[fullKey] = value;
    }
  }
  return keys;
}

// Load all translation files
const langFiles = fs.readdirSync(langDir).filter(f => f.endsWith('.php'));
for (const file of langFiles) {
  const content = fs.readFileSync(path.join(langDir, file), 'utf8');
  const baseName = file.replace('.php', '');
  const parsed = parsePHPArray(content);
  const flat = flattenKeys(parsed, baseName);
  Object.assign(translations, flat);
  totalKeys += Object.keys(flat).length;
}

console.log(`\nLoaded ${totalKeys} translation keys from ${langFiles.length} files\n`);

// Build reverse lookup: English value -> translation key(s)
const valueToKeys = {};
for (const [key, value] of Object.entries(translations)) {
  if (typeof value !== 'string') continue;
  const normalized = value.toLowerCase().trim();
  if (!valueToKeys[normalized]) valueToKeys[normalized] = [];
  valueToKeys[normalized].push(key);
}

// ============================================================================
// 2. Scansiona i file Blade per testi hardcoded
// ============================================================================

function findFiles(dir, ext, results = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) findFiles(full, ext, results);
    else if (entry.name.endsWith(ext)) results.push(full);
  }
  return results;
}

const bladeFiles = findFiles(path.join(__dirname, 'resources', 'views'), '.blade.php');
const hardcodedStrings = [];

for (const file of bladeFiles) {
  const content = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
  const lines = content.split('\n');
  const relPath = file.replace(/\\/g, '/').replace(path.resolve(__dirname).replace(/\\/g, '/') + '/', '');

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimmed = line.trim();

    // Skip empty, comments, pure directives
    if (!trimmed) continue;
    if (trimmed.startsWith('{{--')) continue;
    if (trimmed.startsWith('@if') || trimmed.startsWith('@else') ||
        trimmed.startsWith('@end') || trimmed.startsWith('@for') ||
        trimmed.startsWith('@php') || trimmed.startsWith('@section') ||
        trimmed.startsWith('@extends') || trimmed.startsWith('@include') ||
        trimmed.startsWith('@push') || trimmed.startsWith('@yield') ||
        trimmed.startsWith('@component') || trimmed.startsWith('@slot') ||
        trimmed.startsWith('@unless') || trimmed.startsWith('@isset') ||
        trimmed.startsWith('@empty') || trimmed.startsWith('@switch') ||
        trimmed.startsWith('@case') || trimmed.startsWith('@break') ||
        trimmed.startsWith('@default') || trimmed.startsWith('@stack') ||
        trimmed.startsWith('@props') || trimmed.startsWith('@class') ||
        trimmed.startsWith('@checked') || trimmed.startsWith('@selected') ||
        trimmed.startsWith('@csrf') || trimmed.startsWith('@method')) continue;

    // Skip lines that are purely JS/CSS
    if (trimmed.startsWith('var ') || trimmed.startsWith('let ') ||
        trimmed.startsWith('const ') || trimmed.startsWith('function ') ||
        trimmed.startsWith('//') || trimmed.startsWith('/*') ||
        trimmed.startsWith('*') || trimmed.startsWith('$.') ||
        trimmed.startsWith('$(') || trimmed.startsWith('axios.') ||
        trimmed.startsWith('console.') || trimmed.startsWith('window.') ||
        trimmed.startsWith('document.')) continue;

    // Remove Blade echo tags and PHP
    let cleaned = line.replace(/\{\{.*?\}\}/g, '').replace(/\{!!.*?!!\}/g, '');
    cleaned = cleaned.replace(/<\?php.*?\?>/g, '');

    // Find text between > and <
    const textMatches = cleaned.match(/>\s*([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s',;:.!?()\-\/&]{2,})\s*</g);
    if (textMatches) {
      for (const match of textMatches) {
        let text = match.replace(/^>\s*/, '').replace(/\s*<$/, '').trim();
        if (text.length < 3) continue;
        if (/^[a-z]+[A-Z]/.test(text)) continue; // camelCase
        if (/^(div|span|img|btn|col|row|href|src|alt|class|type|id|name|value|data)/.test(text)) continue;

        hardcodedStrings.push({
          file: relPath,
          line: i + 1,
          text: text,
          type: 'html_text'
        });
      }
    }

    // Check hardcoded attribute values
    const attrMatches = cleaned.match(/(title|placeholder|alt|data-tooltip)\s*=\s*"([^"]*[A-Za-zÀ-ÿ]{3,}[^"]*)"/g);
    if (attrMatches) {
      for (const match of attrMatches) {
        if (match.includes('{{') || match.includes("__('")) continue;
        const textM = match.match(/=\s*"(.+)"/);
        if (textM) {
          const text = textM[1].trim();
          if (text.length < 3) continue;
          if (text.startsWith('http') || text.startsWith('/') || text.startsWith('#')) continue;
          if (/^[a-z_]+$/.test(text)) continue;
          hardcodedStrings.push({
            file: relPath,
            line: i + 1,
            text: text,
            type: 'attribute'
          });
        }
      }
    }

    // Check JS strings in inline scripts (alert, confirm, text content)
    if (trimmed.includes("'") || trimmed.includes('"')) {
      const jsStringMatches = cleaned.match(/(?:alert|confirm|text|html|textContent|innerHTML)\s*[=(]\s*['"]([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s',;:.!?()\-\/]{4,})['"]/g);
      if (jsStringMatches) {
        for (const match of jsStringMatches) {
          const textM = match.match(/['"]([A-Za-zÀ-ÿ][^'"]{4,})['"]/);
          if (textM) {
            hardcodedStrings.push({
              file: relPath,
              line: i + 1,
              text: textM[1].trim(),
              type: 'js_inline'
            });
          }
        }
      }
    }
  }
}

// ============================================================================
// 3. Cross-reference con traduzioni esistenti
// ============================================================================

const withTranslation = [];    // Hardcoded ma hanno già una chiave
const withoutTranslation = []; // Hardcoded e MANCANO dalla traduzione
const seen = new Set();        // Deduplicate

for (const item of hardcodedStrings) {
  const normalized = item.text.toLowerCase().trim();

  // Skip duplicates
  const dedupeKey = `${item.file}:${normalized}`;
  if (seen.has(dedupeKey)) continue;
  seen.add(dedupeKey);

  // Look up in translations
  const matchedKeys = valueToKeys[normalized];
  if (matchedKeys) {
    item.translation_keys = matchedKeys;
    withTranslation.push(item);
  } else {
    // Try partial match (the translation value contains the hardcoded text)
    const partialMatches = [];
    for (const [key, value] of Object.entries(translations)) {
      if (typeof value !== 'string') continue;
      if (value.toLowerCase().includes(normalized) && value.length < normalized.length * 2) {
        partialMatches.push(key);
      }
    }
    if (partialMatches.length > 0) {
      item.possible_keys = partialMatches;
      withTranslation.push(item);
    } else {
      withoutTranslation.push(item);
    }
  }
}

// ============================================================================
// 4. Output report
// ============================================================================

console.log('='.repeat(70));
console.log('  MISSING TRANSLATIONS REPORT');
console.log('='.repeat(70));
console.log(`  Blade files scanned: ${bladeFiles.length}`);
console.log(`  Total hardcoded strings: ${hardcodedStrings.length} (deduplicated)`);
console.log(`  With existing translation: ${withTranslation.length}`);
console.log(`  WITHOUT translation (need to create): ${withoutTranslation.length}`);
console.log('='.repeat(70));

// Group by file
function groupByFile(items) {
  const groups = {};
  for (const item of items) {
    if (!groups[item.file]) groups[item.file] = [];
    groups[item.file].push(item);
  }
  return groups;
}

console.log('\n\n--- STRINGS WITH EXISTING TRANSLATIONS (just need __() wrapper) ---\n');
const withGroups = groupByFile(withTranslation);
for (const [file, items] of Object.entries(withGroups).sort((a, b) => b[1].length - a[1].length)) {
  console.log(`\n  ${file} (${items.length} strings)`);
  for (const item of items.slice(0, 20)) {
    const keys = item.translation_keys || item.possible_keys || [];
    console.log(`    L${item.line} [${item.type}]: "${item.text.substring(0, 50)}" => ${keys[0] || '?'}`);
  }
  if (items.length > 20) console.log(`    ... and ${items.length - 20} more`);
}

console.log('\n\n--- STRINGS WITHOUT TRANSLATION (need to grab from OGame) ---\n');
const withoutGroups = groupByFile(withoutTranslation);
for (const [file, items] of Object.entries(withoutGroups).sort((a, b) => b[1].length - a[1].length)) {
  console.log(`\n  ${file} (${items.length} strings)`);
  for (const item of items.slice(0, 20)) {
    console.log(`    L${item.line} [${item.type}]: "${item.text.substring(0, 80)}"`);
  }
  if (items.length > 20) console.log(`    ... and ${items.length - 20} more`);
}

// ============================================================================
// 5. Genera file JSON con le stringhe mancanti (per il grabber)
// ============================================================================

const missingTextsForGrabber = withoutTranslation.map(item => ({
  text: item.text,
  file: item.file,
  line: item.line,
  type: item.type
}));

// Unique texts only
const uniqueMissing = [...new Map(missingTextsForGrabber.map(i => [i.text, i])).values()];

const outputFile = path.join(__dirname, 'missing_translations.json');
fs.writeFileSync(outputFile, JSON.stringify({
  generated: new Date().toISOString(),
  total_missing: uniqueMissing.length,
  texts: uniqueMissing.sort((a, b) => a.text.localeCompare(b.text))
}, null, 2));

console.log(`\n\nMissing translations saved to: ${outputFile}`);
console.log(`Total unique missing texts: ${uniqueMissing.length}`);
console.log('\nUse grab_ogame_ui_texts.js on the OGame site to capture these texts in Italian.');
