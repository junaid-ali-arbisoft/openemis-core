<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.

ControllerActionComponent - Version 1.0.7
*/

namespace ControllerAction\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class ControllerActionComponent extends Component {
	private $plugin;
	private $controller;
	private $model = null;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $ctpFolder;
	private $paramsPass;
	private $defaultActions = ['index', 'add', 'view', 'edit', 'remove', 'reorder'];
	public $models = [];
	public $buttons = [];
	public $orderField = 'order';
	public $autoRender = true;
	public $autoProcess = true;
	public $removeStraightAway = true;
	public $indexActions = [
		'view' => array('class' => 'fa fa-eye'),
		'edit' => array('class' => 'fa fa-pencil'),
		'remove' => array('class' => 'fa fa-trash')
	];
	public $pageOptions = [10, 20, 30, 40, 50];
	public $Session;

	public $components = ['Message', 'Paginator'];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$controller = $this->_registry->getController();//pr($this->request);
		$this->paramsPass = $this->request->params['pass'];
		$this->currentAction = $this->request->params['action'];
		$this->ctpFolder = $controller->name;

		$this->controller = $controller;
		$this->Session = $this->request->session();
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		$controller = $this->controller;
		
		$action = $this->request->params['action'];
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$this->request->params['action'] = 'ComponentAction';
			} else { // check if it's a model action
				foreach ($this->models as $modelName) {
					$model = $this->getModel($modelName);
					$modelClass = $model['model'];

					if ($action === $modelClass) { // model class found
						$currentAction = 'index';
						if (!empty($this->paramsPass)) {
							$currentAction = array_shift($this->paramsPass);
						}
						$this->plugin = $model['plugin'];
						$this->model = $controller->{$modelClass};
						$this->getFields($this->model);
						$this->currentAction = $currentAction;
						$this->ctpFolder = $this->model->alias();
						$this->request->params['action'] = 'ComponentAction';
						$this->initComponentsForModel();
						if (method_exists($this->model, 'beforeAction')) {
							$this->model->beforeAction();
						}
						$this->triggerFrom = 'Model';
						break;
					}
				}
			}
		}
		$this->initButtons();
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Event $event) {
		$controller = $this->controller;
		if (!is_null($this->model) && !empty($this->model->fields)) {
			$action = $this->currentAction;

			if ($this->triggerFrom == 'Controller') {
				
				
			} else if ($this->triggerFrom == 'Model') {
				$action = $this->model->alias();
				if (method_exists($this->model, 'afterAction')) {
					$this->model->afterAction();
				}
			}
			$this->request->params['action'] = $action;

			uasort($this->model->fields, [$this, 'sortFields']);
			$controller->set('model', $this->model->alias());
			$controller->set('modelObj', $this->model);
			$controller->set('action', $this->currentAction);
			$controller->set('_fields', $this->model->fields);
			$controller->set('_triggerFrom', $this->triggerFrom);
		}
	}

	public function model($model = null) {
		if (is_null($model)) {
			return $this->model;
		} else {
			$this->plugin = $this->getPlugin($model);
			$this->model = $this->controller->loadModel($model);
			$this->getFields($this->model);
		}
	}

	private function initButtons() {
		$controller = $this->controller;

		$named = $this->request->query;
		$pass = $this->request->params['pass'];
		if ($this->triggerFrom == 'Model') {
			unset($pass[0]);
		}

		$defaultUrl = ['plugin' => $this->controller->params['plugin'], 'controller' => $controller->name];

		$buttons = [];

		foreach ($this->defaultActions as $action) {
			$actionUrl = $defaultUrl;
			$actionUrl['action'] = $action;

			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias();
				$actionUrl[] = $action;
			}

			if ($action != 'index') {
				$actionUrl = array_merge($actionUrl, $pass);
			}
			$actionUrl = array_merge($actionUrl, $named);
			$buttons[$action] = array('url' => $actionUrl);
		}

		$backAction = 'index';
		if ($this->currentAction == 'edit' || $this->currentAction == 'remove') {
			$backAction = 'view';
		}

		$backUrl = $defaultUrl;
		$backUrl['action'] = $backAction;
		if ($this->triggerFrom == 'Model') {
			$backUrl['action'] = $this->model->alias();
			$backUrl[] = $backAction;
		}
		if ($backAction != 'index') {
			$backUrl = array_merge($backUrl, $pass);
		}
		$backUrl = array_merge($backUrl, $named);
		$buttons['back'] = array('url' => $backUrl);
		$buttons['remove']['removeStraightAway'] = $this->removeStraightAway;

		// logic for Reorder buttons
		if (!is_null($this->model) && array_key_exists($this->orderField, $this->model->schema())) {
			$reorderUrl = $defaultUrl;
			$reorderUrl['action'] = 'reorder';
			$reorderUrl = array_merge($reorderUrl, $named, $pass);
			$buttons['reorder'] = array('url' => $reorderUrl);
		}
		$this->buttons = $buttons;
		$controller->set('_buttons', $buttons);
		foreach ($this->indexActions as $action => $attr) {
			$this->indexActions[$action] = array_merge($this->indexActions[$action], $buttons[$action]);
		}
		$controller->set('_indexActions', $this->indexActions);
	}

	private function initComponentsForModel() {
		$this->model->controller = $this->controller;
		$this->model->request = $this->request;
		$this->model->Navigation = $this->controller->Navigation;
		$this->model->Session = $this->request->session();
		$this->model->Message = $this->Message;
		$this->model->ControllerAction = $this;
		$this->model->action = $this->currentAction;
		$this->model->setVar = null;
	}

	public function processAction() {
		$result = null;
		if ($this->autoProcess) {
			if ($this->triggerFrom == 'Controller') {
				if (in_array($this->currentAction, $this->defaultActions)) {
					$result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
				}
			} else if ($this->triggerFrom == 'Model') {
				if (method_exists($this->model, $this->currentAction)) {
					$result = call_user_func_array([$this->model, $this->currentAction], $this->paramsPass);
				} else {
					if (in_array($this->currentAction, $this->defaultActions)) {
						$result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
					}
				}
			}
		}

		$this->render();
	}

	public function render() {
		if (empty($this->plugin)) {
			$path = APP . 'View' . DS . $this->controller->name . DS;
		} else {
			$path = APP . 'Plugin' . DS . $this->plugin . DS . 'View' . DS;
		}
		$ctp = $this->ctpFolder . DS . $this->currentAction;

		if (file_exists($path . DS . $ctp . '.ctp')) {
			if ($this->autoRender) {
				$this->autoRender = false;
				$this->controller->render($ctp);
			}
		}

		if ($this->autoRender) {
			$view = $this->currentAction == 'add' ? 'edit' : $this->currentAction;
			$this->controller->render('/Element/ControllerAction/' . $view . '_template');
		}
	}

	public function search($model, $options = array(), $order = array()) {
		$alias = $model->alias();
		$controller = $this->controller;
		$request = $this->request;
		$limit = $this->Session->check($alias.'.search.limit') ? $this->Session->read($alias.'.search.limit') : key($this->pageOptions);
		$search = $this->Session->check($alias.'.search.key') ? $this->Session->read($alias.'.search.key') : '';
		
		$schema = $this->getSchema($model);

		if (empty($order) && array_key_exists($this->orderField, $this->model->schema())) {
			$order = $this->orderField;
		}

		if ($request->is(array('post', 'put'))) {
			if (isset($request->data['Search'])) {
				if (array_key_exists('searchField', $request->data['Search'])) {
					$search = trim($request->data['Search']['searchField']);
				}

				if (array_key_exists('limit', $request->data['Search'])) {
					$limit = $request->data['Search']['limit'];
					$this->Session->write($alias.'.search.limit', $limit);
				}
			}
		}
		$conditions = isset($options['conditions']) ? $options['conditions'] : [];
		$contain = isset($options['contain']) ? $options['contain'] : [];
		// all string fields are searchable by default
		$OR = isset($conditions['OR']) ? $conditions['OR'] : [];
		foreach($schema as $name => $obj) {
			if ($obj['type'] == 'string' && $name != 'password') {
				$OR["$alias.$name LIKE"] = '%' . $search . '%';
			}
		}
		$conditions['OR'] = $OR;

		$paginateOptions = ['limit' => $this->pageOptions[$limit], 'order' => $order, 'conditions' => $conditions];
		if (!empty($contain)) {
			$paginateOptions['contain'] = $contain;
		}

		$this->Session->write($alias.'.search.key', $search);
		$this->request->data['Search']['searchField'] = $search;
		$this->request->data['Search']['limit'] = $limit;
		$controller->set('search', $search);
		$controller->set('pageOptions', $this->pageOptions);

		try {
			$data = $this->Paginator->paginate($model, $paginateOptions);
		} catch (NotFoundException $e) {
			$this->log($e->getMessage(), 'debug');
			return $controller->redirect(['action' => 'index']);
		}
		return $data;
	}

	public function getModalOptions($type) {
		$modal = array();

		if ($type == 'delete') {
			$modal['id'] = 'delete-modal';
			$modal['title'] = $this->model->alias();
			$modal['content'] = __('Are you sure you want to delete this record.');
			$modal['formOptions'] = array(
				'type' => 'delete',
				'action' => $this->buttons['remove']['url']['action']
			);
			$modal['fields'] = array(
				'id' => array('type' => 'hidden', 'id' => 'recordId')
			);
			$modal['buttons'] = array(
				'<button type="submit" class="btn">' . __('Delete') . '</button>'
			);
		}
		return $modal;
	}

	public function index() {
		$model = $this->model;

		$contain = [];
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}

		$data = $this->search($model, ['contain' => $contain]);
		$modal = $this->getModalOptions('delete');

		$indexElements = array(
			array('name' => 'ControllerAction.index', 'data' => array(), 'options' => array())
		);

		if (empty($data)) {
			$this->Message->alert('general.noData');
		} else {
			$indexElements[] = array('name' => 'ControllerAction.pagination', 'data' => array(), 'options' => array());
		}

		$this->controller->set('data', $data);
		$this->controller->set('modal', $modal);
		$this->controller->set('indexElements', $indexElements);
	}

	public function view($id=0) {
		$model = $this->model;

		$contain = [];
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}
		
		if ($model->exists($id)) {
			$data = $model->get($id, ['contain' => $contain]);//pr($data);

			$this->Session->write($model->alias().'.'.$model->primaryKey(), $id);
			$modal = $this->getModalOptions('delete');
			$this->controller->set('modal', $modal);
			$this->controller->set(compact('data'));
		} else {
			$this->Message->alert('general.notExists');
			$action = 'index';
			if ($this->triggerFrom == 'Model') {
				$action = get_class($model);
			}
			return $this->controller->redirect(array('action' => $action));
		}
	}

	public function add() {
		$model = $this->model;
		$data = $model->newEntity();
		if ($this->request->is(array('post', 'put'))) {
			$data = $model->patchEntity($data, $this->request->data);
			//$model->create();
			if ($model->save($data)) {
				$this->Message->alert('general.add.success');
				//$named = $this->controller->params['named'];
				$pass = $this->controller->params['pass'];
				//$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

				$action = array('action' => isset($params['back']) ? $params['back'] : 'view');
				if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$action = ['action' => get_class($model)];
					$action[] = isset($params['back']) ? $params['back'] : 'view';
				}
				$action[] = $model->getLastInsertID();
				$action = array_merge($action, $named, $pass);
				return $this->controller->redirect($action);
			} else {
				//$this->log($model->errors(), 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
		$this->controller->set('data', $data);
	}

	public function edit($id=0) {
		$model = $this->model;

		if ($model->exists($id)) {
			$data = $model->get($id);
			
			if ($this->request->is(array('post', 'put'))) {

				if ($model->save($this->request->data)) {
					$this->Message->alert('general.edit.success');
					$named = $this->controller->params['named'];
					$pass = $this->controller->params['pass'];
					$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

					$action = array('action' => isset($params['back']) ? $params['back'] : 'view');
					if ($this->triggerFrom == 'Model') {
						unset($pass[0]);
						$action = array('action' => get_class($model));
						$action[] = isset($params['back']) ? $params['back'] : 'view';
					}
					
					$action = array_merge($action, $named, $pass);
					return $this->controller->redirect($action);
				} else {
					$this->request->data = array_merge($data, $this->request->data);
					$this->log($model->errors(), 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->controller->set('data', $data);
			}
		} else {
			$this->Message->alert('general.notExists');
			$action = 'index';
			if ($this->triggerFrom == 'Model') {
				$action = get_class($model);
			}
			return $this->controller->redirect(array('action' => $action));
		}
	}

	public function remove() {
		$request = $this->request;
		$model = $this->model;
		
		if ($request->is('delete') && isset($request->data[$model->alias()]['id'])) {
			$id = $request->data[$model->alias()][$model->primaryKey()];
			if ($this->removeStraightAway) {
				if ($model->delete($id)) {
					$this->Message->alert('general.delete.success');
				} else {
					$this->Message->alert('general.delete.failed');
				}
				$action = $this->buttons['index']['url'];
				return $this->controller->redirect($action);
			} else {
				//return $this->removeAndTransfer(array('selectedValue' => $id));
			}
		}
	}

	/*
	private function removeAndTransfer($options = array()) {

		// 'selectedOption' => false, 'selectedValue' => 0
		$selectedOption = isset($options['selectedOption']) ? $options['selectedOption'] : false;

		$selectedValue = isset($options['selectedValue']) ? $options['selectedValue'] : 0;
		$model = $this->model;
		$modelName = $model->alias();

		if ($selectedValue == 0) {
			$this->Message->alert('general.notExists');
			$action = $this->controller->viewVars['_buttons']['index']['url'];
			return $this->controller->redirect($action);
		}

		$allowDelete = (isset($model->allowDelete))? $model->allowDelete: false;
		if (!$allowDelete) {
			$this->Message->alert('general.delete.failed');
			$action = $this->controller->viewVars['_buttons']['view']['url'];
			return $this->controller->redirect($action);
		}

		$allFieldOptionValues = $model->find('list', array('conditions'=>array($model->getListConditions)));
		if (array_key_exists($selectedValue, $allFieldOptionValues)) {
			// unset only if field option exists in list
			unset($allFieldOptionValues[$selectedValue]);
		} else {
			$this->Message->alert('general.notExists');
			$action = $this->controller->viewVars['_buttons']['index']['url'];
			return $this->controller->redirect($action);
		}

		$model->recursive = -1;
		$currentFieldValue = $model->findById($selectedValue);

		// if no legal records to migrate to ... they are not allowed to delete
		if (empty($allFieldOptionValues)) {
			$this->Message->alert('general.delete.cannotDeleteOnlyRecord');
			$action = $this->controller->viewVars['_buttons']['view']['url'];
			return $this->controller->redirect($action);
		}

		$modifyForeignKey = array();
		$hasManyArray = $model->hasMany;
		//pr($model->getAllHasManyTables());die;
		foreach ($hasManyArray as $key => $value) {
			$CurrModelClass = ClassRegistry::init($value['className']);
			$foreignKeyId = isset($value['foreignKey']) ? $value['foreignKey'] : Inflector::underscore($modelName)."_id";
			$modifyForeignKey[$key] = $CurrModelClass->find('count',
				array(
					'recursive' => -1,
					'conditions' => array(
						$CurrModelClass->alias() . '.' .$foreignKeyId => $selectedValue
					)
				)
			);
		}

		$children = false;
		if (isset($currentFieldValue[$modelName]['parent_id'])) {
			$children = $model->find('all', array('conditions'=>array('parent_id'=>$currentFieldValue[$modelName]['id'])));
		}

		if ($this->request->is(array('post', 'put'))) {
			$convertValue = $this->request->data[$modelName]['convert_to'];
			foreach ($modifyForeignKey as $key => $value) {
				$CurrModelClass = ClassRegistry::init($key);
				$foreignKeyId = isset($hasManyArray[$key]['foreignKey']) ? $hasManyArray[$key]['foreignKey'] : Inflector::underscore($modelName)."_id";
				$CurrModelClass->updateAll(
					array($key.'.'.$foreignKeyId => $convertValue),
					array($key.'.'.$foreignKeyId => $selectedValue)
				);
			}
			if (isset($currentFieldValue[$modelName]['parent_id']) && count($children)>0) {
				foreach ($children as $c) {
					$model->id = $c[$modelName]['id'];
					$c[$modelName]['parent_id'] = $convertValue;
					unset($c[$modelName]['lft']);
					unset($c[$modelName]['rght']);
					$model->data = $c;
					$model->saveAll();
				}
			}
			$model->id = $selectedValue;
			if ($model->delete()) {
				$this->Message->alert('general.delete.successAfterTransfer');
				$action = $this->controller->viewVars['_buttons']['index']['url'];
				if (isset($action[1])) {
					unset($action[1]);
				}
				return $this->controller->redirect($action);
			}
		}
				
		$this->controller->set('allOtherFieldOptionValues', $allFieldOptionValues);
		$this->controller->set(compact('header', 'currentFieldValue', 'modifyForeignKey', 'selectedOption', 'selectedValue', 'allowDelete', 'model', 'children'));
	}
	*/

	public function reorder($id=0) {
		$model = $this->model;

		if ($id != 0) {
			$named = $this->controller->params['named'];
			$move = $named['move'];

			$actionUrl = array('action' => 'index');
			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias();
				$actionUrl[] = $action;
			}
			unset($named['move']);
			$actionUrl = array_merge($actionUrl, $named);
			
			$conditions = array();
			//$conditions = isset($this->controller->viewVars['conditions']) ? $this->controller->viewVars['conditions'] : array();
			$this->fixOrder($conditions);
			
			$idField = $model->alias().'.'.$model->primaryKey();
			$orderField = $model->alias() . '.' . $this->orderField;
			$order = $model->field($this->orderField, array($model->primaryKey() => $id));
			$idConditions = array_merge(array($idField => $id), $conditions);
			$updateConditions = array_merge(array($idField . ' <>' => $id), $conditions);

			if($move === 'up') {
				$model->updateAll(array($orderField => $order-1), $idConditions);
				$updateConditions[$orderField] = $order-1;
				$model->updateAll(array($orderField => $order), $updateConditions);
			} else if($move === 'down') {
				$model->updateAll(array($orderField => $order+1), $idConditions);
				$updateConditions[$orderField] = $order+1;
				$model->updateAll(array($orderField => $order), $updateConditions);
			} else if($move === 'first') {
				$model->updateAll(array($orderField => 1), $idConditions);
				$updateConditions[$orderField . ' <'] = $order;
				$model->updateAll(array($orderField => '`'.$orderField . '` + 1'), $updateConditions);
			} else if($move === 'last') {
				$count = $model->find('count', array('conditions' => $conditions));
				$model->updateAll(array($orderField => $count), $idConditions);
				$updateConditions[$orderField . ' >'] = $order;
				$model->updateAll(array($orderField => '`'.$orderField . '` - 1'), $updateConditions);
			}
		}

		return $this->controller->redirect($actionUrl);
    }

    public function fixOrder($conditions) {
    	$model = $this->model;
		$count = $model->find('count', array('conditions' => $conditions));
		if($count > 0) {
			$list = $model->find('list', array(
				'conditions' => $conditions,
				'order' => array(
					$model->alias().'.'.$this->orderField,
					$model->alias().'.'.$model->primaryKey()
				)
			));
			$order = 1;
			foreach($list as $id => $name) {
				$model->id = $id;
				$model->saveField($this->orderField, $order++);
			}
		}
	}

	public function getPlugin($model) {
		$array = $this->getModel($model);
		return $array['plugin'];
	}

	public function getModel($model) {
		$split = explode('.', $model);
		$plugin = null;
		$modelClass = $model;
		if (count($split) > 1) {
			$plugin = $split[0];
			$modelClass = $split[1];
		}
		return array('plugin' => $plugin, 'model' => $modelClass);
	}

	public function getSchema($model) {
		$schema = $model->schema();
		$columns = $schema->columns();
		$fields = [];
		foreach ($columns as $col) {
			$fields[$col] = $schema->column($col);
		}
		return $fields;
	}
	
	public function getFields($model) {
		$defaultFields = array('modified_user_id', 'modified', 'created_user_id', 'created', 'order');

		$fields = $this->getSchema($model);
		
		$i = 0;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = true;
			if ($obj['type'] == 'string') { // make field sortable by default if it is a string data-type
				$fields[$key]['sort'] = true;
			}
		}
		
		$fields[$model->primaryKey()]['type'] = 'hidden';
		foreach ($defaultFields as $field) {
			if (array_key_exists($field, $fields)) {
				if ($field == 'modified_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'ModifiedUser';
				}
				if ($field == 'created_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'CreatedUser';
				}
				$fields[$field]['visible'] = array('view' => true, 'edit' => false);
				$fields[$field]['labelKey'] = 'general';
			}
		}
		$model->fields = $fields;
		return $fields;
	}
	
	public function setFieldOrder($field, $order) {
		$fields = $this->model->fields;
		$found = false;
		$count = 0;
		foreach ($fields as $key => $obj) {
			$count++;
			if (!isset($fields[$key]['order'])) {
				$fields[$key]['order'] = $count;
			}
			
			if ($found && $key !== $field) {
				$fields[$key]['order'] = $fields[$key]['order'] + 1;
			} else {
				if ($field === $key) {
					$found = true;
					$fields[$key]['order'] = $order;
				} else if ($fields[$key]['order'] == $order) {
					$found = true;
					$fields[$key]['order'] = $order + 1;
				}
			}
		}
		$fields[$field]['order'] = $order;
		uasort($fields, array($this, 'sortFields'));
		$this->model->fields = $fields;
	}
	
	public static function sortFields($a, $b) {
		if (isset($a['order']) && isset($b['order'])) {
			return $a['order'] >= $b['order'];
		} else {
			return true;
		}
	}
}