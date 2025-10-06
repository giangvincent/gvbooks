(function (Drupal, drupalSettings) {
  "use strict";

  const settings = drupalSettings.gvquestAnalytics || {};

  function buildPayload(partial) {
    const now = new Date();
    const iso = now.toISOString();
    return Object.assign({
      started_at: iso,
      ended_at: iso,
      pages_delta: 0,
      current_page: 0,
      percent_complete: 0,
      source: 'manual',
    }, partial || {});
  }

  function postEvent(payload) {
    if (!Drupal.gvquestStreaks || typeof Drupal.gvquestStreaks.log !== 'function') {
      if (window.fetch && settings.endpoint) {
        return fetch(settings.endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': settings.csrfToken,
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload),
        });
      }
      return Promise.reject(new Error('Analytics endpoint unavailable.'));
    }
    return Drupal.gvquestStreaks.log(payload);
  }

  Drupal.gvquestAnalytics = Drupal.gvquestAnalytics || {};
  Drupal.gvquestAnalytics.postEvent = function (partial) {
    return postEvent(buildPayload(partial));
  };

  Drupal.gvquestAnalytics.scheduleHeartbeat = function (options) {
    const config = Object.assign({ interval: 30000 }, options || {});
    if (!config.book_nid) {
      throw new Error('book_nid required for analytics heartbeat');
    }
    const timer = setInterval(() => {
      postEvent(buildPayload({
        book_nid: config.book_nid,
        started_at: new Date().toISOString(),
        ended_at: new Date().toISOString(),
        pages_delta: 0,
        current_page: config.current_page || 0,
        percent_complete: config.percent_complete || 0,
        source: config.source || 'manual',
      })).catch(() => {});
    }, config.interval);
    return () => clearInterval(timer);
  };
})(Drupal, drupalSettings);
