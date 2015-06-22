<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AwardsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_awards');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}

	public function beforeAction() {
		// $this->fields['award']['attr']['onfocus'] = 'jsForm.autocomplete(this)';
		// $this->fields['award']['attr']['autocompleteURL'] = $this->controller->name.'/'.$this->alias.'/autocompleteAward';

		// $this->fields['issuer']['attr']['onfocus'] = 'jsForm.autocomplete(this)';
		// $this->fields['issuer']['attr']['autocompleteURL'] = $this->controller->name.'/'.$this->alias.'/autocompleteIssuer';
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	// public function autocompleteAward() {
	// 	if ($this->request->is('ajax')) {
	// 		$this->render = false;
	// 		$this->layout = 'ajax';
	// 		$search = $this->controller->params->query['term'];
	// 		$data = $this->autocomplete($search, 'award');
	// 		return json_encode($data);
	// 	}
	// }

	// public function autocompleteIssuer() {
	// 	if ($this->request->is('ajax')) {
	// 		$this->render = false;
	// 		$this->layout = 'ajax';
	// 		$search = $this->controller->params->query['term'];
	// 		$data = $this->autocomplete($search, 'issuer');
	// 		return json_encode($data);
	// 	}
	// }

	// public function autocomplete($search, $type='award') {
	// 	$field = 'award';
	// 	if($type=='issuer'){
	// 		$field = 'issuer';
	// 	}
	// 	$search = sprintf('%%%s%%', $search);
	// 	$list = $this->find('all', array(
	// 		'recursive' => -1,
	// 		'fields' => array('DISTINCT '.$this->alias.'.' . $field),
	// 		'conditions' => array($this->alias.'.' . $field . ' LIKE' => $search
	// 		),
	// 		'order' => array($this->alias.'.' . $field)
	// 	));
		
	// 	$data = array();
		
	// 	foreach($list as $obj) {
	// 		$awardField = $obj[$this->alias][$field];
	// 		$data[] = $awardField;
	// 		// $data[] = array(
	// 		// 	'label' => trim($studentAwardField),
	// 		// 	'value' => array($field => $studentAwardField)
	// 		// );
	// 	}

	// 	return $data;
	// }
}