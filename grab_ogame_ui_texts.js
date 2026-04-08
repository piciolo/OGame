/**
 * OGame UI Text Grabber
 * =====================
 * Script da eseguire nella console del browser mentre si è loggati su OGame.
 * Naviga automaticamente le pagine principali e raccoglie tutti i testi UI visibili.
 *
 * USO:
 * 1. Accedi al tuo account OGame (server italiano per testi IT)
 * 2. Apri la console del browser (F12 → Console)
 * 3. Incolla e esegui questo script
 * 4. Attendi che finisca (naviga le pagine automaticamente)
 * 5. Il risultato JSON viene scaricato automaticamente
 *
 * NOTA: Eseguire su ogni lingua necessaria (IT, NL, EN, etc.)
 */

(async function grabOGameUITexts() {
  'use strict';

  const results = {
    timestamp: new Date().toISOString(),
    server: window.location.hostname,
    language: document.documentElement.lang || 'unknown',
    pages: {}
  };

  // Helper: attende il caricamento della pagina
  function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  // Helper: estrae testi visibili da un elemento
  function getVisibleText(el) {
    const style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
      return null;
    }
    return el.textContent.trim();
  }

  // ==========================================================================
  // 1. LAYOUT — Elementi comuni presenti su tutte le pagine
  // ==========================================================================
  function grabLayout() {
    const layout = {};

    // Menu di navigazione principale
    const menuLinks = document.querySelectorAll('#menuTable .menubutton, #menuTable a span');
    menuLinks.forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) layout[`menu_item_${i}`] = text;
    });

    // Topbar / header elements
    const topbarItems = document.querySelectorAll('.OGameClock, #bar .bar_item, .tutorialIcon, .comm_menu');
    topbarItems.forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) layout[`topbar_${i}`] = text;
    });

    // Resource bar labels
    const resourceLabels = document.querySelectorAll('#resourcesbarcomponent .resourceIcon');
    resourceLabels.forEach(el => {
      const title = el.getAttribute('title') || el.closest('[title]')?.getAttribute('title');
      if (title) layout[`resource_${el.className}`] = title;
    });

    // Planet list labels
    const planetNames = document.querySelectorAll('#planetList .planet-name, .smallplanet .planet-name');
    planetNames.forEach((el, i) => {
      layout[`planet_name_${i}`] = el.textContent.trim();
    });

    // Tooltips from title attributes on main elements
    document.querySelectorAll('#links [title], #planetList [title], #bar [title], #resourcesbarcomponent [title]').forEach((el, i) => {
      const title = el.getAttribute('title');
      if (title && title.length > 2 && !/^\d+$/.test(title) && !title.startsWith('http')) {
        layout[`tooltip_${i}`] = title;
      }
    });

    // Officers bar
    document.querySelectorAll('#officers .officers_tooltip, #officers [title]').forEach((el, i) => {
      const title = el.getAttribute('title') || el.textContent.trim();
      if (title && title.length > 2) layout[`officer_${i}`] = title;
    });

    return layout;
  }

  // ==========================================================================
  // 2. Grab generico per qualsiasi pagina corrente
  // ==========================================================================
  function grabCurrentPage() {
    const pageData = {};

    // Page title / heading
    const pageTitle = document.querySelector('#inhalt h2, .content-box-s h3, #content .content h2');
    if (pageTitle) pageData.page_title = pageTitle.textContent.trim();

    // All visible text in main content area
    const contentArea = document.querySelector('#inhalt, #content, .maincontent');
    if (!contentArea) return pageData;

    // Buttons
    contentArea.querySelectorAll('button, .btn, input[type="submit"], input[type="button"], a.btn_blue, a.btn_dark, .build-it, .upgrade, .build-it_wrap').forEach((el, i) => {
      const text = el.textContent.trim() || el.value || el.getAttribute('title');
      if (text && text.length > 1) pageData[`btn_${i}`] = text;
    });

    // Labels (th, label, dt, strong with text)
    contentArea.querySelectorAll('th, label, dt, .label, .info_label').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text && text.length > 1 && !/^\d+[\d.,]*$/.test(text)) {
        pageData[`label_${i}`] = text;
      }
    });

    // Tab headers
    contentArea.querySelectorAll('.tabs_wrap li, .tab_inner, .tablinks, .ajax_tab').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) pageData[`tab_${i}`] = text;
    });

    // Tooltips and titles in content
    contentArea.querySelectorAll('[title]').forEach((el, i) => {
      const title = el.getAttribute('title');
      if (title && title.length > 3 && !title.startsWith('http') && !title.startsWith('/') && !/^\d+$/.test(title)) {
        pageData[`title_attr_${i}`] = title;
      }
    });

    // Data-tooltip attributes
    contentArea.querySelectorAll('[data-tooltip], [data-tooltip-title]').forEach((el, i) => {
      const tip = el.getAttribute('data-tooltip') || el.getAttribute('data-tooltip-title');
      if (tip && tip.length > 3) pageData[`data_tooltip_${i}`] = tip;
    });

    // Select options
    contentArea.querySelectorAll('select option').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text && text.length > 1 && !/^\d+$/.test(text)) {
        pageData[`option_${i}`] = text;
      }
    });

    // Placeholder attributes
    contentArea.querySelectorAll('[placeholder]').forEach((el, i) => {
      const ph = el.getAttribute('placeholder');
      if (ph && ph.length > 2) pageData[`placeholder_${i}`] = ph;
    });

    // Alt attributes on images
    contentArea.querySelectorAll('img[alt]').forEach((el, i) => {
      const alt = el.getAttribute('alt');
      if (alt && alt.length > 2 && !/^\d+$/.test(alt)) {
        pageData[`img_alt_${i}`] = alt;
      }
    });

    // Paragraphs and text blocks
    contentArea.querySelectorAll('p, .info, .description, .notice, .warning, .error-box, .success-box').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text && text.length > 5 && text.length < 500) {
        pageData[`text_${i}`] = text;
      }
    });

    // Table headers
    contentArea.querySelectorAll('table th, table .header').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text && text.length > 1) pageData[`table_header_${i}`] = text;
    });

    // Spans with direct text (no child elements)
    contentArea.querySelectorAll('span').forEach((el, i) => {
      if (el.children.length === 0) {
        const text = el.textContent.trim();
        if (text && text.length > 2 && text.length < 100 && !/^\d+[\d.,]*$/.test(text) && !/^[a-z_]+$/.test(text)) {
          pageData[`span_${i}`] = text;
        }
      }
    });

    return pageData;
  }

  // ==========================================================================
  // 3. Grab specifico per dialog/overlay (se aperti)
  // ==========================================================================
  function grabDialogs() {
    const dialogs = {};
    document.querySelectorAll('.ui-dialog, .overlay, .tpd-content, .tooltip, #technologydetails').forEach((el, i) => {
      const texts = [];
      el.querySelectorAll('*').forEach(child => {
        if (child.children.length === 0) {
          const text = child.textContent.trim();
          if (text && text.length > 1 && text.length < 300 && !/^\d+[\d.,]*$/.test(text)) {
            texts.push(text);
          }
        }
      });
      if (texts.length > 0) {
        dialogs[`dialog_${i}`] = [...new Set(texts)];
      }
    });
    return dialogs;
  }

  // ==========================================================================
  // 4. Grab specifico per pagine conosciute
  // ==========================================================================

  // Fleet page specifics
  function grabFleetPage() {
    const fleet = {};

    // Ship names and counts
    document.querySelectorAll('#military, #civil, #battleships, #civilships').forEach(section => {
      section.querySelectorAll('li, .technology').forEach((el, i) => {
        const name = el.querySelector('.name, .technologyName, .tooltip')?.textContent?.trim();
        const title = el.getAttribute('title') || el.querySelector('[title]')?.getAttribute('title');
        if (name) fleet[`ship_name_${i}`] = name;
        if (title) fleet[`ship_title_${i}`] = title;
      });
    });

    // Mission types in fleet dispatch
    document.querySelectorAll('#missions .missionName, #missions [data-mission], .mission_select option').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) fleet[`mission_${i}`] = text;
    });

    // Speed selector
    document.querySelectorAll('#speedPercentage option, .speed_row option').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) fleet[`speed_${i}`] = text;
    });

    // Fleet movement labels
    document.querySelectorAll('.eventFleet .tooltip, .eventFleet td').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text && text.length > 1) fleet[`movement_${i}`] = text;
    });

    return fleet;
  }

  // Galaxy page specifics
  function grabGalaxyPage() {
    const galaxy = {};

    document.querySelectorAll('#galaxyContent th, #galaxyContent .info, .galaxyRow [title]').forEach((el, i) => {
      const text = el.textContent?.trim() || el.getAttribute('title');
      if (text && text.length > 1 && text.length < 200) galaxy[`galaxy_${i}`] = text;
    });

    // Context menu items (right-click actions)
    document.querySelectorAll('.dropdown-menu li, .galaxy_tooltip, .galaxyCell [title]').forEach((el, i) => {
      const text = el.textContent?.trim() || el.getAttribute('title');
      if (text && text.length > 1) galaxy[`action_${i}`] = text;
    });

    return galaxy;
  }

  // Messages page specifics
  function grabMessagesPage() {
    const msgs = {};

    // Message categories/tabs
    document.querySelectorAll('.tabs_wrap li, .msg_actions button, .msg_head').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) msgs[`msg_ui_${i}`] = text;
    });

    // Spy report labels
    document.querySelectorAll('.espionageDefText, .spyRaport th, .spyRaport .res_name').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) msgs[`spy_${i}`] = text;
    });

    // Battle report labels
    document.querySelectorAll('.combatReport th, .combatReport .label').forEach((el, i) => {
      const text = el.textContent.trim();
      if (text) msgs[`combat_${i}`] = text;
    });

    return msgs;
  }

  // ==========================================================================
  // 5. Navigazione automatica delle pagine
  // ==========================================================================
  const pages = [
    { name: 'overview',          url: 'page=ingame&component=overview' },
    { name: 'resources',         url: 'page=ingame&component=supplies' },
    { name: 'resource_settings', url: 'page=ingame&component=resourcesettings' },
    { name: 'facilities',       url: 'page=ingame&component=facilities' },
    { name: 'research',          url: 'page=ingame&component=research' },
    { name: 'shipyard',          url: 'page=ingame&component=shipyard' },
    { name: 'defense',           url: 'page=ingame&component=defenses' },
    { name: 'fleet',             url: 'page=ingame&component=fleetdispatch' },
    { name: 'galaxy',            url: 'page=ingame&component=galaxy' },
    { name: 'messages',          url: 'page=ingame&component=messages' },
    { name: 'alliance',          url: 'page=ingame&component=alliance' },
    { name: 'premium',           url: 'page=ingame&component=premium' },
    { name: 'shop',              url: 'page=ingame&component=shop' },
    { name: 'options',           url: 'page=ingame&component=options' },
    { name: 'highscore',         url: 'page=ingame&component=highscore' },
    { name: 'rewards',           url: 'page=ingame&component=rewards' },
    { name: 'characterclass',    url: 'page=ingame&component=characterclassselection' },
  ];

  // Grab layout (available on current page)
  console.log('[OGame Grabber] Grabbing layout elements...');
  results.pages.layout = grabLayout();
  results.pages.dialogs = grabDialogs();

  // Get base URL
  const baseUrl = window.location.origin + window.location.pathname + '?';

  // Option A: Grab only current page (fast, no navigation)
  const currentComponent = new URLSearchParams(window.location.search).get('component') || 'unknown';
  console.log(`[OGame Grabber] Grabbing current page: ${currentComponent}`);

  const currentData = grabCurrentPage();

  // Add page-specific grabs
  if (currentComponent === 'fleetdispatch') Object.assign(currentData, grabFleetPage());
  if (currentComponent === 'galaxy') Object.assign(currentData, grabGalaxyPage());
  if (currentComponent === 'messages') Object.assign(currentData, grabMessagesPage());

  results.pages[currentComponent] = currentData;

  // ==========================================================================
  // 6. Opzione navigazione automatica (decommentare per usare)
  // ==========================================================================
  /*
  // ATTENZIONE: Questo naviga tra le pagine! Usare con cautela.
  for (const page of pages) {
    console.log(`[OGame Grabber] Navigating to ${page.name}...`);

    // Usa fetch per ottenere l'HTML senza navigare
    try {
      const response = await fetch(baseUrl + page.url, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const html = await response.text();

      // Parse HTML
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      // Extract texts from parsed document
      const pageTexts = {};

      // All elements with text
      doc.querySelectorAll('*').forEach((el, i) => {
        if (el.children.length === 0) {
          const text = el.textContent.trim();
          if (text && text.length > 1 && text.length < 500 &&
              !/^\d+[\d.,]*$/.test(text) && !/^[a-z_]+$/.test(text) &&
              !text.startsWith('{') && !text.startsWith('<')) {
            pageTexts[`text_${i}`] = text;
          }
        }
      });

      // All title/alt/placeholder attributes
      doc.querySelectorAll('[title], [alt], [placeholder]').forEach((el, i) => {
        const attr = el.getAttribute('title') || el.getAttribute('alt') || el.getAttribute('placeholder');
        if (attr && attr.length > 2 && !attr.startsWith('http') && !attr.startsWith('/')) {
          pageTexts[`attr_${i}`] = attr;
        }
      });

      results.pages[page.name] = pageTexts;
      console.log(`[OGame Grabber] ${page.name}: ${Object.keys(pageTexts).length} texts found`);

      await wait(1000); // Rate limiting
    } catch (e) {
      console.error(`[OGame Grabber] Error on ${page.name}:`, e);
      results.pages[page.name] = { error: e.message };
    }
  }
  */

  // ==========================================================================
  // 7. Output e download
  // ==========================================================================

  // Deduplicate values across all pages
  const allTexts = new Set();
  const deduped = {};
  for (const [pageName, pageData] of Object.entries(results.pages)) {
    deduped[pageName] = {};
    for (const [key, value] of Object.entries(pageData)) {
      const textVal = typeof value === 'string' ? value : JSON.stringify(value);
      if (!allTexts.has(textVal)) {
        allTexts.add(textVal);
        deduped[pageName][key] = value;
      }
    }
  }
  results.pages = deduped;
  results.total_unique_texts = allTexts.size;

  // Download as JSON
  const blob = new Blob([JSON.stringify(results, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `ogame_ui_texts_${results.language}_${currentComponent}_${Date.now()}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  console.log('[OGame Grabber] Done! JSON file downloaded.');
  console.log(`[OGame Grabber] Total unique texts: ${allTexts.size}`);
  console.log('[OGame Grabber] Results:', results);

  return results;
})();
