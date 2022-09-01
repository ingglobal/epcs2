<?php
$sub_menu = "950110";
include_once("./_common.php");

auth_check($auth[$sub_menu], 'w');

$mms = get_table_meta('mms', 'mms_idx', $mms_idx);
// print_r2($mms);

$sql = "SELECT dta_type, dta_no
        FROM g5_1_data_measure_".$mms_idx."
        GROUP BY dta_type, dta_no
        ORDER BY dta_type, dta_no
";
$rs = sql_query_pg($sql,1);
for($i=0;$row=sql_fetch_array_pg($rs);$i++) {
    // print_r2($row);
    // 각 태그별 번호 배열 (아래 for 문장에서 활용)
    $dta_arr[$row['dta_type']][] = $row['dta_no'];
}
// print_r2($dta_arr);

$html_title = ($w=='')?'추가':'수정'; 
$g5['title'] = $mms['mms_name'].' 그래프 설정';
include_once('./_head.sub.php');

?>
<style>
.td_item_range {margin-bottom:4px;}
input[type=file] {width: 165px;}
    .dta_type_no {color:#bfbfbf;font-size:0.7em;}
</style>
<div class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <div class="local_desc01 local_desc" style="display:no ne;">
        <p>태그에 이름표(레이블)를 달아주세요.</p>
    </div>

    <form name="form01" id="form01" action="./<?=$g5['file_name']?>_update.php" onsubmit="return form01_check(this);" method="post" enctype="multipart/form-data">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="mms_idx" value="<?php echo $mms_idx; ?>">
    <div class=" new_win_con">
        <div class="tbl_frm01 tbl_wrap">
            <table>
            <caption><?php echo $g5['title']; ?></caption>
            <colgroup>
                <col class="grid_1" style="width:22%;">
                <col class="grid_3">
            </colgroup>
            <tbody>
            <?php
            if(is_array($dta_arr)) {
                foreach($dta_arr as $k1 => $v1) {
                    // echo $k1.'<br>';
                    // print_r2($v1);
                    for($i=0;$i<sizeof($v1);$i++) {
                        // echo $v1[$i].'<br>';
                        $item_name = $g5['set_data_type_value'][$k1] ?: $k1;
                    ?>
                        <tr>
                            <th scope="row"><?=$item_name.'-'.$v1[$i]?></th>
                            <td>
                                <input type="hidden" name="dta_type[]" value="<?=$k1?>" class="frm_input">
                                <input type="hidden" name="dta_no[]" value="<?=$v1[$i]?>" class="frm_input">
                                <input type="text" name="dta_label[]" value="<?=$mms['dta_type_label-'.$k1.'-'.$v1[$i]]?>" class="frm_input" style="width:80%;">
                                <span class="dta_type_no"><?=$k1.'-'.$v1[$i]?></span>
                            </td>
                        </tr>
                    <?php
                    }
                }
            }
            else {
            ?>
                <tr>
                    <td colspan="2" class="empty_table">측정값이 존재하지 않습니다.</td>
                </tr>
            <?php
            }
            ?>
            </tbody>
            </table>
        </div>
    </div>
    <div class="win_btn ">
        <input type="submit" value="확인" class="btn_submit btn" accesskey='s'>
        <input type="button" class="btn_close btn" value="창닫기" onclick="javascript:window.close();">
    </div>

    </form>

    <div class="btn_fixed_top" style="display:none;">
        <a href="./mms_view.popup.php?mms_idx=<?=$mms_idx?>" id="btn_mms_view" class="btn btn_03" title="장비이력카드"><i class="fa fa-address-card-o"></i></a>
        <a href="javascript:window.close();" id="member_add" class="btn btn_02">창닫기</a>
    </div>
</div>

<script>
// 윈도우 크기 재설정
window.onload = reSize;
window.onresize = reSize;
function reSize() {
	resizeTo(520, 680);    // 여는 페이지 설정 높이 80 차이
}

$(function() {

});

function form01_check(f) {
    
    return true;
}
</script>


<?php
include_once('./_tail.sub.php');
?>
