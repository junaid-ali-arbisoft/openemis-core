<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ConsultationTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_consultation_types');
		parent::initialize($config);

		$this->hasMany('Consultations', ['className' => 'Health.Consultations', 'foreignKey' => 'health_consultation_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}