<?php
declare(strict_types=1);

require_once __DIR__.'/funcs.php';
adopt_incoming_code();

$DEBUG = false; // ← 必要なら true

try { $pdo = db_conn(); }
catch (Throwable $e) { if ($DEBUG) echo "<pre>DB CONNECT ERROR: ".h($e->getMessage())."</pre>"; http_response_code(500); exit; }

$uid = (int) current_anon_user_id();

// ========= 入力 =========
$cat     = trim((string)($_GET['cat'] ?? ''));
$q       = trim((string)($_GET['q'] ?? ''));
$sort    = (string)($_GET['sort'] ?? 'new'); // 'new'|'old'
$unread  = !empty($_GET['unread']);
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 12;
$offset  = ($page - 1) * $limit;

// ========= 新着しきい値 =========
$daysNew = 7;
$newThreshold = date('Y-m-d H:i:s', strtotime("-{$daysNew} days"));
$orderDir = ($sort === 'old') ? 'ASC' : 'DESC';

// ========= カテゴリ一覧 =========
$cats = [];
try {
  $cats = $pdo->query("SELECT category, COUNT(*) c FROM articles GROUP BY category ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { if ($DEBUG) echo "<pre>CATS SQL ERROR: ".h($e->getMessage())."</pre>"; }

// ========= WHERE構築 =========
$where = [];
$params = [];

if ($cat !== '') {
  $where[] = 'a.category = :cat';
  $params[':cat'] = $cat;
}
if ($q !== '') {
  $where[] = '(COALESCE(a.title, "") LIKE :kw OR COALESCE(a.description, "") LIKE :kw)';
  $params[':kw'] = '%'.$q.'%';
}
if ($unread && $uid) {
  $where[] = 'NOT EXISTS (SELECT 1 FROM article_reads r WHERE r.article_id = a.id AND r.anonymous_user_id = :me)';
  $params[':me'] = $uid;
}
$whereSql = $where ? ' WHERE '.implode(' AND ', $where) : '';

// ========= 件数 =========
$totalRows = 0;
try {
  $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM articles a{$whereSql}");
  $stmtCount->execute($params);
  $totalRows = (int)$stmtCount->fetchColumn();
} catch (Throwable $e) { if ($DEBUG) echo "<pre>COUNT SQL ERROR: ".h($e->getMessage())."</pre>"; }

$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $limit; }

// ========= 本体 =========
$articles = [];
try {
  $sql = "
    SELECT
      a.id, a.title, a.description, a.thumbnail_url, a.published_at, a.source_name, a.category,
      EXISTS(SELECT 1 FROM article_reads ar WHERE ar.article_id = a.id AND ar.anonymous_user_id = :uid) AS is_read,
      CASE WHEN a.published_at IS NOT NULL AND a.published_at >= :th THEN 1 ELSE 0 END AS is_new
    FROM articles a
    {$whereSql}
    ORDER BY (a.published_at IS NULL), a.published_at {$orderDir}, a.id {$orderDir}
    LIMIT :lim OFFSET :ofs
  ";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) { $stmt->bindValue($k, $k === ':me' ? (int)$v : $v, $k === ':me' ? PDO::PARAM_INT : PDO::PARAM_STR); }
  $stmt->bindValue(':uid', (int)$uid, PDO::PARAM_INT);
  $stmt->bindValue(':th',  $newThreshold, PDO::PARAM_STR);
  $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
  $stmt->bindValue(':ofs', (int)$offset, PDO::PARAM_INT);
  $stmt->execute();
  $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { if ($DEBUG) echo "<pre>LIST SQL ERROR: ".h($e->getMessage())."</pre>"; }

// ========= UTILS =========
function build_query(array $overrides = []): string {
  $base = ['cat'=>$_GET['cat']??'','q'=>$_GET['q']??'','sort'=>$_GET['sort']??'new','unread'=>!empty($_GET['unread'])?'1':''];
  $q = array_merge($base, $overrides);
  foreach ($q as $k=>$v) if ($v === '' || $v === null) unset($q[$k]);
  return http_build_query($q);
}
$fallbackThumb = 'images/article_placeholder.png';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Wellnoa - 健康記事</title>
<link rel="stylesheet" href="css/reset.css">
<link rel="stylesheet" href="css/variables.css">
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/layout.css">
<link rel="stylesheet" href="css/nav.css">
<link rel="stylesheet" href="css/components.css">
<link rel="stylesheet" href="css/forms.css">
<link rel="stylesheet" href="css/notices.css">
<link rel="stylesheet" href="css/utilities.css">
<style>
/* ── コンパクト表示/フォーム行揃え ───────────────────── */
.compact-articles h2{font-size:18px;margin:4px 0 2px;}
.compact-articles h3{font-size:14px;margin:0 0 6px;}
.compact-articles details.filters{margin:4px 0 8px;border:1px solid #eee;border-radius:8px;background:#fff;}
.compact-articles details.filters>summary{
  -webkit-user-select:none; user-select:none;
  padding:6px 10px;font-size:13px;font-weight:600;cursor:pointer;list-style:none;
}
.compact-articles details.filters[open]>summary{border-bottom:1px solid #eee;}

.compact-articles .filter-bar{
  display:flex; flex-wrap:wrap; gap:8px 10px; align-items:center; padding:8px; margin:0;
}
/* 共通高さ */
.compact-articles .filter-bar select,
.compact-articles .filter-bar input[type="search"],
.compact-articles .filter-bar .btn{ height:34px; font-size:14px; border-radius:8px; }

/* セレクト2つを横並びに固定幅 */
.compact-articles .filter-bar .selects{ display:flex; gap:8px; flex:0 0 auto; }
.compact-articles .filter-bar .selects select{
  width:auto !important; flex:0 0 180px; max-width:220px; padding:0 10px;
}

/* 検索は可変 */
.compact-articles .filter-bar input[type="search"]{
  width:auto !important; min-width:180px; flex:1 1 220px; padding:0 10px;
}

/* label 全般の block化を打消し＋揃える */
.compact-articles .filter-bar label{ display:inline-flex; align-items:center; gap:6px; margin:0; font-size:13px; }
.compact-articles .filter-bar .check{ height:34px; padding:0 4px; }
.compact-articles .filter-bar .check input{ margin:0; }

/* ボタン */
.compact-articles .filter-bar .btn{ display:inline-flex; align-items:center; padding:0 12px; }

/* カード */
.compact-articles .grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));}
.compact-articles .card.article{padding:10px;border-radius:8px;}
.compact-articles .card.article .thumb{width:100%;height:120px;object-fit:cover;border-radius:6px;}
.compact-articles .title{font-size:15px;margin:6px 0 4px;line-height:1.3;}
.compact-articles .meta{font-size:12px;opacity:.75;}
.compact-articles .badge{font-size:11px;padding:1px 6px;border-radius:999px;}
/* ページャ */
.compact-articles .pager{display:flex;gap:6px;justify-content:center;margin:12px 0;}
.compact-articles .pager a,.compact-articles .pager span{padding:4px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;}
.compact-articles .is-current{background:#eef6f4;font-weight:600;}

@media (max-width: 480px){
  .compact-articles .filter-bar .selects select{ flex:1 1 160px; }
  .compact-articles .filter-bar input[type="search"]{ flex:1 1 240px; }
}
</style>
</head>
<body>
<div class="layout compact-articles">
  <?php require __DIR__.'/inc/header.php'; ?>
  <aside class="side-nav"><?php require __DIR__.'/inc/side_nav.php'; ?></aside>

  <main class="main">
    <?php require __DIR__.'/inc/notices.php'; ?>

    <div class="container center">
      <h2>Articles</h2>
      <h3>健康記事を読む</h3>
    </div>

    <!-- フィルタ（PCは開いた状態でOK。閉じたいときは open を外す） -->
    <details class="filters" open>
      <summary>絞り込み・検索</summary>
      <form method="get" class="filter-bar" role="search">
        <div class="selects">
          <select name="cat" aria-label="カテゴリ">
            <option value="">すべてのカテゴリ</option>
            <?php foreach ($cats as $c): $v=(string)$c['category']; ?>
              <option value="<?=h($v)?>" <?=($v===$cat)?'selected':''?>><?=h($v)?> (<?= (int)$c['c']?>)</option>
            <?php endforeach; ?>
          </select>

          <select name="sort" aria-label="並び替え">
            <option value="new" <?=($sort==='new')?'selected':''?>>新しい順</option>
            <option value="old" <?=($sort==='old')?'selected':''?>>古い順</option>
          </select>
        </div>

        <input type="search" name="q" value="<?=h($q)?>" placeholder="キーワード" aria-label="キーワード">

        <label class="check">
          <input type="checkbox" name="unread" value="1" <?= $unread?'checked':''?>> 未読のみ
        </label>

        <button class="btn">適用</button>
        <a class="btn" href="articles.php">リセット</a>
      </form>
    </details>

    <div class="meta" style="font-size:12px;opacity:.7;margin-bottom:8px;">
      件数: <?=number_format($totalRows)?>件 (<?= $page ?>/<?= $totalPages ?>)
      <?php if ($DEBUG): ?><span style="color:#b00;">[DEBUG] whereSql: <?= h($whereSql) ?></span><?php endif; ?>
    </div>

    <!-- 一覧 -->
    <div class="grid">
      <?php foreach ($articles as $a):
        $thumb = !empty($a['thumbnail_url']) ? (string)$a['thumbnail_url'] : $fallbackThumb;
        $title = (string)($a['title'] ?? '(タイトル不明)');
        $catv  = (string)($a['category'] ?? 'その他');
        $date  = !empty($a['published_at']) ? date('Y/m/d', strtotime((string)$a['published_at'])) : '—';
        $isRead = ((int)($a['is_read'] ?? 0) === 1);
        $isNew  = ((int)($a['is_new']  ?? 0) === 1);
      ?>
      <a class="card_link" href="article_detail.php?id=<?=h((string)$a['id'])?>">
        <article class="card article">
          <img class="thumb" src="<?=h($thumb)?>" alt="" loading="lazy" referrerpolicy="no-referrer">
          <div class="body">
            <h2 class="title"><?=h($title)?></h2>
            <div class="meta"><?=h($catv)?> / <?=h($date)?></div>
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px;">
              <span class="badge" style="background:<?= $isRead?'#eaf8ea':'#f5f7fb'?>;"><?= $isRead?'読了':'未読' ?></span>
              <?php if ($isNew): ?><span class="badge" style="background:#fff4d6;">新着</span><?php endif; ?>
            </div>
          </div>
        </article>
      </a>
      <?php endforeach; ?>

      <?php if (!$articles): ?>
        <div class="card" style="padding:16px;">
          該当する記事がありません。
          <?php if ($DEBUG): ?><br><small>[DEBUG] <?= h(json_encode(['cat'=>$cat,'q'=>$q,'unread'=>$unread,'sort'=>$sort])) ?></small><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ページャ -->
    <?php if ($totalPages > 1): ?>
    <nav class="pager" aria-label="ページャ">
      <?php if ($page > 1): ?>
        <a href="?<?=build_query(['page'=>1])?>">&laquo;</a>
        <a href="?<?=build_query(['page'=>$page-1])?>">&lsaquo;</a>
      <?php endif; ?>
      <?php for ($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
        <?= $p === $page ? '<span class="is-current">'.$p.'</span>' : '<a href="?'.build_query(['page'=>$p]).'">'.$p.'</a>' ?>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?=build_query(['page'=>$page+1])?>">&rsaquo;</a>
        <a href="?<?=build_query(['page'=>$totalPages])?>">&raquo;</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
  </main>

  <footer class="app-footer"><?php require __DIR__.'/inc/bottom_nav.php'; ?></footer>
</div>
</body>
</html>