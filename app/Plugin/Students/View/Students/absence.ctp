<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
echo $this->Form->create('InstitutionSiteStudentAbsence', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStudentAbsence')
));
?>
<div class="topDropDownWrapper page-controls" url="Students/absence">
	<?php 
	echo $this->Form->input('school_year_id', array('options' => $yearList, 'value' => $yearId, 'id' => 'schoolYearId', 'class' => 'form-control', 'onchange' => 'jsForm.filterAbsenceByMonth(this)'));
	echo $this->Form->input('month_id', array('options' => $monthOptions, 'value' => $monthId, 'id' => 'monthId', 'class' => 'form-control', 'onchange' => 'jsForm.filterAbsenceByMonth(this)'));
	?>
</div>
<?php
if(isset($data)) { 

$tableHeaders = array(__('First Day'), __('Days'), __('Time'), __('Reason'), __('Type'));

$tableData = array();
foreach($data as $val) {
	$tempRow = array();
	$absenceObj = $val['InstitutionSiteStudentAbsence'];
	$firstDateFormatted = $this->Utility->formatDate($absenceObj['first_date_absent'], null, false);
	
	$stampFirstDateAbsent = strtotime($absenceObj['first_date_absent']);
	$stampLastDateAbsent = strtotime($absenceObj['last_date_absent']);
	
	if($absenceObj['full_day_absent'] == 'Yes'){
		if(!empty($absenceObj['last_date_absent']) && $stampLastDateAbsent > $stampFirstDateAbsent){
			$lastDateFormatted = $this->Utility->formatDate($absenceObj['last_date_absent'], null, false);
			$totalWeekdays = $this->Utility->getAbsenceDaysBySettings($absenceObj['first_date_absent'], $absenceObj['last_date_absent'], $settingWeekdays);
			$noOfDays = sprintf('%s (to %s)', $totalWeekdays, $lastDateFormatted);
		}else{
			$noOfDays = 1;
		}
		$timeStr = __('full day');
	}else{
		$noOfDays = 1;
		$timeStr = sprintf('%s - %s', $absenceObj['start_time_absent'], $absenceObj['end_time_absent']);
	}
	$reason = $val['StudentAbsenceReason']['name'];
	$type = $absenceObj['absence_type'];
	
	$tempRow[] = $firstDateFormatted;
	$tempRow[] = $noOfDays;
	$tempRow[] = $timeStr;
	$tempRow[] = $reason;
	$tempRow[] = $type;
	
	$tableData[] = $tempRow;
}

echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

}
$this->end();
?>