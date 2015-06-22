<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowModelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('Workflows', ['className' => 'Workflow.Workflows']);
	}
}