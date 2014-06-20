<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/teachers', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institutions" class="content_wrapper">
	<h1>
		<span><?php echo __('Institutions'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'institutions'), array('class' => 'divider'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create('InstitutionSiteTeacher', array(
		'url' => array('plugin' => 'Teachers', 'controller' => 'Teachers', 'action' => 'institutionsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Institution'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('Start Date'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('End Date'); ?></div>
			<div class="table_cell cell_delete">&nbsp;</div>
		</div>
		
		<div class="table_body">
			<?php 
			if(count($records) > 0){
			$ctr = 1;
			foreach ($records as $record){
				echo '<div class="table_row" id="institution_row_'.$record['InstitutionSiteTeacher']['id'].'">';
				echo '<div class="table_cell">
						<input type="hidden" value="'.$record['InstitutionSiteTeacher']['id'].'" name="data[InstitutionSiteTeacher]['.$ctr.'][id]" />
						<select class="full_width" name="data[InstitutionSiteTeacher]['.$ctr.'][institution_site_id]">';
						foreach ($institutions as $arrInstitutionValue){
                        	$selected = ($record['InstitutionSiteTeacher']['institution_site_id'] == $arrInstitutionValue['InstitutionSite']['id']) ? "selected=selected" : null;
                        	echo "<option value=".$arrInstitutionValue['InstitutionSite']['id']." ".$selected.">".$arrInstitutionValue['Institution']['name']." - ".$arrInstitutionValue['InstitutionSite']['name']."</option>";
                        }
						// foreach ($institutions as $institutionKey => $institutionValue) {
						//	foreach($institutionValue as $key => $value) {
						//		$selected = ($record['InstitutionSiteTeacher']['institution_site_id'] == $key) ? "selected=selected" : null;
						//		echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
						//	}
						// }

				echo 	'</select>
					 </div>
					 <div class="table_cell cell_start_date">'.
						$this->Utility->getDatePicker($this->Form, 'start_date', array('order' => 'my', 'name' => "data[InstitutionSiteTeacher][{$ctr}][start_date]", 'value' => $record['InstitutionSiteTeacher']['start_date']));
				echo '</div>
					<div class="table_cell cell_end_date">'.
						$this->Utility->getDatePicker($this->Form, 'end_date', array('order' => 'my', 'name' => "data[InstitutionSiteTeacher][{$ctr}][end_date]", 'value' => $record['InstitutionSiteTeacher']['end_date']));
				echo '</div>
					<div class="table_cell"><span class="icon_delete" title="'.__("Delete").'" onClick="objTeacher.confirmDeletedlg('.$record['InstitutionSiteTeacher']['id'].')"></span></div>
				</div>';
				$ctr++;
				}
			}
			?>
		</div>
	</div>
	
	<?php if($_add) { ?>
		<!-- <div class="row"><a class="void icon_plus link_add">Add Training</a></div> -->
		<div class="row"><a id="institutions" class="void icon_plus"><?php echo __('Add') . ' ' . __('Institution'); ?></a></div>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return objTeacher.validateAdd();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'institutions'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>