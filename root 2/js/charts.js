// js/charts.js

// ページ読み込み時に実行
document.addEventListener("DOMContentLoaded", () => {
  // 1. 体調と心の調子の折線グラフ
  const ctxCondition = document.getElementById("chart-condition");
  if (ctxCondition) {
    new Chart(ctxCondition, {
      type: "line",
      data: {
        labels: ["9/1", "9/2", "9/3", "9/4", "9/5"], // ← PHPで日付を埋め込むと良い
        datasets: [
          {
            label: "体調",
            data: [70, 65, 80, 75, 85], // ← PHPから動的に生成
            borderColor: "#76c7b0",
            fill: false,
            tension: 0.3,
          },
          {
            label: "心の調子",
            data: [60, 70, 65, 72, 78],
            borderColor: "#ff9f40",
            fill: false,
            tension: 0.3,
          },
        ],
      },
    });
  }

  // 2. 記事カテゴリ割合（円グラフ）
  const ctxCategory = document.getElementById("chart-category");
  if (ctxCategory) {
    new Chart(ctxCategory, {
      type: "doughnut",
      data: {
        labels: ["睡眠", "運動", "食事", "メンタル"],
        datasets: [
          {
            data: [5, 8, 3, 4], // ← PHPから動的に埋め込む
            backgroundColor: ["#4dc9f6", "#f67019", "#f53794", "#537bc4"],
          },
        ],
      },
      options: {
        plugins: {
          legend: { position: "bottom" },
        },
      },
    });
  }

  // 3. 行動割合（棒グラフ）
  const ctxActivity = document.getElementById("chart-activity");
  if (ctxActivity) {
    new Chart(ctxActivity, {
      type: "bar",
      data: {
        labels: ["運動", "読書", "日記", "睡眠"],
        datasets: [
          {
            label: "回数",
            data: [10, 6, 8, 4], // ← PHPから集計結果を渡す
            backgroundColor: "#76c7b0",
          },
        ],
      },
      options: {
        scales: { y: { beginAtZero: true } },
      },
    });
  }
});