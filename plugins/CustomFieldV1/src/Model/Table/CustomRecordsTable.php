<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class CustomRecordsTable extends AppTable {
	private $_contain = ['CustomFieldValues', 'CustomTableCells'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'CustomField.CustomForms']);
		$this->addBehavior('CustomField.Record');
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($moduleOptions, $selectedModule, $formOptions, $selectedForm) = array_values($this->getSelectOptions());
        $this->controller->set(compact('moduleOptions', 'selectedModule', 'formOptions', 'selectedForm'));

		$query
			->where([$this->aliasField('custom_form_id') => $selectedForm])
			->contain($this->_contain);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list(, , $formOptions) = array_values($this->getSelectOptions());

		$this->fields['custom_form_id']['options'] = $formOptions;
		$this->fields['custom_form_id']['onChangeReload'] = true;

		$this->setFieldOrder();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('addEditAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('addEditAfterAction', [$event, $entity]));
		}

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedModule) = array_values($this->getSelectOptions());
		$entity->custom_form_id = $selectedModule;
		return $entity;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->CustomForms->CustomModules->find('list')->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		$formOptions = $this->CustomForms->find('list')->where([$this->CustomForms->aliasField('custom_module_id') => $selectedModule])->toArray();
		$selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

		return compact('moduleOptions', 'selectedModule', 'formOptions', 'selectedForm');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('custom_form_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
	}
}