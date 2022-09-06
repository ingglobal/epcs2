<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 
if(!function_exists('qr_cast_code_update')){
function qr_cast_code_update($arr)
{
	global $g5;
	
	if(!$arr['qrcode']||!$arr['cast_code']) {
		return false;
    }

    $g5_table_name = $g5['qr_cast_code_table'];
    $fields = sql_field_names($g5_table_name);
    $pre = substr($fields[0],0,strpos($fields[0],'_'));
    
    // 변수 재설정
    $arr[$pre.'_update_dt'] = G5_TIME_YMDHIS;
    // $arr[$pre.'_end_ym'] = $arr[$pre.'_end_year'].'-'.$arr[$pre.'_end_month'];   // 년월

    // 공통쿼리
    $skips[] = $pre.'_idx';	// 건너뛸 변수 배열
    $skips[] = $pre.'_reg_dt';
    for($i=0;$i<sizeof($fields);$i++) {
        if(in_array($fields[$i],$skips)) {continue;}
        $sql_commons[] = " ".$fields[$i]." = '".$arr[$fields[$i]]."' ";
    }

    // after sql_common value setting
    // $sql_commons[] = " com_idx = '".$arr['ss_com_idx']."' ";

    // 공통쿼리 생성
    $sql_common = (is_array($sql_commons)) ? implode(",",$sql_commons) : '';
    
    // 중복 조건은 함수마다 다르게 설정!!
    $sql = "SELECT * FROM {$g5_table_name} 
            WHERE qrcode = '{$arr['qrcode']}' AND cast_code = '{$arr['cast_code']}'
    ";
    // echo $sql.'<br>';
    $row = sql_fetch($sql,1);
	if($row[$pre."_idx"]) {
		$sql = "UPDATE {$g5_table_name} SET 
                    {$sql_common} 
				WHERE ".$pre."_idx = '".$row[$pre."_idx"]."'
        ";
		sql_query($sql,1);
	}
	else {
		$sql = "INSERT INTO {$g5_table_name} SET 
                    {$sql_common} 
                    , ".$pre."_reg_dt = '".G5_TIME_YMDHIS."'
        ";
		sql_query($sql,1);
        $row[$pre."_idx"] = sql_insert_id();
	}
//    echo $sql.'<br>';
    return $row[$pre."_idx"];
}
}

// get hour(01,02...23) from alphanum which are 1,2,3,….9, A(10), B(11), C(12) ~ M(22), N(23)
if(!function_exists('get_hour_number')){
function get_hour_number($str) {
    $str = (is_numeric($str)) ? $str : ord($str)-55;
    $hour = sprintf("%02d",$str);
    return $hour;
}
}

// get 1 unit hour string(1,2,3..A, B(11)) from hour 2 digit number(01,02...23)
if(!function_exists('get_hour_string')){
function get_hour_string($str) {
    $str = (intval($str)<10) ? intval($str) : chr(intval($str)+55);
    $hour = $str;
    return $hour;
}
}

// get month mm from alphbet, ex A=01, B=02...
if(!function_exists('get_month_number')){
function get_month_number($str) {
    $mon = sprintf("%02d",ord($str)-64);
    return $mon;
}
}

// get month string from number, ex 01=A, 02=B...
if(!function_exists('get_month_string')){
function get_month_string($str) {
    $mon = chr(64+intval($str));
    return $mon;
}
}

// qrcode 2 qrtime
if(!function_exists('get_qr_time')){
function get_qr_time($qrcode) {
    
    if($qrcode<17) {
        return false;
    }
    $YY = '20'.substr($qrcode,0,2);
    $mm = get_month_number(substr($qrcode,2,1));
    $dd = substr($qrcode,3,2);
    $HH = substr($qrcode,-4,2);
    $ii = substr($qrcode,-2,2);

    return $YY.'-'.$mm.'-'.$dd.' '.$HH.':'.$ii.':00';
}
}

// castcode 2 casttime
if(!function_exists('get_cast_time')){
function get_cast_time($str1, $str2) {
    
    if(!$str1||!$str2) {
        return false;
    }
    $hex1 = dechex($str1);
    $hex2 = dechex($str2);
    // echo $hex1.'<br>';
    // echo $hex2.'<br>';
    // $hex1을 2자리씩 거꾸로 읽어서 16진수 2 ascii 변환
    $ascii1 = hex2bin(substr($hex1,-2,2)).hex2bin(substr($hex1,-4,2)).hex2bin(substr($hex1,-6,2)).hex2bin(substr($hex1,0,2));
    $ascii2 = hex2bin(substr($hex2,-2,2)).hex2bin(substr($hex2,-4,2)).hex2bin(substr($hex2,-6,2));
    $ascii = $ascii1.$ascii2;   // ex)1A25J59
    // echo $ascii1.$ascii2.'<br>';
    $mm = get_month_number(substr($ascii,1,1));
    // 년도 (현재가 01월인데 12인 경우만 작년)
    $YY = (date("m")=='01'&&$mm=='12') ? date("Y")-1 : date("Y");
    $dd = substr($ascii,2,2);
    $HH = get_hour_number(substr($ascii,-3,1));
    $ii = substr($ascii,-2,2);

    return $YY.'-'.$mm.'-'.$dd.' '.$HH.':'.$ii.':00';
}
}

// casttime 2 castcode ex) 2022-01-31 11:32:00 > 2A31B32
if(!function_exists('get_time2castcode')){
function get_time2castcode($dt) {
    
    if(!$dt) {
        return false;
    }
    $mm = get_month_string(intval(substr($dt,5,2)));
    $dd = substr($dt,8,2);
    $HH = get_hour_string(substr($dt,-8,2));
    $ii = substr($dt,-5,2);

    return rand(1,4).$mm.$dd.$HH.$ii;
}
}

// 시간범위를 추출 (데이터가 없는 경우 최근 시간 범위로 설정)
// type=(data:데이터기준, current:현재시점기준), st_date, st_time, en_date, en_time, mms_idx, dta_type, dta_no
if(!function_exists('get_start_end_dt')){
function get_start_end_dt($arr) {

    // 시간차이(초)
    $diff_timestamp = strtotime($arr['en_date'].' '.$arr['en_time'])-strtotime($arr['st_date'].' '.$arr['st_time']);
    // data 기반, 넘어온 시간을 기준으로 계산
    if($arr['type']=='current') {
        $en_date = date("Y-m-d",G5_SERVER_TIME);
        $en_time = date("H:i:s",G5_SERVER_TIME);
        $st_date = date("Y-m-d",G5_SERVER_TIME-$diff_timestamp);
        $st_time = date("H:i:s",G5_SERVER_TIME-$diff_timestamp);
        $start = $st_date.' '.$st_time;
        $end = $en_date.' '.$en_time;
    }
    // 현재시점 기준으로 계산
    else {
        $st_date = $arr['st_date'];
        $st_time = $arr['st_time'];
        $en_date = $arr['en_date'];
        $en_time = $arr['en_time'];
        $start = $st_date.' '.$st_time;
        $end = $en_date.' '.$en_time;
    }

    $sql = "SELECT * FROM g5_1_data_measure_".$arr['mms_idx']."
        WHERE dta_type = '".$arr['dta_type']."' AND dta_no = '".$arr['dta_no']."'
            AND dta_dt >= '".$start."' AND dta_dt <= '".$end."'
        ORDER BY dta_dt DESC LIMIT 1
    ";
    // echo $sql.'<br>';
    $one1 = sql_fetch_pg($sql,1);
    // print_r2($one1);
    // 해당 범위에 값이 없으면 재설정
    if(!$one1['dta_idx']) {
        $sql = "SELECT * FROM g5_1_data_measure_".$arr['mms_idx']."
                WHERE dta_type = '".$arr['dta_type']."' AND dta_no = '".$arr['dta_no']."'
                ORDER BY dta_dt DESC LIMIT 1
        ";
        // echo $sql.'<br>';
        $one2 = sql_fetch_pg($sql,1);
        // print_r2($one2);
        // 마지막 시점을 기준으로 시간 범위를 거꾸로 역산 설정
        $end = substr($one2['dta_dt'],0,19);
        $en_timestamp = strtotime($end);
        $en_date = substr($end,0,10);;
        $en_time = substr($end,11,8);;
        $start = date("Y-m-d H:i:s",$en_timestamp-$diff_timestamp);
        $st_date = substr($start,0,10);;
        $st_time = substr($start,11,8);;
    }
    // echo $start.'~'.$end.'<br>';
    return array('start'=>$start,'st_date'=>$st_date,'st_time'=>$st_time,'end'=>$end,'en_date'=>$en_date,'en_time'=>$en_time);
}
}

// 초를 분으로 변환
if(!function_exists('sec2m')){
function sec2m($t) {
    return floor($t/60);
}
}

// 초를 시:분:초 로 변환 ex)00:11:25
if(!function_exists('sec2hms')){
function sec2hms($t,$f=':') { // t = seconds, f = separator 
    return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
}
}

// QR주조코드 업데이트
if(!function_exists('qr_cast_update')){
function qr_cast_update($arr)
{
	global $g5;
	
	if(!$arr['dta_no']||!$arr['dta_value'])
		return 0;

    $g5_table_name = $g5['qr_cast_code_table'];
    $fields = sql_field_names($g5_table_name);
    $pre = substr($fields[0],0,strpos($fields[0],'_'));
    
    // 변수 재설정
    $arr[$pre.'_update_dt'] = G5_TIME_YMDHIS;
    $arr['qrc_yearmonth'] = $arr['qrc_year'].'-'.$arr['qrc_month'];   // 년월

    // 공통쿼리
    $skips[] = $pre.'_idx';	// 건너뛸 변수 배열
    $skips[] = $pre.'_reg_dt';
    for($i=0;$i<sizeof($fields);$i++) {
        if(in_array($fields[$i],$skips)) {continue;}
        $sql_commons[] = " ".$fields[$i]." = '".$arr[$fields[$i]]."' ";
    }

    // after sql_common value setting
    // $sql_commons[] = " com_idx = '".$arr['ss_com_idx']."' ";

    // 공통쿼리 생성
    $sql_common = (is_array($sql_commons)) ? implode(",",$sql_commons) : '';
    
    $sql = "SELECT * FROM {$g5_table_name} 
            WHERE cast_code = '{$arr['cast_code']}'
    ";
//    echo $sql.'<br>';
    $row = sql_fetch($sql,1);
	if($row[$pre."_idx"]) {
		$sql = "UPDATE {$g5_table_name} SET 
                    {$sql_common} 
				WHERE ".$pre."_idx = '".$row[$pre."_idx"]."'
        ";
		// sql_query($sql,1);
	}
	else {
		$sql = "INSERT INTO {$g5_table_name} SET 
                    {$sql_common} 
                    , ".$pre."_reg_dt = '".G5_TIME_YMDHIS."'
        ";
		sql_query($sql,1);
        $row[$pre."_idx"] = sql_insert_id();
	}
//    echo $sql.'<br>';
    return $row[$pre."_idx"];
}
}

// 임계치 범위 추출
if(!function_exists('get_range')){
function get_range($val, $arr) {
    global $g5;

    $str = 'ok';  // 정상
    if(!$arr[0]||!$arr[1]||!$arr[2]||!$arr[3]||!$arr[4]||!$arr[5]) {
        return $str;
    }
    if($val >= $arr[2])
        $str = 't3';    // 상단위험
    else if($val >= $arr[1])
        $str = 't2';    // 상단경고
    else if($val >= $arr[0])
        $str = 't1';    // 상단주의
    else if($val <= $arr[5])
        $str = 'b3';    // 하단위험
    else if($val <= $arr[4])
        $str = 'b2';    // 하단경고
    else if($val <= $arr[3])
        $str = 'b1';    // 하단주의

    return $str;
}
}    


// 알람 메시지 발송 업데이트
if(!function_exists('update_alarm_send')){
function update_alarm_send($arr) {
    global $g5;

    $sql = " INSERT INTO {$g5['alarm_send_table']} SET
            arm_idx = '".$arr['alarm_idx']."'
            , mms_idx = '".$arr['mms_idx']."'
            , ars_cod_code = '".$arr['code']."'
            , ars_send_type = '".$arr['send_type']."'
            , ars_hp = '".$arr['hp']."'
            , ars_email = '".$arr['email']."'
            , ars_status = 'ok'
            , ars_reg_dt = '".G5_TIME_YMDHIS."'
    ";
    sql_query($sql,1);
    $idx = sql_insert_id();

    return $idx;
}
}    

// 태그알람 메시지 발송 업데이트
if(!function_exists('update_alarm_tag_send')){
function update_alarm_tag_send($arr) {
    global $g5;

    $sql = " INSERT INTO {$g5['alarm_tag_send_table']} SET
            amt_idx = '".$arr['alarm_idx']."'
            , mms_idx = '".$arr['mms_idx']."'
            , ats_tgc_code = '".$arr['code']."'
            , ats_send_type = '".$arr['send_type']."'
            , ats_hp = '".$arr['hp']."'
            , ats_email = '".$arr['email']."'
            , ats_status = 'ok'
            , ats_reg_dt = '".G5_TIME_YMDHIS."'
    ";
    // echo $sql.PHP_EOL;
    sql_query($sql,1);
    $idx = sql_insert_id();

    return $idx;
}
}    


// 푸시발송함수
// send_number, arm_table=('alarm','alarm_tag'),towhom_hp, arm_name, alarm_idx, mms_idx, arm_code, msg_body, push_url
if(!function_exists('send_push')){
function send_push($arr) {
    global $g5,$config;

    $arr["push_title"] = '['.$arr['arm_code'].'] '.$arr['arm_name'];

    for($j=0;$j<sizeof($arr['towhom_hp']);$j++) {
        // 회원정보 검색
        $sql = "SELECT mb_id, mb_6 FROM {$g5['member_table']}
                WHERE mb_leave_date = ''
                    AND REPLACE(mb_hp,'-','') = '".preg_replace('/-/','',$arr['towhom_hp'][$j])."'
                LIMIT 1
        ";
        // echo $sql.'<br>';
        $mb = sql_fetch($sql,1);
        if(!$mb['mb_id']||!$mb['mb_6']) {
            return false;
        }
        $arr['push_key'] = $mb['mb_6']; // 푸시키 정보 추출

        $headings = array(
            "en" => $arr["push_title"]
        );
        $content = array(
            "en" => $arr["msg_body"]
        );
        $fields = array(
            'app_id' => $g5['com_setting']['set_onesignal_id'],
            'include_player_ids' => array($arr['push_key']),
            'data' => array(
                "url" => $arr['push_url']
            ),
            'headings' => $headings,
            'contents' => $content
        );
        $fields = json_encode($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic '.$g5['com_setting']['set_onesignal_key']
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        $response = curl_exec($ch);
        curl_close($ch);

        // 발송기록 저장
        $ar['alarm_idx'] = $arr['alarm_idx'];
        $ar['mms_idx'] = $arr['mms_idx'];
        $ar['code'] = $arr['arm_code'];
        $ar['send_type'] = 'push';
        $ar['hp'] = $arr['towhom_hp'][$j];
        $ar['email'] = '';
        $response['alarm_idx'] = ($arr['arm_table']=='alarm') ? update_alarm_send($ar):update_alarm_tag_send($ar);
        unset($ar);
    }
    // print_r2($response);

    return $response;
}
}    

// 문자발송함수
// arm_table=('alarm','alarm_tag'),towhom_hp, send_number, alarm_idx, mms_idx, arm_code, msg_body
if(!function_exists('send_sms_lms')){
function send_sms_lms($arr) {
    global $g5,$config;

    if($config['cf_sms_type'] == 'LMS') {
        $port_setting = get_icode_port_type($config['cf_icode_id'], $config['cf_icode_pw']);

        // SMS 모듈 클래스 생성
        if($port_setting !== false) {
            $SMS = new LMS;
            $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port_setting);

            for($j=0;$j<sizeof($arr['towhom_hp']);$j++) {

                $strDest[]   = preg_replace("/[^0-9]/", "", $arr['towhom_hp'][$j]);

                // 발송기록 저장, 일단 발송했다고 봄
                $ar['alarm_idx'] = $arr['alarm_idx'];
                $ar['mms_idx'] = $arr['mms_idx'];
                $ar['code'] = $arr['arm_code'];
                $ar['send_type'] = 'lms';
                $ar['hp'] = $arr['towhom_hp'][$j];
                $ar['email'] = '';
                $alarm_idx = ($arr['arm_table']=='alarm') ? update_alarm_send($ar):update_alarm_tag_send($ar);
                unset($ar);

            }
            // $strDest[]   = $receive_number;
            $strCallBack = $arr['send_number'];
            $strCaller   = iconv_euckr(trim($config['cf_title']));
            $strSubject  = '';
            $strURL      = '';
            $strData     = iconv_euckr($arr['msg_body']);
            $strDate     = '';
            $nCount      = count($strDest);

            $res = $SMS->Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate, $nCount);

            $SMS->Send();
            $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
        }
    }
    else {
        $SMS = new SMS; // SMS 연결
        $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);
        // $SMS->Add($receive_number, $arr['send_number'], $config['cf_icode_id'], iconv_euckr(stripslashes($arr['msg_body'])), "");
        for($j=0;$j<sizeof($arr['towhom_hp']);$j++) {

            $SMS->Add(preg_replace("/[^0-9]/", "", $arr['towhom_hp'][$j]), $arr['send_number'], $config['cf_icode_id'], iconv_euckr(stripslashes($arr['msg_body'])), "");

            // 발송기록 저장, 일단 발송했다고 봄
            $ar['alarm_idx'] = $arr['alarm_idx'];
            $ar['mms_idx'] = $arr['mms_idx'];
            $ar['code'] = $arr['arm_code'];
            $ar['send_type'] = 'sms';
            $ar['hp'] = $arr['towhom_hp'][$j];
            $ar['email'] = '';
            $alarm_idx = ($arr['arm_table']=='alarm') ? update_alarm_send($ar):update_alarm_tag_send($ar);
            unset($ar);
        }
        $SMS->Send();
        $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
    }

    return $alarm_idx;
}
}    

// 이메일발송함수
// arm_table=('alarm','alarm_tag'),towhom_email, towhom_name, mms_name, arm_name, arm_code, alarm_idx, mms_idx, msg_body
if(!function_exists('send_email')){
function send_email($arr) {
    global $g5,$config;

    for($j=0;$j<sizeof($arr['towhome_email']);$j++) {

        $sw = preg_match("/[0-9a-zA-Z_]+(\.[0-9a-zA-Z_]+)*@[0-9a-zA-Z_]+(\.[0-9a-zA-Z_]+)*/", $arr['towhome_email'][$j]);
        // 올바른 메일 주소 & if is is under today limit
        if ($sw == true) {
            // echo $arr['towhome_email'][$j].'<br>';
            $patterns = array ( '/{이름}/'
                                ,'/{설비명}/','/{코드명}/'
                                ,'/{코드}/','/{내용}/'
                                ,'/{년월일}/','/{HOME_URL}/'
                            );
                            // print_r2($patterns);
            $replace = array (  $arr['towhom_name'][$j]
                                ,$arr['mms_name'], $arr['arm_name']
                                ,$arr['arm_code'], conv_content($arr['msg_body'],2)
                                ,G5_TIME_YMD, G5_URL
                            );
                            // print_r2($replace);

            $towhom['subject'] = preg_replace($patterns,$replace
                                            ,$g5['setting']['set_tag_subject']);
            $towhom['content'] = preg_replace($patterns,$replace
                                            ,$g5['setting']['set_tag_content']);
            // echo $towhom['subject'].'<br>';
            // echo $towhom['content'].'<br>';

            // 메일발송
            mailer($config['cf_admin_email_name'].'(발신전용)', $config['cf_admin_email'], $arr['towhome_email'][$j], $towhom['subject'], $towhom['content'], 1);

            // 발송기록 저장
            $ar['alarm_idx'] = $arr['alarm_idx'];
            $ar['mms_idx'] = $arr['mms_idx'];
            $ar['code'] = $arr['arm_code'];
            $ar['send_type'] = 'email';
            $ar['hp'] = '';
            $ar['email'] = $arr['towhome_email'][$j];
            $alarm_idx = ($arr['arm_table']=='alarm') ? update_alarm_send($ar):update_alarm_tag_send($ar);
            unset($ar);
        
        }

    }
    return $alarm_idx;
}
}  

// 메시지 발송 함수
// arm_table=('alarm','alarm_tag'), arm_idx=알람번호, amt_idx=알람태그번호, msg_type=메세지타입(sms,push,email), com_msg_type=업체메시지타입, mms_idx=설비번호, mms_name=설비명
// arm_code=태그코드, arm_name=태그명, reports=알림대상(json), msg_limit=알림과부하설정, msg_body=내용
if(!function_exists('send_message')){
function send_message($arr)
{
    global $g5, $config;

    // 하루 메시지(모든 메시지 email, sms, push 전부 중에서..) 발송 횟수를 넘어가면 발송 중지
    if($arr['arm_table']=='alarm') {
        $sql = "SELECT COUNT(ars_idx) AS cnt FROM {$g5['alarm_send_table']}
                WHERE mms_idx = '".$arr['mms_idx']."' AND ars_cod_code = '".$arr['arm_code']."'
                    AND ars_reg_dt > DATE_ADD(now(), INTERVAL -24 HOUR)
        ";
    }
    else {
        $sql = "SELECT COUNT(ats_idx) AS cnt FROM {$g5['alarm_tag_send_table']}
                WHERE mms_idx = '".$arr['mms_idx']."' AND ats_tgc_code = '".$arr['arm_code']."'
                    AND ats_reg_dt > DATE_ADD(now(), INTERVAL -24 HOUR)
        ";
    }
    // echo $sql.PHP_EOL;
	$one = sql_fetch($sql);
    if($one['cnt'] >= $arr['msg_limit']) {
        return 0;
    }

    // 발신자번호
    $send_number = preg_replace("/[^0-9]/", "", $g5['com_setting']['set_from_number']);

    // 발송자 정보 추출
    $infos = json_decode($arr['reports'], true);
    if(is_array($infos)) {
        foreach($infos as $k1 => $v1) {
            // echo $k1.'<br>';
            // print_r2($v1);
            for($j=0;$j<sizeof($v1);$j++) {
                // cell phone array
                if($k1=='r_name') {
                    $towhom_name[] = trim($v1[$j]);
                }
                // cell phone array, remove '-' mark from hp numbers.
                if($k1=='r_hp') {
                    $towhom_hp[] = preg_replace("/[^0-9]/","",trim($v1[$j]));
                }
                // set email array
                else if($k1=='r_email') {
                    $towhom_email[] = trim($v1[$j]);
                }
            }
        }
    }
    // print_r2($towhom_hp);
    // print_r2($towhom_email);

    // 메시지 발송 타입
    $msg_types = explode(",",$arr['msg_type']);

	//문자 발송
	if(in_array("sms",$msg_types)) {
        // 문자 발송
        if ($config['cf_sms_use'] == 'icode' && count($towhom_hp) > 0) {
            // send_number, arm_table=('alarm','alarm_tag'),towhom_hp, alarm_idx, mms_idx, arm_code, msg_body
            $ar['send_number'] = $send_number;
            $ar['arm_table'] = $arr['arm_table'];
            $ar['towhom_hp'] = $towhom_hp;
            $ar['alarm_idx'] = ($arr['arm_table']=='alarm') ? $arr['arm_idx'] : $arr['amt_idx'];
            $ar['mms_idx'] = $arr['mms_idx'];
            $ar['arm_code'] = $arr['arm_code']; // tgc_code or cod_code
            $ar['msg_body'] = $arr['msg_body'];
            send_sms_lms($ar);  // 함수 호출
            // print_r2($ar);
            unset($ar);
        }
	}

	//이메일 발송
	if(in_array("email",$msg_types)) {
        // arm_table=('alarm','alarm_tag'),towhom_email, towhom_name, mms_name, arm_name, arm_code, alarm_idx, mms_idx
        $ar['arm_table'] = $arr['arm_table'];
        $ar['towhome_email'] = $towhom_email;
        $ar['towhom_name'] = $towhom_name;
        $ar['mms_name'] = $arr['mms_name'];
        $ar['arm_name'] = $arr['arm_name'];
        $ar['alarm_idx'] = ($arr['arm_table']=='alarm') ? $arr['arm_idx'] : $arr['amt_idx'];
        $ar['mms_idx'] = $arr['mms_idx'];
        $ar['arm_code'] = $arr['arm_code']; // tgc_code or cod_code
        $ar['msg_body'] = $arr['msg_body'];
        send_email($ar);  // 함수 호출
        unset($ar);
	}

	//푸시 발송
	if(in_array("push",$msg_types)) {
        if (count($towhom_hp) > 0) {
            // send_number, arm_table=('alarm','alarm_tag'),towhom_hp, arm_name, alarm_idx, mms_idx, arm_code, msg_body
            $ar['send_number'] = $send_number;
            $ar['arm_table'] = $arr['arm_table'];
            $ar['towhom_hp'] = $towhom_hp;
            $ar['arm_name'] = $arr['arm_name'];
            $ar['alarm_idx'] = ($arr['arm_table']=='alarm') ? $arr['arm_idx'] : $arr['amt_idx'];
            $ar['mms_idx'] = $arr['mms_idx'];
            $ar['arm_code'] = $arr['arm_code']; // tgc_code or cod_code
            $ar['msg_body'] = $arr['msg_body'];
            $ar['push_url'] = G5_USER_ADMIN_URL.'/message_list.php';
            send_push($ar);  // 함수 호출
            unset($ar);
        }
	}

    return true;
}
}

// Message send_type setting
// array: prefix, com_idx, value
if(!function_exists('set_send_type')){
function set_send_type($arr) {
	global $g5;
	
    // Get the company info.
    $com = get_table_meta('company','com_idx',$arr['com_idx']);

    $set_values = explode(',', preg_replace("/\s+/", "", $g5['setting']['set_send_type']));
    foreach ($set_values as $set_value) {
        list($key, $value) = explode('=', $set_value);
        // echo $key.'/',$value.'<br>';
        // 해당 업체 발송 설정을 먼저 체크해서 비활성 표현
        if(!preg_match("/".$key."/i",$com['com_send_type'])) {
            ${"disable_".$key} = ' disabled'; 
        }
        ${"checked_".$key} = (preg_match("/".$key."/i",$arr['value'])) ? 'checked':''; 
        $str .= '<label for="set_send_type_'.$key.'" class="set_send_type" '.${"disable_".$key}.'>
                <input type="checkbox" id="set_send_type_'.$key.'"
                    name="'.$arr['prefix'].'_send_type[]" value="'.$key.'"
                     '.${"checked_".$key}.${"disable_".$key}.'>'.$value.'('.$key.')
            </label>';
    }

    return $str;
}
}


// 직책, 직급을 SELECT 형식으로 얻음
if(!function_exists('get_set_options_select')){
function get_set_options_select($set_variable, $start=0, $end=200, $selected="",$sub_menu)
{
    global $g5,$auth;

    // 삭제 권한이 있으면 전부
    if(!auth_check($auth[$sub_menu],'d',1)) {
        return $g5[$set_variable.'_options_value'];
    }
    
    if(is_array($g5[$set_variable.'_value'])) {
        foreach ($g5[$set_variable.'_value'] as $k1=>$v1) {
            if($k1 >= $start && $k1 <= $end) {
                $str .= '<option value="'.$k1.'"';
                if ($k1 == $selected)
                    $str .= ' selected="selected"';
                $str .= ">{$v1}</option>\n";
            }
        }    
    }

    return $str;
}
}

// token 체크 판단
if(!function_exists('check_token1')){
function check_token1($token) {

    $str = true;
    $expire_date = 86400*30*6; // 약 6개월 정도

    // 기존 방법 체크, 12자리수 보다 적은 경우, ex) 1099de5drf09
    if( strlen($token) <= 12 ) {
        $to[] = substr($token,0,2);
        $to[] = substr($token,2,2);
        $to[] = substr($token,-2);
        $to[] = substr((string)((int)$to[0]+(int)$to[1]),-2);
        //print_r2($to);
        if($to[2]!=$to[3]) {
            $str = false;
        }
    }
    // 공개키 같은 경우 기간 제한 있음 ex) 2451RNC4xg161355065075
    else {
        $to[] = substr($token,0,2);
        $to[] = substr($token,2,2);
        $to[] = substr($token,-2);
        $to[] = substr((string)((int)$to[0]+(int)$to[1]),-2);
        $to[] = substr($token,10,-2);
        // print_r2($to);
        if($to[2]!=$to[3] || $to[4] < time()-$expire_date) {
            $str = false;
        }
    }
    return $str;
}
}

// make token 함수
if(!function_exists('make_token1')){
function make_token1() {
	// 토큰 생성
	$to[] = rand(10,99);
	$to[] = rand(10,99);
	$to[] = G5_SERVER_TIME;
	$to[] = sprintf("%02d",substr($to[0]+$to[1],-2));
	$token = $to[0].$to[1].random_str(6).$to[2].$to[3];
	//echo $token.'<br>';
    return $token;
}
}

// 배너출력
if(!function_exists('display_banner10')){
function display_banner10($bo_table, $device, $skin_dir='', $skin='', $position, $subject_len=40, $cache_time=1)
{
    global $g5;

    if (!$position) $position = 'main';
    if (!$skin) $skin = 'boxbanner.skin.php';

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $banner_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            if(!is_dir($banner_skin_path))
                $banner_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            $banner_skin_url = str_replace(G5_PATH, G5_URL, $banner_skin_path);
        } else {
            $banner_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            $banner_skin_url = str_replace(G5_PATH, G5_URL, $banner_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $banner_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/board/'.$skin_dir;
            $banner_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/board/'.$skin_dir;
        } else {
            $banner_skin_path = G5_SKIN_PATH.'/board/'.$skin_dir;
            $banner_skin_url  = G5_SKIN_URL.'/board/'.$skin_dir;
        }
    }

    // $cache_fwrite = false;
    $cache_fwrite = true;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/banner10-{$bo_table}-{$skin_dir}-{$skin}-{$device}-serial.php";

        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
            
            if(!$cache_fwrite) {
                try{
                    $file_contents = file_get_contents($cache_file);
                    $file_ex = explode("\n\n", $file_contents);
                    $caches = unserialize(base64_decode($file_ex[1]));
                    echo $file_contents;

                    $list = (is_array($caches) && isset($caches['list'])) ? $caches['list'] : array();
                    $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
                } catch(Exception $e){
                    $cache_fwrite = true;
                    $list = array();
                }
            }
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();
        
        // $device 관련
        $sql_device = (G5_IS_MOBILE) ? " AND wr_8 IN ('all','mo') " : " AND wr_8 IN ('all','pc') ";

        // 게시물 관련 정보
		$tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
		$sql = "SELECT * FROM {$tmp_write_table}
                WHERE ca_name = '".$position."' AND wr_is_comment = 0
                    {$sql_device}
                ORDER BY convert(wr_7, decimal)
        ";
        // echo $sql.'<br>';
        $result = sql_query($sql,1);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
	        $row['file'] = get_file($bo_table, $row['wr_id']);
			
            $list[$i] = $row;
        }
        

        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $caches = array(
                'list' => $list,
                );
            $cache_content = "<?php if (!defined('_GNUBOARD_')) exit; ?>\n\n";
            $cache_content .= serialize($caches);  //serialize

            fwrite($handle, $cache_content);
            fclose($handle);

            @chmod($cache_file, 0640);
        }
    }
    //print_r2($list);

    ob_start();
    include $banner_skin_path.'/'.$skin;
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
}


// 네비 메뉴 출력
if(!function_exists('board_navi10')){
function board_navi10($bo_table, $device, $skin_dir='', $skin='', $subject_len=40, $cache_time=1)
{
    global $g5;

    if (!$skin) $skin = 'navi.skin.php';
    
    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $navi_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            if(!is_dir($navi_skin_path))
                $navi_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            $navi_skin_url = str_replace(G5_PATH, G5_URL, $navi_skin_path);
        } else {
            $navi_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/board/'.$match[1];
            $navi_skin_url = str_replace(G5_PATH, G5_URL, $navi_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $navi_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/board/'.$skin_dir;
            $navi_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/board/'.$skin_dir;
        } else {
            $navi_skin_path = G5_SKIN_PATH.'/board/'.$skin_dir;
            $navi_skin_url  = G5_SKIN_URL.'/board/'.$skin_dir;
        }
    }

    //$cache_fwrite = false;
    $cache_fwrite = true;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/navi10-{$bo_table}-{$skin_dir}-{$skin}-{$device}-serial.php";

        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
            
            if(!$cache_fwrite) {
                try{
                    $file_contents = file_get_contents($cache_file);
                    $file_ex = explode("\n\n", $file_contents);
                    $caches = unserialize(base64_decode($file_ex[1]));
                    echo $file_contents;

                    $list = (is_array($caches) && isset($caches['list'])) ? $caches['list'] : array();
                    $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
                } catch(Exception $e){
                    $cache_fwrite = true;
                    $list = array();
                }
            }
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();
        
        // $device 관련
        $sql_device_pc = ($device=='pc') ? " AND wr1.wr_1 = '' " : "";
        $sql_device_mobile = ($device=='mobile') ? " AND wr1.wr_2 = '' " : "";

        // 게시물 관련 정보
		$tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
		$sql = "	SELECT wr1.wr_id, wr1.wr_reply, wr1.wr_subject, wr1.wr_link1, wr1.wr_file, wr1.wr_1, wr1.wr_2, wr1.wr_3, wr1.wr_4, wr1.wr_5, wr1.wr_10
						,GROUP_CONCAT(wr2.wr_subject ORDER BY wr2.wr_reply SEPARATOR '^') AS group_subject
						,GROUP_CONCAT(wr2.wr_content ORDER BY wr2.wr_reply SEPARATOR '^') AS group_content
						,GROUP_CONCAT(wr2.wr_link1 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_link1
						,GROUP_CONCAT(wr2.wr_1 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_1
						,GROUP_CONCAT(wr2.wr_2 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_2
						,GROUP_CONCAT(wr2.wr_3 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_3
						,GROUP_CONCAT(wr2.wr_4 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_4
						,GROUP_CONCAT(wr2.wr_5 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_5
						,GROUP_CONCAT(wr2.wr_6 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_6
						,GROUP_CONCAT(wr2.wr_10 ORDER BY wr2.wr_reply SEPARATOR '^') AS group_wr_10
						,COUNT(wr2.wr_id) AS group_count
					FROM {$tmp_write_table} AS wr1
						JOIN {$tmp_write_table} AS wr2
					WHERE wr1.wr_is_comment = 0 
                        {$sql_device_pc}
                        {$sql_device_mobile}
                        AND wr1.wr_4 = ''
                        AND wr1.wr_9 = '' AND wr2.wr_9 = ''
						AND wr1.wr_num = wr2.wr_num
						AND wr2.wr_reply LIKE CONCAT(wr1.wr_reply,'%')
					GROUP BY wr1.wr_num, wr1.wr_reply
					ORDER BY wr1.wr_num DESC, wr1.wr_reply
		";
        $result = sql_query($sql);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
			// 단계
			$row['wr_depth'] = strlen($row['wr_reply']);
			
			// 그룹 배열 
			$row['group_subject_items'] = explode('^', $row['group_subject']);
			$row['group_content_items'] = explode('^', $row['group_content']);
			for ($j=0; $j<count($row['group_content_items']); $j++) {
				$row['group_content_items'][$j] = unserialize($row['group_content_items'][$j]);
			}
			$row['group_link1_items'] = explode('^', $row['group_link1']);
			$row['group_wr_1_items'] = explode('^', $row['group_wr_1']);
			$row['group_wr_2_items'] = explode('^', $row['group_wr_2']);
			$row['group_wr_3_items'] = explode('^', $row['group_wr_3']);
			$row['group_wr_4_items'] = explode('^', $row['group_wr_4']);
			$row['group_wr_5_items'] = explode('^', $row['group_wr_5']);
			$row['group_wr_6_items'] = explode('^', $row['group_wr_6']);
			$row['group_wr_10_items'] = explode('^', $row['group_wr_10']);
			
            $list[$i] = $row;
        }
        

        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $caches = array(
                'list' => $list,
                );
            $cache_content = "<?php if (!defined('_GNUBOARD_')) exit; ?>\n\n";
            $cache_content .= base64_encode(serialize($caches));  //serialize

            fwrite($handle, $cache_content);
            fclose($handle);

            @chmod($cache_file, 0640);
        }
    }
    //print_r2($list);

    ob_start();
    include $navi_skin_path.'/'.$skin;
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
}

// 네비 정보 출력 (사용자단), 관리자단 부분은 skin단 list.html 참조
// $rtn 값이 없으면(디폴트) echo 로 li를 바로 뿌려준다.
if(!function_exists('put_navi_menu')){
function put_navi_menu($row, $rtn=0, $class_name='active')
{
    global $g5,$ca,$it,$bo_table,$cont;

    // 배열전체를 복사
    $list = $row;
    unset($row);
//    print_r2($list);

	// 메뉴 active 설정
//    echo $g5['navi_menu'].'<br>';
//    echo $list['wr_10'].'='.substr($g5['navi_menu'],0,($list['wr_depth']+1)*2).'<br>';
    // 게시물 링크인 경우 기본 active룰을 따른다.
	if($g5['navi_menu'])
		$list['wr_active'] = ( substr($g5['navi_menu'],0,($list['wr_depth']+1)*2) == $list['wr_10'] ) ? $class_name : '';
    
    // 게시판 링크만 있는 경우 (1단계메뉴와 2단계 메뉴가 같은 링크일 수 있으므로 예외처리해 줘야 함)
    if( !parse_url2($list['wr_link1'],"wr_id") && parse_url2($list['wr_link1'],"bo_table") ) {
        //print_r2($board);
        //echo $g5['navi_menu'].'/'.$board['bo_10'].'<br>';
        // 1단계인 경우는 게시판 앞에 두자리 코드만 같으면 active 처리해 줘야 함
        // 2단계인 경우는 bo_table까지 일치해야 한다.
        if($list['wr_depth']==0)
            $list['wr_active'] = ( substr($g5['navi_menu'],0,2) == $list['wr_10'] ) ? $class_name : '';
        else
            $list['wr_active'] = ( parse_url2($list['wr_link1'],"bo_table") == $bo_table && $g5['navi_menu'] == $list['wr_10'] ) ? $class_name : '';
    }
    // 쇼핑몰 카테고리 링크인 경우
    if( parse_url2($list['wr_link1'],"ca_id") ) {
        //print_r2($ca);
		$list['wr_active'] = ( $g5['navi_menu'] == $ca['ca_10'] ) ? $class_name : '';
    }
    // 상품 상세보기 링크인 경우
    if( parse_url2($list['wr_link1'],"it_id") ) {
        //print_r2($it);
		$list['wr_active'] = ( $g5['navi_menu'] == $it['it_10'] ) ? $class_name : '';
    }
    // 내용 콘텐츠인 경우
    if( parse_url2($list['wr_link1'],"co_id") ) {
        //print_r2($it);
		$list['wr_active'] = ( $g5['navi_menu'] == $cont['co_10'] ) ? $class_name : '';
    }
	
    // 새창 띄우기인지
    $list['wr_target'] = (!$list['wr_3']) ? '' : ' target="_blank"';
    //print_r2($list);
    
    // 링크 있으면
    if($list['wr_link1']) {
        // 링크에 link= 있으면 내부링크
        if( preg_match("/link=/",$list['wr_link1']) )
            $list['wr_link1'] = "javascript:scrollto('".parse_url2($list['wr_link1'],"link")."')";
        else
            $list['wr_link1'] = add_g5_url($list['wr_link1']);
    }
    else {
		$list['wr_link1'] = "javascript:";
    }
    // 링크 닫기
    $list['_a'] = ( $list['wr_link1'] ) ? '</a>' : '';


    // 첨부 파일이 있는 경우 (메뉴 이미지로 활용, 두개인 경우 오버 효과까지!)
    if( is_array($list['wr_file']) ) {
        $list['file'] = get_file($list['bo_table'], $list['wr_id']);
        // 첨부파일 썸네일 추출
        $list['thumb'] = get_list_thumbnail($list['bo_table'], $list['wr_id'], $thumb_width, $thumb_height, false, true);
        if($list['thumb']['src']) {
            $list['img'] = $list['thumb']['src'];
        } else {
            $list['img'] = G5_IMG_URL.'/no_img.png';
            $list['thumb']['alt'] = '이미지가 없습니다.';
        }
        $list['img_content'] = '<img src="'.$list['img'].'" alt="'.$list['thumb']['alt'].'" >';
    }
   
    $list['depth_no'] = ($list['wr_depth']==1) ? '1010' : '10';
    $list['depth_no'] = ($list['wr_depth']==2) ? '101010' : $list['depth_no'];
    
    // 항목 출력
    $list['li'] = '
        <li class="li'.$list['depth_no'].' '.$list['wr_active'].'">
            <a href="'.$list['wr_link1'].'" class="" '.$list['wr_target'].'>'.$list['wr_subject'].'</a>
    '.PHP_EOL;
    if(!$rtn) {echo $list['li'];}

    return $list;
}
}


// 뿌리오 메시지 발송
if(!function_exists('ppurio_send')){
function ppurio_send($arr)
{
	global $g5,$member;
    
    $_api_url = 'https://message.ppurio.com/api/send_utf8_json.php';     // UTF-8 인코딩과 JSON 응답용 호출 페이지

    $_param['userid'] = $g5['setting']['set_ppurio_userid'];           // [필수] 뿌리오 아이디
//    $_param['callback'] = hyphen_hp_remove($g5['setting']['set_ppurio_callback']);    // [필수] 발신번호 - 숫자만
    $_param['callback'] = trim($arr['from_number']);    // [필수] 발신번호 - 숫자만
    $_param['phone'] = hyphen_hp_remove($arr['to_number']);       // [필수] 수신번호 - 여러명일 경우 |로 구분 '010********|010********|010********'
    $_param['msg'] = $arr['content'];   // [필수] 문자내용 - 이름(names)값이 있다면 [*이름*]가 치환되서 발송됨
//    $_param['names'] = '';            // [선택] 이름 - 여러명일 경우 |로 구분 '홍길동|이순신|김철수'

    $_curl = curl_init();
    curl_setopt($_curl,CURLOPT_URL,$_api_url);
    curl_setopt($_curl,CURLOPT_POST,true);
    curl_setopt($_curl,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($_curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($_curl,CURLOPT_POSTFIELDS,$_param);
    $_result = curl_exec($_curl);
    curl_close($_curl);

    $_result = json_decode($_result);
/*
 * 응답값
 *
 *  <성공시>
 *    result : 'ok'                           - 전송결과 성공
 *    type   : 'sms'                          - 단문은 sms 장문은 lms 포토문자는 mms
 *    msgid  : '123456789'                    - 발송 msgid (예약취소시 필요)
 *    ok_cnt : 1                              - 발송건수
 *
 *  <실패시>
 *    result : 'invalid_member'               - 연동서비스 신청이 안 됐거나 없는 아이디
 *    result : 'under_maintenance'            - 요청시간에 서버점검중인 경우
 *    result : 'allow_https_only'             - http 요청인 경우
 *    result : 'invalid_ip'                   - 등록된 접속가능 IP가 아닌 경우
 *    result : 'invalid_msg'                  - 문자내용에 오류가 있는 경우
 *    result : 'invalid_names'                - 이름에 오류가 있는 경우
 *    result : 'invalid_subject'              - 제목에 오류가 있는 경우
 *    result : 'invalid_sendtime'             - 예약발송 시간에 오류가 있는 경우 (10분이후부터 다음해말까지 가능)
 *    result : 'invalid_sendtime_maintenance' - 예약발송 시간에 서버점검 예정인 경우
 *    result : 'invalid_phone'                - 수신번호에 오류가 있는 경우
 *    result : 'invalid_msg_over_max'         - 문자내용이 너무 긴 경우
 *    result : 'invalid_callback'             - 발신번호에 오류가 있는 경우
 *    result : 'once_limit_over'              - 1회 최대 발송건수 초과한 경우
 *    result : 'daily_limit_over'             - 1일 최대 발송건수 초과한 경우
 *    result : 'not_enough_point'             - 잔액이 부족한 경우
 *    result : 'over_use_limit'               - 한달 사용금액을 초과한 경우
 *    result : 'server_error'                 - 기타 서버 오류
 */
    
//    print_r2($_result);
    return $_result;
    
}
}

// 메시지발송로그
if(!function_exists('message_insert')){
function message_insert($arr)
{
	global $g5;
	
	if(!$arr['msg_content'])
		return 0;

	$sql = " INSERT INTO {$g5['message_table']} SET
				com_idx        		= '".$arr['com_idx']."'
				, mb_id        		= '".$arr['mb_id']."'
				, msg_db_table      = '".$arr['db_table']."'
				, msg_db_id      	= '".$arr['db_id']."'
				, msg_type        	= '".$arr['msg_type']."'
				, msg_hp        	= '".$arr['msg_hp']."'
				, msg_email        	= '".$arr['msg_email']."'
				, msg_subject       = '".$arr['msg_subject']."'
				, msg_content       = '".$arr['msg_content']."'
				, msg_result        = '".$arr['msg_result']."'
				, msg_status        = '".$arr['msg_status']."'
				, msg_reg_dt        = '".G5_TIME_YMDHIS."'
				, msg_update_dt     = '".G5_TIME_YMDHIS."'
	";
    sql_query($sql,1);
    $msg_idx = sql_insert_id();
    
    return $msg_idx;
}
}

                                             
// 추가사항
if(!function_exists('additional_update')){
function additional_update($arr)
{
	global $g5,$config;
	
	if(!$arr['apc_idx'])
		return 0;

    $g5_table_name = $g5['additional_table'];
    $fields = sql_field_names($g5_table_name);
    $pre = substr($fields[0],0,strpos($fields[0],'_'));
    
    // 변수 재설정
    $arr[$pre.'_update_dt'] = G5_TIME_YMDHIS;
    $arr['add_start_ym'] = $arr['add_start_year'].'-'.$arr['add_start_month'];   // 년월
    $arr['add_end_ym'] = $arr['add_end_year'].'-'.$arr['add_end_month'];   // 년월

    // 공통쿼리
    $skips[] = $pre.'_idx';	// 건너뛸 변수 배열
    $skips[] = $pre.'_reg_dt';
    for($i=0;$i<sizeof($fields);$i++) {
        if(in_array($fields[$i],$skips)) {continue;}
        $sql_commons[] = " ".$fields[$i]." = '".$arr[$fields[$i]]."' ";
    }

    // after sql_common value setting
    // $sql_commons[] = " com_idx = '".$arr['ss_com_idx']."' ";

    // 공통쿼리 생성
    $sql_common = (is_array($sql_commons)) ? implode(",",$sql_commons) : '';
    
    $sql = "SELECT * FROM {$g5_table_name} 
            WHERE add_idx = '{$arr['add_idx']}'
    ";
//    echo $sql.'<br>';
    $row = sql_fetch($sql,1);
	if($row[$pre."_idx"]) {
		$sql = "UPDATE {$g5_table_name} SET 
                    {$sql_common} 
				WHERE ".$pre."_idx = '".$row[$pre."_idx"]."'
        ";
		sql_query($sql,1);
	}
	else {
		$sql = "INSERT INTO {$g5_table_name} SET 
                    {$sql_common} 
                    , ".$pre."_reg_dt = '".G5_TIME_YMDHIS."'
        ";
		sql_query($sql,1);
        $row[$pre."_idx"] = sql_insert_id();
	}
//    echo $sql.'<br>';
    return $row[$pre."_idx"];
}
}

// 경력사항
if(!function_exists('career_update')){
function career_update($arr)
{
	global $g5,$config;
	
	if(!$arr['apc_idx'])
		return 0;

    $g5_table_name = $g5['career_table'];
    $fields = sql_field_names($g5_table_name);
    $pre = substr($fields[0],0,strpos($fields[0],'_'));
    
    // 변수 재설정
    $arr[$pre.'_update_dt'] = G5_TIME_YMDHIS;
    $arr['crr_start_ym'] = $arr['crr_start_year'].'-'.$arr['crr_start_month'];   // 년월
    $arr['crr_end_ym'] = $arr['crr_end_year'].'-'.$arr['crr_end_month'];   // 년월

    // 공통쿼리
    $skips[] = $pre.'_idx';	// 건너뛸 변수 배열
    $skips[] = $pre.'_reg_dt';
    for($i=0;$i<sizeof($fields);$i++) {
        if(in_array($fields[$i],$skips)) {continue;}
        $sql_commons[] = " ".$fields[$i]." = '".$arr[$fields[$i]]."' ";
    }

    // after sql_common value setting
    // $sql_commons[] = " com_idx = '".$arr['ss_com_idx']."' ";

    // 공통쿼리 생성
    $sql_common = (is_array($sql_commons)) ? implode(",",$sql_commons) : '';
    
    $sql = "SELECT * FROM {$g5_table_name} 
            WHERE crr_idx = '{$arr['crr_idx']}'
    ";
//    echo $sql.'<br>';
    $row = sql_fetch($sql,1);
	if($row[$pre."_idx"]) {
		$sql = "UPDATE {$g5_table_name} SET 
                    {$sql_common} 
				WHERE ".$pre."_idx = '".$row[$pre."_idx"]."'
        ";
		sql_query($sql,1);
	}
	else {
		$sql = "INSERT INTO {$g5_table_name} SET 
                    {$sql_common} 
                    , ".$pre."_reg_dt = '".G5_TIME_YMDHIS."'
        ";
		sql_query($sql,1);
        $row[$pre."_idx"] = sql_insert_id();
	}
//    echo $sql.'<br>';
    return $row[$pre."_idx"];
}
}


// 학력/자격/교육/어학
if(!function_exists('school_update')){
function school_update($arr)
{
	global $g5,$config;
	
	if(!$arr['apc_idx'])
		return 0;

    $g5_table_name = $g5['school_table'];
    $fields = sql_field_names($g5_table_name);
    $pre = substr($fields[0],0,strpos($fields[0],'_'));
    
    // 변수 재설정
    $arr[$pre.'_update_dt'] = G5_TIME_YMDHIS;
    $arr['shl_yearmonth'] = $arr['shl_year'].'-'.$arr['shl_month'];   // 년월

    // 공통쿼리
    $skips[] = $pre.'_idx';	// 건너뛸 변수 배열
    $skips[] = $pre.'_reg_dt';
    for($i=0;$i<sizeof($fields);$i++) {
        if(in_array($fields[$i],$skips)) {continue;}
        $sql_commons[] = " ".$fields[$i]." = '".$arr[$fields[$i]]."' ";
    }

    // after sql_common value setting
    // $sql_commons[] = " com_idx = '".$arr['ss_com_idx']."' ";

    // 공통쿼리 생성
    $sql_common = (is_array($sql_commons)) ? implode(",",$sql_commons) : '';
    
    $sql = "SELECT * FROM {$g5_table_name} 
            WHERE shl_idx = '{$arr['shl_idx']}'
    ";
//    echo $sql.'<br>';
    $row = sql_fetch($sql,1);
	if($row[$pre."_idx"]) {
		$sql = "UPDATE {$g5_table_name} SET 
                    {$sql_common} 
				WHERE ".$pre."_idx = '".$row[$pre."_idx"]."'
        ";
		sql_query($sql,1);
	}
	else {
		$sql = "INSERT INTO {$g5_table_name} SET 
                    {$sql_common} 
                    , ".$pre."_reg_dt = '".G5_TIME_YMDHIS."'
        ";
		sql_query($sql,1);
        $row[$pre."_idx"] = sql_insert_id();
	}
//    echo $sql.'<br>';
    return $row[$pre."_idx"];
}
}


// 휴대폰번호 정상적인지 체크 return=1 일 때 정상
if(!function_exists('check_hp')){
function check_hp($hp)
{
    $hp = hyphen_hp_number($hp);    // 하이픈(-)을 넣어준다.
//    echo $hp.'<br>';
    if ( preg_match( '/^(010|011|016|017|018|019)-[^0][0-9]{3,4}-[0-9]{4}/',$hp) ) {
        return 1;
    }
    else {
//        echo "잘못된 휴대폰 번호입니다. 숫자, - 를 포함한 숫자만 입력하세요.";
        return 0;
    }
}
}

// 휴대폰번호의 하이픈(-)을 제거하고 숫자만
if(!function_exists('hyphen_hp_remove')){
function hyphen_hp_remove($hp)
{
    $hp = preg_replace("/-/", "", trim($hp));
    return $hp;
}
}

//KOSMO에 log데이터 전송 함수
if(!function_exists('send_kosmo_log')){
function send_kosmo_log(){
	global $g5, $board, $is_member, $member, $w, $stx, $mb;
	if(!$is_member)
		return;
	//print_r2($_SESSION);exit;
	if(!$_SEESSION['ss_com_kosmolog_key'])
		return;

	if(!$member['mb_id'])
		return;
	
	$user_status = '';
	if(preg_match('/update$/i',$g5['file_name'])){
		if(!$w) $user_status = '등록';
		else if($w == 'u') $user_status = '수정';
		else if($w == 'd') $user_status = '삭제';
	}
	else if(preg_match('/list$/i',$g5['file_name'])){
		if($stx) $user_status = '검색';
	}
	else{
		if($g5['file_name'] == 'login_check'){
			//print_r2($member);exit;
			$user_status = '접속';
		}
		else if($g5['file_name'] == 'logout'){
			$user_status = '종료';
		}
	}
	
	if(!$user_status)
		return;
	//print_r3($user_status);return;
	$url = 'https://log.smart-factory.kr/apisvc/sendLogData.json';
	/*
	$crtcKey = $_SEESSION['ss_com_kosmolog_key'];
	$logDt = G5_TIME_YMDHIS;
	$useSe = $user_status;
	$sysUser = $member['mb_id'];
	$conectIp = $member['mb_login_ip'];
	$dataUsgqty = '';
	*/
	$darr = array(
		'crtfcKey' => $_SEESSION['ss_com_kosmolog_key'],
		'logDt' => G5_TIME_YMDHIS,
		'useSe' => $user_status,
		'sysUser' => $member['mb_id'],
		'conectIp' => $member['mb_login_ip'],
		'dataUsgqty' => ''
	);

	$opt = array(
		'http' => array(
			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($darr)
		)
	);
	$context = stream_context_create($opt); //데이터 가공
	$result = file_get_contents($url, false, $context); //전송 ~ 결과값 반환
	$data = json_decode($result, true);
}
}

// update bom_price_history
// bom_idx, bom_start_date, bom_price
if(!function_exists('bom_price_history')){
function bom_price_history($arr) {
    global $g5;

    // Update price table info. Update for same price and date, Insert for not existing.
    $sql = "SELECT * FROM {$g5['bom_price_table']}
        WHERE bom_idx = '".$arr['bom_idx']."'
            AND bop_start_date = '".$arr['bom_start_date']."'
    ";
    $bop = sql_fetch($sql,1);
    if($bop['bop_idx']) {
        $sql = "UPDATE {$g5['bom_price_table']} SET
                    bop_price = '".$arr['bom_price']."',
                    bop_start_date = '".$arr['bom_start_date']."',
                    bop_update_dt = '".G5_TIME_YMDHIS."'
                WHERE bop_idx = '".$bop['bop_idx']."'
        ";
        sql_query($sql,1);
    }
    else {
        $sql = " INSERT INTO {$g5['bom_price_table']} SET
                    bom_idx = '".$arr['bom_idx']."',
                    bop_price = '".$arr['bom_price']."',
                    bop_start_date = '".$arr['bom_start_date']."',
                    bop_reg_dt = '".G5_TIME_YMDHIS."',
                    bop_update_dt = '".G5_TIME_YMDHIS."'
        ";
        sql_query($sql,1);
        $bop['bop_idx'] = sql_insert_id();
    }

    return $bop['bop_idx'];
}
}

// set the today's proper price according the date registered in the bom_price table.
if(!function_exists('set_bom_price')){
function set_bom_price($bom_idx) {
    global $g5;

    // get the latest price info and update the mms_item table info.
    $sql = "UPDATE {$g5['bom_table']} AS bom SET
                    bom_price = (
                        SELECT bop_price
                        FROM {$g5['bom_price_table']}
                        WHERE bom_idx = bom.bom_idx
                            AND bop_start_date <= '".G5_TIME_YMD."'
                        ORDER BY bop_start_date DESC
                        LIMIT 1
                    )
                WHERE bom_idx = '".$bom_idx."' AND bom_status NOT IN ('delete','trash')
    ";
    sql_query($sql,1);

}
}

// get the today's proper price according the date registered in the bom_price table.
if(!function_exists('get_bom_price')){
function get_bom_price($bom_idx) {
    global $g5;

    $sql = "SELECT bop_price
            FROM {$g5['bom_price_table']}
            WHERE bom_idx = '".$bom_idx."'
                AND bop_start_date <= '".G5_TIME_YMD."'
            ORDER BY bop_start_date DESC
            LIMIT 1
    ";
    $row = sql_fetch($sql,1);
    return (int)$row['bop_price'];

}
}


// 사원 정보를 얻는다. (외부 인트라인 경우 내부인트라에서 )
if(!function_exists('get_saler_idx')){
function get_saler_idx($mb_name, $mb_intra='', $mb_intra_id='') {
    global $g5;
    
    if(!$mb_name)
        return false;

    $sql = " SELECT mb_2, mb_9 FROM {$g5['member_table']} WHERE mb_name = TRIM('$mb_name') ";
    $rs = sql_query($sql,1);
    // 한명 이상인 경우는 mb_9 keys 값을 분리해서 해당 회원을 찾아야 함
    if(sql_num_rows($rs) > 1) {
        for($i=0;$row=sql_fetch_array($rs);$i++) {
            // mb9에 기존 인트라 정보 저장됨 (:mb_intra=31:,:mb_intra31_id=jamesjoa:,)
            $row['keys'] = get_keys($row['mb_9']);
            if($row['keys']['mb_intra']==$mb_intra && $row['keys']['mb_intra'.$mb_intra.'_id']==$mb_intra_id)
            $trm_idx = $row['mb_2'];
        }
    }
    else {
        $mb = sql_fetch($sql);
        $trm_idx = $mb['mb_2'];
    }

    return $trm_idx;
}
}


// 게시판 변수설정들을 불려온다. wr_7 serialized 풀어서 배열로 가지고 옴
if(!function_exists('get_board')){
function get_board($bo_table) {
    global $g5;
    
    $sql = " SELECT * FROM ".$g5['board_table']." WHERE bo_table = '$bo_table' ";
    $board = sql_fetch($sql,1);
    $unser = unserialize($board['bo_7']);
    if( is_array($unser) ) {
        foreach ($unser as $k1=>$v1) {
            $board[$k1] = stripslashes64($v1);
        }    
    }
    return $board;
}
}

// number to hangle display
if(!function_exists('num_to_han')){
function num_to_han($mny){
    $stlen = strlen($mny)-1;
    //숫자를 4단위로 한글 단위를 붙인다.
    $names = array("원","만원","억","조","경"); // 단위의 한글발음 (조 다음으로 계속 추가 가능)
    $nums = str_split($mny); // 숫자를 배열로 분리
    $nums = array_reverse($nums);
    $units = array();
    // 역으로 자리숫자마다 숫자 단위를 붙여서 배열로 만듦
    for($i=0,$m=count($nums);$i<$m;$i++){
        $units[] = $names[floor($i/4)];
    }
    // print_r2($units);
    $cu = '';
    $str = '';
    $flag = floor($stlen/4)*4;
    // echo $flag.'<br>';
    // 4자리 단위로 flag 기준 범위만 돌면서 값을 생성 
    for($i=$flag,$m=count($nums); $i<$m; $i++){
        $arr = $nums[$i];
        // echo $t.'<br>';
        // 단위가 바뀔 때만 단위값을 붙여줌
        if($cu != $units[$i]){
            $unit = $units[$i];
        }
        // 숫자를 역으로 돌면서 앞에다 숫자를 붙여줌
        $str = $arr.$str;
    }
    // 만단위 이상인 경우는 끝에 한자리만 더 추가
    if($flag>3) {
            $str .= '.'.$nums[$flag-1];
    }
    $str = $str ?: 0;
    // return($str); 
    return(array($str,$unit)); 
}
}


// 대시보드 기본 삭제함수
if(!function_exists('dash_delete')){
function dash_delete(){
    global $g5;
    //상태값이 trash로 된 이후 일주일이 지난 데이터는 회원을 불문하고 전부 삭제한다. 
    $mta_del_sql = " DELETE FROM {$g5['meta_table']}
    WHERE mta_db_table = 'member'
        AND mta_key = 'dashboard_menu'
        AND mta_status = 'trash'
        AND mta_update_dt < DATE_SUB(NOW(), interval 7 day) ";
    sql_query($mta_del_sql);
    //해당 g5_1_dash_grid 테이블의 레코드도 상태값이 trash로 된 이후 일주일이 지난 데이터는 삭제
    $dsg_del_sql = " DELETE FROM {$g5['dash_grid_table']}
    WHERE dsg_status = 'trash'
        AND dsg_update_dt < DATE_SUB(NOW(), interval 7 day) ";
    sql_query($dsg_del_sql);
    //해당 g5_1_member_dash 테이블의 레코드도 상태값이 trash로 된 이후 일주일이 지난 데이터는 삭제
    $mbd_del_sql = " DELETE FROM {$g5['member_dash_table']}
    WHERE mbd_status = 'trash'
        AND mbd_update_dt < DATE_SUB(NOW(), interval 7 day) ";
    sql_query($mbd_del_sql);
}
}

if(!function_exists('dash_test')){
function dash_test(){
    return 'dash_test';
}
}
?>