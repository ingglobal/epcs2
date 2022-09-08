<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
$comdata_url = G5_USER_ADMIN_COMDATA_URL;
$com_folder_path = G5_USER_ADMIN_COMDATA_PATH.'/'.$g5['com_setting']['set_com_directory_name'];
$com_folder_url = G5_USER_ADMIN_COMDATA_URL.'/'.$g5['com_setting']['set_com_directory_name'];
$menu_path = $com_folder_path.'/_menu.php';
// print_r2($com);
?>
<link type="text/css" href="<?=$comdata_url?>/_css/style.css" rel="stylesheet" />
<header>
    <h2><?=$g5['title']?></h2>
    <nav>
        <ul>
            <li><a href="<?=G5_USER_ADMIN_URL?>" style="font-weight:700;color:orange;"><?=$com['com_name']?>관리</a></li>
            <li><a href="<?=$com_folder_url?>">정보등록HOME</a></li>
            <?php @include_once($menu_path);?>
        </ul>
    </nav>
</header>
<div id="content-wrap">
<div id="content">

