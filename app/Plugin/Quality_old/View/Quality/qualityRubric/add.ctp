<?php
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('Quality.quality.rubric', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($type == 'add') {
	$redirectAction = array('action' => 'qualityRubric');
} else {
	$redirectAction = array('action' => 'qualityRubricView', $this->data[$model]['id']);
}
echo $this->Html->link($this->Label->get('general.back'),$redirectAction, array('class' => 'divider', 'id' => 'back'));

$this->end();
$this->start('contentBody');

$actionName = $this->action;
$formOptions = array('controller' => 'Quality', 'action' => $actionName, 'plugin' => 'Quality');
$formOptions = array_merge($formOptions, $this->params['pass']);
$pathId = !empty($this->data[$model]['id']) ? '/' . $this->data[$model]['id'] : '';
echo $this->Form->create($model, array(
	'url' => $formOptions,
	'link' => 'Quality/' . $this->action . $pathId,
	'class' => 'form-horizontal',
	'type' => 'file',
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));
?>
<?php
if (!empty($this->data[$model]['id'])) {
	echo $this->Form->input('id', array('type' => 'hidden'));
}
?>

<?php echo $this->Form->input('institution_site_id', array('type' => 'hidden')); ?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('School Year'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('academic_period_id', array('id' => 'academicPeriodId', 'options' => $academicPeriodOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($academicPeriodOptions[$this->data['QualityInstitutionRubric']['academic_period_id']])) {
				echo $academicPeriodOptions[$this->data['QualityInstitutionRubric']['academic_period_id']];
			} else {
				echo $academicPeriodOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Grade'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('institution_site_section_grade_id', array('id' => 'institutionSiteSectionGradeId', 'options' => $gradeOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($gradeOptions[$this->data['QualityInstitutionRubric']['institution_site_section_grade_id']])) {
				echo $gradeOptions[$this->data['QualityInstitutionRubric']['institution_site_section_grade_id']];
			} else {
				echo $gradeOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Section'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('institution_site_section_id', array('id' => 'institutionSiteSectionId', 'options' => $sectionOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($sectionOptions[$this->data['QualityInstitutionRubric']['institution_site_section_id']])) {
				echo $sectionOptions[$this->data['QualityInstitutionRubric']['institution_site_section_id']];
			} else {
				echo $sectionOptions[0];
			}
		}
		?>
	</div>
	<div class="value"><?php ?></div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Name'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('rubric_template_id', array('id' => 'rubricsTemplateId', 'options' => $rubricOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($rubricOptions[$this->data['QualityInstitutionRubric']['rubric_template_id']])) {
				echo $rubricOptions[$this->data['QualityInstitutionRubric']['rubric_template_id']];
			} else {
				echo $rubricOptions[0];
			}
		}
		?>
	</div>
</div>


<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Staff'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('staff_id', array('id' => 'institutionSitestaffId', 'options' => $staffOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($staffOptions[$this->data['QualityInstitutionRubric']['staff_id']])) {
				echo $staffOptions[$this->data['QualityInstitutionRubric']['staff_id']];
			} else {
				echo $staffOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Evaluator'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('evaluator', array('disabled' => true, 'class' => 'form-control'));
		} else {

			echo $this->data['QualityInstitutionRubric']['evaluator'];
		}
		?>
	</div>
</div>
<?php if ($type == 'edit') : ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Comment'); ?></label>
		<div class="col-md-4">
			<?php echo $this->Form->input('comment', array('type' => 'textarea', 'class' => 'form-control')); ?>
			<br/>
			<div id="image_upload_info" style="clear: both">
				<em>
					<?php echo __("Maximum 150 words per comment"); ?>
				</em>
			</div>
		</div>
	</div>       
<?php
endif;

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));

echo $this->Form->end();
$this->end();
?>  