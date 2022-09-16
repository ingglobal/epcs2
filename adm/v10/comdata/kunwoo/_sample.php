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
        $arr = array();
        for($row = 1; $row <= $highestRow; $row++) {
            $rowData = $sheet -> rangeToArray("A" . $row . ":" . $highestColumn . $row, NULL, TRUE, FALSE);
            $arr[$row] = $rowData[0];
        }
        array_push($allData,$arr);
	}
} catch(exception $e) {
    echo $e;
    exit;
}
//##################################################################
$g5['title'] = '업체정보등록';
include_once('../_head.php');
print_r2($allData);
?>

<?php
include_once ('../_tail.php');
?>