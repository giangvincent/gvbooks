(function (Drupal, drupalSettings, once) {
  "use strict";

  function getSettings() {
    return drupalSettings.gvquestStreaks || {};
  }

  Drupal.gvquestStreaks = Drupal.gvquestStreaks || {};

  Drupal.gvquestStreaks.log = function log(payload) {
    const settings = getSettings();
    if (!settings.endpoint || !settings.csrfToken) {
      return Promise.reject(new Error('Missing streak logging endpoint configuration.'));
    }

    return fetch(settings.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': settings.csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    }).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to log reading progress.');
      }
      return response.json();
    });
  };

  Drupal.behaviors.gvquestStreaksLog = {
    attach(context) {
      once('gvquest-streak-form', '[data-gvquest-streak-log]', context).forEach((element) => {
        element.addEventListener('submit', (event) => {
          event.preventDefault();
          const form = event.currentTarget;
          const payload = {
            book_nid: parseInt(form.dataset.bookNid || '0', 10),
            pages_read: parseInt((form.querySelector('[name="pages_read"]') || {}).value || '0', 10),
            minutes_read: parseInt((form.querySelector('[name="minutes_read"]') || {}).value || '0', 10),
            percent_complete: parseFloat((form.querySelector('[name="percent_complete"]') || {}).value || '0'),
            source: form.dataset.source || 'manual',
          };

          Drupal.gvquestStreaks.log(payload)
            .then((response) => {
              form.dispatchEvent(new CustomEvent('gvquest-streak-logged', { detail: response, bubbles: true }));
            })
            .catch((error) => {
              // eslint-disable-next-line no-console
              console.error('[gvquest_streaks] Log request failed', error);
            });
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
