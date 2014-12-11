<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'attendanceStudentAbsence'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'attendanceStudentAbsenceEdit', $absenceId), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'attendanceStudentAbsenceDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}

$this->end();

$this->start('contentBody');

$student = $obj['Student'];
$studentIdName = sprintf('%s - %s %s %s %s', $student['identification_no'], $student['first_name'], $student['middle_name'], $student['last_name'], $student['preferred_name']);

$filesList = '';
foreach($attachments AS $objItem){
	$fileName = $objItem['InstitutionSiteStudentAbsenceAttachment']['file_name'];
	$fileId = $objItem['InstitutionSiteStudentAbsenceAttachment']['id'];
	$linkOptions = array('action' => 'attendanceStudentAttachmentsDownload', $fileId);
	$filesList .= '<div>'.$this->Html->link($fileName, $linkOptions).'</div>';
}

$dataModifiedUser = $obj['ModifiedUser'];
$dataCreatedUser = $obj['CreatedUser'];
?>

<div id="studentAbsenceAdd" class="">
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.class'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteClass']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSite.id_name'); ?></div>
		<div class="col-md-6"><?php echo $studentIdName; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.first_date_absent'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStudentAbsence']['first_date_absent']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.full_day_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['full_day_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.last_date_absent'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStudentAbsence']['last_date_absent']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.start_time_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['start_time_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.end_time_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['end_time_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStudentAbsence.reason'); ?></div>
		<div class="col-md-6"><?php echo $obj['StudentAbsenceReason']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.type'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['absence_type']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.comment'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['comment']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.attachments'); ?></div>
		<div class="col-md-6"><?php echo $filesList; ?></div>
	</div>
	<div class="row">
        <div class="col-md-3"><?php echo __('Modified by'); ?></div>
        <div class="col-md-6"><?php echo trim($dataModifiedUser['first_name'] . ' ' . $dataModifiedUser['last_name']); ?></div>
    </div>
    <div class="row">
        <div class="col-md-3"><?php echo __('Modified on'); ?></div>
        <div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['modified']; ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created by'); ?></div>
        <div class="col-md-6"><?php echo trim($dataCreatedUser['first_name'] . ' ' . $dataCreatedUser['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created on'); ?></div>
        <div class="col-md-6"><?php echo $obj['InstitutionSiteStudentAbsence']['created']; ?></div>
    </div>
</div>
<?php
$this->end();
?>