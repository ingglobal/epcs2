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
$objReader = PHPExcel_IOFactory::createReaderForFile($filename);

// 엑셀파일을 읽는다
$objExcel = $objReader->load($filename);
//##################################################################
$g5['title'] = '업체정보등록';
include_once('../_head.php');
?>
<?php
for($i=0;$i<20;$i++){
    echo '안녕하세요'."<br>";
}
?>
<?php
include_once ('../_tail.php');
?>