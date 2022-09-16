<?php
$sub_menu = "940120";
include_once('../../../../common.php');
//##################################################################
$demo = 1;  // 데모모드 = 1

// ref: https://github.com/PHPOffice/PHPExcel
require_once G5_LIB_PATH."/PHPExcel-1.8/Classes/PHPExcel.php"; // PHPExcel.php을 불러옴.
$objPHPExcel = new PHPExcel();
require_once G5_LIB_PATH."/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php"; // IOFactory.php을 불러옴.
$filename = G5_USER_ADMIN_COMDATA_PATH.'/'.$g5['com_setting']['set_com_directory_name'].'/excel/company_code.xls';
PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);

// 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
// $objReader = PHPExcel_IOFactory::createReaderForFile($filename);

// 파일의 저장형식이 utf-8일 경우 한글파일 이름은 깨지므로 euc-kr로 변환해준다.
$filename = iconv("UTF-8", "EUC-KR", $filename);

// 전체 엑셀 데이터를 담을 배을 선언한다.
$allData = array();
$headArr = array();
$contArr = array();

try {
    // 업로드한 PHP 파일을 읽어온다.
	$objPHPExcel = PHPExcel_IOFactory::load($filename);
	$sheetsCount = $objPHPExcel -> getSheetCount();

	// 시트Sheet별로 읽기
	for($i = 0; $i < $sheetsCount; $i++) {       
        $objPHPExcel -> setActiveSheetIndex($i);
        $sheet = $objPHPExcel -> getActiveSheet();
        $highestRow = $sheet -> getHighestRow();   			           // 마지막 행
        $highestColumn = $sheet -> getHighestColumn();	// 마지막 컬럼
        // $arr = array();
        for($row = 1; $row <= $highestRow; $row++) {
            if($row < 5) continue;
            $rowData = $sheet -> rangeToArray("A" . $row . ":" . $highestColumn . $row, NULL, TRUE, FALSE);
            // array_push($arr,$rowData[0]);
            $arr = array(
                'cst_code' => $rowData[0][0]
                ,'com_idx' => 14
                ,'cst_name' => $rowData[0][1]
                ,'cst_addr1' => $rowData[0][2]
                ,'cst_sale_yn' => ($rowData[0][4] == 'Y') ? 1 : 0
                ,'cst_outorder_yn' => ($rowData[0][5] == 'Y') ? 1 : 0
                ,'cst_purchase_yn' => ($rowData[0][6] == 'Y') ? 1 : 0
                ,'cst_tel' => $rowData[0][7]
                ,'cst_fax' => $rowData[0][8]
            );
            array_push($allData,$arr);
        }
	}
} catch(exception $e) {
    echo $e;
    exit;
}


//##################################################################
$g5['title'] = '업체정보등록';
include_once('../_head.php');
// print_r2($allData);
?>
<div class="btn_box">
    <a href="<?=$com_folder_url?>/insert_company.php?start=1" class="btn btn_02 btn_start">[시작]</a>
    <p>
        작업시작~~ <font color="crimson"><b>[끝]</b></font> 이라는 단어가 나오기 전 중간에 중지하지 마세요.
    </p>
</div>
<div id="cont"></div>
<?php
if($start == 1 && count($allData)) { //######################## 작업시작 ################
$countgap = 10; //몇건씩 보낼지 설정
$sleepsec = 10000; //백만분에 몇초간 쉴지 설정(20000/1000000=0.02)(10000/1000000=0.01)(5000/1000000=0.005)
$maxscreen = 30; // 몇건씩 화면에 보여줄건지 설정

//거래처 테이블을 텅비우고 초기화 한다.
$truncate_sql = " TRUNCATE {$g5['customer_table']} ";
sql_query($truncate_sql,1);

flush();
ob_flush();

$tcnt = 0;
for($i=0;$i<count($allData);$i++) {
    
    $sql = " INSERT INTO {$g5['customer_table']} SET
          cst_code = '{$allData[$i]['cst_code']}' 
          , com_idx = 14     
          , cst_name = '{$allData[$i]['cst_name']}'      
          , cst_addr1 = '{$allData[$i]['cst_addr1']}'      
          , cst_sale_yn = '{$allData[$i]['cst_sale_yn']}'      
          , cst_outorder_yn = '{$allData[$i]['cst_outorder_yn']}'      
          , cst_purchase_yn = '{$allData[$i]['cst_purchase_yn']}'      
          , cst_tel = '{$allData[$i]['cst_tel']}'      
          , cst_fax = '{$allData[$i]['cst_fax']}'      
    ";
    sql_query($sql,1);

    $tcnt++;
    echo "<script>document.getElementById('cont').innerHTML += '".$tcnt."개 - ".$allData[$i]['cst_name']." - 처리됨<br>';</script>\n";

    flush();
    ob_flush();
    ob_end_flush();
    usleep($sleepsec);

    //보기 쉽게 묶음 단위로 구분 (단락으로 구분해서 보임)
    if($tcnt % $countgap == 0){
        echo "<script>document.getElementById('cont').innerHTML += '<br>';</script>\n";
    }

    //화면 정리! 부하를 줄임 (화면을 싹 지움)
    if($tcnt % $maxscreen == 0){
        echo "<script>document.getElementById('cont').innerHTML = '';</script>\n";
    }
} //for($i=0;$i<count($allData);$i++)
?>
<script>
document.getElementById('cont').innerHTML += "<br><br>총 <?php echo number_format($tcnt); ?>건 완료<br><br><font color='crimson'><b>[끝]</b></font>";
</script>
<?php
}//if($start == 1 && count($allData)) //############################ 작업종료 ##############
else{
    echo '<div class="display_empty">[시작]버튼을 누르면 작업이 실행됩니다.</div>';
}

include_once ('../_tail.php');