<?php
include_once('./_common.php');
// 인스타 로그인후 인스타쪽 피드 배열을 받아서 올스타에 업데이트하는 폼을 임시로 구현한 페이지입니다.
// 실제로는 member_log.php가 크롤링 서버에서 정보를 받아서 저장합니다.
// 파일명이 실제로는 member_feed_log.php가 더 적합할 수도..

// $com_idx_array = array(9999,67,66,65,64,10000);
$com_idx_array = array($_SESSION['ss_com_idx']);
$group_array = array('mea','mea');

// imp
$sql = "SELECT GROUP_CONCAT(imp_idx) AS imp_idxs
		FROM {$g5['mms_table']}
		WHERE com_idx = '".$_SESSION['ss_com_idx']."'
		ORDER BY mms_idx       
";
// echo $sql.'<br>';
$one = sql_fetch($sql,1);
// print_r2($one);
$imp_idx_array = explode(",",$one['imp_idxs']);
// print_r2($imp_idx_array);

// mms
// $mms_idx_array = array(7,8,9,10);
$sql = "SELECT GROUP_CONCAT(mms_idx) AS mms_idxs
		FROM {$g5['mms_table']}
		WHERE com_idx = '".$_SESSION['ss_com_idx']."'
		ORDER BY mms_idx       
";
// echo $sql.'<br>';
$one = sql_fetch($sql,1);
// print_r2($one);
$mms_idx_array = explode(",",$one['mms_idxs']);
// print_r2($mms_idx_array);
?>
<style>
    #hd_login_msg {display:none;}
    button {background:#37a7ff;padding:10px 20px;font-size:1.5em;border-radius:4px;}
</style>

<form id="form01" action="./form2.php">

<h1>측정데이터 (dta_group=mea)</h1>
<h2>그룹(dta_group): mea=측정,err=에러,pre=예지,run=가동시간,product=생산</h2>
<h2>타입(dta_type): 1=온도,2=토크,3=전류,4=전압,5=진동,6=소리,7=습도,8=압력,9=속도</h2>
Token(암호코드)
<table>
	<tr><td>토큰값</td><td><input type="text" name="token" value="1099de5drf09"></td></tr>
</table>

<hr>
1번
<table>
	<tr><td>업체 idx1</td><td><input type="text" name="com_idx[]" value="<?=$com_idx_array[rand(0,sizeof($com_idx_array)-1)]?>"></td></tr>
	<tr><td>IMP idx1</td><td><input type="text" name="imp_idx[]" value="<?=$imp_idx_array[rand(0,sizeof($imp_idx_array)-1)]?>"></td></tr>
	<tr><td>MMS idx1</td><td><input type="text" name="mms_idx[]" value="<?=$mms_idx_array[rand(0,sizeof($mms_idx_array)-1)]?>"></td></tr>
	<tr><td>교대번호1</td><td><input type="text" name="dta_shf_no[]" value="<?=rand(1,2)?>"></td></tr>
	<tr><td>총교대수1</td><td><input type="text" name="dta_shf_max[]" value="<?=rand(2,3)?>"></td></tr>
	<tr><td>기종1</td><td><input type="text" name="dta_mmi_no[]" value="<?=rand(0,1)?>"></td></tr>
	<tr><td>데이터그룹1</td><td><input type="text" name="dta_group[]" value="<?=$group_array[rand(0,sizeof($group_array)-1)]?>"></td></tr>
	<tr><td>타입1</td><td><input type="text" name="dta_type[]" value="<?=rand(1,9)?>"></td></tr>
	<tr><td>측정번호1</td><td><input type="text" name="dta_no[]" value="<?=rand(1,2)?>"></td></tr>
	<tr><td>날짜1</td><td><input type="text" name="dta_date[]" value="<?=date("y.m.d",time())?>"></td></tr>
	<tr><td>시간1</td><td><input type="text" name="dta_time[]" value="<?=date("H:i:s",time()-rand(0,86400))?>"></td></tr>
	<tr><td>값1</td><td><input type="text" name="dta_value[]" value="<?=rand(1,50)?>"></td></tr>
</table>

<hr>
2번
<table>
	<tr><td>업체 idx2</td><td><input type="text" name="com_idx[]" value="<?=$com_idx_array[rand(0,sizeof($com_idx_array)-1)]?>"></td></tr>
	<tr><td>IMP idx2</td><td><input type="text" name="imp_idx[]" value="<?=$imp_idx_array[rand(0,sizeof($imp_idx_array)-1)]?>"></td></tr>
	<tr><td>MMS idx2</td><td><input type="text" name="mms_idx[]" value="<?=$mms_idx_array[rand(0,sizeof($mms_idx_array)-1)]?>"></td></tr>
	<tr><td>교대번호2</td><td><input type="text" name="dta_shf_no[]" value="<?=rand(1,3)?>"></td></tr>
	<tr><td>총교대수2</td><td><input type="text" name="dta_shf_max[]" value="3"></td></tr>
	<tr><td>기종2</td><td><input type="text" name="dta_mmi_no[]" value="<?=rand(0,1)?>"></td></tr>
	<tr><td>데이터그룹2</td><td><input type="text" name="dta_group[]" value="<?=$group_array[rand(0,sizeof($group_array)-1)]?>"></td></tr>
	<tr><td>타입2</td><td><input type="text" name="dta_type[]" value="<?=rand(1,9)?>"></td></tr>
	<tr><td>측정번호2</td><td><input type="text" name="dta_no[]" value="<?=rand(1,3)?>"></td></tr>
	<tr><td>날짜2</td><td><input type="text" name="dta_date[]" value="<?=date("y.m.d",time()-86400)?>"></td></tr>
	<tr><td>시간2</td><td><input type="text" name="dta_time[]" value="<?=date("H:i:s",time()-rand(0,86400))?>"></td></tr>
	<tr><td>값2</td><td><input type="text" name="dta_value[]" value="<?=rand(1,20)?>"></td></tr>
</table>

<hr>
<button type="submit">확인</button>
</form>
