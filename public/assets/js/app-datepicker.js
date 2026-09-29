/**
 * VISA TRACK — Universal Enhanced DatePicker System
 * Supports Month/Year Dropdown Jumping + Manual DD/MM/YYYY Keyboard Typing.
 * Compatible with macOS (Safari/Chrome/Firefox) and Windows (Chrome/Edge/Firefox).
 */
(function () {
  'use strict';

  function parseFormattedDate(str) {
    if (!str) return null;
    str = String(str).trim();
    if (!str) return null;

    // DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY
    let m = str.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/);
    if (m) {
      let day = parseInt(m[1], 10);
      let month = parseInt(m[2], 10) - 1;
      let year = parseInt(m[3], 10);
      let date = new Date(year, month, day);
      if (date.getFullYear() === year && date.getMonth() === month && date.getDate() === day) {
        return date;
      }
    }

    // YYYY-MM-DD
    m = str.match(/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/);
    if (m) {
      let year = parseInt(m[1], 10);
      let month = parseInt(m[2], 10) - 1;
      let day = parseInt(m[3], 10);
      let date = new Date(year, month, day);
      if (date.getFullYear() === year && date.getMonth() === month && date.getDate() === day) {
        return date;
      }
    }

    // DDMMYYYY (8 digits)
    m = str.match(/^(\d{2})(\d{2})(\d{4})$/);
    if (m) {
      let day = parseInt(m[1], 10);
      let month = parseInt(m[2], 10) - 1;
      let year = parseInt(m[3], 10);
      let date = new Date(year, month, day);
      if (date.getFullYear() === year && date.getMonth() === month && date.getDate() === day) {
        return date;
      }
    }

    return null;
  }

  window.initSystemDatePickers = function (container) {
    if (typeof flatpickr === 'undefined') return;

    var root = container || document;
    var selector = 'input[type="date"], input.datepicker, input[data-datepicker]';
    var inputs = root.querySelectorAll(selector);

    inputs.forEach(function (el) {
      if (el.dataset.flatpickrInitialized) return;

      var minD = el.getAttribute('min') || null;
      var maxD = el.getAttribute('max') || null;
      var val = el.value || '';

      var fp = flatpickr(el, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        altInputClass: (el.className || 'form-control') + ' vt-datepicker-input',
        allowInput: true,
        monthSelectorType: 'dropdown',
        minDate: minD,
        maxDate: maxD,
        defaultDate: val || null,
        parseDate: function (str, format) {
          var customParsed = parseFormattedDate(str);
          if (customParsed) return customParsed;
          return flatpickr.parseDate(str, format);
        },
        onReady: function (selectedDates, dateStr, instance) {
          if (instance.altInput) {
            instance.altInput.placeholder = 'DD/MM/YYYY';
            instance.altInput.setAttribute('title', 'Select date or type DD/MM/YYYY format');
            
            // Manual keyboard typing listener
            instance.altInput.addEventListener('keyup', function (e) {
              if (e.key === 'Enter' || e.key === 'Tab') return;
              var raw = instance.altInput.value;
              var parsed = parseFormattedDate(raw);
              if (parsed) {
                instance.setDate(parsed, true, 'Y-m-d');
              }
            });

            instance.altInput.addEventListener('blur', function () {
              var raw = instance.altInput.value;
              var parsed = parseFormattedDate(raw);
              if (parsed) {
                instance.setDate(parsed, true, 'Y-m-d');
              } else if (raw.trim() === '') {
                instance.clear();
              }
            });
          }
        }
      });

      el.dataset.flatpickrInitialized = 'true';
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    window.initSystemDatePickers();

    document.addEventListener('shown.bs.modal', function (e) {
      window.initSystemDatePickers(e.target);
    });

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (m.addedNodes && m.addedNodes.length > 0) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType === 1) {
              window.initSystemDatePickers(node);
            }
          });
        }
      });
    });

    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    }
  });
})();
