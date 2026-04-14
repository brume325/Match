/**
 * Centralizes lightweight UI behavior to keep HTML templates declarative.
 */
(function bootstrapClient() {
  const navToggle = document.querySelector('[data-nav-toggle]');
  const navTarget = document.querySelector('[data-nav-target]');

  if (!navToggle || !navTarget) {
    return;
  }

  navToggle.addEventListener('click', function handleNavToggle() {
    const expanded = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    navTarget.classList.toggle('is_open', !expanded);
  });
})();
