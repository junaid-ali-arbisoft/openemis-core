<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserIdentitiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('IdentityTypes', ['className' => 'IdentityTypes']);
	}

	public function beforeAction() {
		$this->fields['identity_type_id']['type'] = 'select';
		$this->fields['identity_type_id']['options'] = $this->IdentityTypes->getList();
	}

	public function validationDefault(Validator $validator)
	{

		// 'identity_type_id' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'required' => true,
		// 		'message' => 'Please select a Type'
		// 	)
		// ),
		// 'number' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'required' => true,
		// 		'message' => 'Please enter a valid Number'
		// 	)
		// ),
		// 'issue_location' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'message' => 'Please enter a valid Issue Location'
		// 	)
		// ),
		// 'issue_date' => array(
		// 	'comparison' => array(
		// 		'rule' => array('compareDate', 'expiry_date'),
		// 		'allowEmpty' => true,
		// 		'message' => 'Issue Date Should be Earlier Than Expiry Date'
		// 	)
		// ),
		// 'expiry_date' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'message' => 'Expiry Date Is Required'
		// 	)
		// )
		// return $validator->add('issue_location', 'custom', [
		//     'rule' => [$this, 'customFunction'],
		//     'message' => 'asd'
		// ]);

		return $validator
			->requirePresence('identity_type_id')
			->notEmpty('identity_type_id', 'Please select a Type')
			->requirePresence('number')
			->notEmpty('number', 'Please enter a valid Number')
			->allowEmpty('issue_location')
			// ->add('issue_location', 'custom', [
			// 	'rule' => [$this, 'customFunction'],
			// 	'message' => 'assd'
			// ])
			->notEmpty('expiry_date', 'Expiry Date Is Required')
		;
	}

	// To check start date is earlier than end date from start date field
	// public function compareDate(Model $model, $field = array(), $compareField = null, $equals = false) {
	// 	try {
	// 		$startDate = new DateTime(current($field));
	// 	} catch (Exception $e) {
	// 	    return 'Please input a proper date';
	// 		exit(1);
	// 	}
	// 	if($compareField) {
	// 		$options = array('equals' => $equals, 'reverse' => false);
	// 		return $this->doCompareDates($model, $startDate, $compareField, $options);
	// 	} else {
	// 		return true;
	// 	}
	// }

	public function customFunction($value,$context){
        //some logic here
        return false;
    }

}