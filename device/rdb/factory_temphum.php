<?php
// http://hanjoo.epcs.co.kr/php/hanjoo/device/rdb/factory_temphum.php?token=1099de5drf09&mms_idx=45&st_date=2022-06-02&st_time=13:33:14&en_date=2022-06-02&en_time=14:33:14
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
	// $list = array('');
}
else if($_REQUEST['item_type']){

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
    $sql = "SELECT * FROM g5_1_factory_temphum
            WHERE event_time >= '".$start."'
                AND event_time <= '".$end."'
    ";
//    echo $sql.'<br>';
//    exit;
	$rs = sql_query($sql,1);
	$list = array();
	for($i=0;$row=sql_fetch_array($rs);$i++){
        $row['no'] = $i;
        $row['timestamp'] = strtotime($row['event_time']);
        $dta1[$i][0] = $row['timestamp']*1000;
        $dta1[$i][1] = (float)$row[$item_type];
        // 좌표값
        $list[$i] = $dta1[$i];
    }
    //print_r2($dta1);
    if(!$list[0]) {
        $dta1[0][0] = G5_SERVER_TIME*1000;
        $dta1[0][1] = 0;
        $list[0] = $dta1[0];
    }

}

echo json_encode( $list );
?>