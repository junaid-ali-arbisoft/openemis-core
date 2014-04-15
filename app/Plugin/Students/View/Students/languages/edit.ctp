<?php /*
<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="language" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Languages'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'languagesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentLanguage', array(
		'url' => array('controller' => 'Students', 'action' => 'languagesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StudentLanguage']; ?>
	<?php echo $this->Form->input('StudentLanguage.id');?>
  <div class="row">
        <div class="label"><?php echo __('Evaluation Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'evaluation_date',array('desc' => true, 'value' => $obj['evaluation_date'])); ?></div>
    </div>
	 <div class="row">
        <div class="label"><?php echo __('Language'); ?></div>
        <div class="value"><?php echo $this->Form->input('language_id', array('empty'=>__('--Select--'),'options'=>$languageOptions, 'default'=> $obj['language_id'])); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Listening'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('listening', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Speaking'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('speaking', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Reading'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('reading', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Writing'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('writing', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
     <div class="controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'languagesView',$id), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save  btn_right"));
        
                if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                } 
                if($mandatory!='1' && !$wizardEnd){
                    echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
                } 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
*/ ?>

<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'languagesView', $id), array('class' => 'divider'));
        }
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'languagesAdd'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('evaluation_date', array('id' => 'IssueDate'));
echo $this->Form->input('language_id', array('options'=>$languageOptions));
echo $this->Form->input('listening', array('options'=>$gradeOptions));
echo $this->Form->input('speaking', array('options'=>$gradeOptions));
echo $this->Form->input('reading', array('options'=>$gradeOptions));
echo $this->Form->input('writing', array('options'=>$gradeOptions));


if(!$WizardMode){ 
    echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'languagesView',$id)));
}
else{
    echo '<div class="add_more_controls">'.$this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right")).'</div>';
    
    echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));

    if(!$wizardEnd){
        echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
    }else{
        echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
    }
    if($mandatory!='1' && !$wizardEnd){
        echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
    } 
}

echo $this->Form->end();
$this->end();
?>