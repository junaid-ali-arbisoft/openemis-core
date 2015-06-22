<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', 		 		['className' => 'User.Users', 					'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 	'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes','foreignKey' => 'education_programme_id']);
		$this->belongsTo('StudentStatuses',		['className' => 'FieldOption.StudentStatuses', 	'foreignKey' => 'student_status_id']);
		
		// 'Students.StudentStatus',
		// 'InstitutionSiteProgramme' => array(
		// 	'className' => 'InstitutionSiteProgramme',
		// 	'foreignKey' => false,
		// 	'conditions' => array(
		// 		'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
		// 		'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
		// 	)
		// ),
		// 'EducationProgramme',
		// 'InstitutionSite'

		
		// $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

}