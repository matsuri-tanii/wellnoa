(() => {
  const fab = document.querySelector('.fab-chip');
  if (!fab) return;

  const footerH = parseInt(
    getComputedStyle(document.documentElement).getPropertyValue('--footer-h')
  ) || 72;

  const SAFE_GAP = 12;

  function adjustFabBottom() {
    const vv = window.visualViewport;
    const keyboardOffset = (vv && vv.height < window.innerHeight)
      ? (window.innerHeight - vv.height)
      : 0;
    fab.style.bottom = `calc(env(safe-area-inset-bottom) + ${footerH + SAFE_GAP + keyboardOffset}px)`;
  }

  window.addEventListener('load', adjustFabBottom);
  window.addEventListener('resize', adjustFabBottom);
  window.addEventListener('orientationchange', adjustFabBottom);
  if (window.visualViewport) {
    visualViewport.addEventListener('resize', adjustFabBottom);
    visualViewport.addEventListener('scroll', adjustFabBottom);
  }

  adjustFabBottom();
})();