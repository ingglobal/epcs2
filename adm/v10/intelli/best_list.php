<?php
$sub_menu = "920130";
include_once('./_common.php');

auth_check($auth[$sub_menu],"r");

$pre = 'css';
$fname = preg_replace("/_list/","",$g5['file_name']); // 파일명생성

// Get the list of mms
$sql = "SELECT mms_idx, mms_name
        FROM {$g5['mms_table']}
        WHERE com_idx = '".$_SESSION['ss_com_idx']."'
        ORDER BY mms_idx
";
// echo $sql.'<br>';
$result = sql_query($sql,1);
for ($i=0; $row=sql_fetch_array($result); $i++) {
    // print_r2($row);
    $mms[$row['mms_idx']] = $row['mms_name'];
}

// Get default mms_idx for first mms_idx.
$sql = "SELECT mms_idx, mms_name
        FROM {$g5['mms_table']}
        WHERE com_idx = '".$_SESSION['ss_com_idx']."'
        ORDER BY mms_idx
        LIMIT 1
";
// echo $sql.'<br>';
$one = sql_fetch($sql,1);
$ser_mms_idx = $ser_mms_idx ?: $one['mms_idx'];

if(!$ser_mms_idx)
    alert('설비정보가 존재하지 않습니다.');


$g5['title'] = '최적 파라메터';
@include_once('./_top_menu_output.php');
include_once('./_head.php');
echo $g5['container_sub_title'];

$sql_common = " FROM g5_1_data_measure_best ";

$where = array();
$where[] = " (1) ";   // 디폴트 검색조건

if ($stx) {
    switch ($sfl) {
		case ( $sfl == $pre.'_id' || $sfl == $pre.'_idx' ) :
            $where[] = " ({$sfl} = '{$stx}') ";
            break;
		case ($sfl == $pre.'_hp') :
            $where[] = " ({$sfl} LIKE '%{$stx}%') ";
            break;
       default :
            $where[] = " {$sfl} = '{$stx}' ";
            break;
    }
}

// mms_idx 검색
if ($ser_mms_idx) {
    $where[] = " mms_idx = '".$ser_mms_idx."' ";
}


// 태그검색
if ($ser_type_no) {
    $ser_type_no_arr = explode("_",$ser_type_no);
    $ser_dta_type = $ser_type_no_arr[0];
    $ser_dta_no = $ser_type_no_arr[1];
    $where[] = " dta_type = '".$ser_dta_type."' ";
    $where[] = " dta_no = '".$ser_dta_no."' ";
}

// 기간 검색
if ($st_date) {
    if ($st_time) {
        $where[] = " dmb_reg_dt >= '".$st_date.' '.$st_time."' ";
    }
    else {
        $where[] = " dmb_reg_dt >= '".$st_date.' 00:00:00'."' ";
    }
}
if ($en_date) {
    if ($en_time) {
        $where[] = " dmb_reg_dt <= '".$en_date.' '.$en_time."' ";
    }
    else {
        $where[] = " dmb_reg_dt <= '".$en_date.' 23:59:59'."' ";
    }
}

// 최종 WHERE 생성
if ($where)
    $sql_search = ' WHERE '.implode(' AND ', $where);


if (!$sst) {
    $sst = "dmb_reg_dt";
    $sod = "DESC";
}
$sql_order = " ORDER BY {$sst} {$sod} ";


$sql = " SELECT COUNT(*) as cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql,1);
$total_count = $row['cnt'];


// $rows = $config['cf_page_rows'];
$rows = 100;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = "SELECT *
        {$sql_common} {$sql_search} {$sql_order}
        LIMIT {$from_record}, {$rows}
";
// echo $sql.'<br>';
$result = sql_query($sql,1);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

// 넘겨줄 변수가 추가로 있어서 qstr 별도 설정
$qstr = $qstr."&st_date=$st_date&en_date=$en_date";

add_stylesheet('<link rel="stylesheet" href="'.G5_USER_ADMIN_URL.'/js/timepicker/jquery.timepicker.css">', 0);
?>
<script type="text/javascript" src="<?=G5_USER_ADMIN_URL?>/js/timepicker/jquery.timepicker.js"></script>
<style>
.tbl_body td {text-align:center;border-bottom:solid 1px #e1e1e1;}
</style>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">총건수 </span><span class="ov_num"> <?php echo number_format($total_count) ?> </span></span>
</div>

<div class="local_desc01 local_desc" style="display:none;">
    <p>총건수가 65,411,218 이상이므로 기간 검색을 반드시 설정하세요. 하루 이상 입력 금지</p>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>
<select name="ser_mms_idx" id="ser_mms_idx">
    <?php
    if(is_array($g5['mms'])) {
        foreach ($g5['mms'] as $k1=>$v1 ) {
            // print_r2($g5['mms'][$k1]);
            if($g5['mms'][$k1]['com_idx']==$_SESSION['ss_com_idx']) {
                echo '<option value="'.$k1.'" '.get_selected($ser_mms_idx, $k1).'>'.$g5['mms'][$k1]['mms_name'].'</option>';
            }
        }
    }
    ?>
</select>
<script>$('select[name=ser_mms_idx]').val("<?=$ser_mms_idx?>").attr('selected','selected');</script>

<?php
// get mms info with meta extened data.
$mms = get_table_meta('mms', 'mms_idx', $ser_mms_idx);
// print_r2($mms);
$sql = "SELECT dta_type, dta_no
        FROM g5_1_data_measure_".$ser_mms_idx."
        GROUP BY dta_type, dta_no
        ORDER BY dta_type, dta_no
";
// echo $sql.'<br>';
$rs = sql_query_pg($sql,1);
?>
<select name="ser_type_no" id="ser_type_no">
    <option value="">태그전체</option>
    <?php
    for($i=0;$row=sql_fetch_array_pg($rs);$i++) {
        // print_r2($row);
        // 온도, 압력만
        if(!in_array($row['dta_type'],array(1,8))) {
            continue;
        }

        // 각 태그별 명칭
        // if($mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']]) {
        //     echo $mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']].'<br>';
        // }
        // else {
        //     echo $g5['set_data_type_value'][$row['dta_type']].'-'.$row['dta_no'].'<br>';
        // }
        $row['dta_type_no_name'] = $mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']] ? 
                                        $mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']]
                                            : $g5['set_data_type_value'][$row['dta_type']].'-'.$row['dta_no'];
        // echo $row['dta_type_no_name'].'<br>';
        echo '<option value="'.$row['dta_type'].'_'.$row['dta_no'].'">'.$row['dta_type_no_name'].'</option>';
    }
    // print_r2($dta_arr);
    ?>
</select>
<script>$('select[name=ser_type_no]').val("<?=$ser_type_no?>").attr('selected','selected');</script>

기간:
<input type="text" name="st_date" value="<?php echo $st_date ?>" id="st_date" class="frm_input" style="width:80px;"> ~
<input type="text" name="st_time" value="<?=$st_time?>" id="st_time" class="frm_input" autocomplete="off" style="width:65px;" placeholder="00:00:00">
~
<input type="text" name="en_date" value="<?php echo $en_date ?>" id="en_date" class="frm_input" style="width:80px;">
<input type="text" name="en_time" value="<?=$en_time?>" id="en_time" class="frm_input" autocomplete="off" style="width:65px;" placeholder="00:00:00">
&nbsp;&nbsp;
<select name="sfl" id="sfl">
    <option value="dta_idx" <?=get_selected($sfl, 'dta_idx')?>>dta_idx</option>
    <option value="dmb_idx" <?=get_selected($sfl, 'dmb_idx')?>>dmb_idx</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
</form>


<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<thead>
	<tr>
		<th scope="col">번호</th>
		<th scope="col">설비</th>
		<th scope="col">태그명</th>
		<th scope="col">값</th>
		<th scope="col">등록일</th>
		<th scope="col" style="display:no ne;">관리</th>
	</tr>
	</thead>
	<tbody class="tbl_body">
	<?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {

        // 태그명
        $row['dta_type_no_name'] = $mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']] ? 
                                                $mms['dta_type_label-'.$row['dta_type'].'-'.$row['dta_no']]
                                                    : $g5['set_data_type_value'][$row['dta_type']].'-'.$row['dta_no'];
        // echo $row['dta_type_no_name'].'<br>';
        $row['dta_type_no_name'] .= ' <span class="font_size_8">'.$row['dta_type'].'-'.$row['dta_no'].'</span>';

        $s_mod_a = '<a href="./'.$fname.'_form.php?'.$qstr.'&w=u&dmb_idx='.$row['dmb_idx'].'">';
        $s_mod = '<a href="./'.$fname.'_form.php?'.$qstr.'&w=u&dmb_idx='.$row['dmb_idx'].'" class="btn btn_03">수정</a>';
        $s_copy = '<a href="./'.$fname.'_form.php?'.$qstr.'&w=c&dmb_idx='.$row['dmb_idx'].'" class="btn btn_03">복제</a>';

        echo '
			<tr tr_id="'.$i.'">
				<td>'.$row['dmb_idx'].'</td>
				<td>'.$g5['mms'][$row['mms_idx']]['mms_name'].'</td>
				<td>'.$row['dta_type_no_name'].'</td>
				<td>'.round($row['dta_value'],2).'</td>
				<td>'.$row['dmb_reg_dt'].'</td>
				<td style="display:no ne;">'.$s_mod.'</td>
			</tr>
		';
	}
	if ($i == 0)
		echo '<tr class="no-data"><td colspan="15" class="text-center">등록(검색)된 자료가 없습니다.</td></tr>';
	?>
    </tbody>
    </table>
</div>
<!-- //리스트 테이블 -->

<div class="btn_fixed_top" style="display:no ne;">
    <a href="./<?=$fname?>_form.php" id="btn_add" class="btn btn_01" style="display:none;">추가하기</a> 
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
//-- $(document).ready 페이지로드 후 js실행 --//
$(document).ready(function(){

    // timepicker 설정
    $("input[name$=_time]").timepicker({
        'timeFormat': 'H:i:s',
        'step': 10
    });

    // st_date chage
    $(document).on('focusin', 'input[name=st_date]', function(){
        // console.log("Saving value: " + $(this).val());
        $(this).data('val', $(this).val());
    }).on('change','input[name=st_date]', function(){
        var prev = $(this).data('val');
        var current = $(this).val();
        // console.log("Prev value: " + prev);
        // console.log("New value: " + current);
        if(prev=='') {
            $('input[name=st_time]').val('00:00:00');
        }
    });
    // en_date chage
    $(document).on('focusin', 'input[name=en_date]', function(){
        $(this).data('val', $(this).val());
    }).on('change','input[name=en_date]', function(){
        var prev = $(this).data('val');
        if(prev=='') {
            $('input[name=en_time]').val('23:59:59');
        }
    });

    $("#st_date,#en_date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd",
		showButtonPanel: true,
		yearRange: "c-99:c+99",
		//maxDate: "+0d"
	});

	$( "#fsearch" ).submit(function(e) {
		if($('input[name=st_date]').val() > $('input[name=en_date]').val()) {
			alert('시작일이 종료일보다 큰 값이면 안 됩니다.');
			e.preventDefault();
		}
	});

});
</script>

<?php
include_once ('./_tail.php');
?>
