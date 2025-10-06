(function (Drupal, drupalSettings, once) {
  "use strict";

  function renderSeries(container, series, scaleFactor) {
    if (!series || !Array.isArray(series)) {
      return;
    }
    const maxValue = Math.max(1, ...series.map((item) => item.value));
    series.forEach((item) => {
      const span = document.createElement('span');
      const ratio = maxValue === 0 ? 0 : item.value / maxValue;
      const size = Math.max(4, Math.round(ratio * scaleFactor));
      span.style.height = `${size}px`;
      span.title = `${item.date}: ${item.value}`;
      container.appendChild(span);
    });
  }

  Drupal.behaviors.gvquestAnalyticsDashboard = {
    attach(context) {
      const settings = drupalSettings.gvquestAnalytics || {};
      const series = settings.series || {};

      once('gvquest-analytics-sparkline', '.gvquest-analytics__sparkline', context).forEach((element) => {
        const data = series[element.dataset.series] || [];
        renderSeries(element, data, 40);
      });

      once('gvquest-analytics-barchart', '.gvquest-analytics__barchart', context).forEach((element) => {
        const data = series[element.dataset.series] || [];
        renderSeries(element, data, 80);
      });
    },
  };
})(Drupal, drupalSettings, once);
