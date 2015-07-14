<?php if ($ControllerAction['action'] == 'index') : ?>
	<?= isset($attr['value']) ? $attr['value'] : 0; ?>
<?php elseif ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="table-in-view table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="clearfix"></div>
		<hr>
		<h3><?= __('Education Subjects')?></h3>
		<div class="clearfix">
			<?= 
				$this->Form->input($ControllerAction['table']->alias().".education_subject_id", [
					'label' => $this->Label->get('EducationGrades.add_subject'),
					'type' => 'select',
					'options' => $attr['options'],
					'value' => 0,
					'onchange' => "$('#reload').val('addSubject').click();"
				]);
			?>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>