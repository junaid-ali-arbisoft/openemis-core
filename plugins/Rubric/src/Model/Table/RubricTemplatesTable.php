<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class RubricTemplatesTable extends AppTable {
	private $weightingType = [
		1 => ['id' => 1, 'name' => 'Points'],
		2 => ['id' => 2, 'name' => 'Percentage']
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('RubricSections', ['className' => 'Rubric.RubricSections', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique'],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		//Auto insert default rubric_template_options when add
		if ($entity->isNew()) {
			$data = [
				'rubric_template_options' => [
					['name' => __('Good'), 'weighting' => 3, 'color' => '#00ff00', 'order' => 1],
					['name' => __('Normal'), 'weighting' => 2, 'color' => '#000ff0', 'order' => 2],
					['name' => __('Bad'), 'weighting' => 1, 'color' => '#ff0000', 'order' => 3],
				]
			];

			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($weightingTypeOptions) = array_values($this->getSelectOptions());

		$this->fields['weighting_type']['type'] = 'select';
		$this->fields['weighting_type']['options'] = $weightingTypeOptions;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$weightingTypeOptions = [];
		foreach ($this->weightingType as $key => $weightingType) {
			$weightingTypeOptions[$weightingType['id']] = __($weightingType['name']);
		}
		$selectedWeightingType = key($weightingTypeOptions);

		return compact('weightingTypeOptions', 'selectedWeightingType');
	}
}