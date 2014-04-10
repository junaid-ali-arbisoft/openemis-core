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
class TrainingModeDelivery extends AppModel {
	public $hasMany = array('TrainingCourse');
	
	public function getLookupVariables() {
		$lookup = array('Conditions' => array('model' => 'TrainingModeDelivery'));
		return $lookup;
	}

	public function getOptions(){
		$data = $this->find('all', array('recursive' => -1, 'conditions'=>array('visible'=>1), 'order' => array('TrainingModeDelivery.order')));
		$list = array();
		foreach($data as $obj){
			$list[$obj['TrainingModeDelivery']['id']] = $obj['TrainingModeDelivery']['name'];
		}

		return $list;
	}
	
}