<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LanguagesTable extends Table {
	public function initialize(array $config) {
		$this->table('field_option_values');
		$this->addBehavior('FieldOptionValues');
		$this->hasMany('UserLanguages', ['className' => 'UserLanguages']);
	}
}