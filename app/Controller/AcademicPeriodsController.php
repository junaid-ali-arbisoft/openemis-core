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

App::uses('AppController', 'Controller');

class AcademicPeriodsController extends AppController {
	public $uses = array('AcademicPeriod', 'AcademicPeriodLevel');
	
	public $modules = array(
		'AcademicPeriod',
		'AcademicPeriodLevel',
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'AcademicPeriods', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Academic Periods', array('controller' => 'AcademicPeriods', 'action' => 'index'));
		
		$this->set('selectedAction', $this->action);
	}
	
	public function recover($i) {
		$this->autoRender = false;
		$params = array('AcademicPeriod', 'run', $i);
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
		$nohup = 'nohup %s > %stmp/logs/processes.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, APP);
		$this->log($shellCmd, 'debug');
		pr($shellCmd);
		$pid = exec($shellCmd);
		pr($pid);
	}
	
	public function index() {
		return $this->redirect(array('action' => 'AcademicPeriod'));
	}
}