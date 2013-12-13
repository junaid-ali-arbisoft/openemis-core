<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="contactiew" class="content_wrapper">
    <h1>
        <span><?php echo __('Contacts'); ?></span>
        <?php
        $data = $contactObj[0]['StudentContact'];
        echo $this->Html->link(__('List'), array('action' => 'contacts', $data['student_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'contactsEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'contactsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $contactOptions[$contactObj[0]['ContactType']['contact_option_id']]; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value"><?php echo $contactObj[0]['ContactType']['name']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Value'); ?></div>
        <div class="value"><?php echo $data['value']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Preferred'); ?></div>
        <div class="value"><?php echo $this->Utility->checkOrCrossMarker($data['preferred']==1); ?></div>
    </div>
    
   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($contactObj[0]['ModifiedUser']['first_name'] . ' ' . $contactObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($contactObj[0]['CreatedUser']['first_name'] . ' ' . $contactObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>