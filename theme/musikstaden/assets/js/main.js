/**
 * Musikstaden front-end scripts.
 */
(function () {
  'use strict';

  var CONSENT_KEY = 'musikstaden_cookie_consent';

  function initCookieBanner() {
    var banner = document.getElementById('cookie-banner');
    if (!banner) return;

    if (localStorage.getItem(CONSENT_KEY)) {
      return;
    }

    banner.hidden = false;

    banner.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cookie]');
      if (!btn) return;

      var choice = btn.getAttribute('data-cookie');
      localStorage.setItem(CONSENT_KEY, choice);
      document.cookie = CONSENT_KEY + '=' + choice + ';path=/;max-age=31536000;SameSite=Lax';
      banner.hidden = true;

      if (choice === 'accept') {
        document.dispatchEvent(new CustomEvent('musikstaden:consent-granted'));
      }
    });
  }

  function initCardHoverGlow() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.querySelectorAll('.artist-card').forEach(function (card) {
      card.addEventListener('mouseenter', function () {
        card.style.borderColor = 'rgba(168, 85, 247, 0.6)';
      });
      card.addEventListener('mouseleave', function () {
        card.style.borderColor = '';
      });
    });
  }

  function initBookingAccordion() {
    document.querySelectorAll('.band-booking__toggle').forEach(function (toggle) {
      var panel = document.getElementById(toggle.getAttribute('aria-controls'));
      if (!panel) return;

      toggle.addEventListener('click', function () {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        panel.hidden = expanded;
      });
    });
  }

  function initCheckDropdowns() {
    document.querySelectorAll('[data-ms-check-dropdown]').forEach(function (dropdown) {
      var trigger = dropdown.querySelector('.ms-check-dropdown__trigger');
      var menu = dropdown.querySelector('.ms-check-dropdown__menu');
      var textEl = dropdown.querySelector('.ms-check-dropdown__text');
      if (!trigger || !menu || !textEl) return;

      function updateLabel() {
        var checked = dropdown.querySelectorAll('[data-ms-check-option]:checked').length;
        var placeholder = textEl.dataset.placeholder || '';
        var countLabel = textEl.dataset.countLabel || '%d valda';
        if (checked > 0) {
          textEl.textContent = countLabel.replace('%d', String(checked));
        } else {
          textEl.textContent = placeholder;
        }
        dropdown.classList.remove('is-invalid');
      }

      function setOpen(isOpen) {
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menu.hidden = !isOpen;
      }

      trigger.addEventListener('click', function () {
        var isOpen = trigger.getAttribute('aria-expanded') === 'true';
        document.querySelectorAll('[data-ms-check-dropdown] .ms-check-dropdown__trigger[aria-expanded="true"]').forEach(function (otherTrigger) {
          if (otherTrigger !== trigger) {
            otherTrigger.setAttribute('aria-expanded', 'false');
            var otherMenu = document.getElementById(otherTrigger.getAttribute('aria-controls'));
            if (otherMenu) otherMenu.hidden = true;
          }
        });
        setOpen(!isOpen);
      });

      dropdown.querySelectorAll('[data-ms-check-option]').forEach(function (input) {
        input.addEventListener('change', updateLabel);
      });

      updateLabel();
    });

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-ms-check-dropdown]')) return;
      document.querySelectorAll('[data-ms-check-dropdown]').forEach(function (dropdown) {
        var trigger = dropdown.querySelector('.ms-check-dropdown__trigger');
        var menu = dropdown.querySelector('.ms-check-dropdown__menu');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (menu) menu.hidden = true;
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      document.querySelectorAll('[data-ms-check-dropdown]').forEach(function (dropdown) {
        var trigger = dropdown.querySelector('.ms-check-dropdown__trigger');
        var menu = dropdown.querySelector('.ms-check-dropdown__menu');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (menu) menu.hidden = true;
      });
    });

    document.querySelectorAll('.band-studio__form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var blocked = false;
        form.querySelectorAll('[data-ms-check-dropdown][data-required]').forEach(function (dropdown) {
          var hasChecked = dropdown.querySelector('[data-ms-check-option]:checked');
          dropdown.classList.toggle('is-invalid', !hasChecked);
          if (!hasChecked) blocked = true;
        });
        if (blocked) e.preventDefault();
      });
    });
  }

  function initMobileNav() {
    var toggle = document.querySelector('.site-nav-toggle');
    var nav = document.getElementById('site-nav');
    if (!toggle || !nav) return;

    var openLabel = toggle.dataset.openLabel || 'Öppna meny';
    var closeLabel = toggle.dataset.closeLabel || 'Stäng meny';

    function setOpen(isOpen) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggle.setAttribute('aria-label', isOpen ? closeLabel : openLabel);
      nav.classList.toggle('is-open', isOpen);
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setOpen(false);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        setOpen(false);
      }
    });

    window.addEventListener('resize', function () {
      if (window.matchMedia('(min-width: 769px)').matches) {
        setOpen(false);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initCookieBanner();
    initCardHoverGlow();
    initBookingAccordion();
    initCheckDropdowns();
    initMobileNav();
  });
})();
