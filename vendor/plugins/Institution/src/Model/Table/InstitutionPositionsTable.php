<?php
namespace Institution\Model\Table;

use DateTime;
use DateInterval;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;

class InstitutionPositionsTable extends ControllerActionTable
{
    use OptionsTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        //$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        /*$this->belongsTo('InstitutionStaffs', ['className' => 'Institution.Staff', 'foreignKey' => 'institution_position_id',]);POCOR-5069*/

        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffPositions', ['className' => 'Staff.Positions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferIn', ['className' => 'Institution.StaffTransferIn', 'foreignKey' => 'new_institution_position_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Workflow.Workflow');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportInstitutionPositions']);
        $this->addBehavior('Excel', [
            'pages' => ['index']
        ]);
    }

    public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $transferredTo = $entity->convert_to;
        $securityRole = $this->find()
            ->matching('StaffPositionTitles.SecurityRoles')
            ->where([$this->aliasField('id') => $transferredTo])
            ->select(['security_role_id' => 'SecurityRoles.id'])
            ->hydrate(false)
            ->first();
        $securityRole = $securityRole['security_role_id'];

        $securityGroupUserIds = $this->InstitutionStaff->find()
            ->select([$this->InstitutionStaff->aliasField('security_group_user_id')])
            ->where([$this->InstitutionStaff->aliasField('institution_position_id') => $transferredTo]);

        $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
        $SecurityGroupUsersTable->updateAll(
            ['security_role_id' => $securityRole],
            ['id IN ' => $securityGroupUserIds]
        );
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('position_no', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('position_no', 'ruleNoSpaces', [
                'rule' => 'checkNoSpaces',
                'provider' => 'custom'
            ])
           // ->requirePresence('shift_id')
            /*POCOR-5069 Starts
            ->add('staff_position_grade_id', 'custom', [
                'rule' => function ($value, $context) {
                    $StaffPositionTitlesGrades = TableRegistry::get('Institution.StaffPositionTitlesGrades');
                    $staffPositionTitleId = $context['data']['staff_position_title_id'];

                    $result = $StaffPositionTitlesGrades
                        ->find()
                        ->where([
                            'AND' => [
                                [$StaffPositionTitlesGrades->aliasField('staff_position_title_id') => $staffPositionTitleId],
                                'OR' => [
                                    [$StaffPositionTitlesGrades->aliasField('staff_position_grade_id') => $value],
                                    [$StaffPositionTitlesGrades->aliasField('staff_position_grade_id') => -1]
                                ]
                            ]
                        ])
                        ->all();

                    return !$result->isEmpty();
                },
                'message' => $this->getMessage('Import.staff_title_grade_not_match')
            ])POCOR-5069 Ends*/
            //Remove is_homeroom from position table-POCOR-5070
            // ->requirePresence('is_homeroom', function ($context) {
            //     if (array_key_exists('staff_position_title_id', $context['data']) && strlen($context['data']['staff_position_title_id']) > 0) {
            //         $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
            //         $titleId = $context['data']['staff_position_title_id'];

            //         $titleEntity = $StaffPositionTitles
            //             ->find()
            //             ->select([$StaffPositionTitles->aliasField('type')])
            //             ->where([$StaffPositionTitles->aliasField('id') => $titleId])
            //             ->first();

            //         if (!is_null($titleEntity)) {
            //             $positionType = $titleEntity->type;
            //             return $positionType == 1;
            //         } else {
            //             return false;
            //         }
            //     }
            //     return false;
            // })
            // ->add('is_homeroom', 'ruleCheckHomeRoomTeacherAssignments', [
            //     'rule' => 'checkHomeRoomTeacherAssignments',
            //     'on' => function ($context) {
            //         //trigger validation only when homeroom teacher set to no and edit operation
            //         return ($context['data']['is_homeroom'] == 0 && !$context['newRecord']);
            //     }
            // ])
            // ->add('is_homeroom', 'ruleIsHomeroomEmpty', [
            //     // validations to ensure no value for is_homeroom field, if the selected title type is non-teaching type (0), for position import - POCOR-4258
            //     'rule' => function ($value, $context) {
            //         return strlen($value) == 0;
            //     },
            //     'on' => function ($context) {
            //         if (array_key_exists('staff_position_title_id', $context['data']) && strlen($context['data']['staff_position_title_id']) > 0) {
            //             $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
            //             $titleId = $context['data']['staff_position_title_id'];

            //             $titleEntity = $StaffPositionTitles
            //                 ->find()
            //                 ->select([$StaffPositionTitles->aliasField('type')])
            //                 ->where([$StaffPositionTitles->aliasField('id') => $titleId])
            //                 ->first();

            //             if (!is_null($titleEntity)) {
            //                 $positionType = $titleEntity->type;
            //                 return $positionType == 0;
            //             } else {
            //                 return false;
            //             }
            //         }
            //         return false;
            //     }
            // ])
            ->add('shift_id', 'rulecheckShiftPresent', [ //POCOR-6971
                'rule' => function ($value, $context) {
                    if($value == 0){
                            return 'This field cannot be left empty';
                    }else{
                        return true;
                    }
                }
            ])
            
            ->add('status_id', 'ruleCheckStatusIdValid', [
                'rule' => ['checkStatusIdValid'],
                'provider' => 'table',
                'on' => function ($context) {  
                    if (array_key_exists('action_type', $context['data']) && $context['data']['action_type'] == 'imported') {
                        return true;
                    }
                    return false;
                }
            ]);

        return $validator;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        /*POCOR-5069 Starts
        if ($entity->has('is_homeroom') && $entity->dirty('is_homeroom')) {
            $currIsHomeroom = $entity->is_homeroom;
            // have to find all the staff that is holding this institution position
            $InstitutionStaffTable = $this->InstitutionStaff;
            $staffInvolved = $InstitutionStaffTable->find()
                ->where([
                    $InstitutionStaffTable->aliasField('institution_position_id') => $entity->id,
                    $InstitutionStaffTable->aliasField('security_group_user_id IS NOT NULL')
                ])
                ->where([
                    'OR' => [
                        [function ($exp) use ($InstitutionStaffTable) {
                            return $exp->gte($InstitutionStaffTable->aliasField('end_date'), $InstitutionStaffTable->find()->func()->now('date'));
                        }],
                        [$InstitutionStaffTable->aliasField('end_date').' IS NULL']
                    ]
                ])
                ;
            if (!empty($staffInvolved)) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
                $homeroomSecurityRoleId = $SecurityRoles->getHomeroomRoleId();
                try {
                    $securityGroupId = $this->Institutions->get($entity->institution_id)->security_group_id;
                    foreach ($staffInvolved as $key => $value) {
                        $homeRoomData = [
                            'security_role_id' => $homeroomSecurityRoleId,
                            'security_group_id' => $securityGroupId,
                            'security_user_id' => $value->staff_id
                        ];
                        if ($currIsHomeroom) {
                            // add 1 homeroom value
                            $newHomeroomEntity = $SecurityGroupUsers->newEntity($homeRoomData);
                            $entity = $SecurityGroupUsers->save($newHomeroomEntity);
                        } else {
                            // remove homeroom value - find 1 entry and delete it
                            $homeroomEntity = $SecurityGroupUsers->find()
                                ->where($homeRoomData)
                                ->first();
                            if (!empty($homeroomEntity)) {
                                $SecurityGroupUsers->delete($homeroomEntity);
                            }
                        }
                    }
                } catch (InvalidPrimaryKeyException $ex) {
                    Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $entity->institution_id . ')');
                }
            }
        }POCOR-5069 Ends*/
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('position_no', ['visible' => true]);
        $this->field('staff_position_title_id', [
            'visible' => true,
            'type' => 'select'
        ]);
        $this->field('current_staff_list', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Positions/current',
            'visible' => true
        ]);
        $this->field('past_staff_list', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Positions/past',
            'visible' => true
        ]);
    }

    public function onUpdateFieldPositionNo(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['attr']['value'] = $this->getUniquePositionNo();
            return $attr;
        }
    }

    /*POCOR-5069 starts
    public function onUpdateFieldStaffPositionGradeId(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $positionGradeOptions = [];
            if ($entity->has('staff_position_title_id')) {
                $positionGradeOptions = $this->StaffPositionGrades->getAvailablePositionGrades($entity->staff_position_title_id);
            }
            $attr['options'] = $positionGradeOptions;
        }

        return $attr;
    }


    
    public function onUpdateFieldIsHomeroom(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $visibility = false;
            $requestData = $request->data;

            if ($action == 'add') {
                if (isset($requestData[$this->alias()]) && !empty($requestData[$this->alias()]['staff_position_title_id'])) {
                    $positionTitleId = $requestData[$this->alias()]['staff_position_title_id'];
                    $positionTypeId = $this->StaffPositionTitles->get($positionTitleId)->type;

                    if ($positionTypeId == 1) { // teaching
                        $visibility = true;
                        $attr['options'] = $this->getSelectOptions('general.yesno');
                        $attr['default'] = '';
                    }
                }

            } else if ($action == 'edit') {
                $entity = $attr['entity'];
                $isHomeroom = $entity->is_homeroom;
                $positionTitleId = $entity->staff_position_title_id;
                $positionTypeId = $this->StaffPositionTitles->get($positionTitleId)->type;

                if ($positionTypeId == 1) { // Teaching
                   $visibility = true;
                }

                $attr['type'] = 'select';
                $attr['options'] = $this->getSelectOptions('general.yesno');
                $attr['default'] = $isHomeroom;
            }

            $attr['visible'] = false; //POCOR-5070
        }

        return $attr;
    }

    public function onGetIsHomeroom(Event $event, Entity $entity)
    {
        $isHomeroom = $entity->is_homeroom;
        return $this->getSelectOptions('general.yesno')[$isHomeroom];
    }POCOR-5069 Ends*/

    public function onGetStaffPositionTitleId(Event $event, Entity $entity)
    {
        $types = $this->getSelectOptions('Staff.position_types');
        if ($entity->has('staff_position_title')) {
            return $this->fields['staff_position_title_id']['options'][$entity->staff_position_title->id];
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        /*POCOR-5069 starts $this->field('staff_position_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);POCOR-5069 ends*/

        //POCOR-6971
        $this->field('shift_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        //$this->field('is_homeroom', ['entity' => $entity]); POCOR-5069 

        // POCOR-3003 - [...] decision is to make Position Title not editable on the position edit page
        if ($entity->has('staff_position_title_id')) {
            $types = $this->getSelectOptions('Staff.position_types');
            $staffPositionData = $this->StaffPositionTitles->find()
                ->select(['name', 'type'])
                ->where([$this->StaffPositionTitles->aliasField($this->StaffPositionTitles->primaryKey()) => $entity->staff_position_title_id])
                ->first();
            if (!empty($staffPositionData)) {
                $type = (array_key_exists($staffPositionData->type, $types))? $types[$staffPositionData->type]: null;
                $this->fields['staff_position_title_id']['attr']['value'] = $staffPositionData->name;
                if (!empty($type)) {
                    $this->fields['staff_position_title_id']['attr']['value'] .= ' - ' . $type;
                }
            }
        }
    }

    public function onUpdateFieldStaffPositionTitleId(Event $event, array $attr, $action, $request)
    {
        if (in_array($action, ['edit'])) {
            // POCOR-3003 - [...] decision is to make Position Title not editable on the position edit page
            $attr['type'] = 'readonly';
            return $attr;
        }

        $types = $this->getSelectOptions('Staff.position_types');
        $titles = new ArrayObject();
        if (in_array($action, ['add'])) {
            $userId = $this->Auth->user('id');
            $institutionId = $this->Session->read('Institution.Institutions.id');
            if ($this->AccessControl->isAdmin()) {
                $userId = null;
                $roles = [];
            } else {
                $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            }

            $staffTitleOptions = $this->StaffPositionTitles
                    ->find()
                    ->innerJoinWith('SecurityRoles')
                    ->select([
                        'security_role_id' => 'SecurityRoles.id',
                        'name' => $this->StaffPositionTitles->aliasField('name')])
                    ->order([
                        $this->StaffPositionTitles->aliasField('type') => 'DESC',
                        $this->StaffPositionTitles->aliasField('order'),
                    ])
                    ->autoFields(true)
                    ->toArray();

            // Filter by role previlege
            $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
            $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
            $roleOptions = array_keys($roleOptions);
            $staffTitleRoles = $this->array_column($staffTitleOptions, 'security_role_id');
            $staffTitleOptions = array_intersect_key($staffTitleOptions, array_intersect($staffTitleRoles, $roleOptions));

            // Adding the opt group
            $titles = [];
            foreach ($staffTitleOptions as $title) {
                $type = __($types[$title->type]);
                $titles[$type][$title->id] = $title->name;
            }
        } else {
            $titles = $this->StaffPositionTitles
                ->find()
                ->order([$this->StaffPositionTitles->aliasField('order')])
                ->map(function ($row) use ($types) {
                    // map() is a collection method, it executes the query
                    $row->name_and_type = $row->name . ' - ' . (array_key_exists($row->type, $types) ? $types[$row->type] : $row->type);
                    return $row;
                })
                ->combine('id', 'name_and_type') // combine() is another collection method
                ->toArray(); // Also a collections library method
        }
        $attr['options'] = $titles;
        $attr['onChangeReload'] = true;
        return $attr;
    }

 
    public function getUniquePositionNo($institutionId = null)
    {
        $prefix = '';
        $currentStamp = time();

        if (is_null($institutionId)) {
            $institutionId = $this->Session->read('Institution.Institutions.id');
        }

        $latestPositionEntity = $this
            ->find()
            ->contain(['Institutions'])
            ->order($this->aliasField('id') . ' DESC ')
            ->first();

        if (!is_null($latestPositionEntity)) {
            $latestInstitutionCode = $latestPositionEntity->institution->code;
            $latestPositionNumber = $latestPositionEntity->position_no;
            $list = explode('-', $latestPositionNumber);

            if(count($list) == 3){ //POCOR-7417
                // if position number is auto generated, index 0,1 will be the institution code
                    if ($list[0]."-".$list[1] == $latestInstitutionCode) {
                        $latestTimestamp = $list[2];
                    }
                }else{
                // if position number is auto generated, index 0 will be the institution code
                if ($list[0] == $latestInstitutionCode) {
                    $latestTimestamp = $list[1];
                    if ($list[0] == $latestInstitutionCode) {
                        $latestTimestamp = $list[1];
                    }
                }
            }
        }
        

        $institutionCode = $this->Institutions->get($institutionId)->code;
        $prefix .= $institutionCode;

        // if latest timestamp can be found and the current timestamp is smaller/equal, set to latest + 1
        if (isset($latestTimestamp) && $latestTimestamp >= $currentStamp) {
            $newStamp = $latestTimestamp + 1;
        } else {
            $newStamp = $currentStamp;
        }

        return $prefix.'-'.$newStamp;
    }


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('grade');//PCOOR-5069
        $this->field('homeroom_teacher');//PCOOR-5069
        $this->field('current_staff');

        $this->fields['current_staff_list']['visible'] = false;
        $this->fields['past_staff_list']['visible'] = false;
        $this->fields['shift_id']['visible'] = false; //POCOR-6971

        $this->fields['staff_position_title_id']['sort'] = ['field' => 'StaffPositionTitles.order'];
        //$this->fields['staff_position_grade_id']['sort'] = ['field' => 'StaffPositionGrades.order'];//PCOOR-5069
        $this->fields['assignee_id']['sort'] = ['field' => 'Assignees.first_name'];
        $this->setFieldOrder([
            'position_no', 'staff_position_title_id',
            //'staff_position_grade_id'//PCOOR-5069
        ]);

        if ($extra['auto_search']) {
            $search = $this->getSearchKey();
            if (!empty($search)) {
                $extra['OR'] = [$this->StaffPositionTitles->aliasField('name').' LIKE' => '%' . $search . '%'];
            }
        }
        if (is_null($this->request->query('sort'))) {
            $this->request->query['sort'] = 'created';
            $this->request->query['direction'] = 'desc';
        }
    }

    public function onGetCurrentStaff(Event $event, Entity $entity)
    {
        $value = '';
        $id = $entity->id;

        $currentStaff = $this->getCurrentStaff($id)->toArray();

        if (empty($currentStaff[0])) {
            $value = '-';
        } else {
            foreach ($currentStaff as $singleCurrentStaff) {
                if ($singleCurrentStaff->user->id == end($currentStaff)->user->id) {
                    $value .= $singleCurrentStaff->user->name;
                } else {
                    $value .= $singleCurrentStaff->user->name .', ';
                }
            }
        }

        return $value;
    }
    //PCOOR-5069 Starts
    public function onGetGrade(Event $event, Entity $entity)
    {
        $value = '';
        $id = $entity->id;
        $currentStaff = $this->getCurrentStaff($id)->toArray();
        
        $session = $this->Session;
        $Staff = TableRegistry::get('institution_staff');
        $StaffPositionGrades = TableRegistry::get('staff_position_grades');
        if (empty($currentStaff[0])) {
            $value = '-';
        } else {
            foreach ($currentStaff as $singleCurrentStaff) {
                if ($singleCurrentStaff->user->id == end($currentStaff)->user->id) {
                    $staffId = $singleCurrentStaff->user->id;
                } else {
                    $staffId = $singleCurrentStaff->user->id;
                }
                $currentStaff = $Staff
                                ->find()
                                ->select([
                                    'staff_id'=>  $Staff->aliasField('id'),
                                    'staff_position_grade_id'=> $Staff->aliasField('staff_position_grade_id'),
                                    'staff_position_grade_name'=> $StaffPositionGrades->aliasField('name')
                                ])
                                ->LeftJoin([$StaffPositionGrades->alias() => $StaffPositionGrades->table()],
                                        [
                                            $StaffPositionGrades->aliasField('id') . ' = '. $Staff->aliasField('staff_position_grade_id')
                                        ])
                                ->where([
                                    $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                                    $Staff->aliasField('institution_position_id') => $id,
                                    $Staff->aliasField('staff_id') => $staffId
                                ])->first();
                if(!empty($currentStaff)){
                    $value .= $currentStaff->staff_position_grade_name;
                }
            }
        }

        return $value;
    }

    public function onGetHomeroomTeacher(Event $event, Entity $entity)
    {
        $value = '';
        $id = $entity->id;
        $currentStaff = $this->getCurrentStaff($id)->toArray();
        
        $session = $this->Session;
        $Staff = TableRegistry::get('institution_staff');
        if (empty($currentStaff[0])) {
            $value = '';
        } else {
            foreach ($currentStaff as $singleCurrentStaff) {
                if ($singleCurrentStaff->user->id == end($currentStaff)->user->id) {
                    $staffId = $singleCurrentStaff->user->id;
                } else {
                    $staffId = $singleCurrentStaff->user->id;
                }
                $currentStaff = $Staff
                                ->find()
                                ->select([
                                    'staff_id'=>  $Staff->aliasField('id'),
                                    'is_homeroom'=> $Staff->aliasField('is_homeroom'),
                                ])
                                ->where([
                                    $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                                    $Staff->aliasField('institution_position_id') => $id,
                                    $Staff->aliasField('staff_id') => $staffId
                                ])->first();
                if(!empty($currentStaff)){
                    $isHomeroom = $currentStaff->is_homeroom;
                    $value .= $this->getSelectOptions('general.yesno')[$isHomeroom];
                }
            }
        }
        return $value;
    }//PCOOR-5069 Ends

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;
        $extra['auto_order'] = false;
        $institutionStaff = TableRegistry::get('institution_staff');

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('position_no'),
                $this->aliasField('staff_position_title_id'),
                //$this->aliasField('staff_position_grade_id'),
                $this->aliasField('assignee_id'),
               
                $this->aliasField('created')
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.id',
                        'Statuses.name'
                    ]
                ],
                'StaffPositionTitles'=> [
                    'fields' => [
                        'StaffPositionTitles.id',
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.order'
                    ]
                ],
                /*POCOR-5069 starts'StaffPositionGrades'=> [
                    'fields' => [
                        'StaffPositionGrades.id',
                        'StaffPositionGrades.name',
                        'StaffPositionGrades.order'
                    ]
                ],POCOR-5069 Ends*/
                'Assignees'=> [
                    'fields' => [
                        'Assignees.id',
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name'
                    ]
                ]
                
            ])->leftJoin([$institutionStaff->alias() => $institutionStaff->table()],
                [$institutionStaff->aliasField('institution_position_id = ') . $this->aliasField('id')])
            ->distinct([$this->aliasField('position_no')]);
        $sortList = ['position_no', 'StaffPositionTitles.order'/*,POCOR-5069 'StaffPositionGrades.order'*/, 'created','Assignees.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

    public function addEditBeforeAction(Event $event)
    {
        $this->fields['current_staff_list']['visible'] = false;
        $this->fields['past_staff_list']['visible'] = false;

        $this->setFieldOrder([
            'position_no', 'staff_position_title_id',
            //'staff_position_grade_id',POCOR-5069
            'shift_id',
        ]);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        /*POCOR-5069 Starts$this->field('staff_position_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);POCOR-5069 Ends*/
        
        //POCOR-6971
        $this->field('shift_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
    }

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

    public function viewBeforeAction(Event $event)
    {
        $this->setFieldOrder([
            //'staff_position_grade_id',POCOR-5069
            'position_no',
            'staff_position_title_id',
            'modified_user_id', 'modified', 'created_user_id', 'created',
            'current_staff_list', 'past_staff_list','shift_id'
        ]);

        $session = $this->Session;
        $pass = $this->request->param('pass');
        if (is_array($pass) && !empty($pass)) {
            $id = $this->paramsDecode($pass[1])['id'];
        }
        if (!isset($id)) {
            if ($session->check($this->registryAlias() . '.id')) {
                $id = $session->read($this->registryAlias() . '.id');
            }
        }

        if (!isset($id)) {
            die('no position id specified');
        }
        // start Current Staff List field
        $Staff = $this->Institutions->Staff;
        $currentStaff = $this->getCurrentStaff($id);

        $this->fields['current_staff_list']['data'] = $currentStaff;
        $totalCurrentFTE = '0.00';
        if (count($currentStaff) > 0) {
            foreach ($currentStaff as $cs) {
                $totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($cs->FTE)), 2);
            }
        }
        $this->fields['current_staff_list']['totalCurrentFTE'] = $totalCurrentFTE;
        // end Current Staff List field

        // start PAST Staff List field
        $pastStaff  = $Staff
            ->find()
            ->select([
                $Staff->aliasField('FTE'),
                $Staff->aliasField('start_date'),
                $Staff->aliasField('end_date'),
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'StaffStatuses.name'
            ])
            ->contain(['Users', 'StaffStatuses'])
            ->where([
                $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                $Staff->aliasField('institution_position_id') => $id,
                $Staff->aliasField('end_date').' IS NOT NULL',
                $Staff->aliasField('end_date').' < DATE(NOW())'
            ])
            ->order([$Staff->aliasField('start_date')]);
        $this->fields['past_staff_list']['data'] = $pastStaff;
        // end Current Staff List field
        return true;
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
        if (!empty($entity->modified_user_id)) {
            $this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
        }
        return $entity;
    }


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/

    public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query->where([$this->aliasField('institution_id') => $institutionId]);
    }


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
    public function getInstitutionPositions($userId, $isAdmin, $activeStatusId = [], $institutionId, $fte, $startDate, $endDate = '')
    {
        $selectedFTE = empty($fte) ? 0 : $fte;
        $startDate = new Date($startDate);

        $StaffTable = TableRegistry::get('Institution.Staff');
        $excludePositions = $StaffTable->find()
            ->select(['position_id' => $StaffTable->aliasField('institution_position_id')])
            ->where([$StaffTable->aliasField('institution_id') => $institutionId])
            ->group($StaffTable->aliasField('institution_position_id'))
            ->having([
                'OR' => [
                    'SUM('.$StaffTable->aliasField('FTE') .') >= ' => 1,
                    'SUM('.$StaffTable->aliasField('FTE') .') > ' => (1-$selectedFTE)
                ]
            ])
            ->hydrate(false);

        if (!empty($endDate)) {
            $endDate = new Date($endDate);
            $excludePositions = $excludePositions->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
        } else {
            $orCondition = [
                $StaffTable->aliasField('end_date') . ' >= ' => $startDate,
                $StaffTable->aliasField('end_date') . ' IS NULL'
            ];
            $excludePositions = $excludePositions->where([
                'OR' => $orCondition
            ]);
        }
        $excludeArray = $excludePositions->extract('position_id')->toArray();

        if ($isAdmin) {
            $userId = null;
            $roles = [];
        } else {
            $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        }

        // Filter by active status
        $positionConditions = [];
        $positionConditions[$this->aliasField('institution_id')] = $institutionId;
        if (!empty($activeStatusId)) {
            $positionConditions[$this->aliasField('status_id').' IN '] = $activeStatusId;
        }
        if (!empty($excludeArray)) {
            $positionConditions[$this->aliasField('id').' NOT IN '] = $excludeArray;
        }

        if ($selectedFTE > 0) {
            $staffPositionsOptions = $this->find()
                ->select([
                    'security_role_id' => 'SecurityRoles.id',
                    'type' => 'StaffPositionTitles.type'
                    //'grade_name' => 'StaffPositionGrades.name'//POCOR-5069
                ])
                ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                //->innerJoinWith('StaffPositionGrades')//POCOR-5069
                ->where($positionConditions)
                ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
                ->autoFields(true)
                ->toArray();
        } else {
            $staffPositionsOptions = [];
        }

        // Filter by role previlege
        $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
        $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
        $roleOptions = array_keys($roleOptions);
        $staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
        $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

        // Adding the opt group
        $types = $this->getSelectOptions('Staff.position_types');
        $options = [];
        foreach ($staffPositionsOptions as $position) {
            $name = $position->name . ' - ' . $position->grade_name;
            $type = __($types[$position->type]);
            $options[$type][$position->id] = $name;
        }

        return $options;
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('position_no'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->StaffPositionTitles->aliasField('name'),
                // $this->StaffPositionGrades->aliasField('name'),//POCOR-7412
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->StaffPositionTitles->alias(), $this->Institutions->alias(), $this->CreatedUser->alias(),'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Positions',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $positionWithTitle = $row->position_no.' - '.__($row->staff_position_title->name);
                    $row['request_title'] = sprintf(__('%s'), $positionWithTitle);//POCOR-7412
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    /**
     * POCOR-6820 change in query get position number and associated staff
    */
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query
            ->select([
               'id'=> $this->aliasField('id'),
                $this->aliasField('status_id'),
               'position_no'=> $this->aliasField('position_no'),
                $this->aliasField('staff_position_title_id'),
                //$this->aliasField('staff_position_grade_id'),POCOR-5069 
                $this->aliasField('assignee_id'),
                $this->aliasField('institution_id'),
                
                $this->aliasField('created')
            ])
            ->where([$this->aliasField('institution_id')=>$institutionId]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $position = TableRegistry::get('Institution.InstitutionPositions');
                    $data = $position->find()
                    ->select([
                        'status_name'=>'Statuses.name',
                        //'staff_id'=>$position->aliasField('staff_id'),
                        'titles'=>'StaffPositionTitles.name',
                        //'grades'=>'StaffPositionGrades.name',
                        'institution_name' => 'Institutions.name',
                        'assignees_name' => $position->find()->func()->concat([
                            'Assignees.first_name' => 'literal',
                            " ",
                            'Assignees.middle_name' => 'literal',
                            " ",
                            'Assignees.third_name' => 'literal',
                            " ",
                            'Assignees.last_name' => 'literal',

                        ]),
                    ])
                    ->contain([
                    'Statuses' => [
                        'fields' => [
                            'Statuses.id',
                            'status_name'=>'Statuses.name'
                        ]
                    ],
                    'StaffPositionTitles'=> [
                        'fields' => [
                            'StaffPositionTitles.id',
                           'titles'=> 'StaffPositionTitles.name',
                            'StaffPositionTitles.order'
                        ]
                    ],
                    /*'StaffPositionGrades'=> [
                        'fields' => [
                            'StaffPositionGrades.id',
                          'grades'=>  'StaffPositionGrades.name',
                            'StaffPositionGrades.order'
                        ]
                    ],*/
                    'Institutions'=> [
                        'fields' => [
                            'Institutions.id',
                          'institution_name'=>  'Institutions.name',
                            'Institutions.code'
                        ]
                    ],
                    'Assignees'=> [
                        'fields' => [
                            'Assignees.id',
                            'Assignees.first_name',
                            'Assignees.middle_name',
                            'Assignees.third_name',
                            'Assignees.last_name',
                        ]
                    ],
                    
                ])
                ->where([$position->aliasField('id') => $row['id']])
                ->first();
                if(!empty($data)){
                    $row['status_name'] = $data->status_name;
                    $row['titles'] = $data->titles;
                    //$row['grades'] = $data->grades;
                    $row['institution_name'] = $data->institution_name; //POCOR-7701
                    $row['assignees_name'] = $data->assignees_name;
                }
                return $row;
            });
        });
           
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
        $newArray = [];
        $newArray[] = [
            'key' => 'status_name',
            'field' => 'status_name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newArray[] = [
            'key' => 'position_no',
            'field' => 'position_no',
            'type' => 'string',
            'label' => __('Number')
        ];
        $newArray[] = [
            'key' => 'staff_position_title_id',
            'field' => 'staff_position_title_id',
            'type' => 'string',
            'label' =>  __('Title')
        ];
        $newArray[] = [
            'key' => 'staff_position_grade_name',
            'field' => 'staff_position_grade_name',
            'type' => 'string',
            'label' => __('Grade')
        ];
        $newArray[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];
        $newArray[] = [
            'key' => 'assignees_name',
            'field' => 'assignees_name',
            'type' => 'string',
            'label' => __('Assignees')
        ];
        
        $newArray[] = [
            'key' => 'openemis_id',
            'field' => 'openemis_id',
            'type' => 'string',
            'label' => __('OpenEMIS Number')
        ];
        $newArray[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'integer',
            'label' => __('Staff')
        ];
        $newArray[] = [
            'key' => 'fte',
            'field' => 'fte',
            'type' => 'string',
            'label' => __('FTE')
        ];
        $newArray[] = [
            'key' => 'status_name',
            'field' => 'status_name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newArray[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newArray[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
       // $newFields = array_merge($fields->getArrayCopy(), $newArray);
        $fields->exchangeArray($newArray);
    }

    /*POCOR-5069 Starts
    public function onExcelGetIsHomeroom(Event $event, Entity $entity)
    {
        return ($entity->is_homeroom) ? __('Yes') : __('No');
    }POCOR-5069 Ends*/

    public function onExcelGetStaffPositionTitleId(Event $event, Entity $entity)
    {
        if ($entity->has('staff_position_title') && !empty($entity->staff_position_title)) {
            $isTeaching = ($entity->staff_position_title->type) ? __('Teaching') : __('Non-Teaching');
            return $entity->staff_position_title->name . ' - ' . $isTeaching;
        }
    }

    public function onExcelGetStaffId(Event $event, Entity $entity)
    {
        $UsersTable = TableRegistry::get('Security.Users');

        if (!empty($entity->staff_id)) {
            return $UsersTable->get($entity->staff_id)->name;
        }
    }

    private function getCurrentStaff($id)
    {
        $session = $this->Session;

        $Staff = $this->Institutions->Staff;
        $currentStaff = $Staff
            ->find()
            ->select([
                $Staff->aliasField('FTE'),
                $Staff->aliasField('start_date'),
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name'
            ])
            ->contain(['Users'])
            ->where([
                $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                $Staff->aliasField('institution_position_id') => $id,
                'OR' => [
                    $Staff->aliasField('end_date').' IS NULL',
                    'AND' => [
                        $Staff->aliasField('end_date').' IS NOT NULL',
                        $Staff->aliasField('end_date').' >= DATE(NOW())'
                    ]
                ]
            ])
            ->order([$Staff->aliasField('start_date')]);

        return $currentStaff;
    }
    /**
     * POCOR-6820 
     * on the basis of the position number get staff data.
    */
    public function onExcelGetOpenemisId(Event $event, Entity $entity)
    {
        $session = $this->Session;
        $position_id = $entity->id;
        $Staff = $this->Institutions->Staff;
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $StaffPositionGrades = TableRegistry::get('staff_position_grades');
        $entity->fte = '';
        $entity->openemis_no = '';
        $entity->staff_name = '';
        $entity->status_name = '';
        $entity->identity_number = '';
        $entity->identity_type = '';
        
        $currentStaff = $Staff
                        ->find()
                        ->select([
                            'fte'=>$Staff->aliasField('FTE'),
                            'date'=>  $Staff->aliasField('start_date'),
                            'staff_id'=>  $Staff->aliasField('staff_id'),
                            'id'=>'Users.id',
                            'openemis_no'=> 'Users.openemis_no',
                            'first_name'=> 'Users.first_name',
                            'middle_name'=> 'Users.middle_name',
                            'third_name'=>'Users.third_name',
                            'last_name'=>'Users.last_name',
                            'status_name'=>'StaffStatuses.name',
                        ])
                        ->contain(['Users'])
                        ->leftJoinWith('StaffStatuses')
                        ->where([
                            $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                            $Staff->aliasField('institution_position_id') => $position_id,
                        ])->first();
                if(!empty($currentStaff)){
                    $entity->fte = $currentStaff->fte;
                    $StaffId = $currentStaff->staff_id;
                    $entity->openemis_no = $currentStaff->openemis_no;
                    $IdentityTypeId = $currentStaff->identity_type_id;
                    $entity->staff_name = $currentStaff->first_name.' '.$currentStaff->middle_name.' '.$currentStaff->third_name.' '.$currentStaff->last_name ;
                    $entity->status_name = $currentStaff->status_name;
                    
                    $UserIdentitiesIds = $UserIdentities->find()->select(['number'=>'Identities.number','name'=>'IdentityTypes.name'])
                                        ->leftJoin(
                                    [$IdentityTypes->alias() => $IdentityTypes->table()],
                                    [
                                        $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                    ])
                                    ->where([$UserIdentities->aliasField('security_user_id')=>$StaffId,
                                        $IdentityTypes->aliasField('default')=>1 ])->first();
                    if(!empty($UserIdentitiesIds)){
                        $entity->identity_type =  $UserIdentitiesIds->name;
                        $entity->identity_number =  $UserIdentitiesIds->number;
                    }

                }
          
        return $entity->openemis_no;
    }

    public function onExcelGetFte(Event $event, Entity $entity)
    {
        return $entity->fte;
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        return $entity->staff_name;
    }

    //PCOOR-5069 Starts
    public function onExcelGetStaffPositionGradeName(Event $event, Entity $entity)
    {   
        $session = $this->Session;
        $position_id = $entity->id;
        $Staff = $this->Institutions->Staff;
        $StaffPositionGrades = TableRegistry::get('staff_position_grades');
        $entity->staff_position_grade_name = '';
        
        $currentStaff = $Staff
                        ->find()
                        ->select([
                            'staff_id'=>  $Staff->aliasField('staff_id'),
                            'staff_position_grade_id'=>$Staff->aliasField('staff_position_grade_id'),
                            'staff_position_grade_name'=>  $StaffPositionGrades->aliasField('name')
                        ])
                        ->LeftJoin([$StaffPositionGrades->alias() => $StaffPositionGrades->table()],
                                [
                                    $StaffPositionGrades->aliasField('id') . ' = '. $Staff->aliasField('staff_position_grade_id')
                                ])
                        ->where([
                            $Staff->aliasField('institution_id') => $session->read('Institution.Institutions.id'),
                            $Staff->aliasField('institution_position_id') => $position_id,
                        ])->first();
                if(!empty($currentStaff)){
                    $StaffId = $currentStaff->staff_id;
                    $entity->staff_position_grade_name = $currentStaff->staff_position_grade_name;
                }
          
        return $entity->staff_position_grade_name;
    }//PCOOR-5069 Ends

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        return $entity->identity_type;
    }

    public function onExcelGetidentityNumber(Event $event, Entity $entity)
    {
        return $entity->identity_number;
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Positions';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {                        
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                            
                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);
                            
                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }

    /**
     * POCOR-6971 change in POCOR-7221
     * add shift dropdown
    */
    public function onUpdateFieldShiftId(Event $event, array $attr, $action, Request $request)
    {   
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        $this->currentAcademicPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);
        $currentAcademicPeriodIdd = $this->currentAcademicPeriod->id;
        $institutionShifts = TableRegistry::get('institution_shifts');
        $shiftOptions = TableRegistry::get('shift_options'); //POCOR-7233 add shift name
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $option = [];
        $optionAll = $institutionShifts->find('all')->select(['stime'=>$institutionShifts->aliasField('start_time'),'          etime'=>$institutionShifts->aliasField('end_time'),
                      'shift_option_id'=>$institutionShifts->   aliasField('shift_option_id'),
                      'name'=>$shiftOptions->aliasField('name')
                    ])
                    ->leftJoin([$shiftOptions->alias() => $shiftOptions->table()],
                    [$shiftOptions->aliasField('id = ') . $institutionShifts->aliasField('shift_option_id')])
                    ->where([$institutionShifts->aliasField('location_institution_id')=>$institutionId, $institutionShifts->aliasField('academic_period_id')=>$currentAcademicPeriodIdd])->toArray();
        foreach($optionAll as $key => $result){
            $option[$result->shift_option_id] = $result->name.': '.$result->stime. ' - '.$result->etime;
        } //POCOR-7233//add name
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Shift') . ' --']+$option;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }

    }
}
