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
 */

class InfrastructureTypesController extends InfrastructureAppController {
	public $uses = array(
		'Infrastructure.InfrastructureLevel',
		'Infrastructure.InfrastructureType'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Infrastructure', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureLevels', 'action' => 'index'));
		
		$model = 'InfrastructureType';
		$this->set(compact('model'));
	}

	public function index(){
		$this->Navigation->addCrumb('Types');
		
		$levelId = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		$levelOptions = $this->InfrastructureLevel->getLevelOptions();
		
		if(!empty($levelOptions)){
			if ($levelId != 0) {
				if (!array_key_exists($levelId, $levelOptions)) {
					$levelId = key($levelOptions);
				}
			} else {
				$levelId = key($levelOptions);
			}
		}
		
		if(empty($levelOptions)){
			$this->Message->alert('InstitutionSiteInfrastructure.noLevel');
		}
		
		$level = $this->InfrastructureLevel->findById($levelId);
		
		$conditions = array('InfrastructureType.infrastructure_level_id' => $levelId);
		
		$data = $this->InfrastructureType->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureType.order')
		));
		
		$currentTab = 'Types';
		
		$this->set(compact('data', 'levelId', 'level', 'currentTab', 'levelOptions'));
	}

	public function view() {
		$this->Navigation->addCrumb('Type Details');

		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		if ($this->InfrastructureType->exists($id)) {
			$data = $this->InfrastructureType->findById($id);

			$this->Session->write('InfrastructureType.id', $id);
			
			if(!empty($data)){
				$level = $this->InfrastructureLevel->findById($data['InfrastructureType']['infrastructure_level_id']);
			}
			
			$this->set(compact('data', 'level'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add Type');
		
		$visibleOptions = $this->Option->get('yesno');
		$levelId = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		$level = $this->InfrastructureLevel->findById($levelId);
		$levelName = !empty($level) ? $level['InfrastructureLevel']['name'] : '';
		
		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;
			$this->InfrastructureType->create();
			
			if ($this->InfrastructureType->save($postData)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'index', $levelId));
			} else {
				$this->request->data = $postData;
				$this->Message->alert('general.add.failed');
			}
		}
		
		$this->set(compact('visibleOptions','levelId' , 'levelName'));
	}

	public function edit() {
		$this->Navigation->addCrumb('Edit Type');
		
		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		$data = $this->InfrastructureType->findById($id);
		$visibleOptions = $this->Option->get('yesno');
		
		if(!empty($data)){
			$level = $this->InfrastructureLevel->findById($data['InfrastructureType']['infrastructure_level_id']);
		}

		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;

			if ($this->InfrastructureType->save($postData)) {
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => 'view', $id));
			} else {
				$this->request->data = $postData;
				$this->Message->alert('general.edit.failed');
			}
		} else {
			$this->request->data = $data;
		}
		
		$this->set(compact('id', 'visibleOptions', 'level'));
	}
	
	public function remove() {
		if ($this->Session->check('InfrastructureType.id')) {
			$id = $this->Session->read('InfrastructureType.id');
			
			if(!empty($id)){
				$type = $this->InfrastructureType->findById($id);
				$levelId = $type['InfrastructureType']['infrastructure_level_id'];
			}

			$this->InfrastructureType->deleteAll(array('InfrastructureType.id' => $id));
			$this->Message->alert('general.delete.success');
		}
		$this->redirect(array('action' => 'index', !empty($levelId) ? $levelId : 0));
	}
	
	public function reorder() {
		$this->Navigation->addCrumb('Types');
		
		$levelId = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		$conditions = array('InfrastructureType.infrastructure_level_id' => $levelId);
		
		$data = $this->InfrastructureType->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureType.order')
		));
		
		$levelOptions = $this->InfrastructureLevel->getLevelOptions();
		
		$currentTab = 'Types';
		
		$this->set(compact('data', 'levelId', 'currentTab', 'levelOptions'));
	}
	
	public function move() {
		$this->autoRender = false;
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$levelId = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
			$conditions = array('InfrastructureType.infrastructure_level_id' => $levelId);
			$this->InfrastructureType->moveOrder($data, $conditions);
			$redirect = array('action' => 'reorder', $levelId);
			return $this->redirect($redirect);
		}
	}

}