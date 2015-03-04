<?php
	echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
	echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
	echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Options');?></label>
	<div class="col-md-6">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<?php if ($this->action == 'edit') : ?>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<?php endif ?>
						<th><?php echo __('Name'); ?></th>
					<?php if ($this->action == 'add') : ?>
						<th class="cell-delete"></th>
					<?php endif ?>
				</tr>
			</thead>
			<tbody>
				<?php
				if (isset($this->request->data[$Custom_FieldOption])) :
					$optionOrder = 1;
					foreach ($this->request->data[$Custom_FieldOption] as $key => $obj) :
				?>
					<tr>
						<?php if ($this->action == 'edit') : ?>
							<td class="checkbox-column">
								<?php
									echo $this->Form->checkbox("$Custom_FieldOption.$key.visible", array('class' => 'icheck-input', 'checked' => $obj['visible']));
								?>
							</td>
						<?php endif ?>
							<td>
								<?php
									//to handle add new Field Option in edit mode
									if(isset($this->request->data[$Custom_FieldOption][$key]['id'])) {
										echo $this->Form->hidden("$Custom_FieldOption.$key.id");
									}
									echo $this->Form->hidden("$Custom_FieldOption.$key.order", array('value' => $optionOrder));
									echo $this->Form->input("$Custom_FieldOption.$key.value", array('label' => false, 'div' => false, 'between' => false, 'after' => false));
								?>
							</td>
						<?php if ($this->action == 'add') : ?>
							<td>
								<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="jsTable.doRemove(this)"></span>
							</td>
						<?php endif ?>
					</tr>
				<?php
					$optionOrder++;
					endforeach;
				endif;
				?>
			</tbody>
		</table>
		<a class="void icon_plus" onclick="$('#reload').val('<?php echo $Custom_FieldOption ?>').click()"><?php echo $this->Label->get('general.add'); ?></a>
	</div>
</div>