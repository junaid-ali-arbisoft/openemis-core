<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class InstitutionCasesTable extends ControllerActionTable
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
		$this->belongsTo('Assignees', ['className' => 'User.Users']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('LinkedRecords', ['className' => 'Institution.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->toggle('add', false);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.LinkedRecord.afterSave'] = 'linkedRecordAfterSave';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $autoGenerateCaseNumber = $this->getAutoGenerateCaseNumber($entity->institution_id);
            $entity->case_number = $autoGenerateCaseNumber;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $newCaseNumber = $entity->case_number . "-" . $entity->id;
            $this->updateAll(
                ['case_number' => $newCaseNumber],
                ['id' => $entity->id]
            );
        }
    }

    public function linkedRecordAfterSave(Event $event, Entity $linkedRecordEntity)
    {
        $this->autoLinkRecordWithCases($linkedRecordEntity);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'custom_linked_records',
            'valueClass' => 'table-full-width',
            'after' => 'description'
        ]);
        $this->field('created', [
            'visible' => true,
            'after' => 'linked_records'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'custom_linked_records',
            'valueClass' => 'table-full-width',
            'after' => 'description'
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('case_number', ['type' => 'readonly']);
        $this->field('title', ['type' => 'readonly']);
    }

    public function onGetCustomLinkedRecordsElement(Event $mainEvent, $action, $entity, $attr, $options=[])
    {
        if ($action == 'index') {
            if ($entity->has('linked_records')) {
                $attr['value'] = sizeof($entity->linked_records);
            }
        } else if ($action == 'view') {
            $tableHeaders = [__('Feature'), __('Summary')];
            $tableCells = [];

            if ($entity->has('linked_records')) {
                $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();
                $featureAttr = $this->getSelectOptions('WorkflowRules.features');

                foreach ($entity->linked_records as $recordEntity) {
                    $rowData = [];

                    $recordId = $recordEntity->record_id;
                    $feature = $recordEntity->feature;

                    $className = $featureAttr[$feature]['className'];
                    $recordModel = TableRegistry::get($className);
                    $summary = $recordId;
                    $event = $recordModel->dispatchEvent('InstitutionCase.onSetCustomCaseSummary', [$recordId], $recordModel);
                    if ($event->isStopped()) { return $event->result; }
                    if (!empty($event->result)) {
                        $summary = $event->result;
                    }

                    $baseUrl = $featureAttr[$feature]['url'];
                    $baseUrl[] = 'view';
                    $baseUrl[] = $this->paramsEncode(['id' => $recordId]);

                    $url = $mainEvent->subject()->Html->link($summary, $baseUrl);

                    $rowData[] = isset($featureOptions[$recordEntity->feature]) ? $featureOptions[$recordEntity->feature] : $recordEntity->feature;
                    $rowData[] = $url;

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $mainEvent->subject()->renderElement('Institution.Cases/linked_records', ['attr' => $attr]);
    }

    public function autoLinkRecordWithCases($linkedRecordEntity)
    {
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $linkedRecordModel = TableRegistry::get($linkedRecordEntity->source());
        $registryAlias = $linkedRecordModel->registryAlias();
        $feature = $WorkflowRules->getFeatureByRegistryAlias($registryAlias);

        $statusId = 0;
        $assigneeId = 0;
        $institutionId = $linkedRecordEntity->has('institution_id') ? $linkedRecordEntity->institution_id : 0;
        $recordId = $linkedRecordEntity->id;

        $title = $feature;
        $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetCustomCaseTitle', [$linkedRecordEntity], $linkedRecordModel);
        if ($event->isStopped()) { return $event->result; }
        if (!empty($event->result)) {
            $title = $event->result;
        }

        $workflowRuleResults = $WorkflowRules
            ->find()
            ->where([
                $WorkflowRules->aliasField('feature') => $feature
            ])
            ->all();

        // loop through each rule setup for the feature
        // if the record match the rule, then create a new case and linked it with the record
        if (!$workflowRuleResults->isEmpty()) {
            foreach ($workflowRuleResults as $workflowRuleEntity) {
                $ruleArray = json_decode($workflowRuleEntity->rule, true);
                if (array_key_exists('where', $ruleArray)) {
                    $where = $ruleArray['where'];
                    $where['id'] = $recordId;

                    $query = $linkedRecordModel
                        ->find()
                        ->where($where);

                    if ($query->count() > 0) {
                        $existingLinkedCaseResults = $this
                            ->find()
                            ->matching('LinkedRecords', function ($q) use ($recordId, $feature) {
                                return $q->where([
                                    'record_id' => $recordId,
                                    'feature' => $feature
                                ]);
                            })
                            ->all();

                        if ($existingLinkedCaseResults->isEmpty()) {
                            $linkedRecords = [];
                            $linkedRecords[] = [
                                'record_id' => $recordId,
                                'feature' => $feature
                            ];

                            $newData = [
                                'case_number' => '',
                                'title' => $title,
                                'status_id' => $statusId,
                                'assignee_id' => $assigneeId,
                                'institution_id' => $institutionId,
                                'workflow_rule_id' => $workflowRuleEntity->id, // required by workflow behavior to get the correct workflow
                                'linked_records' => $linkedRecords
                            ];

                            $patchOptions = ['validate' => false];

                            $newEntity = $this->newEntity();
                            $newEntity = $this->patchEntity($newEntity, $newData, $patchOptions);
                            $this->save($newEntity);
                        }
                    }
                }
            }
        }
    }

    private function getAutoGenerateCaseNumber($institutionId=0)
    {
        $autoGenerateCaseNumber = '';
        $institutionEntity = $this->Institutions
            ->find()
            ->where([
                $this->Institutions->aliasField('id') => $institutionId
            ])
            ->select([$this->Institutions->aliasField('code')])
            ->first();

        if (!empty($institutionId)) {
            $autoGenerateCaseNumber .= $institutionEntity->code . "-";
        }

        $todayDate = date("dmY");
        $autoGenerateCaseNumber .= $todayDate;

        return $autoGenerateCaseNumber;
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
                $this->aliasField('title'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Cases',
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
                    $row['request_title'] = $row->title;
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}