</main>
<div class="footer">&copy; ProfPlanner</div>
<script>
(() => {
  const body = document.body;
  const toggle = document.getElementById('menuToggle');
  const overlay = document.getElementById('navOverlay');
  const nav = document.getElementById('siteNav');
  if (!toggle || !overlay || !nav) return;

  const closeMenu = () => {
    body.classList.remove('nav-open');
    toggle.setAttribute('aria-expanded', 'false');
  };
  const openMenu = () => {
    body.classList.add('nav-open');
    toggle.setAttribute('aria-expanded', 'true');
  };

  toggle.addEventListener('click', () => {
    if (body.classList.contains('nav-open')) closeMenu();
    else openMenu();
  });
  overlay.addEventListener('click', closeMenu);
  nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeMenu();
  });
})();

(() => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  if (!token) return;

  document.querySelectorAll('form[method="post"], form[method="POST"]').forEach((form) => {
    if (!form.querySelector('input[name="csrf_token"]')) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'csrf_token';
      hidden.value = token;
      form.appendChild(hidden);
    }
  });

  const originalFetch = window.fetch.bind(window);
  window.fetch = (input, init = {}) => {
    const options = { ...init };
    const headers = new Headers(options.headers || {});
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD') {
      headers.set('X-CSRF-Token', token);
    }
    options.headers = headers;
    return originalFetch(input, options);
  };
})();
</script>
</body>
</html>
