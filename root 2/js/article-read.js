(function(){
  const bottom = document.getElementById('bottom');
  const toast  = document.getElementById('toast');
  if (!bottom) return;

  const showToast = (msg) => {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 1800);
  };

  let sent = false;
  const postRead = (articleId) => {
    const params = new URLSearchParams();
    params.append('article_id', articleId);
    fetch('save_article_read.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: params.toString()
    })
    .then(r => r.text())
    .then(t => {
      if (t.trim()==='OK' || t.includes('already')) showToast('読了を記録しました');
      else showToast('読了記録に失敗しました');
    })
    .catch(() => showToast('通信エラーが発生しました'));
  };

  const articleId = document.body.dataset.articleId; // HTMLに data-article-id を入れておく
  const trySend = () => {
    if (sent) return;
    const top = bottom.getBoundingClientRect().top;
    if (top < window.innerHeight) { sent = true; postRead(articleId); }
  };

  window.addEventListener('scroll', trySend);
  window.addEventListener('load', trySend);
})();