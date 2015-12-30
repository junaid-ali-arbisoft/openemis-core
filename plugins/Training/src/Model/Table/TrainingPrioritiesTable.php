<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingPrioritiesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_priority_id']);
	}
}