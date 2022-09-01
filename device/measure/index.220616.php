<?php
// 크롬 요소검사 열고 확인하면 되겠습니다. 
// print_r2 안 쓰고 print_r로 확인하는 게 좋습니다.
header('Content-Type: application/json; charset=UTF-8');
include_once('./_common.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');

// print_r($_REQUEST);exit;
//echo $_REQUEST['shf_type'][0];
$rawBody = file_get_contents("php://input"); // 본문을 불러옴
$getData = array(json_decode($rawBody,true)); // 데이터를 변수에 넣고
// print_r($getData);
// exit;

// 토큰 비교
if(!check_token1($getData[0]['token'])) {
	$result_arr = array("code"=>499,"message"=>"token error");
}
else if(is_array($getData[0]['list'])) {

    for($i=0;$i<sizeof($getData[0]['list']);$i++) {
        $arr = $getData[0]['list'][$i];

        $arr['dta_status'] = '0';
        $arr['dta_dt'] = strtotime(preg_replace('/\./','-',$arr['dta_date'])." ".$arr['dta_time']);
        $arr['dta_date1'] = date("Y-m-d",$arr['dta_dt']);   // 2 or 4 digit format(20 or 2020) no problem.
        $arr['st_time'] = strtotime($arr['dta_date1']." 00:00:00"); // 해당 날짜의 시작
        $arr['en_time'] = strtotime($arr['dta_date1']." 23:59:59"); // 해당 날짜의 끝
        // echo $arr['dta_date1'].PHP_EOL;
        // echo $arr['st_time'].'~'.$arr['en_time'].PHP_EOL;
        $table_name = 'g5_1_data_measure_'.$arr['mms_idx'].'_'.$arr['dta_type'].'_'.$arr['dta_no'];


        // checkout db table exists and create if not exists.
        $sql = "SELECT EXISTS (
                    SELECT 1 FROM Information_schema.tables
                    WHERE TABLE_SCHEMA = '".G5_MYSQL_DB."'
                    AND TABLE_NAME = '".$table_name."'
                ) AS flag
        ";
        $tb1 = sql_fetch($sql,1);
        if(!$tb1['flag']) {
            $file = file('./sql_write.sql');
            $file = get_db_create_replace($file);
            $sql = implode("\n", $file);
            $source = array('/__TABLE_NAME__/', '/;/');
            $target = array($table_name, '');
            $sql = preg_replace($source, $target, $sql);
            sql_query($sql, FALSE);
        }

        // 중복체크
        $sql_dta = "   SELECT dta_idx FROM {$table_name}
                        WHERE dta_dt = '".$arr['dta_dt']."'
        ";
        //echo $sql_dta.'<br>';
		$dta = sql_fetch($sql_dta,1);
        
		// 정보 업데이트
		if($dta['dta_idx']) {
			
			$sql = "UPDATE {$table_name} SET 
                        dta_value = '".$arr['dta_value']."'
						, dta_update_dt = '".G5_SERVER_TIME."'
					WHERE dta_idx = '".$dta['dta_idx']."'";
			sql_query($sql,1);
            $result_arr[$i]['code'] = 200;
            $result_arr[$i]['message'] = "Updated OK!";

		}
        // 정보 입력
        else{

			$sql = "INSERT INTO {$table_name} SET 
						dta_dt = '".$arr['dta_dt']."'
                        , dta_value = '".$arr['dta_value']."'
						, dta_reg_dt = '".G5_SERVER_TIME."'
						, dta_update_dt = '".G5_SERVER_TIME."'
            ";
			sql_query($sql,1);
//            echo $sql.'<br>';
            $dta['dta_idx'] = sql_insert_id();
            $result_arr[$i]['code'] = 200;
            $result_arr[$i]['message'] = "Inserted OK!";
        
        }
        $result_arr[$i]['dta_idx'] = $dta['dta_idx'];   // 고유번호





        // 코드 정보 추출
        $sql = "SELECT *
                FROM {$g5['tag_code_table']}
                WHERE mms_idx = '".$arr['mms_idx']."'
                    AND dta_type = '".$arr['dta_type']."'
                    AND dta_no = '".$arr['dta_no']."'
                    AND tgc_status = 'ok'
        ";
        // echo $sql.PHP_EOL;
        $cod = sql_fetch($sql,1);
        // 태그 설정 정보가 존재할 때만
        if($cod['tgc_idx']) {

            // 예외 조건에 따라 빈도수가 너무 많을 때 무시
            $unsual_flag = 0;   // 조건에 해당되면 1로 변경
            if($cod['tgc_count_unsual'] || $cod['tgc_range1_count_unsual']) {
                if($cod['tgc_type']=='range') { // 임계치
                    $cod['tgc_time_interval'] = G5_SERVER_TIME - $cod['tgc_range1_interval_unsual']; // 시간 주기
                    $sql_value = " AND (dta_value >= ".$cod['tgc_range_t1']." OR dta_value <= ".$cod['tgc_range_b1'].") ";   // 값 범위
                    $cod['tgc_compare_count'] = $cod['tgc_count_unsual']; // 비교 횟수
                }
                else {
                    // echo $cod['tgc_interval_unsual'].PHP_EOL;
                    $cod['tgc_time_interval'] = G5_SERVER_TIME - $cod['tgc_interval_unsual']; // 시간 주기
                    $sql_value = " AND dta_value ".$cod['tgc_minmax']." ".$cod['tgc_target']." ";   // 값 범위
                    $cod['tgc_compare_count'] = $cod['tgc_range1_count_unsual']; // 비교 횟수
                }
                // 세부 항목들을 다 보고 싶을 때..
                // $sql = "SELECT *, FROM_UNIXTIME(dta_reg_dt,'%Y-%m-%d %H:%i:%s') AS dta_reg_dt2
                //         FROM {$table_name}
                //         WHERE dta_dt > ".$cod['tgc_time_interval']."
                //             {$sql_value}
                // ";
                $sql = "SELECT COUNT(dta_idx) AS cnt
                        FROM {$table_name}
                        WHERE dta_dt > ".$cod['tgc_time_interval']."
                            {$sql_value}
                ";
                // echo $sql.PHP_EOL;
                $one = sql_fetch($sql,1);
                // print_r2($one).PHP_EOL;
                // echo $cod['tgc_compare_count'].PHP_EOL;
                // 예외설정 빈도수로 설정값 값보다 작은 적당한 조건일 때만 알람 발생 ---------
                if($one['cnt'] && $one['cnt'] >= $cod['tgc_compare_count']) {
                    $unsual_flag = 1;
                }
            }

            // 예외조건이 아닌 정상 조건일 때만 알람 입력
            if(!$unsual_flag) {
                // print_r2($cod).PHP_EOL;

                // 값의 성격 정의 (임계치설정일 때 amt_type 을 정의해야 비교할 수 있음)
                // (ok=정상, t1=상단주의알람, t2=상단경고알람, t3=상단위험알람, b1=하단주의알람, b2=하단경고알람, b3=하단위험알람)
                $alarm_flag = 0;   // 조건에 해당되면 1로 변경
                if($cod['tgc_type']=='range') { // 임계치
                    $ranges = array($cod['tgc_range_t1'],$cod['tgc_range_t2'],$cod['tgc_range_t3'],$cod['tgc_range_b1'],$cod['tgc_range_b2'],$cod['tgc_range_b3']);
                    $cod['amt_type'] = get_range($arr['dta_value'],$ranges);    // ok, t1, t2, t3, b1, b2, b3
                    // 알람 범위 설정값인 경우만
                    if($cod['amt_type']!='ok') {
                        $range_text = substr($cod['amt_type'],0,1);    // 비교연산자(>,<,>=...)추출을 위해서 첫단어(t,b)만 추출
                        $range_operator = ($range_text=='t') ? '>=':'<=';    // 비교연산자(>,<,>=...) 정의
                        $range_no = substr($cod['amt_type'],-1);    // 숫자만 추출
                        // echo $range_no.PHP_EOL;
                        // 조건절 정의 (어디 조건에 해당하는 지 찾아야 함)
                        $if_sentence = $arr['dta_value'].$range_operator.$cod['tgc_range_'.$cod['amt_type']];
                        if(eval("return ".$if_sentence.";")) {
                            $alarm_flag = 1;   // 조건에 해당되면 1로 변경
                            $cod['cod_interval'] = $cod['tgc_range'.$range_no.'_interval'];    // 주기
                            $cod['cod_target_count'] = $cod['tgc_range'.$range_no.'_count'];    // 횟수
                        }
                    }
                }
                else {
                    $cod['amt_type'] = '';
                    $cod['cod_interval'] = $cod['tgc_interval'];    // 주기
                    $cod['cod_target_count'] = $cod['tgc_count'];    // 횟수
                    $if_sentence = $arr['dta_value'].$cod['tgc_minmax'].$cod['tgc_target'];   // 조건절
                    if(eval("return ".$if_sentence.";")) {
                        $alarm_flag = 1;   // 조건에 해당되면 1로 변경
                    }
                }
                // echo $if_sentence.PHP_EOL;
                // echo $cod['amt_type'].PHP_EOL;
                // exit;

                
                // 알람을 발생해야 하는 조건의 값인 경우만 알람 입력
                if($alarm_flag) {
                    // echo $alarm_flag.PHP_EOL;

                    // 예지 타이밍인지 체크 (해당 휫수에 해당하는 지 체크), 지난 예지 이후 발생한 횟수를 카운터해야 하므로 sub query를 사용함
                    $pre_yn = 0;   // 조건에 해당되면 1로 변경
                    // 리스트조회시: SELECT amt_idx, amt_pre_yn, amt_reg_dt
                    $sql = "SELECT COUNT(amt_idx) AS cnt
                            FROM {$g5['alarm_tag_table']}
                            WHERE mms_idx = '".$arr['mms_idx']."'
                                AND tgc_idx = '".$cod['tgc_idx']."'
                                AND dta_type = '".$arr['dta_type']."'
                                AND dta_no = '".$arr['dta_no']."'
                                AND amt_type = '".$cod['amt_type']."'
                                AND amt_idx > IFNULL((
                                    SELECT amt_idx FROM {$g5['alarm_tag_table']}
                                    WHERE mms_idx = '".$arr['mms_idx']."'
                                        AND tgc_idx = '".$cod['tgc_idx']."'
                                        AND dta_type = '".$arr['dta_type']."'
                                        AND dta_no = '".$arr['dta_no']."'
                                        AND amt_type = '".$cod['amt_type']."'
                                        AND amt_reg_dt > DATE_ADD(now(), INTERVAL -".$cod['cod_interval']." SECOND)
                                        AND amt_pre_yn = 1 /* 예지발생여부 */
                                    ORDER BY amt_idx DESC
                                    LIMIT 1
                                ),0)
                                AND amt_reg_dt > DATE_ADD(now(), INTERVAL -".$cod['cod_interval']." SECOND)
                    ";
                    // echo $sql.PHP_EOL;
                    // exit;
                    $one = sql_fetch($sql,1);
                    // print_r2($one);
                    // echo $one['cnt'].PHP_EOL;
                    // echo $cod['cod_target_count'].PHP_EOL;
                    if($one['cnt'] >= ($cod['cod_target_count']-1)) {
                        $pre_yn = 1;
                    }
                    // echo $pre_yn.PHP_EOL;
                    // exit;
        
                    // alarm table insert, update later for arm_no
                    $amt_keys = keys_update('mms_idx',$arr['mms_idx'],'','~');  // 최초값
                    $amt_keys = keys_update('tgc_code',$arr['dta_code'],$amt_keys,'~'); // 코드
                    $amt_keys = keys_update('cod_interval',$cod['cod_interval'],$amt_keys,'~');
                    $amt_keys = keys_update('cod_target_count',$cod['cod_target_count'],$amt_keys,'~');
                    $sql = " INSERT INTO {$g5['alarm_tag_table']} SET
                                com_idx = '".$arr['com_idx']."'
                                , mms_idx = '".$arr['mms_idx']."'
                                , tgc_idx = '".$cod['tgc_idx']."'
                                , dta_idx = '".$dta['dta_idx']."'
                                , amt_tgc_category = '".$cod['trm_idx_tag']."'
                                , dta_type = '".$arr['dta_type']."'
                                , dta_no = '".$arr['dta_no']."'
                                , dta_value = '".$arr['dta_value']."'
                                , amt_tgc_type = '".$cod['tgc_type']."'
                                , amt_type = '".$cod['amt_type']."'
                                , amt_pre_yn = '".$pre_yn."'
                                , amt_send_type = '".$cod['tgc_send_type']."'
                                , amt_keys = '".$amt_keys."'
                                , amt_status = 'ok'
                                , amt_reg_dt = '".G5_TIME_YMDHIS."'
                    ";
                    // echo $sql.PHP_EOL;
                    // exit;
                    sql_query($sql,1);
                    $amt_idx = sql_insert_id();
        
                    // Send message(email. sms, push) under the condition of max time daily.
                    if($pre_yn) {
                        // company info
                        $com = get_table_meta('company','com_idx',$arr['com_idx'],'com_send_type');
                        // mms info
                        $mms = get_table_meta('mms','mms_idx',$arr['mms_idx']);
        
                        // sms send, only if company sms setting is possible.
                        if( preg_match("/sms/",$cod['tgc_send_type']) && preg_match("/sms/",$com['com_send_type']) ) {
                            if ($config['cf_sms_use'] == 'icode') {
                                if($config['cf_sms_type'] == 'LMS') {
                                    include_once(G5_LIB_PATH.'/icode.lms.lib.php');
                                }
                                else {
                                    include_once(G5_LIB_PATH.'/icode.sms.lib.php');
                                }
                            }
                        }

                        // 발송 내용 정의
                        if( preg_match("/sms/",$cod['tgc_send_type']) || preg_match("/push/",$cod['tgc_send_type']) ) {
                            // 내용
                            $msg_body = '설비명:'.$mms['mms_name'].PHP_EOL;
                            $msg_body .= '['.$cod['tgc_code'].']'.PHP_EOL;
                            $msg_body .= ($cod['tgc_name']) ? '알람:'.$cod['tgc_name'].PHP_EOL : '';
                            $msg_body .= $cod['tgc_memo'];
                        }
                        else {
                            $msg_body = $cod['tgc_memo'];
                        }
        
                        // 메시지 발송 함수
                        $ar['arm_table'] = 'alarm_tag';
                        $ar['arm_idx'] = $arm_idx;  // 알람용
                        $ar['amt_idx'] = $amt_idx;  // 알람태그용
                        $ar['msg_type'] = $cod['tgc_send_type'];
                        $ar['com_msg_type'] = $com['com_send_type'];
                        $ar['mms_idx'] = $mms['mms_idx'];
                        $ar['mms_name'] = $mms['mms_name'];
                        $ar['arm_code'] = $cod['tgc_code'];
                        $ar['arm_name'] = $cod['tgc_name'];
                        $ar['reports'] = $cod['tgc_reports'];
                        $ar['msg_limit'] = $cod['tgc_message_limit'];
                        $ar['msg_body'] = $msg_body;
                        send_message($ar);
                        // print_r2($ar);
                        unset($ar);
                    }
                    // end of sending email, sms, push..

                }
                // echo $alamt_flag.PHP_EOL;
                // exit;
                //// 알람을 발생해야 하는 조건의 값인 경우만 알람 입력

            }
            //// 예외조건이 아닐 때만 알람 입력

        }
        //// 태그 설정 정보가 존재할 때만







        // 일간 sum 합계 입력
        $sum_common = " mms_idx = '".$arr['mms_idx']."'
                        AND dta_type = '".$arr['dta_type']."'
                        AND dta_no = '".$arr['dta_no']."'
        ";
        // 쿼리는 좌변을 가공하면 속도가 느리다.
        $sql = "SELECT ROUND(AVG(dta_value),2) AS dta_avg
                        , SUM(dta_value) AS dta_sum
                        , MAX(dta_value) AS dta_max
                        , MIN(dta_value) AS dta_min
                FROM {$table_name}
                WHERE dta_dt >= '".$arr['st_time']."' AND dta_dt <= '".$arr['en_time']."'
        ";
        // $sql = "SELECT ROUND(AVG(dta_value),2) AS dta_avg
        //                 , SUM(dta_value) AS dta_sum
        //                 , MAX(dta_value) AS dta_max
        //                 , MIN(dta_value) AS dta_min
        //         FROM {$table_name}
        //         WHERE FROM_UNIXTIME(dta_dt,'%Y-%m-%d') = '".$arr['dta_date1']."'
        // ";
        // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_value = 'sum: ".addslashes($sql)."' ");
        $sum1 = sql_fetch($sql,1); // 일 평균 데이터값 추출 

        // 있으면 업데이트, 없으면 생성
        $sql_sum = "   SELECT dta_idx FROM {$g5['data_measure_sum_table']} 
                        WHERE {$sum_common}
                            AND dta_date = '".$arr['dta_date1']."'
        ";
        //echo $sql_sum.'<br>';
		$sum = sql_fetch($sql_sum,1);
		// 정보 업데이트
		if($sum['dta_idx']) {
            $sql = "UPDATE {$g5['data_measure_sum_table']} SET
                        dta_avg = '".$sum1['dta_avg']."'
                        , dta_sum = '".$sum1['dta_sum']."'
                        , dta_max = '".$sum1['dta_max']."'
                        , dta_min = '".$sum1['dta_min']."'
                    WHERE {$sum_common}
                        AND dta_date = '".$arr['dta_date1']."'
            ";
            // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_value = 'up: ".addslashes($sql)."' ");
            $result = sql_query($sql);
        }
        else {
            $sql = " INSERT INTO {$g5['data_measure_sum_table']} SET
                        com_idx = '".$arr['com_idx']."'
                        , imp_idx = '".$arr['imp_idx']."'
                        , mms_idx = '".$arr['mms_idx']."'
                        , mmg_idx = '".$g5['mms'][$arr['mms_idx']]['mmg_idx']."'
                        , dta_shf_no = '".$arr['dta_shf_no']."'
                        , dta_mmi_no = '".$arr['dta_mmi_no']."'
                        , dta_group = '".$arr['dta_group']."'
                        , dta_type = '".$arr['dta_type']."'
                        , dta_no = '".$arr['dta_no']."'
                        , dta_date = '".$arr['dta_date1']."'
                        , dta_avg = '".$sum1['dta_avg']."'
                        , dta_sum = '".$sum1['dta_sum']."'
                        , dta_max = '".$sum1['dta_max']."'
                        , dta_min = '".$sum1['dta_min']."'
            ";
            // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_value = 'in: ".addslashes($sql)."' ");
            $result = sql_query($sql);
        }
    
    }
	
}
else {
	$result_arr = array("code"=>599,"message"=>"error");
}

//exit;
//echo json_encode($arr);
echo json_encode( array('meta'=>$result_arr) );
?>