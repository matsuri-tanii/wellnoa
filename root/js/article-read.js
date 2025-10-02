// js/article-read.js
(function () {
  // すでに読了表示なら何もしない（トーストは「未読→読了」でだけ出す）
  const badge = document.getElementById('readBadge');
  const bottom = document.getElementById('bottom');
  if (!bottom) return;

  let alreadyDone = badge ? (badge.dataset.state === 'done') : false;
  let sent = false; // 二重送信防止
  let lastToastTimer = null;

  function showToast(msg) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
    // 再起動用
    void el.offsetWidth; 
    el.classList.add('show');
    clearTimeout(lastToastTimer);
    lastToastTimer = setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => { el.style.display = 'none'; }, 300);
    }, 2000);
  }

  async function markAsRead() {
    if (sent) return;
    sent = true;
    try {
      const params = new URLSearchParams(window.location.search);
      const articleId = parseInt(params.get('id') || '0', 10);
      if (!articleId) return;

      const form = new FormData();
      form.append('article_id', articleId.toString());

      const res = await fetch('save_article_read.php', {
        method: 'POST',
        body: form,
        headers: { 'X-Requested-With': 'fetch' },
        credentials: 'same-origin',
      });

      const json = await res.json();
      if (!json.ok) throw new Error(json.error || 'failed');

      // 既に読了済みの場合はトーストを出さない（初期が未読→今回で読了の時だけ出す）
      if (!json.already && !alreadyDone) {
        // バッジを「読了」に更新
        if (badge) {
          badge.dataset.state = 'done';
          const label = badge.querySelector('.label');
          if (label) label.textContent = '読了';
        }
        showToast('読了を記録しました！');
        alreadyDone = true;
      }
    } catch (err) {
      console.error(err);
      // エラー時は静かにスルー（必要ならトースト出してもOK）
      // showToast('記録に失敗しました…');
    }
  }

  // IntersectionObserver で #bottom が見えたら記録
  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          markAsRead();
          // 一度で十分
          io.disconnect();
          break;
        }
      }
    },
    {
      // フッターに隠れて発火しない問題を避けるため「下方向に余裕」を持たせる
      root: null,
      rootMargin: '0px 0px -25% 0px', // 画面の下25%手前で発火
      threshold: 0,
    }
  );
  io.observe(bottom);

  // 念のためのフォールバック（極端に短い記事など）
  window.addEventListener('load', () => {
    // 既に下端近くまで見えてる場合の保険
    if (bottom.getBoundingClientRect().top < window.innerHeight * 0.75) {
      markAsRead();
      io.disconnect();
    }
  });
})();