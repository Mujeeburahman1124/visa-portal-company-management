/**
 * VISA TRACK — Central Theme Engine
 * Applies data-theme attribute to <html>, persists to localStorage,
 * syncs across tabs via storage event.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'vt_theme';
  const DEFAULT_THEME = 'ocean-royal';

  const THEMES = [
    {
      id: 'ocean-royal',
      name: 'Ocean Royal',
      feel: 'Modern SaaS + Travel',
      swatches: ['#1e40af', '#0891b2', '#7c3aed'],
    },
    {
      id: 'sunset-fusion',
      name: 'Sunset Fusion',
      feel: 'Premium Colorful Recruitment',
      swatches: ['#be185d', '#e11d48', '#ea580c'],
    },
    {
      id: 'emerald-royal',
      name: 'Emerald Royal',
      feel: 'Premium Business & Finance',
      swatches: ['#065f46', '#0d9488', '#1d4ed8'],
    },
    {
      id: 'violet-aurora',
      name: 'Violet Aurora',
      feel: 'Modern Technology SaaS',
      swatches: ['#5b21b6', '#2563eb', '#db2777'],
    },
    {
      id: 'crimson-midnight',
      name: 'Crimson Midnight',
      feel: 'Premium International Business',
      swatches: ['#991b1b', '#881337', '#92400e'],
    },
  ];

  /* ── Read saved theme (runs before DOM ready to avoid flash) ── */
  function getSavedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
    } catch (e) {
      return DEFAULT_THEME;
    }
  }

  function saveTheme(id) {
    try { localStorage.setItem(STORAGE_KEY, id); } catch (e) {}
  }

  function applyTheme(id) {
    const valid = THEMES.find(t => t.id === id);
    const theme = valid ? id : DEFAULT_THEME;
    document.documentElement.setAttribute('data-theme', theme);
    if (document.body) {
      document.body.setAttribute('data-theme', theme);
    }
    saveTheme(theme);
    updateSelectorUI(theme);
    window.dispatchEvent(new CustomEvent('vt-theme-changed', { detail: { theme: theme } }));
  }

  /* ── Apply immediately (pre-DOM) to prevent flash ── */
  const initialTheme = getSavedTheme();
  document.documentElement.setAttribute('data-theme', initialTheme);
  document.addEventListener('DOMContentLoaded', function () {
    if (document.body) {
      document.body.setAttribute('data-theme', getSavedTheme());
    }
  });

  /* ── Sync across browser tabs ── */
  window.addEventListener('storage', function (e) {
    if (e.key === STORAGE_KEY && e.newValue) {
      document.documentElement.setAttribute('data-theme', e.newValue);
      if (document.body) {
        document.body.setAttribute('data-theme', e.newValue);
      }
      updateSelectorUI(e.newValue);
    }
  });

  /* ── Update all theme selector UI instances on page ── */
  function updateSelectorUI(activeId) {
    // Update swatch in button
    document.querySelectorAll('.theme-swatch-current').forEach(function (el) {
      const theme = THEMES.find(t => t.id === activeId);
      if (theme) el.style.background = theme.swatches[0];
    });

    // Update active state on options
    document.querySelectorAll('.theme-option').forEach(function (el) {
      el.classList.toggle('is-active', el.dataset.themeId === activeId);
    });
  }

  /* ── Build theme selector dropdown HTML ── */
  function buildDropdown(container) {
    const activeId = getSavedTheme();

    const titleEl = document.createElement('div');
    titleEl.className = 'theme-dropdown-title';
    titleEl.textContent = 'Choose Theme';
    container.appendChild(titleEl);

    THEMES.forEach(function (theme) {
      const opt = document.createElement('div');
      opt.className = 'theme-option' + (theme.id === activeId ? ' is-active' : '');
      opt.dataset.themeId = theme.id;
      opt.setAttribute('role', 'option');
      opt.setAttribute('aria-selected', theme.id === activeId ? 'true' : 'false');
      opt.setAttribute('tabindex', '0');

      const swatchesHtml = theme.swatches.map(function (c) {
        return '<span class="theme-option-swatch" style="background:' + c + '"></span>';
      }).join('');

      opt.innerHTML =
        '<div class="theme-option-swatches">' + swatchesHtml + '</div>' +
        '<div class="theme-option-info">' +
        '  <div class="theme-option-name">' + theme.name + '</div>' +
        '  <div class="theme-option-feel">' + theme.feel + '</div>' +
        '</div>' +
        '<i class="fa-solid fa-check theme-option-check" aria-hidden="true"></i>';

      opt.addEventListener('click', function () {
        applyTheme(theme.id);
        // Close dropdown
        const dd = container.closest('.theme-dropdown');
        if (dd) dd.classList.remove('is-open');
      });
      opt.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); opt.click(); }
      });

      container.appendChild(opt);
    });
  }

  /* ── Initialize all .theme-selector-wrap elements ── */
  function initSelectors() {
    document.querySelectorAll('.theme-selector-wrap').forEach(function (wrap) {
      const btn = wrap.querySelector('.theme-selector-btn');
      if (!btn) return;

      // Ensure dropdown exists
      let dd = wrap.querySelector('.theme-dropdown');
      if (!dd) {
        dd = document.createElement('div');
        dd.className = 'theme-dropdown';
        dd.setAttribute('role', 'listbox');
        dd.setAttribute('aria-label', 'Select theme');
        wrap.appendChild(dd);
      }

      // Build options if empty
      if (!dd.querySelector('.theme-option')) {
        buildDropdown(dd);
      }

      // Button click → toggle dropdown
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dd.classList.toggle('is-open');
      });

      // Close on outside click
      document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
          dd.classList.remove('is-open');
        }
      });

      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dd.classList.remove('is-open');
      });
    });

    // Sync active state on init
    updateSelectorUI(getSavedTheme());
  }

  /* ── Mobile nav toggle ── */
  function initMobileNav() {
    const hamburger = document.getElementById('pubHamburger');
    const mobileNav = document.getElementById('pubMobileNav');
    const overlay   = document.getElementById('pubOverlay');
    const closeBtn  = document.getElementById('pubMobileClose');

    function openNav() {
      if (!mobileNav) return;
      mobileNav.classList.add('is-open');
      if (overlay) overlay.classList.add('is-open');
      if (hamburger) hamburger.classList.add('is-open');
      hamburger && hamburger.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeNav() {
      if (!mobileNav) return;
      mobileNav.classList.remove('is-open');
      if (overlay) overlay.classList.remove('is-open');
      if (hamburger) hamburger.classList.remove('is-open');
      hamburger && hamburger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    if (hamburger) hamburger.addEventListener('click', openNav);
    if (closeBtn)  closeBtn.addEventListener('click', closeNav);
    if (overlay)   overlay.addEventListener('click', closeNav);

    // Close nav on link click (mobile)
    if (mobileNav) {
      mobileNav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeNav);
      });
    }

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });
  }

  /* ── Navbar scroll effect ── */
  function initNavScroll() {
    const nav = document.querySelector('.pub-navbar');
    if (!nav) return;
    window.addEventListener('scroll', function () {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
  }

  /* ── FAQ accordion ── */
  function initFaq() {
    document.querySelectorAll('.pub-faq-trigger').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const item = btn.closest('.pub-faq-item');
        if (!item) return;
        const isOpen = item.classList.contains('is-open');
        const body   = item.querySelector('.pub-faq-body');

        // Close all
        document.querySelectorAll('.pub-faq-item.is-open').forEach(function (el) {
          el.classList.remove('is-open');
          const b = el.querySelector('.pub-faq-body');
          if (b) b.style.maxHeight = '0';
          const t = el.querySelector('.pub-faq-trigger');
          if (t) t.setAttribute('aria-expanded', 'false');
        });

        // Open clicked if it was closed
        if (!isOpen && body) {
          item.classList.add('is-open');
          body.style.maxHeight = body.scrollHeight + 'px';
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  /* ── Run on DOM ready ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  function boot() {
    initSelectors();
    initMobileNav();
    initNavScroll();
    initFaq();
  }

  /* ── Expose globally for PHP-rendered pages ── */
  window.VTTheme = { apply: applyTheme, current: getSavedTheme };

})();
