<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __('Student Information'); ?></span>
		<?php 
		$obj = $data['Student'];
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'studentsEdit'), array('class' => 'divider'));
		}
                if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'studentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		if($_accessControl->check($this->params['controller'], 'studentsCustFieldYrView')) {
			echo $this->Html->link(__('Academic'), array('action' => 'studentsCustFieldYrView', $obj['id']), array('class' => 'divider'));
		}
		if($_accessControl->check($this->params['controller'], 'studentsBehaviour')) {
			echo $this->Html->link(__('Behaviour'), array('action' => 'studentsBehaviour', $obj['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_break" id="general">
		<legend><?php echo __('General'); ?></legend>
		<?php
		    $path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null'))? "/Students/fetchImage/{$obj['id']}":"/Students/img/default_student_profile.jpg";
		    echo $this->Html->image($path, array('class' => 'profile_image', 'alt' => '90x115'));
		?>
		<div class="row">
			<div class="label"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="value">
				<?php
				if($_view_details) {
					echo $this->Html->link($obj['identification_no'], array('controller' => 'Students', 'action' => 'viewStudent', $obj['id']), array('class' => 'link_back'));
				} else {
					echo $obj['identification_no'];
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value"><?php echo $obj['middle_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value"><?php echo $obj['preferred_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Date of Death'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_death']); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Programmes'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell" style="width: 220px;"><?php echo __('Programme'); ?></div>
				<div class="table_cell"><?php echo __('From'); ?></div>
				<div class="table_cell"><?php echo __('To'); ?></div>
				<div class="table_cell" style="width: 100px;"><?php echo __('Status'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($details as $detail) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $detail['EducationProgramme']['name']; ?></div>
					<div class="table_cell center"><?php echo $this->Utility->formatDate($detail['InstitutionSiteStudent']['start_date']); ?></div>
					<div class="table_cell center"><?php echo $this->Utility->formatDate($detail['InstitutionSiteStudent']['end_date']); ?></div>
					<div class="table_cell center"><?php echo $detail['StudentStatus']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Classes'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell" style="width: 80px;"><?php echo __('Year'); ?></div>
				<div class="table_cell" style="width: 120px;"><?php echo __('Class'); ?></div>
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell" style="width: 120px;"><?php echo __('Grade'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($classes as $class) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $class['SchoolYear']['name']; ?></div>
					<div class="table_cell"><?php echo $class['InstitutionSiteClass']['name']; ?></div>
					<div class="table_cell"><?php echo $class['EducationCycle']['name'] . ' - ' . $class['EducationProgramme']['name']; ?></div>
					<div class="table_cell"><?php echo $class['EducationGrade']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Assessments'); ?></legend>
		<?php foreach($results as $gradeId => $result) { ?>
		<fieldset class="section_group" style="margin-top: 15px;">
			<legend><?php echo $result['name']; ?></legend>
			<?php foreach($result['assessments'] as $id => $assessment) { ?>
			<fieldset class="section_break">
				<legend><?php echo $assessment['name']; ?></legend>
				<div class="table">
					<div class="table_head">
						<div class="table_cell"><?php echo __('Code'); ?></div>
						<div class="table_cell"><?php echo __('Subject'); ?></div>
						<div class="table_cell"><?php echo __('Marks'); ?></div>
						<div class="table_cell"><?php echo __('Grading'); ?></div>
					</div>
					
					<div class="table_body">
						<?php foreach($assessment['subjects'] as $subject) { ?>
						<div class="table_row">
							<div class="table_cell"><?php echo $subject['code']; ?></div>
							<div class="table_cell"><?php echo $subject['name']; ?></div>
							<div class="table_cell"><?php echo $subject['marks']; ?></div>
							<div class="table_cell"><?php echo $subject['grading']; ?></div>
						</div>
						<?php } ?>
					</div>
				</div>
			</fieldset>
			<?php } ?>
		</fieldset>
		<?php } ?>
	</fieldset>
</div>