<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identity" class="content_wrapper">
	<h1>
		<span><?php echo __('Identities'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Teachers/identitiesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<div class="table_cell"><?php echo __('Issued'); ?></div>
			<div class="table_cell"><?php echo __('Expiry'); ?></div>
			<div class="table_cell"><?php echo __('Location'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['TeacherIdentity']['id']; ?>">
				<div class="table_cell"><?php echo $obj['IdentityType']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherIdentity']['number']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherIdentity']['issue_date']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherIdentity']['expiry_date']); ?></div>
				<div class="table_cell"><?php echo $obj['TeacherIdentity']['issue_location']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>