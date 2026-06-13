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

  document.addEventListener('DOMContentLoaded', function () {
    initCookieBanner();
    initCardHoverGlow();
  });
})();
