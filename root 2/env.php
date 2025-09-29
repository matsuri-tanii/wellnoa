<?php

define('OPENWEATHER_API_KEY', 'd2bae2af195bb3f8ecf16ab3e107132d');


function sakura_db_info(){
    
    $associative_array = array(
        "db_name" =>    "wellnoa_wellnoa",               //データベース名
        "db_host" =>    'mysql3109.db.sakura.ne.jp', //DBホスト
        "db_id" =>      'wellnoa_wellnoa',                  //アカウント名
        "db_pw" =>      'comvyF-tapnoj-cogwi4'            //パスワード。さくらのDBのPW
    );

    return $associative_array;
}

if (!defined('ADMIN_USER'))     define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', 'wellnoachangesme');

?>
