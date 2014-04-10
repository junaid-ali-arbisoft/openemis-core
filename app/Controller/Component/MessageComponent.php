<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright � 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class MessageComponent extends Component {
	public $components = array('Session', 'Message');
	public $alertTypes = array(
		'ok' => 'alert_ok',
		'error' => 'alert_error',
		'info' => 'alert_info',
		'warn' => 'alert_warn'
	);
	
	public $messages = array(
		'general' => array(
			'notExists' => array('type' => 'info', 'msg' => 'The Record does not exist.'),
			'noData' => array('type' => 'info', 'msg' => 'There are no records.'),
			'error' => array('type' => 'error', 'msg' => 'An unexpected error has been encounted. Please contact the administrator for assistance.'),
			'add' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been added successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not added due to errors encountered.')
			),
			'edit' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been updated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not updated due to errors encountered.')
			),
			'delete' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been deleted successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not deleted due to errors encountered.'),
			)
		),
		'security' => array(
			'login' => array(
				'timeout' => array('type' => 'info', 'msg' => 'Your session has timed out. Please login again.'),
				'fail' => array('type' => 'error', 'msg' => 'You have entered an invalid username or password.'),
				'inactive' => array('type' => 'error', 'msg' => 'You are not an authorized user.')
			)
		),
		'search' => array(
			'no_result' => array('type' => 'info', 'msg' => 'No result returned from the search.')
		)
	);
	
	public function get($code) {
		$index = explode('.', $code);
		$message = $this->messages;
		foreach($index as $i) {
			if(isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = '[Message Not Found]';
				break;
			}
		}
		return !is_array($message) ? __($message) : $message;
	}
	
	public function alert($code, $settings=array()) {
		$types = $this->alertTypes;
		$_settings = array(
			'type' => key($types),
			'types' => $types,
			'dismissOnClick' => true,
			'params' => array()
		);
		$_settings = array_merge($_settings, $settings);
		$message = $this->get($code);
		if(!array_key_exists($_settings['type'], $types)) {
			$_settings['type'] = key($types);
		} else {
			$_settings['type'] = $message['type'];
		}
		if(!empty($_settings['params'])) {
			$message = vsprintf($message, $_settings['params']);
		}
		$_settings['message'] = $message['msg'];
		$this->Session->write('_alert', $_settings);
	}
}