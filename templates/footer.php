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
</script>
</body>
</html>
