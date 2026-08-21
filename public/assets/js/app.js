/**
 * IPNA Performance Management System - Main Application JavaScript
 * Handles sidebar, AJAX, toasts, forms, and UI interactions.
 */

'use strict';

/* ============================================================
   1. CSRF TOKEN MANAGEMENT
   ============================================================ */
const CSRF = {
  _token: null,

  getToken() {
    if (this._token) return this._token;
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
      this._token = meta.getAttribute('content');
      return this._token;
    }
    const input = document.querySelector('input[name="csrf_token"], input[name="_token"]');
    if (input) {
      this._token = input.value;
      return this._token;
    }
    return '';
  },

  refresh() {
    this._token = null;
    return this.getToken();
  }
};

/* ============================================================
   2. SIDEBAR TOGGLE & MOBILE MENU
   ============================================================ */
const Sidebar = {
  sidebar: null,
  overlay: null,
  mainContent: null,
  topNavbar: null,
  toggleBtn: null,
  hamburgerBtn: null,

  init() {
    this.sidebar = document.querySelector('.sidebar');
    this.overlay = document.querySelector('.sidebar-overlay');
    this.mainContent = document.querySelector('.main-content');
    this.topNavbar = document.querySelector('.top-navbar');
    this.toggleBtn = document.querySelector('.sidebar-toggle-btn');
    this.hamburgerBtn = document.querySelector('.hamburger-btn');

    if (this.toggleBtn) {
      this.toggleBtn.addEventListener('click', () => this.toggleDesktop());
    }

    if (this.hamburgerBtn) {
      this.hamburgerBtn.addEventListener('click', () => this.toggleMobile());
    }

    if (this.overlay) {
      this.overlay.addEventListener('click', () => this.closeMobile());
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.closeMobile();
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 991) {
        this.closeMobile();
        this.overlay?.classList.remove('show');
      }
    });

    this._applyState();
  },

  toggleDesktop() {
    document.body.classList.toggle('sidebar-collapsed');
    this._applyState();
    localStorage.setItem('ipna-sidebar-collapsed',
      document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
    );
  },

  toggleMobile() {
    this.sidebar?.classList.toggle('show');
    this.overlay?.classList.toggle('show');
    document.body.style.overflow = this.sidebar?.classList.contains('show') ? 'hidden' : '';
  },

  closeMobile() {
    this.sidebar?.classList.remove('show');
    this.overlay?.classList.remove('show');
    document.body.style.overflow = '';
  },

  _applyState() {
    const collapsed = document.body.classList.contains('sidebar-collapsed');
    if (this.mainContent) {
      this.mainContent.style.marginRight = collapsed ? '0' : '';
      this.mainContent.style.width = collapsed ? '100%' : '';
    }
    if (this.topNavbar) {
      this.topNavbar.style.right = collapsed ? '0' : '';
    }
  },

  restore() {
    if (localStorage.getItem('ipna-sidebar-collapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
      this._applyState();
    }
  }
};

/* ============================================================
   3. AJAX HELPER FUNCTIONS
   ============================================================ */
async function fetchAPI(url, options = {}) {
  const headers = {
    'X-CSRF-TOKEN': CSRF.getToken(),
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    ...options.headers
  };

  if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  try {
    const response = await fetch(url, {
      ...options,
      headers
    });

    if (response.status === 401 || response.status === 419) {
      window.location.reload();
      return null;
    }

    if (response.status === 204) {
      return { ok: true, status: 204 };
    }

    const data = await response.json();

    if (!response.ok) {
      throw { status: response.status, data };
    }

    return data;
  } catch (error) {
    if (error.status) throw error;
    showToast('خطا در ارتباط با سرور', 'error');
    throw error;
  }
}

async function postAPI(url, data = {}) {
  return fetchAPI(url, {
    method: 'POST',
    body: data
  });
}

async function putAPI(url, data = {}) {
  return fetchAPI(url, {
    method: 'PUT',
    body: data
  });
}

async function deleteAPI(url) {
  return fetchAPI(url, { method: 'DELETE' });
}

async function getAPI(url) {
  return fetchAPI(url, { method: 'GET' });
}

/* ============================================================
   4. TOAST NOTIFICATION SYSTEM
   ============================================================ */
const Toast = {
  container: null,

  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'success', duration = 4000) {
    if (!this.container) this.init();

    const icons = {
      success: 'bi-check-circle-fill',
      error: 'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill'
    };

    const toast = document.createElement('div');
    toast.className = `ipna-toast toast-${type}`;
    toast.innerHTML = `
      <span class="toast-icon"><i class="bi ${icons[type] || icons.info}"></i></span>
      <span class="toast-message">${message}</span>
      <button class="toast-close" onclick="Toast.dismiss(this.parentElement)">&times;</button>
      <div class="toast-progress"><div class="toast-progress-bar" style="animation-duration: ${duration}ms"></div></div>
    `;

    this.container.appendChild(toast);

    const timer = setTimeout(() => this.dismiss(toast), duration);
    toast._timer = timer;

    return toast;
  },

  dismiss(toast) {
    if (!toast || !toast.parentElement) return;
    clearTimeout(toast._timer);
    toast.classList.add('toast-removing');
    setTimeout(() => toast.remove(), 300);
  }
};

function showToast(message, type = 'success', duration = 4000) {
  return Toast.show(message, type, duration);
}

/* ============================================================
   5. FORM VALIDATION HELPERS
   ============================================================ */
const FormValidator = {
  validate(form) {
    let isValid = true;
    this.clearErrors(form);

    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        this.showError(field, 'این فیلد الزامی است');
        isValid = false;
      }
    });

    const emailFields = form.querySelectorAll('[type="email"]');
    emailFields.forEach(field => {
      if (field.value && !this.isValidEmail(field.value)) {
        this.showError(field, 'ایمیل صحیح وارد کنید');
        isValid = false;
      }
    });

    const minFields = form.querySelectorAll('[min-length]');
    minFields.forEach(field => {
      const min = parseInt(field.getAttribute('min-length'));
      if (field.value && field.value.length < min) {
        this.showError(field, `حداقل ${min} کاراکتر وارد کنید`);
        isValid = false;
      }
    });

    const matchFields = form.querySelectorAll('[data-match]');
    matchFields.forEach(field => {
      const target = form.querySelector(field.getAttribute('data-match'));
      if (target && field.value !== target.value) {
        this.showError(field, 'مقدار وارد شده مطابقت ندارد');
        isValid = false;
      }
    });

    const numericFields = form.querySelectorAll('[data-numeric]');
    numericFields.forEach(field => {
      if (field.value && isNaN(field.value)) {
        this.showError(field, 'لطفاً عدد صحیح وارد کنید');
        isValid = false;
      }
    });

    return isValid;
  },

  showError(field, message) {
    field.classList.add('is-invalid');
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    feedback.textContent = message;
  },

  clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
  },

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
};

/* ============================================================
   6. CONFIRM DELETE DIALOG
   ============================================================ */
function confirmDelete(message = 'آیا از حذف این مورد اطمینان دارید؟', callback) {
  const modal = document.getElementById('confirmModal');
  if (modal) {
    const bodyEl = modal.querySelector('.modal-body p, .modal-body');
    if (bodyEl) bodyEl.textContent = message;
    const confirmBtn = modal.querySelector('.btn-confirm-delete');
    if (confirmBtn && callback) {
      confirmBtn.onclick = () => {
        callback();
        bootstrap.Modal.getInstance(modal)?.hide();
      };
    }
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  } else {
    if (confirm(message)) {
      callback?.();
    }
  }
}

function confirmAction(message, callback) {
  confirmDelete(message, callback);
}

/* ============================================================
   7. DATE PICKER INITIALIZATION
   ============================================================ */
function initPersianDatePickers() {
  const pickers = document.querySelectorAll('.persian-date');
  pickers.forEach(input => {
    if (input.type === 'date') {
      input.setAttribute('lang', 'fa');
    }
  });
}

function initFlatpickr() {
  if (typeof flatpickr !== 'undefined') {
    flatpickr('.persian-date', {
      locale: 'fa',
      dateFormat: 'Y-m-d',
      disableMobile: true,
      direction: 'rtl'
    });
  }
}

/* ============================================================
   8. DATA TABLE INITIALIZATION
   ============================================================ */
function initDataTables() {
  if (typeof $.fn.DataTable === 'undefined') return;

  $.extend($.fn.dataTable.ext.classes, {
    sWrapper: 'dataTables_wrapper dt-bootstrap5',
    sFilterInput: 'form-control form-control-sm',
    sLengthSelect: 'form-select form-select-sm'
  });

  $('.data-table').each(function () {
    if ($.fn.DataTable.isDataTable(this)) return;

    const options = {
      pageLength: parseInt(this.dataset.pageLength) || 10,
      order: [],
      language: {
        search: 'جستجو:',
        lengthMenu: 'نمایش _MENU_ رکورد',
        info: 'نمایش _START_ تا _END_ از _TOTAL_ رکورد',
        infoEmpty: 'رکوردی یافت نشد',
        infoFiltered: '(فیلتر شده از _MAX_ رکورد)',
        zeroRecords: 'داده‌ای یافت نشد',
        paginate: {
          first: 'اول',
          last: 'آخر',
          next: 'بعدی',
          previous: 'قبلی'
        }
      },
      responsive: true,
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
    };

    const customSort = this.dataset.sortColumn;
    if (customSort) {
      options.order = [[parseInt(customSort), this.dataset.sortDirection || 'asc']];
    }

    $(this).DataTable(options);
  });
}

/* ============================================================
   9. CHART INITIALIZATION HELPERS
   ============================================================ */
function initDashboardCharts() {
  document.querySelectorAll('[data-chart-type]').forEach(canvas => {
    const type = canvas.dataset.chartType;
    const url = canvas.dataset.chartUrl;
    if (url) {
      fetchAPI(url).then(data => {
        if (data && typeof IPNACharts !== 'undefined') {
          const config = {
            labels: data.labels || [],
            datasets: data.datasets || data.data || []
          };
          switch (type) {
            case 'line':
              IPNACharts.createLineChart(canvas.id, config.labels, config.datasets);
              break;
            case 'bar':
              IPNACharts.createBarChart(canvas.id, config.labels, config.datasets);
              break;
            case 'doughnut':
              IPNACharts.createDoughnutChart(canvas.id, config.labels, config.datasets);
              break;
            case 'radar':
              IPNACharts.createRadarChart(canvas.id, config.labels, config.datasets);
              break;
          }
        }
      }).catch(() => {});
    }
  });
}

/* ============================================================
   10. AUTO-REFRESH DASHBOARD
   ============================================================ */
const DashboardAutoRefresh = {
  interval: null,
  defaultInterval: 300000,

  start(interval) {
    this.stop();
    this.interval = setInterval(() => this.refresh(), interval || this.defaultInterval);
  },

  stop() {
    if (this.interval) {
      clearInterval(this.interval);
      this.interval = null;
    }
  },

  async refresh() {
    const dataUrl = document.body.dataset.dashboardRefreshUrl;
    if (!dataUrl) return;

    try {
      const data = await fetchAPI(dataUrl);
      if (data) {
        this._updateCards(data);
        this._updateTimestamp();
      }
    } catch (e) {
      console.warn('Dashboard refresh failed:', e);
    }
  },

  _updateCards(data) {
    Object.entries(data.kpis || {}).forEach(([key, value]) => {
      const el = document.querySelector(`[data-kpi="${key}"]`);
      if (el) {
        el.textContent = value;
        el.classList.add('animate-pulse');
        setTimeout(() => el.classList.remove('animate-pulse'), 1000);
      }
    });
  },

  _updateTimestamp() {
    const el = document.querySelector('.last-updated');
    if (el) {
      el.textContent = `آخرین بروزرسانی: ${new Date().toLocaleTimeString('fa-IR')}`;
    }
  }
};

/* ============================================================
   11. ACTIVITY FORM QUICK-SUBMIT
   ============================================================ */
const ActivityForm = {
  form: null,
  step: 1,
  totalSteps: 1,

  init() {
    this.form = document.querySelector('.activity-form form');
    if (!this.form) return;

    this.totalSteps = this.form.querySelectorAll('.form-step').length || 1;
    if (this.totalSteps > 1) {
      this._setupSteps();
    }

    this._setupQuickSubmit();
    this._setupAutoSave();
  },

  goToStep(step) {
    if (step < 1 || step > this.totalSteps) return;
    this.form.querySelectorAll('.form-step').forEach((el, i) => {
      el.style.display = (i + 1 === step) ? 'block' : 'none';
    });
    this.step = step;
    this._updateStepIndicators();
  },

  _setupSteps() {
    this.goToStep(1);

    this.form.querySelectorAll('.btn-next-step').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.goToStep(this.step + 1);
      });
    });

    this.form.querySelectorAll('.btn-prev-step').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.goToStep(this.step - 1);
      });
    });
  },

  _updateStepIndicators() {
    this.form.querySelectorAll('.step-indicator').forEach((el, i) => {
      el.classList.toggle('active', i + 1 === this.step);
      el.classList.toggle('completed', i + 1 < this.step);
    });
  },

  _setupQuickSubmit() {
    const quickBar = document.querySelector('.quick-submit-bar');
    if (!quickBar) return;

    const input = quickBar.querySelector('input, textarea');
    const submitBtn = quickBar.querySelector('.btn');

    if (input && submitBtn) {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          this.quickSubmit(input.value);
        }
      });

      submitBtn.addEventListener('click', () => {
        this.quickSubmit(input.value);
      });
    }
  },

  async quickSubmit(text) {
    if (!text?.trim()) return;

    const url = this.form.dataset.quickSubmitUrl || this.form.action;
    try {
      await postAPI(url, { description: text.trim() });
      showToast('فعالیت ثبت شد', 'success');
      const input = document.querySelector('.quick-submit-bar input, .quick-submit-bar textarea');
      if (input) input.value = '';
    } catch (e) {
      showToast('خطا در ثبت فعالیت', 'error');
    }
  },

  _setupAutoSave() {
    let timeout;
    this.form?.addEventListener('input', () => {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        const formData = new FormData(this.form);
        const url = this.form.dataset.autosaveUrl;
        if (url) {
          fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': CSRF.getToken(),
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).catch(() => {});
        }
      }, 3000);
    });
  }
};

/* ============================================================
   12. SEARCH FILTER HANDLERS
   ============================================================ */
const SearchFilters = {
  init() {
    document.querySelectorAll('.filter-bar form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.applyFilters(form);
      });
    });

    document.querySelectorAll('.filter-bar select').forEach(select => {
      select.addEventListener('change', () => {
        const form = select.closest('form');
        if (form) this.applyFilters(form);
      });
    });

    document.querySelectorAll('.filter-bar input[type="search"], .filter-bar input[type="text"]').forEach(input => {
      let debounceTimer;
      input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          const form = input.closest('form');
          if (form) this.applyFilters(form);
        }, 400);
      });
    });

    document.querySelectorAll('.filter-reset').forEach(btn => {
      btn.addEventListener('click', () => {
        const form = btn.closest('form');
        if (form) {
          form.reset();
          this.applyFilters(form);
        }
      });
    });
  },

  applyFilters(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    const url = form.action || window.location.pathname;
    const separator = url.includes('?') ? '&' : '?';
    window.location.href = `${url}${separator}${params.toString()}`;
  }
};

/* ============================================================
   13. EXPORT BUTTON HANDLERS
   ============================================================ */
const ExportHandlers = {
  init() {
    document.querySelectorAll('[data-export]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.handleExport(btn);
      });
    });
  },

  async handleExport(btn) {
    const format = btn.dataset.export || 'excel';
    const url = btn.dataset.exportUrl || btn.closest('form')?.action;

    if (!url) {
      showToast('آدرس خروجی یافت نشد', 'error');
      return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> در حال پردازش...';

    try {
      const params = new URLSearchParams();
      params.set('format', format);

      const form = btn.closest('form');
      if (form) {
        const formData = new FormData(form);
        for (const [key, value] of formData.entries()) {
          params.set(key, value);
        }
      }

      const response = await fetch(`${url}?${params.toString()}`, {
        headers: {
          'X-CSRF-TOKEN': CSRF.getToken(),
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) throw new Error('Export failed');

      const blob = await response.blob();
      const disposition = response.headers.get('content-disposition');
      let filename = `export_${Date.now()}`;
      if (disposition) {
        const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (match) filename = match[1].replace(/['"]/g, '');
      }

      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename;
      a.click();
      URL.revokeObjectURL(a.href);

      showToast('فایل با موفقیت دانلود شد', 'success');
    } catch (e) {
      showToast('خطا در خروجی گیری', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }
};

/* ============================================================
   14. FLASH MESSAGE DISPLAY ON PAGE LOAD
   ============================================================ */
function displayFlashMessages() {
  const flashes = document.querySelectorAll('.flash-message, [data-flash]');
  flashes.forEach(el => {
    const type = el.dataset.flashType || el.dataset.flash || 'info';
    const message = el.dataset.flashMessage || el.textContent?.trim();
    if (message) {
      showToast(message, type);
    }
    el.remove();
  });

  const successEl = document.querySelector('[data-success]');
  if (successEl) {
    showToast(successEl.dataset.success, 'success');
    successEl.remove();
  }

  const errorEl = document.querySelector('[data-error]');
  if (errorEl) {
    showToast(errorEl.dataset.error, 'error');
    errorEl.remove();
  }
}

/* ============================================================
   15. PROGRESS BAR ANIMATION
   ============================================================ */
function animateProgressBars() {
  document.querySelectorAll('.progress-bar[data-value]').forEach(bar => {
    const value = parseInt(bar.dataset.value) || 0;
    bar.style.width = '0%';
    setTimeout(() => {
      bar.style.width = `${Math.min(value, 100)}%`;
    }, 100);
  });

  document.querySelectorAll('.progress-bar[data-target]').forEach(bar => {
    const target = parseInt(bar.dataset.target) || 0;
    bar.style.width = '0%';
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            bar.style.width = `${Math.min(target, 100)}%`;
          }, 200);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    observer.observe(bar.closest('.progress') || bar);
  });
}

/* ============================================================
   16. UTILITY FUNCTIONS
   ============================================================ */
function formatNumber(num) {
  if (num === null || num === undefined) return '۰';
  return new Intl.NumberFormat('fa-IR').format(num);
}

function formatPercent(num) {
  if (num === null || num === undefined) return '۰٪';
  return `${new Intl.NumberFormat('fa-IR').format(num)}٪`;
}

function debounce(fn, delay = 300) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

function throttle(fn, delay = 300) {
  let last = 0;
  return function (...args) {
    const now = Date.now();
    if (now - last >= delay) {
      last = now;
      fn.apply(this, args);
    }
  };
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    showToast('کپی شد', 'success', 2000);
  }).catch(() => {
    showToast('خطا در کپی', 'error');
  });
}

function printSection(selector) {
  const el = document.querySelector(selector);
  if (!el) return;
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
      <meta charset="UTF-8">
      <title>چاپ</title>
      <style>
        body { font-family: 'Vazirmatn', Tahoma, sans-serif; direction: rtl; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background: #f5f5f5; }
      </style>
    </head>
    <body>${el.innerHTML}</body>
    </html>
  `);
  printWindow.document.close();
  printWindow.print();
  printWindow.close();
}

/* ============================================================
   17. PAGE LOADER
   ============================================================ */
function hidePageLoader() {
  const loader = document.querySelector('.page-loader');
  if (loader) {
    loader.style.opacity = '0';
    setTimeout(() => loader.remove(), 300);
  }
}

/* ============================================================
   18. SMOOTH SCROLL
   ============================================================ */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

/* ============================================================
   19. KEYBOARD SHORTCUTS
   ============================================================ */
function initKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      const searchInput = document.querySelector('.navbar-search input');
      if (searchInput) searchInput.focus();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
      e.preventDefault();
      Sidebar.toggleDesktop();
    }
  });
}

/* ============================================================
   20. INITIALIZATION
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  Sidebar.init();
  Sidebar.restore();

  Toast.init();

  CSRF.getToken();

  Sidebar.init();

  initPersianDatePickers();
  initFlatpickr();
  initDataTables();
  initDashboardCharts();
  initSmoothScroll();
  initKeyboardShortcuts();

  ActivityForm.init();
  SearchFilters.init();
  ExportHandlers.init();

  animateProgressBars();
  displayFlashMessages();
  hidePageLoader();

  if (document.body.dataset.autoRefresh === 'true') {
    DashboardAutoRefresh.start(parseInt(document.body.dataset.refreshInterval) || 300000);
  }
});
