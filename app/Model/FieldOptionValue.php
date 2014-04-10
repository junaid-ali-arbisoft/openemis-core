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

App::uses('AppModel', 'Model');

class FieldOptionValue extends AppModel {
	public $belongsTo = array(
		'FieldOption',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	public $actsAs = array('FieldOption');
	public $parent = null;
	
	public function setParent($obj) {
		$this->parent = $obj;
	}
	
	public function getModel($obj=null) {
		$model = $this;
		if(is_null($obj)) {
			$obj = $this->parent;
		}
		if(!is_null($obj['params'])) {
			$params = (array) json_decode($obj['params']);
			if(isset($params['model'])) {
				$model = ClassRegistry::init($params['model']);
			}
		}
		return $model;
	}
	
	public function getAllValues($obj=null) {
		$obj = $this->parent;
		$model = $this->getModel();
		$conditions = array();
		if($model->alias === $this->alias) {
			$conditions['field_option_id'] = $obj['id'];
		}
		$data = $model->getAllOptions($conditions);
		return $data;
	}
	
	public function getValue($id) {
		$model = $this->getModel();
		$model->recursive = 0;
		$data = $model->findById($id);
		return $data;
	}
	
	public function saveValue($data) {
		$obj = $this->parent;
		$model = $this->getModel();
		if($model->alias === $this->alias) {
			$data[$model->alias]['field_option_id'] = $obj['id'];
		}
		return $model->save($data);
	}
	
	public function getFields() {
		$model = $this->getModel();
		$data = $model->getOptionFields();
		return $data;
	}
	
	public function getHeader() {
		$header = $this->parent['parent'];
		$header .= (count($header) > 0 ? ' - ' : '') . $this->parent['name'];
		return $header;
	}
}
?>