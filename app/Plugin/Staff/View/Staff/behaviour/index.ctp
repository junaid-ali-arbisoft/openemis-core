<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="behaviour" class="content_wrapper">
    <h1>
        <span><?php echo __('List of Behaviour'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(!empty($data)) { ?>
	<div class="table full_width allow_hover" action="Staff/behaviourView/">
		<div class="table_head">
			<div class="table_cell cell_behaviour_date"><?php echo __('Date'); ?></div>
            <div class="table_cell cell_behaviour_category"><?php echo __('Category'); ?></div>
            <div class="table_cell cell_behaviour_title"><?php echo __('Title'); ?></div>
        	<div class="table_cell"><?php echo __('Insitution Site'); ?></div>
		</div>

		<div class="table_body">
			<?php foreach($data as $id => $obj) { $i=0; ?>
			<div class="table_row" row-id="<?php echo $obj['StaffBehaviour']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffBehaviour']['date_of_behaviour']); ?></div>
                <div class="table_cell"><?php echo $obj['StaffBehaviourCategory']['name']; ?></div>
                <div class="table_cell"><?php echo $obj['StaffBehaviour']['title']; ?></div>
                <div class="table_cell"><?php echo $obj['InstitutionSite']['name']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</div>
 * 
 */?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if(!empty($data)) { 
	$tableHeaders = array(__('Date'), __('Category'), __('Title'), __('Insitution Site'));
	$tableData = array();
	foreach ($data as $obj) {
		$row = array();
		$row[] = $obj[$model]['date_of_behaviour'];
		$row[] = $obj['StaffBehaviourCategory']['name'];
		$row[] = $this->Html->link($obj[$model]['title'], array('action' => 'behaviourView', $obj[$model]['id']), array('escape' => false));
		$row[] = $obj['InstitutionSite']['name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
}
$this->end();
?>