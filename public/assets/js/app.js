/**
 * VISA TRACK — Staff Visa Tracking & Management System
 * Core JavaScript Application Shell Logic
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // 1. Initialize Bootstrap Tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
      trigger: 'hover',
      container: 'body'
    });
  });

  // 2. Desktop Sidebar Collapse / Expand with LocalStorage Persistence
  const desktopSidebarBtn = document.getElementById('desktopSidebarToggleBtn');
  const isCollapsed = localStorage.getItem('vt_sidebar_collapsed') === 'true';

  if (isCollapsed && window.innerWidth >= 992) {
    document.body.classList.add('sidebar-collapsed');
  }

  desktopSidebarBtn?.addEventListener('click', function () {
    document.body.classList.toggle('sidebar-collapsed');
    const currentlyCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('vt_sidebar_collapsed', currentlyCollapsed);
  });

  // 3. Mobile Sidebar Drawer Toggle & Overlay Click
  const mobileToggleBtn = document.getElementById('sidebarToggleBtn');
  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  function openMobileSidebar() {
    document.body.classList.add('sidebar-mobile-open');
  }

  function closeMobileSidebar() {
    document.body.classList.remove('sidebar-mobile-open');
  }

  mobileToggleBtn?.addEventListener('click', openMobileSidebar);
  sidebarCloseBtn?.addEventListener('click', closeMobileSidebar);
  sidebarOverlay?.addEventListener('click', closeMobileSidebar);

  // Close mobile sidebar on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('sidebar-mobile-open')) {
      closeMobileSidebar();
    }
  });

  // 4. Global Search Live Database Query & Shortcut (Ctrl + K)
  const searchInput = document.getElementById('globalSearchInput');
  const searchResultsDropdown = document.getElementById('globalSearchResults');
  const searchResultsContent = document.getElementById('searchResultsContent');
  const closeSearchBtn = document.getElementById('closeSearchDropdown');
  let searchDebounceTimer = null;

  // Keyboard shortcut (Ctrl+K or Cmd+K)
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      searchInput?.focus();
    }
  });

  searchInput?.addEventListener('input', function () {
    const query = this.value.trim();
    clearTimeout(searchDebounceTimer);

    if (query.length < 2) {
      searchResultsDropdown?.classList.add('d-none');
      return;
    }

    searchResultsDropdown?.classList.remove('d-none');
    searchResultsContent.innerHTML = '<div class="text-center py-3 text-muted small"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Searching database...</div>';

    searchDebounceTimer = setTimeout(() => {
      fetch(`/api/search?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          renderSearchResults(data, query);
        })
        .catch(() => {
          searchResultsContent.innerHTML = '<div class="text-center py-2 text-danger small">Error searching database.</div>';
        });
    }, 250);
  });

  function renderSearchResults(data, query) {
    if (!data || (!data.applications?.length && !data.customers?.length)) {
      searchResultsContent.innerHTML = `<div class="text-center py-3 text-muted small">No results matching "<strong>${escapeHtml(query)}</strong>"</div>`;
      return;
    }

    let html = '';

    if (data.applications && data.applications.length > 0) {
      html += '<div class="px-2 py-1 small fw-bold text-uppercase text-muted" style="font-size: 0.68rem;">Visa Applications</div>';
      data.applications.forEach(app => {
        html += `
          <a href="/applications/show?id=${app.id}" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none text-dark hover-bg-light border-bottom">
            <div>
              <div class="fw-semibold small"><i class="fa-solid fa-folder text-primary me-1"></i> ${escapeHtml(app.application_number)}</div>
              <div class="text-muted" style="font-size: 0.75rem;">${escapeHtml(app.customer_name || 'Applicant')} &bull; ${escapeHtml(app.visa_type_name || 'Visa')}</div>
            </div>
            <span class="badge bg-primary-subtle text-primary" style="font-size: 0.7rem;">${escapeHtml(app.status || 'Active')}</span>
          </a>`;
      });
    }

    if (data.customers && data.customers.length > 0) {
      html += '<div class="px-2 py-1 mt-2 small fw-bold text-uppercase text-muted" style="font-size: 0.68rem;">Applicants &amp; Passports</div>';
      data.customers.forEach(cust => {
        html += `
          <a href="/customers/show?id=${cust.id}" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none text-dark hover-bg-light border-bottom">
            <div>
              <div class="fw-semibold small"><i class="fa-solid fa-user text-success me-1"></i> ${escapeHtml(cust.full_name)}</div>
              <div class="text-muted" style="font-size: 0.75rem;">Pass: ${escapeHtml(cust.passport_number || 'N/A')} &bull; ${escapeHtml(cust.nationality || 'Nationality')}</div>
            </div>
            <span class="badge bg-light text-muted" style="font-size: 0.7rem;">${escapeHtml(cust.customer_code || '')}</span>
          </a>`;
      });
    }

    searchResultsContent.innerHTML = html;
  }

  closeSearchBtn?.addEventListener('click', function () {
    searchResultsDropdown?.classList.add('d-none');
  });

  // Close search on clicking outside
  document.addEventListener('click', function (e) {
    if (!searchInput?.contains(e.target) && !searchResultsDropdown?.contains(e.target)) {
      searchResultsDropdown?.classList.add('d-none');
    }
  });

  // 5. Global Toast Notification Utility
  window.showToast = function (message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'danger' ? 'text-bg-danger' : (type === 'success' ? 'text-bg-success' : (type === 'warning' ? 'text-bg-warning' : 'text-bg-info'));
    const iconClass = type === 'danger' ? 'fa-circle-exclamation' : (type === 'success' ? 'fa-circle-check' : (type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info'));

    const toastEl = document.createElement('div');
    toastEl.id = toastId;
    toastEl.className = `toast align-items-center ${bgClass} border-0 shadow`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="fa-solid ${iconClass}"></i>
          <span>${escapeHtml(message)}</span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

    container.appendChild(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, { delay: duration });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
      toastEl.remove();
    });
  };

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
