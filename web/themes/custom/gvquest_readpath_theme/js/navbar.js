(function (Drupal, once) {
  Drupal.behaviors.gvquestNavbar = {
    attach: function (context) {
      const toggles = once('gvquest-navbar-toggle', '.js-gvquest-nav-toggle', context);
      toggles.forEach(function (toggle) {
        const targetId = toggle.getAttribute('aria-controls');
        const target = document.getElementById(targetId);
        if (!target) {
          return;
        }
        toggle.addEventListener('click', function () {
          const expanded = toggle.getAttribute('aria-expanded') === 'true';
          toggle.setAttribute('aria-expanded', (!expanded).toString());
          target.classList.toggle('hidden');
        });
      });
    }
  };
})(Drupal, once);
