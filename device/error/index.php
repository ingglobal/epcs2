<?php
// 크롬 요소검사 열고 확인하면 되겠습니다. 
// print_r2 안 쓰고 print_r로 확인하는 게 좋습니다.
header('Content-Type: application/json; charset=UTF-8');
include_once('./_common.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');

//print_r2($_REQUEST);exit;
//echo $_REQUEST['shf_type'][0];
$rawBody = file_get_contents("php://input"); // 본문을 불러옴
$getData = array(json_decode($rawBody,true)); // 데이터를 변수에 넣고

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

        // 에러 분류 추출 (data/cache/mms-code.php, 설정은 user.07.default.php)
        $arr['trm_idx_category'] = $g5['code'][$arr['mms_idx']][$arr['dta_code']]['trm_idx_category'];

        // 공통요소
        $sql_common[$i] = " com_idx = '".$arr['com_idx']."'
                        , imp_idx = '".$arr['imp_idx']."'
                        , mms_idx = '".$arr['mms_idx']."'
                        , cod_idx = '".$arr['cod_idx']."'
                        , trm_idx_category = '".$arr['trm_idx_category']."'
                        , dta_shf_no = '".$arr['dta_shf_no']."'
                        , dta_shf_max = '".$arr['dta_shf_max']."'
                        , dta_group = '".$arr['dta_group']."'
                        , dta_code = '".$arr['dta_code']."'
                        , dta_message = '".$arr['dta_message']."'
                        , dta_dt = '".$arr['dta_dt']."'
                        , dta_status = '".$arr['dta_status']."'
        ";
        
        //com_idx, imp_idx, mms_idx, dta_code, dta_group, dta_type, dta_no, dta_date, dta_time
        // 상기 8개 값 체크를 해서 같은 값이 들어오면 중복으로 본다. (업데이트)
        $sql_dta = "   SELECT dta_idx FROM {$g5['data_error_table']} 
                        WHERE com_idx = '".$arr['com_idx']."'
                            AND mms_idx = '".$arr['mms_idx']."'
                            AND trm_idx_category = '".$arr['trm_idx_category']."'
                            AND dta_group = '".$arr['dta_group']."'
                            AND dta_code = '".$arr['dta_code']."'
                            AND dta_dt = '".$arr['dta_dt']."'
        ";
        // echo $sql_dta.PHP_EOL;
		$dta = sql_fetch($sql_dta,1);
		// 정보 업데이트
		if($dta['dta_idx']) {
			
			$sql = "UPDATE {$g5['data_error_table']} SET 
						{$sql_common[$i]}
						, dta_update_dt = '".G5_SERVER_TIME."'
					WHERE dta_idx = '".$dta['dta_idx']."'";
			sql_query($sql,1);
            $result_arr[$i]['code'] = 200;
            $result_arr[$i]['message'] = "Updated OK!";

		}
        // 정보 입력
        else{

			// 일단 에러 정보 입력 후
            $sql = "INSERT INTO {$g5['data_error_table']} SET 
						{$sql_common[$i]}
						, dta_reg_dt = '".G5_SERVER_TIME."'
						, dta_update_dt = '".G5_SERVER_TIME."'
            ";
            // echo $sql.'<br>';
            $result = sql_query($sql, 1);
            $dta['dta_idx'] = sql_insert_id();
            $result_arr[$i]['code'] = 200;
            $result_arr[$i]['message'] = "Insert OK!";


            // 코드 정보 업데이트 (새로운 정보가 들어오면 코드에 입력 or 코드쪽 누적 카운터 증가)
            $sql = "SELECT *
                    FROM {$g5['code_table']}
                    WHERE mms_idx = '".$arr['mms_idx']."'
                        AND cod_code = '".$arr['dta_code']."'
                        AND cod_status = 'ok'
            ";
            // echo $sql.'---'.PHP_EOL;
            $cod = sql_fetch($sql,1);
            // 코드 정보가 이미 존재하면 업데이트
            if($cod['cod_idx']) {

                // 메시지 값이 있을 때만 입력
                $sql_cod_name = ($arr['dta_message']) ? ", cod_name = '".$arr['dta_message']."'" : "";

                $sql = "UPDATE {$g5['code_table']} SET
                            cod_group = '".$arr['dta_group']."'
                            {$sql_cod_name}
                            , cod_code_count = cod_code_count + 1
                        WHERE cod_idx = '".$cod['cod_idx']."'
                ";
                $result = sql_query($sql,1);

            }
            // 코드 정보가 없으면 생성
            // You have to get the nessesary fields (cod_type, cod_send_type(plular), cod_idx..)
            else {

                // Set cod_type variable. (if dta_group = pre, cod_type=p2(plc predict))
                $cod['cod_type'] = ($arr['dta_group']=='pre') ? 'p2':'a';
                // 맨 처음 PLC예지 들어오면 발송대상자가 불분명하므로 맨 처음 등록자 한사람을 자동 할당함
                if($cod['cod_type']=='p2') {
                    $sql = "SELECT cmm.mb_id, mb_name, mb_hp, mb_email
                            FROM {$g5['company_member_table']} AS cmm
                                LEFT JOIN {$g5['member_table']} AS mb ON mb.mb_id = cmm.mb_id
                            WHERE com_idx = '".$arr['com_idx']."'
                                AND cmm_status = 'ok'
                            ORDER BY cmm_reg_dt ASC
                            LIMIT 1
                    ";
                    $cmm = sql_fetch($sql);
                    if($cmm['mb_id']) {
                        $reports['r_name'][] = $cmm['mb_name'];
                        $reports['r_hp'][] = $cmm['mb_hp'];
                        $reports['r_email'][] = $cmm['mb_email'];
                        $cod['cod_reports'] = json_encode( $reports, JSON_UNESCAPED_UNICODE );
                    }
                }
 
                // 발송타입 설정: email,sms,push... from company info.
                $cod['cod_send_type'] = $com['com_send_type'];

                $sql = " INSERT INTO {$g5['code_table']} SET
                            com_idx = '".$arr['com_idx']."'
                            , imp_idx = '".$arr['imp_idx']."'
                            , mms_idx = '".$arr['mms_idx']."'
                            , trm_idx_category = '".$arr['trm_idx_category']."'
                            , cod_code = '".$arr['dta_code']."'
                            , cod_group = '".$arr['dta_group']."'
                            , cod_type = '".$cod['cod_type']."'
                            , cod_count_limit = 5
                            , cod_name = '".$arr['dta_message']."'
                            , cod_send_type = '".$cod['cod_send_type']."'
                            , cod_code_count = 1
                            , cod_status = 'ok'
                            , cod_reg_dt = '".G5_TIME_YMDHIS."'
                            , cod_update_dt = '".G5_TIME_YMDHIS."'
                ";
                $result = sql_query($sql);
                $cod['cod_idx'] = sql_insert_id();
            }



            // 기록해야 하는 경우만 저장: r=저장 (기록만 수행), a=알람 (발생 시 알림), p=주기설정(조건 만족 시 알림), p2=PLC예지 (발생 시 즉시 알림)
            // Alarm/Predit register (if conditions is right.)
            $send_flag = 0;             // initialize
            $towhom_hp = array();       // initialize
            $towhom_email = array();    // initialize
            if($cod['cod_type']) {

                // 예외 조건에 따라 빈도수가 너무 많을 때 무시
                $unsual_flag = 0;   // 조건에 해당되면 1로 변경
                if($cod['cod_min_sec']) {

                    $sql = "SELECT arm_idx, arm_reg_dt FROM {$g5['alarm_table']}
                            WHERE com_idx = '".$arr['com_idx']."'
                                AND mms_idx = '".$arr['mms_idx']."'
                                AND arm_cod_code = '".$arr['dta_code']."'
                                AND arm_reg_dt > DATE_ADD(now(), INTERVAL -".$cod['cod_min_sec']." SECOND)
                            LIMIT 1
                    ";
                    $one = sql_fetch($sql,1);
                    // print_r2($one).PHP_EOL;
                    // 최소주기 안에 레코드가 있으면 기록 안 함 ---------
                    if($one['arm_idx']) {
                        $unsual_flag = 1;
                    }
                }
                // echo $unsual_flag.PHP_EOL;

                // 예외조건이 아닌 정상 조건일 때만 알람 입력
                if(!$unsual_flag) {

                    // 예지 타이밍인지 체크 (해당 휫수에 해당하는 지 체크), 지난 예지 이후 발생한 횟수를 카운터해야 하므로 sub query를 사용함
                    $pre_yn = 0;   // 조건에 해당되면 1로 변경
                    // 리스트조회시: SELECT arm_idx, arm_pre_yn, arm_reg_dt
                    $sql = "SELECT COUNT(arm_idx) AS cnt
                            FROM {$g5['alarm_table']}
                            WHERE mms_idx = '".$arr['mms_idx']."'
                                AND cod_idx = '".$cod['cod_idx']."'
                                AND arm_cod_type IN ('a','p')   /* p2=PLC예지이므로 제외 */
                                AND arm_idx > IFNULL((
                                    SELECT arm_idx FROM {$g5['alarm_table']}
                                    WHERE mms_idx = '".$arr['mms_idx']."'
                                        AND cod_idx = '".$cod['cod_idx']."'
                                        AND arm_cod_type IN ('a','p')
                                        AND arm_reg_dt > DATE_ADD(now(), INTERVAL -".$cod['cod_interval']." SECOND)
                                        AND arm_pre_yn = 1 /* 이전 예지 발생 레코드 추출 */
                                    ORDER BY arm_idx DESC
                                    LIMIT 1
                                ),0)
                                AND arm_reg_dt > DATE_ADD(now(), INTERVAL -".$cod['cod_interval']." SECOND)
                    ";
                    // echo $sql.PHP_EOL;
                    // exit;
                    $one = sql_fetch($sql,1);
                    // print_r2($one);
                    // echo $one['cnt'].PHP_EOL;
                    // echo $cod['cod_count'].PHP_EOL;
                    if($one['cnt'] >= ($cod['cod_count']-1)) {
                        $pre_yn = 1;
                    }
                    // echo $pre_yn.PHP_EOL;
                    // exit;
                    // PLC 예지인 경우는 무조건 예지
                    if($cod['cod_type']=='p2') {
                        $pre_yn = 1;
                    }


                    // alarm table insert, update later for arm_no
                    $arm_keys = keys_update('mms_idx',$arr['mms_idx'],'','~');  // 최초값
                    $arm_keys = keys_update('cod_code',$arr['dta_code'],$arm_keys,'~'); // 업데이트
                    $arm_keys = keys_update('cod_interval',$cod['cod_interval'],$arm_keys,'~');
                    $arm_keys = keys_update('cod_count',$cod['cod_count'],$arm_keys,'~');
                    $sql = " INSERT INTO {$g5['alarm_table']} SET
                                com_idx = '".$arr['com_idx']."'
                                , mms_idx = '".$arr['mms_idx']."'
                                , cod_idx = '".$cod['cod_idx']."'
                                , dta_idx = '".$dta['dta_idx']."'
                                , arm_shf_no = '".$arr['dta_shf_no']."'
                                , arm_cod_code = '".$arr['dta_code']."'
                                , arm_cod_category = '".$cod['trm_idx_category']."'
                                , arm_cod_type = '".$cod['cod_type']."'
                                , arm_pre_yn = '".$pre_yn."'
                                , arm_send_type = '".$cod['cod_send_type']."'
                                , arm_keys = '".$arm_keys."'
                                , arm_reg_dt = '".G5_TIME_YMDHIS."'
                    ";
                    sql_query($sql,1);
                    $arm_idx = sql_insert_id();


                    // Send message(email. sms, push) under the condition of max time daily.
                    if($pre_yn) {
                        // company info
                        $com = get_table_meta('company','com_idx',$arr['com_idx']);

                        // mms info
                        $mms = get_table_meta('mms','mms_idx',$arr['mms_idx']);

                        // sms send, only if company sms setting is possible.
                        if( preg_match("/sms/",$cod['cod_send_type']) && preg_match("/sms/",$com['com_send_type']) ) {
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
                        if( preg_match("/sms/",$cod['cod_send_type']) || preg_match("/push/",$cod['cod_send_type']) ) {
                            // 내용
                            $msg_body = '설비명:'.$mms['mms_name'].PHP_EOL;
                            $msg_body .= '['.$cod['cod_code'].']'.PHP_EOL;
                            $msg_body .= ($cod['cod_name']) ? '알람:'.$cod['cod_name'].PHP_EOL : '';
                            $msg_body .= $cod['cod_memo'];
                        }
                        else {
                            $msg_body = $cod['cod_memo'];
                        }

                        // 메시지 발송 함수
                        $ar['arm_table'] = 'alarm';
                        $ar['arm_idx'] = $arm_idx;  // 알람용
                        $ar['amt_idx'] = $amt_idx;  // 알람태그용
                        $ar['msg_type'] = $cod['cod_send_type'];
                        $ar['com_msg_type'] = $com['com_send_type'];
                        $ar['mms_idx'] = $mms['mms_idx'];
                        $ar['mms_name'] = $mms['mms_name'];
                        $ar['arm_code'] = $cod['cod_code'];
                        $ar['arm_name'] = $cod['cod_name'];
                        $ar['reports'] = $cod['cod_reports'];
                        $ar['msg_limit'] = $cod['cod_count_limit'];
                        $ar['msg_body'] = $msg_body;
                        send_message($ar);
                        // print_r2($ar);
                        unset($ar);
                    }
                    // end of sending email, sms, push..

                }
                //// 예외조건이 아닌 정상 조건일 때만 알람 입력
            }
            //// 기록해야 하는 경우만 저장: r=저장 (기록만 수행), a=알람 (발생 시 알림), p=주기설정(조건 만족 시 알림), p2=PLC예지 (발생 시 즉시 알림)
        }
        $result_arr[$i]['dta_idx'] = $dta['dta_idx'];   // 고유번호 (최종 json 표현하는 값)





        // 일간 sum 합계 입력
        $sum_common = " mms_idx = '".$arr['mms_idx']."'
                        AND trm_idx_category = '".$arr['trm_idx_category']."'
                        AND dta_shf_no = '".$arr['dta_shf_no']."'
                        AND dta_group = '".$arr['dta_group']."'
                        AND dta_code = '".$arr['dta_code']."'
        ";
        $sql = "SELECT COUNT(dta_idx) AS dta_count_sum
                FROM {$g5['data_error_table']} 
                WHERE dta_status = 0
                    AND {$sum_common}
                    AND FROM_UNIXTIME(dta_dt,'%Y-%m-%d') = '".$arr['dta_date1']."'
        ";
        // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_key ='sum_calculate',  mta_value = '".addslashes($sql)."' ");
        $sum1 = sql_fetch($sql,1); // 일 합계 데이터값 추출 

        // 있으면 업데이트, 없으면 생성
        $sql_sum = "   SELECT dta_idx FROM {$g5['data_error_sum_table']} 
                        WHERE {$sum_common}
                            AND dta_date = '".$arr['dta_date1']."'
        ";
        // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_key ='sum_check', mta_value = '".addslashes($sql_sum)."' ");
		$sum = sql_fetch($sql_sum,1);
		// 정보 업데이트
		if($sum['dta_idx']) {
            $sql = "UPDATE {$g5['data_error_sum_table']} SET
                        dta_value = '".$sum1['dta_count_sum']."'
                    WHERE {$sum_common}
                        AND dta_date = '".$arr['dta_date1']."'
            ";
            // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_key ='update', mta_value = '".addslashes($sql)."' ");
            $result = sql_query($sql,1);
        }
        else {
            $sql = " INSERT INTO {$g5['data_error_sum_table']} SET
                        com_idx = '".$arr['com_idx']."'
                        , imp_idx = '".$arr['imp_idx']."'
                        , mms_idx = '".$arr['mms_idx']."'
                        , mmg_idx = '".$g5['mms'][$arr['mms_idx']]['mmg_idx']."'
                        , trm_idx_category = '".$arr['trm_idx_category']."'
                        , dta_shf_no = '".$arr['dta_shf_no']."'
                        , dta_group = '".$arr['dta_group']."'
                        , dta_code = '".$arr['dta_code']."'
                        , dta_date = '".$arr['dta_date1']."'
                        , dta_value = '".$sum1['dta_count_sum']."'
            ";
            // sql_query(" INSERT INTO {$g5['meta_table']} SET mta_key ='insert', mta_value = '".addslashes($sql)."' ");
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