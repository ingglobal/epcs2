<?php
$sub_menu = "910130";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], 'w');

if(!$config['cf_faq_skin']) $config['cf_faq_skin'] = "basic";
if(!$config['cf_mobile_faq_skin']) $config['cf_mobile_faq_skin'] = "basic";

$g5['title'] = '업체환경설정';
// include_once('./_top_menu_setting.php');
include_once('./_head.php');
echo $g5['container_sub_title'];

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_cf_default">업체기본설정</a></li>
    <li><a href="#anc_cf_message">메시지설정</a></li>
    <li><a href="#anc_cf_secure">관리설정</a></li>
</ul>';

if (!$config['cf_icode_server_ip'])   $config['cf_icode_server_ip'] = '211.172.232.124';
if (!$config['cf_icode_server_port']) $config['cf_icode_server_port'] = '7295';

if ($config['cf_sms_use'] && $config['cf_icode_id'] && $config['cf_icode_pw']) {
    $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
}
?>

<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);">
<input type="hidden" name="token" value="" id="token">
<input type="hidden" name="com_idx" value="<?=$_SESSION['ss_com_idx']?>" id="com_idx">

<section id="anc_cf_default">
	<h2 class="h2_frm">업체기본설정</h2>
	<?php echo $pg_anchor ?>
	
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<caption>업체기본설정</caption>
		<colgroup>
			<col class="grid_4">
			<col>
			<col class="grid_4">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">기본폴더명</th>
			<td colspan="3">
				<?php echo help('업체관련 디렉토리는 업체영문명으로 생성합시다.') ?>
				<input type="text" name="set_com_directory_name" value="<?php echo $g5['com_setting']['set_com_directory_name'] ?>" id="set_status" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
        <tr>
			<th scope="row">생산통계기준</th>
			<td>
                <select name="set_prod_stats_standard" id="set_prod_stats_standard">
                    <option value="">생산통계기준을 선택하세요.</option>
                    <?=$g5['set_mms_set_data_options']?>
                </select>
                <script>$('select[name="set_prod_stats_standard"]').val('<?=$g5['com_setting']['set_prod_stats_standard']?>');</script>
            </td>
		</tr>
		<tr>
			<th scope="row">주조기설정</th>
			<td colspan="3">
				<?php echo help('주조기 번호와 설비DB고유번호(mms고유번호)를 매칭합니다. LPM05=60(17호기), LPM04=61(18호기), LPM03=62(19호기), LPM02=63(20호기)') ?>
				<input type="text" name="set_cast_no" value="<?php echo $g5['com_setting']['set_cast_no'] ?>" id="set_status" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
        <tr>
            <th scope="row">IMP 묶음단위</th>
            <td>
                <input type="text" name="set_imp_count" value="<?php echo $g5['com_setting']['set_imp_count']; ?>" class="frm_input" style="width:30px;"> 개
            </td>
        </tr>
		<tr>
			<th scope="row">모니터별업로드이미지개수</th>
			<td colspan="3">
				<?php echo help('예) 3 : 최대 3장 업로드 가능') ?>
				<input type="text" name="set_monitor_cnt" value="<?php echo $g5['com_setting']['set_monitor_cnt'] ?>" id="set_monitor_cnt" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
		<tr>
			<th scope="row">모니터이미지로테이션시간</th>
			<td colspan="3">
				<?php echo help('예) 3000 : 3초') ?>
				<input type="text" name="set_monitor_time" value="<?php echo $g5['com_setting']['set_monitor_time'] ?>" id="set_monitor_time" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
		<tr>
			<th scope="row">모니터페이지리로딩간격시간</th>
			<td colspan="3">
				<?php echo help('예) 10000 : 10초') ?>
				<input type="text" name="set_monitor_reload" value="<?php echo $g5['com_setting']['set_monitor_reload'] ?>" id="set_monitor_reload" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
		<tr>
            <th scope="row">ONESIGNAL APP ID</th>
            <td colspan="3">
                <input type="text" name="set_onesignal_id" value="<?php echo $g5['com_setting']['set_onesignal_id']; ?>" class="frm_input" style="width:60%;">
            </td>
        </tr>
		<tr>
            <th scope="row">ONESIGNAL REST API KEY</th>
            <td colspan="3">
                <?php echo help('OneSignal > Settings > Keys & IDs : REST API KEY'); ?>
                <input type="text" name="set_onesignal_key" value="<?php echo $g5['com_setting']['set_onesignal_key']; ?>" class="frm_input" style="width:60%;">
            </td>
        </tr>
		<tr>
            <th scope="row">1차분류(카테고리1)</th>
            <td>
                <?php echo help('1차분류를 ex)cat1_name=카테고리1설명 이와같이 작성하고 구분은 줄바꿈으로 세로로 나열하세요.<br>(가능한 기존의 작성 순서를 지켜주시고 만약 순서를 변경 하였을시 각 제품(BOM)의 설정된 카테고리도 변경해야 할 수 있습니다.)') ?>
                <textarea name="set_cat_1" id="set_cat_1" style="width:50%;height:200px;"><?php echo get_text($g5['com_setting']['set_cat_1']); ?></textarea>
            </td>
        </tr>
		<tr>
            <th scope="row">2차분류(카테고리2)</th>
            <td>
                <?php echo help('2차분류를 ex)cat2_name=카테고리2설명 이와같이 작성하고 구분은 줄바꿈으로 세로로 나열하세요.<br>(가능한 기존의 작성 순서를 지켜주시고 만약 순서를 변경 하였을시 각 제품(BOM)의 설정된 카테고리도 변경해야 할 수 있습니다.)') ?>
                <textarea name="set_cat_2" id="set_cat_2" style="width:50%;height:200px;"><?php echo get_text($g5['com_setting']['set_cat_2']); ?></textarea>
            </td>
        </tr>
		<tr>
            <th scope="row">3차분류(카테고리3)</th>
            <td>
                <?php echo help('3차분류를 ex)cat3_name=카테고리3설명 이와같이 작성하고 구분은 줄바꿈으로 세로로 나열하세요.<br>(가능한 기존의 작성 순서를 지켜주시고 만약 순서를 변경 하였을시 각 제품(BOM)의 설정된 카테고리도 변경해야 할 수 있습니다.)') ?>
                <textarea name="set_cat_3" id="set_cat_3" style="width:50%;height:200px;"><?php echo get_text($g5['com_setting']['set_cat_3']); ?></textarea>
            </td>
        </tr>
		<tr>
            <th scope="row">4차분류(카테고리4)</th>
            <td>
                <?php echo help('4차분류를 ex)cat4_name=카테고리4설명 이와같이 작성하고 구분은 줄바꿈으로 세로로 나열하세요.<br>(가능한 기존의 작성 순서를 지켜주시고 만약 순서를 변경 하였을시 각 제품(BOM)의 설정된 카테고리도 변경해야 할 수 있습니다.)') ?>
                <textarea name="set_cat_4" id="set_cat_4" style="width:50%;height:200px;"><?php echo get_text($g5['com_setting']['set_cat_4']); ?></textarea>
            </td>
        </tr>
		<tr>
			<th scope="row">메뉴권한종류 mb_8</th>
			<td colspan="3">
				<?php echo help('adm=총괄관리권한,adm_production=생산관리권한,adm_quality=품질관리권한,normal=일반사원권한') ?>
				<input type="text" name="set_mb_auth" value="<?php echo $g5['com_setting']['set_mb_auth'] ?>" id="set_mb_auth" required class="required frm_input" style="width:60%;">
			</td>
		</tr>
		<tr>
            <th scope="row">총괄관리메뉴권한</th>
            <td>
                <?php echo help('총괄관리자의 메뉴 접근권한입니다.') ?>
                <textarea name="set_admin_auth" id="set_admin_auth" style="width:50%;"><?php echo get_text($g5['com_setting']['set_admin_auth']); ?></textarea>
            </td>
        </tr>
		<tr>
            <th scope="row">생산관리메뉴권한</th>
            <td>
                <?php echo help('생산관리자의 메뉴 접근권한입니다.') ?>
                <textarea name="set_admin_production_auth" id="set_admin_production_auth" style="width:50%;"><?php echo get_text($g5['com_setting']['set_admin_production_auth']); ?></textarea>
            </td>
        </tr>
		<tr>
            <th scope="row">품질관리메뉴권한</th>
            <td>
                <?php echo help('품질관리자의 메뉴 접근권한입니다.') ?>
                <textarea name="set_admin_quality_auth" id="set_admin_quality_auth" style="width:50%;"><?php echo get_text($g5['com_setting']['set_admin_quality_auth']); ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">사원 메뉴권한</th>
			<td colspan="3">
                <?php echo help('사원이 등록될 때 디폴트 메뉴 접근권한입니다.') ?>
                <textarea name="set_employee_auth" id="set_employee_auth" style="width:50%;"><?php echo get_text($g5['com_setting']['set_employee_auth']); ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">모바일 메뉴권한</th>
			<td colspan="3">
                <?php echo help('모바일 회원등록될 때 디폴트 메뉴 접근권한입니다.') ?>
                <textarea name="set_mobile_auth" id="set_mobile_auth" style="width:50%;"><?php echo get_text($g5['com_setting']['set_mobile_auth']); ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">품질정보입력시차</th>
            <td>
				<?php echo help('교대 시간이 바뀌어도 시차 간격을 두고 품질 정보를 입력합니다.'); ?>
                <input type="text" name="set_quality_input_time" value="<?php echo $g5['com_setting']['set_quality_input_time']; ?>" class="frm_input" style="width:40px;"> 시간
            </td>
        </tr>
        <tr>
            <th scope="row">비가동정보입력시차</th>
            <td>
				<?php echo help('설정 시간 이전의 비가동 정보는 입력할 수 없습니다.'); ?>
                <input type="text" name="set_downtime_input_time" value="<?php echo $g5['com_setting']['set_downtime_input_time']; ?>" class="frm_input" style="width:40px;"> 시간
            </td>
        </tr>
		<tr>
			<th scope="row">원가설정타입</th>
			<td colspan="3">
				<?php echo help('electricity=전기, consumable=소모품, oil=장비유류대, worker=현장작업자, engineer=장비기사'); ?>
				<input type="text" name="set_csc_type" value="<?php echo $g5['com_setting']['set_csc_type']; ?>" class="frm_input" style="width:70%;">
			</td>
		</tr>
		<tr>
			<th scope="row">BOM타입</th>
			<td colspan="3">
				<?php echo help('product=완성품,half=반제품,material=자재'); ?>
				<input type="text" name="set_bom_type" value="<?php echo $g5['com_setting']['set_bom_type']; ?>" class="frm_input" style="width:70%;">
			</td>
		</tr>
		<tr>
			<th scope="row">BOM구성 표시</th>
			<td colspan="3">
				<?php echo help('제품사양 정보 목록에서 BOM 구조를 표시할 BOM타입을 입력하세요. 쉼표로 구분하고 영문만 입력하세요. ex)product,half '); ?>
				<input type="text" name="set_bom_type_display" value="<?php echo $g5['com_setting']['set_bom_type_display']; ?>" class="frm_input" style="width:70%;">
			</td>
		</tr>
		<tr>
			<th scope="row">BOM상태</th>
			<td colspan="3">
				<input type="text" name="set_bom_status" value="<?php echo $g5['com_setting']['set_bom_status']; ?>" class="frm_input" style="width:70%;">
			</td>
		</tr>
        <tr>
            <th scope="row">업체만의 KPI 서브메뉴</th>
			<td colspan="3">
                <?php echo help('해당업체만의 KPI 통계 페이지 서브메뉴 설정입니다.') ?>
                <textarea name="set_kpi_com_menu" id="set_kpi_com_menu" style="width:70%;"><?php echo get_text($g5['com_setting']['set_kpi_com_menu']); ?></textarea>
            </td>
        </tr>
		<tr>
			<th scope="row">문자발송 번호</th>
			<td colspan="3">
				<?php echo help('아이코드에 등록된 발신자 번호') ?>
				<input type="text" name="set_from_number" value="<?php echo $g5['com_setting']['set_from_number']; ?>" class="frm_input" style="width:60%;">
			</td>
		</tr>
		<tr>
			<th scope="row">X-ray 판정정보 입력</th>
			<td colspan="3">
                <input type="hidden" name="set_xray_test_yn" value="<?=($g5['com_setting']['set_xray_test_yn'])?'1':''?>">
                <label><input type="checkbox" <?=($g5['com_setting']['set_xray_test_yn'])?'checked':''?> id="set_xray_test_yn"> 테스트 입력</label>
                <script>
                $(document).on('click','#set_xray_test_yn',function(e){
                    if($(this).is(':checked')) {$('input[name=set_xray_test_yn]').val(1);}
                    else {$('input[name=set_xray_test_yn]').val(0);}
                });
                </script>
			</td>
		</tr>
		<tr>
			<th scope="row">주조코드 테스트 입력</th>
			<td colspan="3">
                <input type="hidden" name="set_dicast_test_yn" value="<?=($g5['com_setting']['set_dicast_test_yn'])?'1':''?>">
                <label><input type="checkbox" <?=($g5['com_setting']['set_dicast_test_yn'])?'checked':''?> id="set_dicast_test_yn"> 테스트 입력</label>
                <script>
                $(document).on('click','#set_dicast_test_yn',function(e){
                    if($(this).is(':checked')) {$('input[name=set_dicast_test_yn]').val(1);}
                    else {$('input[name=set_dicast_test_yn]').val(0);}
                });
                </script>
			</td>
		</tr>
		<tr>
			<th scope="row">데이타 테스트 입력</th>
			<td colspan="3">
				<?php echo help('데이터를 테스트로 생성할 때 체크하세요. 정상 데이터가 입력되면 체크를 제거하세요.') ?>
                <input type="hidden" name="set_data_test_yn" value="<?=($g5['com_setting']['set_data_test_yn'])?'1':''?>">
                <label><input type="checkbox" <?=($g5['com_setting']['set_data_test_yn'])?'checked':''?> id="set_data_test_yn"> 테스트 입력</label>
                <script>
                $(document).on('click','#set_data_test_yn',function(e){
                    if($(this).is(':checked')) {$('input[name=set_data_test_yn]').val(1);}
                    else {$('input[name=set_data_test_yn]').val(0);}
                });
                </script>
			</td>
		</tr>
        </tbody>
		</table>
	</div>
</section>


    
<section id="anc_cf_message">
    <h2 class="h2_frm">메시지설정</h2>
    <?php echo $pg_anchor; ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>메시지설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">코드별 전송 메일</th>
            <td colspan="3">
                <?php echo help('치환 변수: {제목} {업체명} {이름} {설비명} {코드} {만료일} {년월일} {남은기간} {HOME_URL}'); ?>
                <input type="text" name="set_error_subject" value="<?php echo $g5['setting']['set_error_subject']; ?>" class="frm_input" style="width:80%;" placeholder="메일제목">
                <?php echo editor_html("set_error_content", get_text($g5['setting']['set_error_content'], 0)); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">태그별 전송 메일</th>
            <td colspan="3">
                <?php echo help('치환 변수: {제목} {업체명} {이름} {설비명} {코드} {만료일} {년월일} {남은기간} {HOME_URL}'); ?>
                <input type="text" name="set_tag_subject" value="<?php echo $g5['setting']['set_tag_subject']; ?>" class="frm_input" style="width:80%;" placeholder="메일제목">
                <?php echo editor_html("set_tag_content", get_text($g5['setting']['set_tag_content'], 0)); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">계획정비 메일</th>
            <td colspan="3">
                <?php echo help('치환 변수: {제목} {업체명} {이름} {설비명} {만료일} {년월일} {남은기간} {HOME_URL}'); ?>
                <input type="text" name="set_maintain_plan_subject" value="<?php echo $g5['setting']['set_maintain_plan_subject']; ?>" class="frm_input" style="width:80%;" placeholder="메일제목">
                <?php echo editor_html("set_maintain_plan_content", get_text($g5['setting']['set_maintain_plan_content'], 0)); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">게시판 new 아이콘</th>
            <td>
                <input type="text" name="set_new_icon_hour" value="<?php echo $g5['setting']['set_new_icon_hour']; ?>" class="frm_input" style="width:20px;"> 시간동안 new 아이콘 표시
            </td>
            <th scope="row">new 아이콘 주말포함</th>
            <td>
                <div style="visibility:hidden;">
                <label for="set_new_icon_holiday_yn_1">
                    <input type="radio" name="set_new_icon_holiday_yn" value="1" id="set_new_icon_holiday_yn_1" <?php echo ($g5['setting']['set_new_icon_holiday_yn']) ? 'checked':'' ?>> 영업일만 포함
                </label> &nbsp;&nbsp;
                <label for="set_new_icon_holiday_yn_0">
                    <input type="radio" name="set_new_icon_holiday_yn" value="0" id="set_new_icon_holiday_yn_0" <?php echo ($g5['setting']['set_new_icon_holiday_yn']) ? '':'checked' ?>> 주말까지 포함
                </label>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">만료공지 메일</th>
            <td colspan="3">
                <?php echo help('치환 변수: {법인명} {업체명} {담당자} {년월일} {승인명} {남은기간} {HOME_URL} {연락처} {이메일}'); ?>
                <input type="text" name="set_expire_email_subject" value="<?php echo $g5['setting']['set_expire_email_subject']; ?>" class="frm_input" style="width:80%;" placeholder="메일제목">
                <?php echo editor_html("set_expire_email_content", get_text($g5['setting']['set_expire_email_content'], 0)); ?>
            </td>
        </tr>
		</tbody>
		</table>
	</div>
</section>

<section id="anc_cf_secure">
    <h2 class="h2_frm">관리설정</h2>
    <?php echo $pg_anchor; ?>
    <div class="local_desc02 local_desc">
        <p>관리자 설정입니다.</p>
    </div>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>관리설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">관리자메모</th>
            <td>
                <?php echo help('관리자 메모입니다.') ?>
                <textarea name="set_memo_super" id="set_memo_super"><?php echo get_text($g5['setting']['set_memo_super']); ?></textarea>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top btn_confirm">
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
$(function(){

});

function fconfigform_submit(f) {

    <?php echo get_editor_js("set_expire_email_content"); ?>
    <?php echo chk_editor_js("set_expire_email_content"); ?>
    <?php echo get_editor_js("set_maintain_plan_content"); ?>
    <?php echo chk_editor_js("set_maintain_plan_content"); ?>
    <?php echo get_editor_js("set_error_content"); ?>
    <?php echo chk_editor_js("set_error_content"); ?>
    <?php echo get_editor_js("set_tag_content"); ?>
    <?php echo chk_editor_js("set_tag_content"); ?>

    f.action = "./config_com_form_update.php";
    return true;
}
</script>
<?php
include_once ('./_tail.php');
?>