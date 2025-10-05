// js/admin-articles.js
document.addEventListener("click", async (e) => {
  const btn = e.target.closest(".toggle-publish-btn");
  if (!btn) return;

  const id = btn.dataset.id;
  const newState = btn.dataset.state === "1" ? 0 : 1;

  btn.disabled = true;

  try {
    const params = new URLSearchParams();
    params.append("id", id);
    params.append("is_published", newState);

    const res = await fetch("admin_article_toggle.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString(),
    });

    const text = await res.text();

    if (text.trim() === "OK") {
      // ボタン表示更新
      btn.dataset.state = String(newState);
      btn.textContent = newState === 1 ? "公開中" : "非公開";
      btn.classList.toggle("published", newState === 1);
      btn.classList.toggle("unpublished", newState === 0);
    } else {
      alert("更新に失敗しました");
      console.log(text);
    }
  } catch (err) {
    console.error(err);
    alert("通信エラーが発生しました");
  } finally {
    btn.disabled = false;
  }

});