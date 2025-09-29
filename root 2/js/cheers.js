btn.addEventListener('click', async () => {
  const type = btn.dataset.type;
  const id   = btn.dataset.id;

  const params = new URLSearchParams();
  params.append('target_type', type);
  params.append('target_id', id);

  const res = await fetch('cheer_toggle.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: params.toString()
  });
  const json = await res.json();

  if (json.ok && json.status === 'added') {
    btn.querySelector('img').src = 'images/ouen_active.png';
  } else if (json.ok && json.status === 'removed') {
    btn.querySelector('img').src = 'images/ouen.png';
  } else if (json.error === 'selfNotAllowed') {
    alert('自分の記録には応援できません');
  } else {
    alert('応援できませんでした');
    console.log(json);
  }
});