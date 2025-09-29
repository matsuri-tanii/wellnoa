<?php
/**
 * ポイント設定（後で増減しやすいよう分離）
 */
return [
    'earn' => [
        'daily_log_create' => 1,
        'article_read'     => 1,
        'cheer_send'       => 1,
    ],
    'thresholds' => [
        'suggest_register_after' => 5, // この回数に到達したら登録を促す
    ],
];
