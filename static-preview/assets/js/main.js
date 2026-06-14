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
    initMobileNav();
  });
})();
