console.log('[cheers.js] loaded v2');

document.addEventListener('click', async (e) => {
  console.log('[cheers.js] click', e.target);
  const btn = e.target.closest('.support-btn');
  console.log('[cheers.js] closest', btn);
  if (!btn) return;

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
    console.log('[cheers.js] response text:', text);

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