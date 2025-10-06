document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.support-btn');
  if (!btn) return;
  if (btn.classList.contains('is-disabled') || btn.disabled) return;

  const type = btn.dataset.type;
  const id   = btn.dataset.id;
  if (!type || !id) return;

  const countEl = btn.parentElement.querySelector('.support-count');

  btn.disabled = true; // 連打防止
  try {
    const form = new FormData();
    form.append('target_type', type);
    form.append('target_id', id);

    const res = await fetch('cheer_toggle.php', {
      method: 'POST',
      body: form,
      headers: { 'X-Requested-With': 'fetch' },
      credentials: 'same-origin',
    });

    const text = await res.text();
    const json = JSON.parse(text);
    if (!json.ok) throw new Error(json.error || 'toggle failed');

    // 見た目更新
    if (json.cheered) {
      btn.classList.add('is-on');
      btn.setAttribute('aria-pressed', 'true');
      btn.setAttribute('aria-label', '応援をやめる');
      const img = btn.querySelector('img'); if (img) img.alt = '応援中';
    } else {
      btn.classList.remove('is-on');
      btn.setAttribute('aria-pressed', 'false');
      btn.setAttribute('aria-label', '応援する');
      const img = btn.querySelector('img'); if (img) img.alt = '応援する';
    }

    if (countEl && typeof json.count === 'number') {
      countEl.textContent = `応援 ${json.count}`;
    }
  } catch (err) {
    console.error(err);
    alert('応援の更新に失敗しました。通信環境をご確認ください。');
  } finally {
    btn.disabled = false;
  }
});