<?php
if (!defined('_GNUBOARD_')) exit;
// 카테고리 관리 상단 공통 탭 링크들입니다.

// 최고관리자인 경우만
if($member['mb_level']>=9) {
    // $sub_title_list = ' <a href="'.G5_BBS_URL.'/board.php?bo_table=setting1" class="btn_top_menu '.$active_term_list.'">환경설정게시판</a>
    // ';
}

${'active_'.$g5['file_name']} = ' btn_top_menu_active';
$g5['container_sub_title'] = '
<h2 id="container_sub_title">
    <a href="./robot_realtime.php" class="btn_top_menu '.$active_robot_realtime.'">로봇실시간제어</a>
    <a href="./robot_data_torque.php" class="btn_top_menu '.$active_robot_data_torque.'">데이터조회</a>
    <a href="./config_form_robot.php" class="btn_top_menu '.$active_config_form_robot.'">로봇설정</a>
    <a href="./robot_realtime1.php" class="btn_top_menu '.$active_robot_realtime1.'">로봇그래프샘플</a>
</h2>
';
?>
