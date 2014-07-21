<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->script('Visualizer.visualizer', false);

$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.reset'), array('action' => 'reset'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Visualizer'));
$formOptions['inputDefaults']['label']['class'] = 'col-md-1 control-label';

$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($this->action, $formOptions);
echo $this->Form->input('search', array('id' => 'search'));
?>
<div class='visualizer-list-table'>
	<?php
	$tableClass = 'table-checkable table-input';

	$headerfirstCol = array($this->Form->input(null, array('class' => 'icheck-input', 'label' => false, 'div' => false, 'type' => 'checkbox', 'checked' => false)) => array(
		'class' => 'checkbox-column',
		'onchange' => 'Visualizer.checkboxChangeAll(this)',
		'url' => 'Visualizer/ajaxUpdateUserCBSelection'
		));
	
	$tableHeaders = array($headerfirstCol,__('Time Period'));

	$tableData = array();
	if (!empty($tableRowData)) {
		$i = 0;
		foreach ($tableRowData as $obj) {
			if (empty($selectedTimeperiodIds)) {
				$checked = false;
			} else {
				$checked = (in_array($obj['TimePeriod_NId'], $selectedTimeperiodIds)) ? 'checked' : false;
			}
			
			$bodyFirstColOptions = array(
				'type' => 'checkbox', 
				'class' => 'icheck-input', 
				'label' => false, 
				'div' => false, 
				'checked' => $checked, 
				'value' => $obj['TimePeriod_NId'],
				'sectionType' => 'timeperiod', 
				'onchange' => 'Visualizer.checkboxChange(this)',
				'url' => 'Visualizer/ajaxUpdateUserCBSelection');
			$additionalClass = 'checkbox-column center';
			$input = $this->Form->input('TimePeriod.TimePeriod_NId.' . $i, $bodyFirstColOptions);

			$row = array();
			$row[] = array($input, array('class' => $additionalClass));
			$row[] = array($obj['TimePeriod'], array('class' => 'data-list'));
			$tableData[] = $row;
			$i++;
		}
	}
	echo $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</div>

<?php
echo $this->Form->end();
$this->end();
?>