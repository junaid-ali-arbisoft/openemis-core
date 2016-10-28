<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionRoomsTable extends AppTable {
	use OptionsTrait;
	const UPDATE_DETAILS = 1;	// In Use
	const END_OF_USAGE = 2;
	const CHANGE_IN_ROOM_TYPE = 3;

	private $Levels = null;
	private $levelOptions = [];
	private $roomLevel = null;

	private $canUpdateDetails = true;
	private $currentAcademicPeriod = null;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('RoomStatuses', ['className' => 'Infrastructure.RoomStatuses']);
		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_infrastructure_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RoomTypes', ['className' => 'Infrastructure.RoomTypes']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
		$this->belongsTo('PreviousRooms', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'previous_room_id']);

		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'infrastructure_custom_field_id',
			'tableColumnKey' => null,
			'tableRowKey' => null,
			'fieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'formKey' => 'infrastructure_custom_form_id',
			'filterKey' => 'infrastructure_custom_filter_id',
			'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
			'formFilterClass' => ['className' => 'Infrastructure.RoomCustomFormsFilters'],
			'recordKey' => 'institution_room_id',
			'fieldValueClass' => ['className' => 'Infrastructure.RoomCustomFieldValues', 'foreignKey' => 'institution_room_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => null
		]);
		$this->addBehavior('Institution.InfrastructureShift');

		$this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
		$this->levelOptions = $this->Levels->getOptions(['keyField' => 'id', 'valueField' => 'name']);
		$this->roomLevel = $this->Levels->getFieldByCode('ROOM', 'id');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
			->add('code', [
	    		'ruleUnique' => [
			        'rule' => ['validateUnique', ['scope' => ['start_date', 'institution_id']]],
			        'provider' => 'table'
			    ]
		    ])
		    ->add('start_date', [
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id']
				]
			])
			->add('end_date', [
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id']
				],
				'ruleCompareDateReverse' => [
					'rule' => ['compareDateReverse', 'start_date', true]
				]
			])
			->add('new_start_date', [
				'ruleCompareDateReverse' => [
					'rule' => ['compareDateReverse', 'start_date', false]
				]
			])
			->requirePresence('new_room_type', function ($context) {
				if (array_key_exists('change_type', $context['data'])) {
					$selectedEditType = $context['data']['change_type'];
					if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
						return true;
					}
				}

				return false;
			})
			->requirePresence('new_start_date', function ($context) {
				if (array_key_exists('change_type', $context['data'])) {
					$selectedEditType = $context['data']['change_type'];
					if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
						return true;
					}
				}

				return false;
			})
		;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew() && $entity->has('change_type')) {
			$editType = $entity->change_type;
			$statuses = $this->RoomStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
			$functionKey = Inflector::camelize(strtolower($statuses[$editType]));
			$functionName = "process$functionKey";

			if (method_exists($this, $functionName)) {
				$event->stopPropagation();
				$this->$functionName($entity);
			}
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		// logic to copy custom fields (general only) where new room is created when change in room type
		$this->processCopy($entity);
	}

	public function onGetInfrastructureLevel(Event $event, Entity $entity) {
		return $this->levelOptions[$this->roomLevel];
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'institution_id') {
			return __('Owner');
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

    	// unset edit_type so that will always default to Update Details
    	foreach ($buttons as $action => $attr) {
    		if (array_key_exists('url', $attr) && array_key_exists('edit_type', $attr['url'])) {
    			unset($buttons[$action]['url']['edit_type']);
    		}
    	}

    	return $buttons;
    }

    public function beforeAction(Event $event) {
    	// For breadcrumb to build the baseUrl
		$this->controller->set('breadcrumbPlugin', 'Institution');
		$this->controller->set('breadcrumbController', 'Institutions');
		$this->controller->set('breadcrumbAction', 'Infrastructures');
		// End
    }

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'room_type_id', 'room_status_id']);

		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('infrastructure_level', ['after' => 'name']);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('institution_infrastructure_id', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);
		$this->ControllerAction->field('previous_room_id', ['visible' => false]);

		$toolbarElements = [];
		$toolbarElements = $this->addBreadcrumbElement($toolbarElements);
		$toolbarElements = $this->addControlFilterElement($toolbarElements);
		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
	{
		// get the list of owner institution id
        $ownerInstitutionIds = $this->getOwnerInstitutionId();

        if (!empty($ownerInstitutionIds)) {
        	$conditions = [];
            $conditions[$this->aliasField('institution_id IN ')] = $ownerInstitutionIds;
            $query->where($conditions, [], true);
        }

		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('institution_infrastructure_id') => $parentId]);
		} else {
			$query->where([$this->aliasField('institution_infrastructure_id IS NULL')]);
		}

		// Academic Period
		list($periodOptions, $selectedPeriod) = array_values($this->getPeriodOptions());
		$query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
		$this->controller->set(compact('periodOptions', 'selectedPeriod'));
		// End

		// Room Types
		list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
		if ($selectedType != '-1') {
			$query->where([$this->aliasField('room_type_id') => $selectedType]);
		}
		$this->controller->set(compact('typeOptions', 'selectedType'));
		// End

		// Room Statuses
		list($statusOptions, $selectedStatus) = array_values($this->getStatusOptions([
			'conditions' => [
				'code IN' => ['IN_USE', 'END_OF_USAGE']
			],
			'withAll' => true
		]));
		if ($selectedStatus != '-1') {
			$query->where([$this->aliasField('room_status_id') => $selectedStatus]);
		} else {
			// default show In Use and End Of Usage
			$query->matching('RoomStatuses', function ($q) {
				return $q->where([
					'RoomStatuses.code IN' => ['IN_USE', 'END_OF_USAGE']
				]);
			});
		}
		$this->controller->set(compact('statusOptions', 'selectedStatus'));
		// End

		$options['order'] = [
			$this->aliasField('code') => 'asc',
			$this->aliasField('name') => 'asc'
		];
	}

	public function indexAfterAction(Event $event, $data) {
		$session = $this->request->session();

		$sessionKey = $this->registryAlias() . '.warning';
		if ($session->check($sessionKey)) {
			$warningKey = $session->read($sessionKey);
			$this->Alert->warning($warningKey);
			$session->delete($sessionKey);
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['AcademicPeriods', 'RoomTypes', 'InfrastructureConditions']);
	}

	public function editBeforeAction(Event $event) {
		$session = $this->request->session();

		$sessionKey = $this->registryAlias() . '.warning';
		if ($session->check($sessionKey)) {
			$warningKey = $session->read($sessionKey);
			$this->Alert->warning($warningKey);
			$session->delete($sessionKey);
		}
	}

	public function editAfterQuery(Event $event, Entity $entity) {
		list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

		$session = $this->request->session();
		$sessionKey = $this->registryAlias() . '.warning';
		if (!$isEditable) {
			$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
			$endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

			if ($entity->room_status_id == $inUseId) {
				$session->write($sessionKey, $this->aliasField('in_use.restrictEdit'));
			} else if ($entity->room_status_id == $endOfUsageId) {
				$session->write($sessionKey, $this->aliasField('end_of_usage.restrictEdit'));
			}

			$url = $this->ControllerAction->url('index');
			$event->stopPropagation();
			return $this->controller->redirect($url);
		} else {
			$selectedEditType = $this->request->query('edit_type');
			if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$today = new DateTime();
				$diff = date_diff($entity->start_date, $today);

				// Not allowed to change room type in the same day
				if ($diff->days == 0) {
					$session->write($sessionKey, $this->aliasField('change_in_room_type.restrictEdit'));

					$url = $this->ControllerAction->url('edit');
					$url['edit_type'] = self::UPDATE_DETAILS;
					$event->stopPropagation();
					return $this->controller->redirect($url);
				}
			}
		}
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));
		
		if (!$isDeletable) {
			$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
			$endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

			$session = $this->request->session();
			$sessionKey = $this->registryAlias() . '.warning';
			if ($entity->room_status_id == $inUseId) {
				$session->write($sessionKey, $this->aliasField('in_use.restrictDelete'));
			} else if ($entity->room_status_id == $endOfUsageId) {
				$session->write($sessionKey, $this->aliasField('end_of_usage.restrictDelete'));
			}

			$url = $this->ControllerAction->url('index');
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}

    	$extra['excludedModels'] = [$this->CustomFieldValues->alias()];
    }

	public function addEditBeforeAction(Event $event) {
		$toolbarElements = $this->addBreadcrumbElement();
		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$selectedEditType = $this->request->query('edit_type');
		if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
			foreach ($this->fields as $field => $attr) {
				if ($this->startsWith($field, 'custom_') || $this->startsWith($field, 'section_')) {
					$this->fields[$field]['visible'] = false;
				}
			}
		}
	}

	public function onUpdateFieldChangeType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add') {
			$attr['visible'] = false;
		} else if ($action == 'edit') {
			$editTypeOptions = $this->getSelectOptions($this->aliasField('change_types'));
			$selectedEditType = $this->queryString('edit_type', $editTypeOptions);
			$this->advancedSelectOptions($editTypeOptions, $selectedEditType);
			$this->controller->set(compact('editTypeOptions'));

			if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$this->canUpdateDetails = false;
			}

			$attr['type'] = 'element';
			$attr['element'] = 'Institution.Room/change_type';

			$this->controller->set(compact('editTypeOptions'));
		}

		return $attr;
	}

	public function onUpdateFieldRoomStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['type'] = 'select';
		} else if ($action == 'add') {
			$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
			$attr['value'] = $inUseId;
		}

		return $attr;
	}

	public function onUpdateFieldInstitutionInfrastructureId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$entity = $attr['entity'];

			$attr['type'] = 'hidden';
			$parentId = $entity->institution_infrastructure_id;
			if (!empty($parentId)) {
				$list = $this->Parents->findPath(['for' => $parentId, 'withLevels' => true]);
			} else {
				$list = [];
			}

			$field = 'institution_infrastructure_id';
			$after = $field;
			foreach ($list as $key => $infrastructure) {
				$this->ControllerAction->field($field.$key, [
					'type' => 'readonly',
					'attr' => ['label' => $infrastructure->_matchingData['Levels']->name],
					'value' => $infrastructure->code_name,
					'after' => $after
				]);
				$after = $field.$key;
			}
		} else if ($action == 'add' || $action == 'edit') {
			$parentId = $this->request->query('parent');

			if (is_null($parentId)) {
				$attr['type'] = 'hidden';
				$attr['value'] = null;
			} else {
				$attr['type'] = 'readonly';
				$attr['value'] = $parentId;
				$attr['attr']['value'] = $this->Parents->getParentPath($parentId);
			}
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
			$this->currentAcademicPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);

			$attr['type'] = 'readonly';
			$attr['value'] = $currentAcademicPeriodId;
			$attr['attr']['value'] = $this->currentAcademicPeriod->name;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];
			$this->currentAcademicPeriod = $entity->academic_period;

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->academic_period->id;
			$attr['attr']['value'] = $entity->academic_period->name;
		}

		return $attr;
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'index' || $action == 'view') {
			if (!empty($this->getOwnerInstitutionId())) {
				$attr['type'] = 'select';
			}
		}

		return $attr;
	}

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$parentId = $request->query('parent');
			$autoGenerateCode = $this->getAutoGenerateCode($parentId);

			$attr['attr']['default'] = $autoGenerateCode;
			$attr['type'] = 'readonly';
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}

		return $attr;
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if (!$this->canUpdateDetails) {
				$attr['type'] = 'readonly';
			}
		}

		return $attr;
	}

	public function onUpdateFieldRoomTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changeRoomType';
		} else if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::END_OF_USAGE) {
				$attr['type'] = 'hidden';
			} else {
				$entity = $attr['entity'];

				$attr['type'] = 'readonly';
				$attr['value'] = $entity->room_type->id;
				$attr['attr']['value'] = $entity->room_type->name;
			}
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$startDate = $this->currentAcademicPeriod->start_date->format('d-m-Y');
			/* restrict Start Date from start until end of academic period
			$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');
			*/
			// temporary restrict until today until have better solution
			$today = new DateTime();
			$endDate = $today->format('d-m-Y');

			$attr['date_options']['startDate'] = $startDate;
			$attr['date_options']['endDate'] = $endDate;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->start_date->format('Y-m-d');
			$attr['attr']['value'] = $this->formatDate($entity->start_date);
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['visible'] = false;
		} else if ($action == 'add') {
			$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

			$attr['type'] = 'hidden';
			$attr['value'] = $endDate;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::END_OF_USAGE) {
				/* restrict End Date from start date until end of academic period
				$startDate = $entity->start_date->format('d-m-Y');
				$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

				$attr['date_options']['startDate'] = $startDate;
				$attr['date_options']['endDate'] = $endDate;
				*/

				// temporary restrict to today until have better solution
				$today = new DateTime();

				$attr['type'] = 'readonly';
				$attr['value'] = $today->format('Y-m-d');
				$attr['attr']['value'] = $this->formatDate($today);
			} else {
				$attr['type'] = 'hidden';
				$attr['value'] = $entity->end_date->format('Y-m-d');
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureConditionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if (!$this->canUpdateDetails) {
				$attr['type'] = 'hidden';
			}
		}

		return $attr;
	}

	public function onUpdateFieldPreviousRoomId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function onUpdateFieldNewRoomType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$roomTypeOptions = $this->RoomTypes
					->find('list')
					->find('visible')
					->where([
						$this->RoomTypes->aliasField('id <>') => $entity->room_type_id
					])
					->toArray();

				$attr['visible'] = true;
				$attr['options'] = $roomTypeOptions;
				$attr['select'] = false;
			}
		}

		return $attr;
	}

	public function onUpdateFieldNewStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				/* restrict End Date from start date until end of academic period
				$startDateObj = $entity->start_date->copy();
				$startDateObj->addDay();

				$startDate = $startDateObj->format('d-m-Y');
				$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

				$attr['visible'] = true;
				$attr['null'] = false;	// for asterisk to appear
				$attr['date_options']['startDate'] = $startDate;
				$attr['date_options']['endDate'] = $endDate;
				*/

				// temporary restrict to today until have better solution
				$today = new DateTime();

				$attr['visible'] = true;
				$attr['null'] = false;	// for asterisk to appear
				$attr['type'] = 'readonly';
				$attr['value'] = $today->format('Y-m-d');
				$attr['attr']['value'] = $this->formatDate($today);
			}
		}

		return $attr;
	}

	public function addEditOnChangeRoomType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('room_type_id', $request->data[$this->alias()])) {
					$selectedType = $request->data[$this->alias()]['room_type_id'];
					$request->query['type'] = $selectedType;
				}

				if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_field_values']);
				}
			}
		}
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->setFieldOrder([
			'change_type', 'institution_infrastructure_id', 'academic_period_id', 'institution_id', 'code', 'name', 'room_type_id', 'room_status_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_room_id', 'new_room_type', 'new_start_date'
		]);

		$this->ControllerAction->field('change_type');
		$this->ControllerAction->field('room_status_id', ['type' => 'hidden']);
		$this->ControllerAction->field('institution_infrastructure_id', ['entity' => $entity]);
		$this->ControllerAction->field('academic_period_id', ['entity' => $entity]);
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('code');
		$this->ControllerAction->field('name');
		$this->ControllerAction->field('room_type_id', ['type' => 'select', 'entity' => $entity]);
		$this->ControllerAction->field('start_date', ['entity' => $entity]);
		$this->ControllerAction->field('end_date', ['entity' => $entity]);
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
		$this->ControllerAction->field('previous_room_id', ['type' => 'hidden']);
		$this->ControllerAction->field('new_room_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
		$this->ControllerAction->field('new_start_date', ['type' => 'date', 'visible' => false, 'entity' => $entity]);
	}

	private function getAutoGenerateCode($parentId) {
		$codePrefix = '';
		$lastSuffix = '00';
		$conditions = [];
		// has Parent then get the ID of the parent then followed by counter
		$parentData = $this->Parents->find()
			->where([
				$this->Parents->aliasField($this->Parents->primaryKey()) => $parentId
			])
			->first();

		$codePrefix = $parentData->code;

		// $conditions[] = $this->aliasField('code')." LIKE '" . $codePrefix . "%'";
		$lastRecord = $this->find()
			->where([
				$this->aliasField('institution_infrastructure_id') => $parentId,
				$this->aliasField('code')." LIKE '" . $codePrefix . "%'"
			])
			->order($this->aliasField('code DESC'))
			->first();

		if (!empty($lastRecord)) {
			$lastSuffix = str_replace($codePrefix, "", $lastRecord->code);
		}

		$codeSuffix = intval($lastSuffix) + 1;

		// if 1 character prepend '0'
		$codeSuffix = (strlen($codeSuffix) == 1) ? '0'.$codeSuffix : $codeSuffix;
		$autoGenerateCode = $codePrefix . $codeSuffix;

		return $autoGenerateCode;
	}

	private function addBreadcrumbElement($toolbarElements=[]) {
		$parentId = $this->request->query('parent');
		$crumbs = $this->Parents->findPath(['for' => $parentId]);
		$toolbarElements[] = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => compact('crumbs'), 'options' => []];

		return $toolbarElements;
	}

	private function addControlFilterElement($toolbarElements=[]) {
		$toolbarElements[] = ['name' => 'Institution.Room/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => []];

		return $toolbarElements;
	}

	private function checkIfCanEditOrDelete($entity) {
		$isEditable = true;
    	$isDeletable = true;

		$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
		$endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

		if ($entity->room_status_id == $inUseId) {	// If is in use, not allow to delete if the rooms is appear in other academic period
			$count = $this
    			->find()
    			->where([
    				$this->aliasField('previous_room_id') => $entity->id
    			])
    			->count();

			if ($count > 0) {
    			$isEditable = false;
    		}

    		$count = $this
    			->find()
    			->where([$this->aliasField('code') => $entity->code])
    			->count();

    		if ($count > 1) {
    			$isDeletable = false;
    		}
    	} else if ($entity->room_status_id == $endOfUsageId) {	// If already end of usage, not allow to edit or delete
			$isEditable = false;
    		$isDeletable = false;
    	}

		return compact('isEditable', 'isDeletable');
	}

    private function updateRoomStatus($code, $conditions) {
    	$roomStatuses = $this->RoomStatuses->findCodeList();
		$status = $roomStatuses[$code];

		$entity = $this->find()->where([$conditions])->first();
		$entity->room_status_id = $status;
		$this->save($entity);
	}

	private function processEndOfUsage($entity) {
		$where = ['id' => $entity->id];
		$this->updateRoomStatus('END_OF_USAGE', $where);

		$url = $this->ControllerAction->url('index');
		return $this->controller->redirect($url);
	}

	private function processChangeInRoomType($entity) {
		$newStartDateObj = new Date($entity->new_start_date);
		$endDateObj = $newStartDateObj->copy();
		$endDateObj->addDay(-1);
		$newRoomTypeId = $entity->new_room_type;

		$oldEntity = $this->find()->where(['id' => $entity->id])->first();
		$newRequestData = $oldEntity->toArray();

		// Update old entity
		$oldEntity->end_date = $endDateObj;

		$where = ['id' => $oldEntity->id];
		$this->updateRoomStatus('CHANGE_IN_ROOM_TYPE', $where);
		$this->save($oldEntity);
		// End

		// Update new entity
		$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
		foreach ($ignoreFields as $key => $field) {
			unset($newRequestData[$field]);
		}
		$newRequestData['start_date'] = $newStartDateObj;
		$newRequestData['room_type_id'] = $newRoomTypeId;
		$newRequestData['previous_room_id'] = $oldEntity->id;
		$newEntity = $this->newEntity($newRequestData, ['validate' => false]);
		$newEntity = $this->save($newEntity);
		// End

		$url = $this->ControllerAction->url('edit');
		unset($url['type']);
		unset($url['edit_type']);
		$url[1] = $newEntity->id;
		return $this->controller->redirect($url);
	}

	public function getPeriodOptions($params=[]) {
		$periodOptions = $this->AcademicPeriods->getYearList();
		if (is_null($this->request->query('period_id'))) {
			$this->request->query['period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedPeriod = $this->queryString('period_id', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);

		return compact('periodOptions', 'selectedPeriod');
	}

	public function getTypeOptions($params=[]) {
		$withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

		$typeOptions = $this->RoomTypes
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->find('visible')
			->toArray();
		if($withAll && count($typeOptions) > 1) {
			$typeOptions = ['-1' => __('All Room Types')] + $typeOptions;
		}
		$selectedType = $this->queryString('type', $typeOptions);
		$this->advancedSelectOptions($typeOptions, $selectedType);

		return compact('typeOptions', 'selectedType');
	}

	public function getStatusOptions($params=[]) {
		$conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
		$withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

		$statusOptions = $this->RoomStatuses
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where($conditions)
			->toArray();
		if($withAll && count($statusOptions) > 1) {
			$statusOptions = ['-1' => __('All Statuses')] + $statusOptions;
		}
		$selectedStatus = $this->queryString('status', $statusOptions);
		$this->advancedSelectOptions($statusOptions, $selectedStatus);

		return compact('statusOptions', 'selectedStatus');
	}

	public function processCopy(Entity $entity) {
		// if is new and room status of previous room usage is change in room type then copy all general custom fields
		if ($entity->isNew()) {
			if ($entity->has('previous_room_id') && $entity->previous_room_id != 0) {
				$copyFrom = $entity->previous_room_id;
				$copyTo = $entity->id;

				$previousEntity = $this->get($copyFrom);
				$changeInRoomTypeId = $this->RoomStatuses->getIdByCode('CHANGE_IN_ROOM_TYPE');

				if ($previousEntity->room_status_id == $changeInRoomTypeId) {
					// third parameters set to true means copy general only
					$this->copyCustomFields($copyFrom, $copyTo, true);
				}
			}
		}
	}
}