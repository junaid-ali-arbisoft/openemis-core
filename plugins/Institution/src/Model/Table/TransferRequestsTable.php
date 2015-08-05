<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class TransferRequestsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_student_transfers');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentTransferReasons', ['className' => 'FieldOption.StudentTransferReasons']);
	}

	public function validationDefault(Validator $validator) {
		return $validator
 	        ->add('end_date', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'start_date', false]
	    	    ])
	        ;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$institutionId = $entity->previous_institution_id;
			$selectedStudent = $entity->security_user_id;
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

			$status = $StudentStatuses
				->find()
				->where([$StudentStatuses->aliasField('code') => 'PENDING_TRANSFER'])
				->first()
				->id;

			$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
			$InstitutionSiteStudents->updateAll(
				['student_status_id' => $status],
				[
					'institution_site_id' => $institutionId,
					'security_user_id' => $selectedStudent
				]
			);

			$this->Alert->success('TransferRequests.request');

			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['add']['url'];
			$action['action'] = $Students->alias();
			$action[0] = 'view';
			$action[1] = $selectedStudent;

			return $this->controller->redirect($action);
		}
    }

	public function addOnInitialize(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institutions.id');
		$selectedStudent = $this->Session->read($this->alias().'.security_user_id');

		$InstitutionSiteStudents = TableRegistry::get('Institutions.InstitutionSiteStudents');
		$student = $InstitutionSiteStudents
			->find()
			->where([
				$InstitutionSiteStudents->aliasField('institution_site_id') => $institutionId,
				$InstitutionSiteStudents->aliasField('security_user_id') => $selectedStudent
			])
			->first();

		$entity->security_user_id = $selectedStudent;
		$entity->education_programme_id = $student->education_programme_id;
		$entity->start_date = date('Y-m-d', strtotime($student->start_date));
		$entity->end_date = date('Y-m-d', strtotime($student->end_date));
		$entity->previous_institution_id = $institutionId;

		$this->request->data[$this->alias()]['security_user_id'] = $entity->security_user_id;
		$this->request->data[$this->alias()]['education_programme_id'] = $entity->education_programme_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['previous_institution_id'] = $entity->previous_institution_id;
	}

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->alias().'.security_user_id')) {

			$this->ControllerAction->field('student');
			$this->ControllerAction->field('security_user_id');
			$this->ControllerAction->field('institution_id');
			$this->ControllerAction->field('education_programme_id');
			$this->ControllerAction->field('status');
			$this->ControllerAction->field('start_date');
			$this->ControllerAction->field('end_date');
			$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
			$this->ControllerAction->field('comment');
			$this->ControllerAction->field('previous_institution_id');

			$this->ControllerAction->setFieldOrder([
				'student',
				'institution_id', 'education_programme_id',
				'status', 'start_date', 'end_date', 
				'student_transfer_reason_id', 'comment',
				'previous_institution_id'
			]);
		} else {
			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['index']['url'];
			$action['action'] = $Students->alias();

			return $this->controller->redirect($action);
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		$this->request->data[$this->alias()]['security_user_id'] = $entity->security_user_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['education_programme_id'] = $entity->education_programme_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$transferEntity = $this->newEntity($data[$this->alias()]);
		if ($this->save($transferEntity)) {
		} else {
			$this->log($transferEntity->errors(), 'debug');
		}

		$Students = TableRegistry::get('Institution.Students');
		$action = $this->ControllerAction->buttons['edit']['url'];
		$action['action'] = $Students->alias();
		$action[0] = 'view';
		$action[1] = $transferEntity->security_user_id;

		return $this->controller->redirect($action);
    }

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('student');
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('education_programme_id');
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('end_date');
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('comment');
		$this->ControllerAction->field('previous_institution_id');

		$this->ControllerAction->setFieldOrder([
			'student',
			'institution_id', 'education_programme_id',
			'status', 'start_date', 'end_date',
			'student_transfer_reason_id', 'comment',
			'previous_institution_id'
		]);
	}

	/* to be implemented with custom autocomplete
	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}
	*/

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['security_user_id'];

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['security_user_id'];

		$attr['type'] = 'hidden';
		$attr['attr']['value'] = $selectedStudent;

		return $attr;
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$selectedProgramme = $request->data[$this->alias()]['education_programme_id'];

			$InstitutionSiteProgrammes = TableRegistry::get('Institutions.InstitutionSiteProgrammes');
			$institutionId = $this->Session->read('Institutions.id');
			$institutionOptions = $this->Institutions
				->find('list')
				->join([
					'table' => $InstitutionSiteProgrammes->_table,
					'alias' => $InstitutionSiteProgrammes->alias(),
					'conditions' => [
						$InstitutionSiteProgrammes->aliasField('institution_site_id =') . $this->Institutions->aliasField('id'),
						$InstitutionSiteProgrammes->aliasField('education_programme_id') => $selectedProgramme,
					]
				])
				->where([$this->Institutions->aliasField('id <>') => $institutionId])
				->toArray();

			$attr['type'] = 'select';
			$attr['options'] = $institutionOptions;

			/* to be implemented with custom autocomplete
			$attr['type'] = 'string';
			$attr['attr'] = [
				'class' => 'autocomplete',
				'autocomplete-url' => '/core_v3/Institutions/Transfers/ajaxInstitutionAutocomplete',
				'autocomplete-class' => 'error-message',
				'autocomplete-no-results' => __('No Institution found.'),
				'value' => ''
			];
			*/
		} else if ($action == 'edit') {
			$selectedInstitution = $request->data[$this->alias()]['institution_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->name;
		}

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$selectedProgramme = $request->data[$this->alias()]['education_programme_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationProgrammes->get($selectedProgramme)->cycle_programme_name;
		}

		return $attr;
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$status = 0; // New

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $status;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institutions.id');

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $institutionId;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$startDate = $request->data[$this->alias()]['start_date'];

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
		} else if ($action == 'edit') {
			$startDate = $request->data[$this->alias()]['start_date'];

			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$endDate = $request->data[$this->alias()]['end_date'];

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
		} else if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];

			$attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
		}

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add' || $action == 'edit') {
			$Students = TableRegistry::get('Institution.Students');
			$toolbarButtons['back']['url']['action'] = $Students->alias();
			$toolbarButtons['back']['url'][0] = 'view';
			$toolbarButtons['back']['url'][1] = $this->Session->read($this->alias().'.security_user_id');
		}
	}

	/* to be implemented with custom autocomplete
	public function ajaxInstitutionAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Institutions->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}
	*/
}