<?php
// http://hanjoo.epcs.co.kr/php/hanjoo/device/rdb/shot_sub.multi.php?token=1099de5drf09&mms_idx=45&st_date=2022-06-02&st_time=13:33:14&en_date=2022-06-02&en_time=14:33:14
header("Content-Type: text/plain; charset=utf-8");
include_once('./_common.php');
if(isset($_SERVER['HTTP_ORIGIN'])){
	header("Access-Control-Allow-Origin:{$_SERVER['HTTP_ORIGIN']}");
	header("Access-Control-Allow-Credentials:true");
	header("Access-Control-Max-Age:86400"); //cache for 1 day
}

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
	if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		header("Access-Control-Allow-Methods:GET,POST,OPTIONS");
	if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
		header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	exit(0);
}

if(!$_REQUEST['token']){
//	$list = array('');
}
else if($_REQUEST['mms_idx']){

    // 토큰 비교
    if(!check_token1($_REQUEST['token'])) {
        $list = array("code"=>499,"message"=>"token error");
        echo json_encode( array($list) );
		exit;
    }

    $where = array();
    $where[] = " (1) ";   // 디폴트 검색조건
    
    // 최종 WHERE 생성
    if ($where)
        $sql_search = ' WHERE '.implode(' AND ', $where);


    // 최종 날짜 조건
    $start = $st_date.' '.$st_time;
    $end = $en_date.' '.$en_time;
 
 	// 측정 추출
    $sql = "SELECT * FROM g5_1_cast_shot_sub
            WHERE shot_id IN (
                SELECT shot_id FROM g5_1_cast_shot
                WHERE start_time >= '".$start."'
                    AND start_time <= '".$end."'
                    AND machine_id = '".$mms_idx."'
            )
    ";
//    echo $sql.'<br>';
//    exit;
	$rs = sql_query($sql,1);
	$list = array();
	for($i=0;$row=sql_fetch_array($rs);$i++){
        $row['no'] = $i;
        $row['timestamp'] = strtotime($row['event_time']);
        // 좌표에 표현할 value
        $dta1[$i]['x'] = $row['timestamp']*1000;
        $dta1[$i]['y'] = (float)$row[$item_type];
        $dta1[$i]['machine_id'] = (int)$row['machine_id'];
        $dta1[$i]['shot_no'] = (int)$row['shot_no'];
        $dta1[$i]['yraw'] = ($dta1[$i]['y']) ?: 0;
        $dta1[$i]['yamp'] = 1;
        $dta1[$i]['ymove'] = 0;
        // 좌표 list array
        $list[$i] = $dta1[$i];
    }
    //print_r2($dta1);

    // in case of no data.
    if(!$list[0]) {
        $dta1[0]['x'] = G5_SERVER_TIME*1000;
        $dta1[0]['y'] = 0;
        $dta1[0]['machine_id'] = null;
        $dta1[0]['shot_no'] = null;
        $dta1[0]['yraw'] = 0;
        $dta1[0]['yamp'] = 1;
        $dta1[0]['ymove'] = 0;
        $list[0] = $dta1[0];
    }

}

echo json_encode( $list );
?>