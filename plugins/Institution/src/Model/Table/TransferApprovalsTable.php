<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class TransferApprovalsTable extends AppTable {
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
    	$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
    	return $events;
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
		if ($action == 'edit') {
			$selectedInstitution = $request->data[$this->alias()]['institution_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->name;
		}

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$selectedProgramme = $request->data[$this->alias()]['education_programme_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationProgrammes->get($selectedProgramme)->cycle_programme_name;
		}

		return $attr;
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$startDate = $request->data[$this->alias()]['start_date'];

			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];

			$attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
		}

		return $attr;
	}

	public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$selectedReason = $request->data[$this->alias()]['student_transfer_reason_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->StudentTransferReasons->get($selectedReason)->name;
		}

		return $attr;
	}

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$attr['attr']['disabled'] = 'disabled';
		}

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'edit') {
			$toolbarButtons['back']['url']['action'] = 'index';
			unset($toolbarButtons['back']['url'][0]);
			unset($toolbarButtons['back']['url'][1]);
		}
	}

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
    	if ($AccessControl->check(['Dashboard', 'TransferApprovals', 'edit'])) {
    		// $institutionIds = $AccessControl->getInstitutionsByUser(null, ['Dashboard', 'TransferApprovals', 'edit']);
			$resultSet = $this
				->find()
				->where([
					$this->aliasField('status') => 0
				])
				->contain(['Users', 'Institutions', 'EducationProgrammes', 'PreviousInstitutions', 'ModifiedUser', 'CreatedUser'])
				->order([
					$this->aliasField('created')
				])
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Transfer of student (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => 'TransferApprovals',
					'edit',
					$obj->id
				];

				$data[] = [
					'request_title' => ['title' => $requestTitle, 'url' => $url],
					'receive_date' => date('Y-m-d', strtotime($obj->modified)),
					'due_date' => '<i class="fa fa-minus"></i>',
					'requester' => $obj->created_user->username,
					'type' => __('Student Transfer')
				];
			}
    	}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			$buttons[0] = [
				'name' => '<i class="fa fa-check"></i> ' . __('Approve'),
				'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'approve']
			];

			$buttons[1] = [
				'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
				'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
			];
		}
	}

	public function editOnApprove(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$transferEntity = $this->newEntity($data[$this->alias()]);
		if ($this->save($transferEntity)) {
		} else {
			$this->log($transferEntity->errors(), 'debug');
		}

		// Update status to Transferred in previous school
    	$institutionId = $entity->previous_institution_id;
		$selectedStudent = $entity->security_user_id;
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

		$status = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'TRANSFERRED'])
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
		// End

		// Update status to 1 => approve
		$this->updateAll(
			['status' => 1],
			[
				'id' => $entity->id
			]
		);
		// End

		// Add Student to new school
		$currentStatus = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
			->first()
			->id;

		$requestData = [
			'start_date' => $transferEntity->start_date,
			'start_year' => date("Y", strtotime($transferEntity->start_date)),
			'end_date' => $transferEntity->end_date,
			'end_year' => date("Y", strtotime($transferEntity->end_date)),
			'security_user_id' => $transferEntity->security_user_id,
			'student_status_id' => $currentStatus,
			'institution_site_id' => $transferEntity->institution_id,
			'education_programme_id' => $transferEntity->education_programme_id
		];

		$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
		$studentEntity = $InstitutionSiteStudents->newEntity($requestData);

		if ($InstitutionSiteStudents->save($studentEntity)) {
		} else {
			$this->log($studentEntity->errors(), 'debug');
		}
		// End

		$this->Alert->success('TransferApprovals.approve');
		$event->stopPropagation();

		return $this->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
	}

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Update status to Current in previous school
    	$institutionId = $entity->previous_institution_id;
		$selectedStudent = $entity->security_user_id;
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

		$status = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
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
		// End

		// Update status to 2 => reject
		$this->updateAll(
			['status' => 2],
			[
				'id' => $entity->id
			]
		);
		// End

		$this->Alert->success('TransferApprovals.reject');
		$event->stopPropagation();

		return $this->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
	}
}