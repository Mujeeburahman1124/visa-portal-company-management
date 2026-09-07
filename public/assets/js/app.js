/**
 * VISA TRACK — Staff Visa Tracking & Management System
 * Core JavaScript Application Shell Logic
 */

// 0. Define global modal & dropdown helper functions early
window.openModalById = function (modalId) {
  const el = document.getElementById(modalId);
  if (!el) {
    console.error('Modal element not found: ' + modalId);
    return;
  }
  try {
    if (window.bootstrap && bootstrap.Modal) {
      const modalInstance = bootstrap.Modal.getOrCreateInstance(el);
      modalInstance.show();
      return;
    }
  } catch (err) {
    console.warn('Bootstrap modal instance failed, using fallback:', err);
  }

  // Fallback modal open
  el.classList.add('show');
  el.style.display = 'block';
  el.removeAttribute('aria-hidden');
  el.setAttribute('aria-modal', 'true');
  document.body.classList.add('modal-open');

  let backdrop = document.getElementById('vt-modal-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.id = 'vt-modal-backdrop';
    backdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(backdrop);
  }
};

window.closeModalById = function (modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;

  try {
    if (window.bootstrap && bootstrap.Modal) {
      const instance = bootstrap.Modal.getInstance(el);
      if (instance) {
        instance.hide();
        return;
      }
    }
  } catch (e) {}

  el.classList.remove('show');
  el.style.display = 'none';
  el.setAttribute('aria-hidden', 'true');
  el.removeAttribute('aria-modal');

  const openModals = document.querySelectorAll('.modal.show');
  if (openModals.length === 0) {
    document.body.classList.remove('modal-open');
    const backdrop = document.getElementById('vt-modal-backdrop');
    if (backdrop) backdrop.remove();
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
  }
};

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // 1. Safe Initialize Bootstrap Tooltips
  try {
    if (window.bootstrap && bootstrap.Tooltip) {
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl, { trigger: 'hover', container: 'body' });
      });
    }
  } catch (e) {
    console.warn('Tooltip init skipped:', e);
  }

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

  // 4. Universal Click Handler for Modal Triggers & Dismissals
  document.addEventListener('click', function (e) {
    // Check if clicked element or its parent is a modal toggle button
    const modalBtn = e.target.closest('[data-bs-toggle="modal"], [data-toggle="modal"]');
    if (modalBtn) {
      const targetId = modalBtn.getAttribute('data-bs-target') || modalBtn.getAttribute('data-target') || modalBtn.getAttribute('href');
      if (targetId && targetId.startsWith('#')) {
        const cleanId = targetId.substring(1);
        if (cleanId) {
          window.openModalById(cleanId);
        }
      }
    }

    // Check if clicked element is a modal dismiss button
    const dismissBtn = e.target.closest('[data-bs-dismiss="modal"], [data-dismiss="modal"]');
    if (dismissBtn) {
      const parentModal = dismissBtn.closest('.modal');
      if (parentModal && parentModal.id) {
        window.closeModalById(parentModal.id);
      }
    }

    // Check if clicking on modal background to close
    if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
      window.closeModalById(e.target.id);
    }
  });

  // 5. Universal Dropdown Click & Auto-Close Handler
  document.addEventListener('click', function (e) {
    const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"], .dropdown-toggle');
    
    // If clicking a dropdown button
    if (dropdownToggle) {
      e.preventDefault();
      e.stopPropagation();
      const parentDropdown = dropdownToggle.closest('.dropdown, .dropup, .btn-group');
      if (!parentDropdown) return;

      const menu = parentDropdown.querySelector('.dropdown-menu');
      if (!menu) return;

      const isOpen = menu.classList.contains('show') || parentDropdown.classList.contains('show');

      // Close all open dropdowns first
      document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
      document.querySelectorAll('.dropdown.show, .dropup.show, .btn-group.show').forEach(d => d.classList.remove('show'));

      // If it wasn't open before, open it now
      if (!isOpen) {
        parentDropdown.classList.add('show');
        menu.classList.add('show');
        dropdownToggle.setAttribute('aria-expanded', 'true');
      } else {
        dropdownToggle.setAttribute('aria-expanded', 'false');
      }
      return;
    }

    // If clicking inside a dropdown menu (e.g. on an item that opens a modal)
    if (e.target.closest('.dropdown-menu')) {
      const clickedItem = e.target.closest('.dropdown-item');
      if (clickedItem && !clickedItem.classList.contains('dropdown-toggle')) {
        // Close parent dropdown menu
        document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
        document.querySelectorAll('.dropdown.show, .dropup.show, .btn-group.show').forEach(d => d.classList.remove('show'));
      }
      return;
    }

    // If clicking outside, close all open dropdowns
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    document.querySelectorAll('.dropdown.show, .dropup.show, .btn-group.show').forEach(d => d.classList.remove('show'));
  });

  // 6. Global Search Live Database Query & Shortcut (Ctrl + K)
  const searchInput = document.getElementById('globalSearchInput');
  const searchResultsDropdown = document.getElementById('globalSearchResults');
  const searchResultsContent = document.getElementById('searchResultsContent');
  const closeSearchBtn = document.getElementById('closeSearchDropdown');
  let searchDebounceTimer = null;

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

  // 7. Global Toast Notification Utility
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
    try {
      if (window.bootstrap && bootstrap.Toast) {
        const bsToast = new bootstrap.Toast(toastEl, { delay: duration });
        bsToast.show();
      } else {
        toastEl.classList.add('show');
        setTimeout(() => toastEl.remove(), duration);
      }
    } catch (e) {
      toastEl.classList.add('show');
      setTimeout(() => toastEl.remove(), duration);
    }

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
