// js/article-read.js
(function () {
  const badge  = document.getElementById('readBadge');
  const bottom = document.getElementById('bottom');
  if (!bottom) return;

  let alreadyDone = badge ? (badge.dataset.state === 'done') : false;
  let sent = false;
  let lastToastTimer = null;

  const scroller = document.scrollingElement || document.documentElement;

  const loadAt = performance.now();
  const MIN_DWELL_MS   = 3000;
  const MIN_SCROLL_PX  = Math.max(150, Math.round(window.innerHeight * 0.4));
  const READ_THRESHOLD = 0.8;

  const startTop = scroller.scrollTop;

  function scrolledEnough() {
    const delta = Math.max(0, scroller.scrollTop - startTop);
    return delta >= MIN_SCROLL_PX;
  }
  function dwelledEnough() {
    return performance.now() - loadAt >= MIN_DWELL_MS;
  }
  function progressEnough() {
    const p = (scroller.scrollTop + window.innerHeight) / scroller.scrollHeight;
    return p >= READ_THRESHOLD;
  }
  function isTooShort() {
    return scroller.scrollHeight <= window.innerHeight + 20;
  }

  function showToast(msg) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
    requestAnimationFrame(() => el.classList.add('show'));
    clearTimeout(lastToastTimer);
    lastToastTimer = setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => { el.style.display = 'none'; }, 300);
    }, 2500);
  }

  async function markAsRead() {
    if (sent) return;
    sent = true;
    try {
      const params = new URLSearchParams(window.location.search);
      const articleId = parseInt(params.get('id') || '0', 10);
      if (!articleId) return;

      const form = new FormData();
      form.append('article_id', String(articleId));

      const res = await fetch('save_article_read.php', {
        method: 'POST',
        body: form,
        headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => (res.ok ? { ok: true, already: false } : null));
      if (!json || !json.ok) throw new Error(json?.error || 'failed');

      if (!json.already && !alreadyDone) {
        requestAnimationFrame(() => {
          const b = document.getElementById('readBadge');
          if (b) {
            b.dataset.state = 'done';
            const label = b.querySelector('.label');
            if (label) label.textContent = '読了';
          }
          showToast('読了を記録しました！');
        });
        alreadyDone = true;
      }
    } catch (err) {
      console.error(err);
    }
  }

  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (!e.isIntersecting) continue;
        if (alreadyDone) return;
        if (document.visibilityState !== 'visible') return;

        if (isTooShort() || (dwelledEnough() && (scrolledEnough() || progressEnough()))) {
          markAsRead();
          io.disconnect();
        }
      }
    },
    { root: null, rootMargin: '0px 0px -5% 0px', threshold: 0.01 }
  );

  io.observe(bottom);
})();