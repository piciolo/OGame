/**
 * OGame UI Text Grabber — Autonomous Edition
 * ============================================
 * Naviga AUTOMATICAMENTE tutte le pagine OGame e raccoglie ogni testo UI.
 * Usa localStorage per persistere i dati tra le navigazioni.
 *
 * USO:
 * 1. Accedi al tuo account OGame (server IT per testi italiani, NL per olandesi, ecc.)
 * 2. Apri la console del browser (F12 → Console)
 * 3. Incolla e esegui questo script
 * 4. NON TOCCARE NULLA — lo script naviga da solo tutte le 17+ pagine
 * 5. Al termine scarica automaticamente il JSON con TUTTI i testi
 *
 * COMANDI MANUALI (in console):
 *   OGameGrabber.status()   — mostra progresso corrente
 *   OGameGrabber.abort()    — ferma la navigazione e scarica quello che ha
 *   OGameGrabber.reset()    — cancella tutto e ricomincia da zero
 *   OGameGrabber.download() — forza il download del JSON raccolto finora
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'ogame_grabber_data';
  const STORAGE_QUEUE = 'ogame_grabber_queue';
  const STORAGE_STATE = 'ogame_grabber_state';
  const DELAY_BETWEEN_PAGES = 3000;       // ms tra una pagina e l'altra
  const DELAY_BEFORE_GRAB = 1500;         // ms attesa dopo il caricamento DOM
  const DELAY_TECHNOLOGY_CLICK = 800;     // ms attesa dopo click su tecnologia
  const MAX_TECH_DETAILS_PER_PAGE = 50;   // max tecnologie da cliccare per pagina

  // ========================================================================
  // Pagine da visitare (in ordine)
  // ========================================================================
  const ALL_PAGES = [
    { name: 'overview',          component: 'overview' },
    { name: 'supplies',          component: 'supplies' },
    { name: 'resource_settings', component: 'resourcesettings' },
    { name: 'facilities',        component: 'facilities' },
    { name: 'research',          component: 'research' },
    { name: 'shipyard',          component: 'shipyard' },
    { name: 'defense',           component: 'defenses' },
    { name: 'fleet',             component: 'fleetdispatch' },
    { name: 'galaxy',            component: 'galaxy' },
    { name: 'messages',          component: 'messages' },
    { name: 'alliance',          component: 'alliance' },
    { name: 'premium',           component: 'premium' },
    { name: 'shop',              component: 'shop' },
    { name: 'options',           component: 'options' },
    { name: 'highscore',         component: 'highscore' },
    { name: 'rewards',           component: 'rewards' },
    { name: 'characterclass',    component: 'characterclassselection' },
    { name: 'techtree',          component: 'techtree' },
    { name: 'notes',             component: 'notes' },
    { name: 'search',            component: 'search' },
  ];

  // ========================================================================
  // Persistence helpers
  // ========================================================================
  function loadData() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
    catch { return {}; }
  }

  function saveData(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  }

  function loadQueue() {
    try { return JSON.parse(localStorage.getItem(STORAGE_QUEUE)) || []; }
    catch { return []; }
  }

  function saveQueue(queue) {
    localStorage.setItem(STORAGE_QUEUE, JSON.stringify(queue));
  }

  function loadState() {
    try { return JSON.parse(localStorage.getItem(STORAGE_STATE)) || {}; }
    catch { return {}; }
  }

  function saveState(state) {
    localStorage.setItem(STORAGE_STATE, JSON.stringify(state));
  }

  function clearAll() {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(STORAGE_QUEUE);
    localStorage.removeItem(STORAGE_STATE);
  }

  // ========================================================================
  // Text extraction engine
  // ========================================================================

  function extractAllTexts(root) {
    const texts = {};
    let idx = 0;

    function addText(category, value) {
      if (!value || typeof value !== 'string') return;
      const clean = value.trim().replace(/\s+/g, ' ');
      if (clean.length < 2 || clean.length > 2000) return;
      if (/^\d+[\d.,:%]*$/.test(clean)) return;           // pure numbers
      if (/^[a-z][a-zA-Z0-9_]+$/.test(clean)) return;     // camelCase/identifiers
      if (clean.startsWith('http') || clean.startsWith('//') || clean.startsWith('javascript:')) return;
      if (clean.startsWith('{') || clean.startsWith('<')) return;
      texts[`${category}_${idx++}`] = clean;
    }

    // 1. Leaf text nodes (elements with no children)
    root.querySelectorAll('*').forEach(el => {
      if (el.children.length === 0 && el.closest('script, style, noscript') === null) {
        addText('text', el.textContent);
      }
    });

    // 2. title attributes
    root.querySelectorAll('[title]').forEach(el => {
      addText('title', el.getAttribute('title'));
    });

    // 3. alt attributes
    root.querySelectorAll('[alt]').forEach(el => {
      addText('alt', el.getAttribute('alt'));
    });

    // 4. placeholder attributes
    root.querySelectorAll('[placeholder]').forEach(el => {
      addText('placeholder', el.getAttribute('placeholder'));
    });

    // 5. data-tooltip / data-tooltip-title
    root.querySelectorAll('[data-tooltip], [data-tooltip-title]').forEach(el => {
      addText('tooltip', el.getAttribute('data-tooltip') || el.getAttribute('data-tooltip-title'));
    });

    // 6. value of buttons and inputs
    root.querySelectorAll('input[type="submit"], input[type="button"]').forEach(el => {
      addText('input_value', el.value);
    });

    // 7. option text in selects
    root.querySelectorAll('select option').forEach(el => {
      addText('option', el.textContent);
    });

    // 8. aria-label
    root.querySelectorAll('[aria-label]').forEach(el => {
      addText('aria', el.getAttribute('aria-label'));
    });

    return texts;
  }

  // Grab the global layout elements (menu, topbar, officers, resources)
  function extractLayout() {
    const layout = {};
    let idx = 0;

    function add(cat, val) {
      if (!val || val.length < 2) return;
      const clean = val.trim().replace(/\s+/g, ' ');
      if (/^\d+[\d.,:%]*$/.test(clean)) return;
      layout[`${cat}_${idx++}`] = clean;
    }

    // Navigation menu
    document.querySelectorAll('#menuTable a, #menuTable .menubutton').forEach(el => {
      add('menu', el.textContent.trim());
      add('menu_title', el.getAttribute('title'));
    });

    // Top bar
    document.querySelectorAll('#bar a, #bar span, .OGameClock').forEach(el => {
      if (el.children.length === 0) add('topbar', el.textContent.trim());
      add('topbar_title', el.getAttribute('title'));
    });

    // Resource bar
    document.querySelectorAll('#resourcesbarcomponent [title]').forEach(el => {
      add('resource', el.getAttribute('title'));
    });

    // Officers
    document.querySelectorAll('#officers [title], #officers a').forEach(el => {
      add('officer', el.getAttribute('title'));
    });

    // Planet sidebar
    document.querySelectorAll('#planetList [title], .smallplanet .planet-name').forEach(el => {
      add('planet', el.getAttribute('title') || el.textContent.trim());
    });

    // Comm menu (messages icon etc)
    document.querySelectorAll('#comm_menu [title], .comm_menu [title]').forEach(el => {
      add('comm', el.getAttribute('title'));
    });

    return layout;
  }

  // Click on each technology item to grab its detail overlay
  async function extractTechnologyDetails() {
    const details = {};
    const techItems = document.querySelectorAll(
      '.technology, .hasmark, li[data-technology], .buildable, .detail_button, ' +
      '.research_items li, .defense_items li, .ship_items li'
    );

    const clickTargets = [];
    techItems.forEach(el => {
      const techId = el.getAttribute('data-technology') || el.getAttribute('data-tech') || el.id;
      if (techId && !clickTargets.find(t => t.id === techId)) {
        clickTargets.push({ id: techId, el });
      }
    });

    const toClick = clickTargets.slice(0, MAX_TECH_DETAILS_PER_PAGE);
    console.log(`[Grabber] Found ${clickTargets.length} technologies, clicking ${toClick.length}...`);

    for (const target of toClick) {
      try {
        // Click to open the detail panel
        target.el.click();
        await new Promise(r => setTimeout(r, DELAY_TECHNOLOGY_CLICK));

        // Grab text from the detail panel
        const detailPanel = document.querySelector(
          '#technologydetails, .technologyDetails, #technologydetails_content, ' +
          '.detail_content, #detail'
        );
        if (detailPanel) {
          const panelTexts = extractAllTexts(detailPanel);
          if (Object.keys(panelTexts).length > 0) {
            details[`tech_${target.id}`] = panelTexts;
          }
        }
      } catch (e) {
        console.warn(`[Grabber] Could not click tech ${target.id}:`, e.message);
      }
    }

    return details;
  }

  // ========================================================================
  // Deduplication
  // ========================================================================
  function deduplicateResults(allPages) {
    const seen = new Set();
    const deduped = {};

    for (const [pageName, pageData] of Object.entries(allPages)) {
      if (typeof pageData !== 'object' || pageData === null) continue;
      deduped[pageName] = {};

      // Handle nested objects (technology details)
      for (const [key, value] of Object.entries(pageData)) {
        if (typeof value === 'object' && value !== null) {
          const nestedDeduped = {};
          for (const [nk, nv] of Object.entries(value)) {
            const sv = String(nv);
            if (!seen.has(sv)) { seen.add(sv); nestedDeduped[nk] = nv; }
          }
          if (Object.keys(nestedDeduped).length > 0) deduped[pageName][key] = nestedDeduped;
        } else {
          const sv = String(value);
          if (!seen.has(sv)) { seen.add(sv); deduped[pageName][key] = value; }
        }
      }

      if (Object.keys(deduped[pageName]).length === 0) delete deduped[pageName];
    }

    return { deduped, uniqueCount: seen.size };
  }

  // ========================================================================
  // Download helper
  // ========================================================================
  function downloadJSON(data, filename) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  // ========================================================================
  // Progress banner UI
  // ========================================================================
  function showBanner(text, color = '#00cc00') {
    let banner = document.getElementById('ogame-grabber-banner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'ogame-grabber-banner';
      banner.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; z-index: 999999;
        padding: 8px 16px; font-family: monospace; font-size: 14px; font-weight: bold;
        color: #fff; text-align: center; transition: background 0.3s;
      `;
      document.body.appendChild(banner);
    }
    banner.style.background = color;
    banner.textContent = text;
  }

  function removeBanner() {
    document.getElementById('ogame-grabber-banner')?.remove();
  }

  // ========================================================================
  // Navigation logic
  // ========================================================================
  function getBaseUrl() {
    // Works on both /game/index.php?page=... and similar URL structures
    const url = new URL(window.location.href);
    return url.origin + url.pathname + '?';
  }

  function navigateToComponent(component) {
    const base = getBaseUrl();
    window.location.href = `${base}page=ingame&component=${component}`;
  }

  // ========================================================================
  // MAIN — runs on every page load
  // ========================================================================
  async function main() {
    const state = loadState();

    // FIRST RUN: initialize queue
    if (!state.running) {
      console.log('%c[OGame Grabber] Starting autonomous scan...', 'color: #0f0; font-size: 16px');
      console.log('[Grabber] Will navigate through', ALL_PAGES.length, 'pages automatically.');
      console.log('[Grabber] DO NOT interact with the page. Use OGameGrabber.abort() to stop.');

      clearAll();
      saveState({ running: true, startTime: Date.now(), pagesCompleted: 0, totalPages: ALL_PAGES.length });
      saveQueue(ALL_PAGES.map(p => p.component));
      saveData({
        timestamp: new Date().toISOString(),
        server: window.location.hostname,
        language: document.documentElement.lang || navigator.language || 'unknown',
        pages: {}
      });

      // Grab layout from current page first
      showBanner(`[Grabber] Initializing... Grabbing layout elements`, '#005500');
      await new Promise(r => setTimeout(r, DELAY_BEFORE_GRAB));

      const data = loadData();
      data.pages._layout = extractLayout();
      saveData(data);

      // Start navigating
      const queue = loadQueue();
      if (queue.length > 0) {
        const next = queue[0];
        showBanner(`[Grabber] Navigating to: ${next} (1/${ALL_PAGES.length})`, '#333399');
        await new Promise(r => setTimeout(r, 500));
        navigateToComponent(next);
      }
      return;
    }

    // SUBSEQUENT RUNS: grab current page, then navigate to next
    const queue = loadQueue();
    const currentComponent = new URLSearchParams(window.location.search).get('component') || 'unknown';

    if (queue.length === 0) {
      // All done! Finalize and download
      finalize();
      return;
    }

    // Check if we're on the expected page
    const expectedComponent = queue[0];
    if (currentComponent !== expectedComponent) {
      // Track redirect attempts to avoid infinite loops
      const retryKey = `ogame_grabber_retry_${expectedComponent}`;
      const retryCount = parseInt(sessionStorage.getItem(retryKey) || '0', 10);

      if (retryCount >= 1) {
        // Already tried once — OGame is redirecting us away. SKIP this page.
        console.warn(`[Grabber] Page "${expectedComponent}" redirects to "${currentComponent}". SKIPPING.`);
        sessionStorage.removeItem(retryKey);

        // Remove from queue and advance
        queue.shift();
        saveQueue(queue);
        const completed = (state.pagesCompleted || 0) + 1;
        saveState({ ...state, pagesCompleted: completed });

        if (queue.length === 0) {
          finalize();
          return;
        }

        const nextComponent = queue[0];
        showBanner(
          `[Grabber] Skipped "${expectedComponent}" (redirect). Next: ${nextComponent}`,
          '#996600'
        );
        await new Promise(r => setTimeout(r, 1500));
        navigateToComponent(nextComponent);
        return;
      }

      // First attempt — try once to navigate to the expected page
      sessionStorage.setItem(retryKey, String(retryCount + 1));
      console.warn(`[Grabber] Expected ${expectedComponent}, got ${currentComponent}. Retrying (attempt ${retryCount + 1})...`);
      await new Promise(r => setTimeout(r, 1000));
      navigateToComponent(expectedComponent);
      return;
    }

    // Clear any retry counter for this page (we arrived successfully)
    sessionStorage.removeItem(`ogame_grabber_retry_${expectedComponent}`);

    const completed = state.pagesCompleted || 0;
    const total = state.totalPages || ALL_PAGES.length;
    const pageName = ALL_PAGES.find(p => p.component === currentComponent)?.name || currentComponent;

    showBanner(
      `[Grabber] Scanning: ${pageName} (${completed + 1}/${total}) — DO NOT TOUCH`,
      '#005500'
    );

    console.log(`%c[Grabber] Scanning page: ${pageName}`, 'color: #0f0');

    // Wait for dynamic content to load
    await new Promise(r => setTimeout(r, DELAY_BEFORE_GRAB));

    // === EXTRACT TEXTS ===
    const contentRoot = document.querySelector('#inhalt, #content, .maincontent, #contentWrapper, body');
    const pageTexts = extractAllTexts(contentRoot || document.body);

    // === CLICK TECHNOLOGIES for detail panels ===
    let techDetails = {};
    const techPages = ['supplies', 'facilities', 'research', 'shipyard', 'defenses', 'fleetdispatch'];
    if (techPages.includes(currentComponent)) {
      showBanner(
        `[Grabber] Clicking technologies on: ${pageName} (${completed + 1}/${total})`,
        '#555500'
      );
      techDetails = await extractTechnologyDetails();
    }

    // === GRAB DIALOGS if any are open ===
    const dialogTexts = {};
    document.querySelectorAll('.ui-dialog, .overlay, .tpd-content, #technologydetails').forEach((el, i) => {
      const dt = extractAllTexts(el);
      if (Object.keys(dt).length > 0) dialogTexts[`dialog_${i}`] = dt;
    });

    // === SAVE PAGE DATA ===
    const data = loadData();
    data.pages[pageName] = {
      component: currentComponent,
      url: window.location.href,
      ...pageTexts,
    };

    if (Object.keys(techDetails).length > 0) {
      data.pages[`${pageName}_tech_details`] = techDetails;
    }
    if (Object.keys(dialogTexts).length > 0) {
      data.pages[`${pageName}_dialogs`] = dialogTexts;
    }

    saveData(data);

    // === ADVANCE QUEUE ===
    queue.shift();
    saveQueue(queue);
    saveState({ ...state, pagesCompleted: completed + 1 });

    const textsFound = Object.keys(pageTexts).length + Object.keys(techDetails).length;
    console.log(`[Grabber] ${pageName}: ${textsFound} texts captured`);

    if (queue.length === 0) {
      // All pages done! Finalize.
      showBanner(`[Grabber] All pages scanned! Preparing download...`, '#006600');
      await new Promise(r => setTimeout(r, 1000));
      finalize();
      return;
    }

    // Navigate to next page
    const nextComponent = queue[0];
    const nextName = ALL_PAGES.find(p => p.component === nextComponent)?.name || nextComponent;
    showBanner(
      `[Grabber] Done with ${pageName} (${textsFound} texts). Next: ${nextName} in ${DELAY_BETWEEN_PAGES / 1000}s...`,
      '#333399'
    );

    await new Promise(r => setTimeout(r, DELAY_BETWEEN_PAGES));
    navigateToComponent(nextComponent);
  }

  // ========================================================================
  // Finalization — deduplicate & download
  // ========================================================================
  function finalize() {
    const data = loadData();
    const state = loadState();

    const { deduped, uniqueCount } = deduplicateResults(data.pages);
    const elapsed = state.startTime ? Math.round((Date.now() - state.startTime) / 1000) : 0;

    const finalResult = {
      timestamp: data.timestamp,
      completed: new Date().toISOString(),
      server: data.server,
      language: data.language,
      elapsed_seconds: elapsed,
      pages_scanned: state.pagesCompleted || Object.keys(data.pages).length,
      total_unique_texts: uniqueCount,
      pages: deduped
    };

    const lang = data.language || 'unknown';
    const filename = `ogame_ALL_UI_TEXTS_${lang.toUpperCase()}_${Date.now()}.json`;
    downloadJSON(finalResult, filename);

    showBanner(
      `DONE! ${uniqueCount} unique texts from ${finalResult.pages_scanned} pages (${elapsed}s). File: ${filename}`,
      '#006600'
    );

    console.log('%c[OGame Grabber] COMPLETE!', 'color: #0f0; font-size: 18px');
    console.log(`  Unique texts: ${uniqueCount}`);
    console.log(`  Pages scanned: ${finalResult.pages_scanned}`);
    console.log(`  Time elapsed: ${elapsed}s`);
    console.log(`  Downloaded: ${filename}`);
    console.log('[Grabber] Full results:', finalResult);

    // Clean up localStorage
    clearAll();

    // Keep banner visible for 30 seconds
    setTimeout(removeBanner, 30000);
  }

  // ========================================================================
  // Public API (accessible via console)
  // ========================================================================
  window.OGameGrabber = {
    status() {
      const state = loadState();
      const queue = loadQueue();
      const data = loadData();
      const pagesScanned = Object.keys(data.pages || {}).length;
      const elapsed = state.startTime ? Math.round((Date.now() - state.startTime) / 1000) : 0;

      console.log('%c[OGame Grabber Status]', 'color: #0ff; font-size: 14px');
      console.log(`  Running: ${state.running ? 'YES' : 'NO'}`);
      console.log(`  Pages completed: ${state.pagesCompleted || 0} / ${state.totalPages || '?'}`);
      console.log(`  Remaining in queue: ${queue.length}`);
      console.log(`  Data keys collected: ${pagesScanned}`);
      console.log(`  Elapsed: ${elapsed}s`);
      if (queue.length > 0) console.log(`  Next page: ${queue[0]}`);
      return { state, queueLength: queue.length, pagesScanned, elapsed };
    },

    abort() {
      console.log('%c[OGame Grabber] Aborting... downloading collected data.', 'color: #ff0; font-size: 14px');
      finalize();
    },

    reset() {
      clearAll();
      removeBanner();
      console.log('%c[OGame Grabber] Reset complete. Run the script again to restart.', 'color: #f80; font-size: 14px');
    },

    download() {
      const data = loadData();
      if (!data || !data.pages) {
        console.error('[Grabber] No data to download.');
        return;
      }
      finalize();
    },

    // Skip to a specific page (useful if stuck)
    skipTo(componentName) {
      const queue = loadQueue();
      const idx = queue.indexOf(componentName);
      if (idx === -1) {
        console.error(`[Grabber] Component "${componentName}" not found in queue. Available:`, queue);
        return;
      }
      const newQueue = queue.slice(idx);
      saveQueue(newQueue);
      console.log(`[Grabber] Skipping to ${componentName}. Remaining pages: ${newQueue.length}`);
      navigateToComponent(componentName);
    },

    // Resume if the script stopped (e.g., after browser refresh without script)
    resume() {
      const state = loadState();
      if (!state.running) {
        console.error('[Grabber] No active session. Run the full script to start.');
        return;
      }
      const queue = loadQueue();
      if (queue.length === 0) {
        finalize();
        return;
      }
      console.log(`[Grabber] Resuming... ${queue.length} pages remaining.`);
      navigateToComponent(queue[0]);
    }
  };

  // ========================================================================
  // Auto-start
  // ========================================================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => main());
  } else {
    // Small delay to let OGame's own JS finish loading
    setTimeout(() => main(), 500);
  }

})();
