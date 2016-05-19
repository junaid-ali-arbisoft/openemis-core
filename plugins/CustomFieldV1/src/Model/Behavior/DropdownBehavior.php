<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class DropdownBehavior extends Behavior {
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch'
		]
	];

	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('options', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/dropdown',
	            'visible' => true,
	            'valueClass' => 'table-full-width'
	        ]);
        }
    }

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (isset($data[$this->_table->alias()]['is_default']) && !empty($data[$this->_table->alias()]['custom_field_options'])) {
			$defaultKey = $data[$this->_table->alias()]['is_default'];
			$data[$this->_table->alias()]['custom_field_options'][$defaultKey]['is_default'] = 1;
		}
    }

    public function addEditOnAddDropdownOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fieldOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_field_options'][] = $fieldOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomFieldOptions' => ['validate' => false]
		];
	}

    public function onGetCustomDropdownElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';
        $dropdownOptions = [];
        $dropdownDefault = null;
		foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
			$dropdownOptions[$obj->id] = $obj->name;
			if ($obj->is_default == 1) {
				$dropdownDefault = $obj->id;
			}
		}

        if ($action == 'view') {
        	if (!empty($dropdownOptions)) {
        		$valueKey = !is_null($attr['value']) ? $attr['value'] : key($dropdownOptions);
        		$value = $dropdownOptions[$valueKey];
        	}
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'select';
            $options['default'] = !is_null($attr['value']) ? $attr['value'] : $dropdownDefault;
            $options['value'] = !is_null($attr['value']) ? $attr['value'] : $dropdownDefault;
			$options['options'] = $dropdownOptions;

 			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
			if (!is_null($attr['id'])) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }
        }

        return $value;
    }
}