<?php
$comdata_path = G5_USER_ADMIN_COMDATA_PATH.'/'.$g5['com_setting']['set_com_directory_name'];
$comdata_url = G5_USER_ADMIN_COMDATA_URL.'/'.$g5['com_setting']['set_com_directory_name'];
$comdata_arr = array(
    '990100' => array('업체등록','insert_company')
    ,'990110' => array('제품등록','insert_bom')
);
$menu["menu990"] = array (
    array('990000', '업체관련데이터등록', $comdata_url.'/insert_company.php', 'insert_company')
);

foreach($comdata_arr as $cd_key => $cd_val){
    if(file_exists($comdata_path.'/'.$cd_val[1].'.php')){
        $arr = array($cd_key,$cd_val[0],$comdata_url.'/'.$cd_val[1].'.php',$cd_val[1]);
        array_push($menu["menu990"],$arr);
    }
}
?>