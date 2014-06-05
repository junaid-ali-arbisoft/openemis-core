<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => $_action, $_condition => $conditionId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action.'Add', $_condition => $conditionId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('admission_age');
echo $this->Form->input('education_level_id', array('options' => $levelOptions, 'disabled'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action, $_condition => $conditionId)));
echo $this->Form->end();

$this->end();
?>