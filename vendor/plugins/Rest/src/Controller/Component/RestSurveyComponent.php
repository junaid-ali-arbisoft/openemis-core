<?php

namespace Rest\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Xml;
use Cake\Utility\Text;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");

class RestSurveyComponent extends Component
{
    public $controller;
    public $action;

    public $components = ['Paginator', 'Workflow'];

    public $allowedActions = array('listing', 'schools', 'download' . 'downloadUrl', 'studentlist', 'stafflist');

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];

        $models = $this->config('models');
        foreach ($models as $key => $model) {
            if (!is_null($model)) {
                $this->{$key} = TableRegistry::get($model);
                $this->{lcfirst($key) . 'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
            } else {
                $this->{$key} = null;
            }

            $modelInfo = explode('.', $model);
            $base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
            $this->controller->set('Custom_' . $key, $base);
        }
    }

    public function downloadUrl()
    {
        $url = '/' . $this->controller->name . '/survey/download/xform/';
        $this->response->body(json_encode($url, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');
        return $this->response;
    }

    public function download($format = "xform", $id = 0, $output = true)
    {
        switch ($format) {
            case 'xform':
                $result = $this->getXForms($format, $id);
                break;
            default:
                break;
        }

        if ($output) { // true = output to screen
            if (is_object($result)) {
                $this->response->body($result->asXML());
            } else {
                $this->response->body($result);
            }
            $this->response->type('xml');

            return $this->response;
        } else { // download as file
            $fileName = $format . '_' . date('Ymdhis');

            $this->response->body($result->asXML());
            $this->response->type('xml');

            // Optionally force file download
            $this->response->download($fileName . '.xml');

            // Return response object to prevent controller from trying to render a view.
            return $this->response;
        }
    }
    //POCOR-7707
    public function studentlist($format = "xform", $id = 0, $insCode = 0, $academicPeriod = 0, $surveyQuesId = 0, $output = true)
    {
        switch ($format) {
            case 'xform':
                $result = $this->getXList($format, $id, $insCode, $academicPeriod, $surveyQuesId);
                break;
            default:
                break;
        }

        if ($output) { // true = output to screen
            if (is_object($result)) {
                $this->response->body($result->asXML());
            } else {
                $this->response->body($result);
            }
            $this->response->type('xml');

            return $this->response;
        } else { // download as file
            $fileName = $format . '_' . date('Ymdhis');

            $this->response->body($result->asXML());
            $this->response->type('xml');

            // Optionally force file download
            $this->response->download($fileName . '.xml');

            // Return response object to prevent controller from trying to render a view.
            return $this->response;
        }
    }
    //POCOR-7707

    public function upload()
    {
        $generateErrorResponse = function ($params = [], $code = 500) {
            $this->response->statusCode($code);
            $this->response->body(json_encode(($params), JSON_UNESCAPED_UNICODE));
            $this->response->type('json');
            return $this->response;
        };

        if ($this->request->is(['post', 'put'])) {
            // check for valid authorization token to upload survey
            $header = $this->request->header('authorization');
            if ($header) {
                $token = str_ireplace('bearer ', '', $header);
                try {
                    $payload = JWT::decode($token, Configure::read('Application.public.key'), ['RS256']);
                } catch (ExpiredException $e) {
                    return $generateErrorResponse(['message' => __('Expired token')], 500);
                }

                // check userId in the token payload
                if (empty($payload->sub)) {
                    return $generateErrorResponse(['message' => __('No userId found in token')], 500);
                }
                $userId = $payload->sub;
            } else {
                return $generateErrorResponse(['message' => __('Do not have permission to access the server')], 500);
            }

            $data = $this->request->data;
            Log::write('debug', 'Data:');
            Log::write('debug', $data);

            if (array_key_exists('response', $data)) {
                $CustomRecords = TableRegistry::get('Institution.InstitutionSurveys');
                $formAlias = $this->Form->alias();
                $fieldAlias = $this->Field->alias();

                $xmlResponse = $data['response'];

                // lines below is for testing
                // $xmlResponse = "<xf:instance id='xform'><oe:SurveyForms id='1'><oe:Institutions>1</oe:Institutions><oe:AcademicPeriods>10</oe:AcademicPeriods><oe:SurveyQuestions id='2'>some text</oe:SurveyQuestions><oe:SurveyQuestions id='3'>0</oe:SurveyQuestions><oe:SurveyQuestions id='4'>some long long text</oe:SurveyQuestions><oe:SurveyQuestions id='6'>3</oe:SurveyQuestions><oe:SurveyQuestions id='7'>5 6 7</oe:SurveyQuestions><oe:SurveyQuestions id='25'><oe:SurveyTableRows id='20'><oe:SurveyTableColumns0 id='0'>Male</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>10</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>20</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>30</oe:SurveyTableColumns3></oe:SurveyTableRows><oe:SurveyTableRows id='21'><oe:SurveyTableColumns0 id='0'>Female</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>15</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>25</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>35</oe:SurveyTableColumns3></oe:SurveyTableRows></oe:SurveyQuestions></oe:SurveyForms></xf:instance>";
                // $xmlResponse = '<xf:instance id="xform"><oe:SurveyForms id="16"><oe:Institutions>1059</oe:Institutions><oe:AcademicPeriods>10</oe:AcademicPeriods><oe:SurveyQuestions id="113" array-id="1">1.3641 123.9214</oe:SurveyQuestions><oe:SurveyQuestions id="114" array-id="2">1.74 100.243</oe:SurveyQuestions><oe:SurveyQuestions id="16" array-id="3">5</oe:SurveyQuestions></oe:SurveyForms></xf:instance>';
                // end testing data //

                // save response into database for debug purpose, always purge 3 days old response
                $this->deleteExpiredResponse();
                $this->addResponse($xmlResponse);
                // End

                Log::write('debug', 'XML Response');
                Log::write('debug', $xmlResponse);
                $xmlResponse = str_replace("xf:", "", $xmlResponse);
                $xmlResponse = str_replace("oe:", "", $xmlResponse);

                $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlResponse;
                Log::write('debug', 'XML String:');
                Log::write('debug', $xmlstr);
                $xml = Xml::build($xmlstr);

                $periodId = $xml->{$formAlias}->AcademicPeriods->__toString();
                $formId = $xml->{$formAlias}->attributes()->id->__toString();
                $institutionCode = $xml->{$formAlias}->Institutions->__toString();

                // checking institutionId
                $Institutions = TableRegistry::get('Institution.Institutions');
                $institutionResult = $Institutions
                    ->find()
                    ->where([
                        'LOWER(Institutions.code) = ' => strtolower($institutionCode)
                    ])
                    ->all();

                if ($institutionResult->isEmpty()) {
                    $msg = __('Invalid institution code');
                    return $generateErrorResponse(['message' => __('Invalid institution code')], 500);
                }

                $institutionRecord = $institutionResult->first();
                $institutionId = $institutionRecord->id;
                // end of check for institutionId

                $SecurityUser = TableRegistry::get('User.Users');
                $userEntity = $SecurityUser->get($userId);

                // checking of access only if the user is not super admin    
                if ($userEntity->super_admin == 0) {
                    $userHasAccess = $Institutions
                        ->find('byAccess', ['userId' => $userId])
                        ->where([
                            $Institutions->aliasField('id') => $institutionId
                        ])
                        ->count();

                    if ($userHasAccess == 0) {
                        return $generateErrorResponse(['message' => __('You do not have the permission to upload this survey')], 500);
                    }
                }

                // build survey records than check if the record don't exist, it is a invalid combination
                $CustomRecords->buildSurveyRecords($institutionId, $formId, $periodId);

                $institutionSurveyResults = $CustomRecords
                    ->find()
                    ->where([
                        $CustomRecords->aliasField($this->formKey) => $formId,
                        $CustomRecords->aliasField('institution_id') => $institutionId,
                        $CustomRecords->aliasField('academic_period_id') => $periodId
                    ])
                    ->contain('Statuses')
                    ->all();

                if ($institutionSurveyResults->isEmpty()) {
                    return $generateErrorResponse(['message' => __('No record found for institution for the form for the period')], 500);
                }

                $institutionSurveyEntity = $institutionSurveyResults->first();
                $institutionSurveyId = $institutionSurveyEntity->id;
                $institutionSurveyStatusId = $institutionSurveyEntity->status_id;

                // if the survey is expired
                if ($institutionSurveyStatusId == $CustomRecords::EXPIRED) {
                    $message = 'Survey record is not saved.';
                    Log::write('debug', 'Message:');
                    Log::write('debug', $message);
                    return $generateErrorResponse(['message' => __('Survey is already expired')], 500);
                }

                // if the survey is done
                if (!is_null($institutionSurveyEntity->status) && $institutionSurveyEntity->status->category == WorkflowSteps::DONE) {
                    $message = 'Survey record is not saved.';
                    Log::write('debug', 'Message:');
                    Log::write('debug', $message);
                    return $generateErrorResponse(['message' => __('Survey is already completed')], 500);
                }

                // update modified user id to the entitiy
                $institutionSurveyEntity = $CustomRecords->patchEntity($institutionSurveyEntity, ['modified_user_id' => $userId], ['validate' => false]);
                if ($CustomRecords->save($institutionSurveyEntity)) {
                    $message = 'Survey record has been submitted successfully.';
                    Log::write('debug', 'Message:');
                    Log::write('debug', $message);
                } else {
                    Log::write('debug', $institutionSurveyEntity->errors());
                }

                // Delete relevance questions
                $this->deleteQuestionWithRules($formId, $institutionSurveyId);

                // Rules
                $RulesTable = TableRegistry::get('Survey.SurveyRules');
                $rules = $RulesTable
                    ->find('SurveyRulesList', [
                        'survey_form_id' => $formId
                    ])
                    ->toArray();
                // End Rules

                $answers = new ArrayObject();
                $fields = $xml->{$formAlias}->{$fieldAlias};

                foreach ($fields as $field) {
                    $fieldId = $field->attributes()->id->__toString();
                    $fieldEntity = $this->Field->get($fieldId);
                    $fieldType = $fieldEntity->field_type;
                    $responseValue = urldecode($field->__toString());

                    $fieldTypeFunction = "upload" . Inflector::camelize(strtolower($fieldType));
                    if (method_exists($this, $fieldTypeFunction)) {
                        $responseData = [
                            $this->recordKey => $institutionSurveyEntity->id,
                            $this->fieldKey => $fieldId,
                            'created_user_id' => $userId
                        ];

                        $extra = new ArrayObject([]);
                        $extra['model'] = $this->FieldValue;
                        $extra['cellModel'] = $this->TableCell;
                        $extra['data'] = $responseData;
                        $extra['value'] = trim($responseValue);
                        $extra['recordKey'] = $this->recordKey;
                        $extra['formKey'] = $this->formKey;
                        $extra['fieldKey'] = $this->fieldKey;
                        $extra['fieldEntity'] = $fieldEntity;

                        $questionId = $extra['data']['survey_question_id'];
                        $show = $this->isRelevantQuestion($rules, $questionId, $answers, $responseValue);
                        if ($show) {
                            $this->$fieldTypeFunction($field, $institutionSurveyEntity, $extra);
                        }
                    }
                }
            }
        }
    }

    private function isRelevantQuestion($rules, $questionId, ArrayObject $answers, $responseValue)
    {
        $show = true;
        if (isset($rules[$questionId])) {
            $show = false;
            $dependentQuestions = $rules[$questionId];
            $ans = $answers->getArrayCopy();
            $intersectKey = array_intersect_key($ans, $dependentQuestions);
            foreach ($intersectKey as $key => $value) {
                $ruleOptions = json_decode($dependentQuestions[$key]);
                if (in_array($value, $ruleOptions)) {
                    $show = true;
                }
            }
        }
        if ($show) {
            $answers[$questionId] = $responseValue;
        }
        return $show;
    }

    private function deleteQuestionWithRules($surveyFormId, $recordId)
    {
        $RulesTable = TableRegistry::get('Survey.SurveyRules');
        $questions = $RulesTable
            ->find()
            ->select([
                $RulesTable->aliasField('survey_question_id')
            ])
            ->where([
                $RulesTable->aliasField('survey_form_id') => $surveyFormId,
                $RulesTable->aliasField('enabled') => 1
            ]);

        $CustomFieldValues = $this->FieldValue;
        $CustomTableCells = $this->TableCell;
        $CustomFieldValues->deleteAll([
            'survey_question_id IN ' => $questions,
            'institution_survey_id' => $recordId
        ]);

        $CustomTableCells->deleteAll([
            'survey_question_id IN ' => $questions,
            'institution_survey_id' => $recordId
        ]);
    }

    private function deleteFieldValue($data, $extra)
    {
        $model = $extra['model'];
        $recordKey = $extra['recordKey'];
        $fieldKey = $extra['fieldKey'];

        $model->deleteAll([
            $model->aliasField($recordKey) => $data[$recordKey],
            $model->aliasField($fieldKey) => $data[$fieldKey]
        ]);
    }

    private function saveFieldValue($answerData, $extra)
    {
        $model = $extra['model'];

        $answerEntity = $model->newEntity($answerData);
        if (!$model->save($answerEntity)) {
            Log::write('debug', $answerEntity->errors());
        }
    }

    private function deleteTableCell($data, $extra)
    {
        $cellModel = $extra['cellModel'];
        $recordKey = $extra['recordKey'];
        $fieldKey = $extra['fieldKey'];

        $cellModel->deleteAll([
            $cellModel->aliasField($recordKey) => $data[$recordKey],
            $cellModel->aliasField($fieldKey) => $data[$fieldKey]
        ]);
    }

    private function saveTableCell($cellData, $extra)
    {
        $cellModel = $extra['cellModel'];

        $cellEntity = $cellModel->newEntity($cellData);
        if (!$cellModel->save($cellEntity)) {
            Log::write('debug', $cellEntity->errors());
        }
    }

    private function processUpload($key, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        if (!empty($data)) {
            $this->deleteFieldValue($data, $extra);
            if (strlen($value) != 0) {
                $data[$key] = $value;
                $this->saveFieldValue($data, $extra);
            }
        }
    }

    private function uploadText($field, $entity, $extra)
    {
        $this->processUpload('text_value', $extra);
    }

    private function uploadNumber($field, $entity, $extra)
    {
        $this->processUpload('number_value', $extra);
    }

    private function uploadDecimal($field, $entity, $extra)
    {
        $this->processUpload('decimal_value', $extra);
    }

    private function uploadTextarea($field, $entity, $extra)
    {
        $this->processUpload('textarea_value', $extra);
    }

    private function uploadDropdown($field, $entity, $extra)
    {
        $this->processUpload('number_value', $extra);
    }

    private function uploadStudentList($field, $entity, $extra)
    {
        $thresholdDataaa = json_decode($extra['value'], true);

        $InstitutionStudentSurveysTbl = TableRegistry::get('institution_student_surveys');
        $InstitutionStudentSurveyAnswersTbl = TableRegistry::get('institution_student_survey_answers');
        $students = $thresholdDataaa;
        foreach ($students as $w => $stu) {
            $alreadyExistData = $InstitutionStudentSurveysTbl->find('all', ['conditions' => [
                'status_id' => 1,
                'institution_id' => $stu['institution_id'],
                'student_id' => $stu['student_id'],
                'academic_period_id' => $stu['academic_period_id'],
                'survey_form_id' => $stu['student_list_form_id'],
                'parent_form_id' => $stu['institution_form_id'],
            ]])->first();
            if (empty($alreadyExistData)) {
                $nEntity = $InstitutionStudentSurveysTbl->newEntity([
                    'status_id' => 1,
                    'institution_id' => $stu['institution_id'],
                    'student_id' => $stu['student_id'],
                    'academic_period_id' => $stu['academic_period_id'],
                    'survey_form_id' => $stu['student_list_form_id'],
                    'parent_form_id' => $stu['institution_form_id'],
                    'created_user_id' => 2,
                    'created' => date('Y-m-d H:i:s')


                ]);
                $successData = $InstitutionStudentSurveysTbl->save($nEntity);
            } else {
                $successData = $alreadyExistData;
            }
            if ($successData) {
                $questions = $stu['questions'];
                foreach ($questions as $t => $ques) {


                    $duplicateData11 = $InstitutionStudentSurveyAnswersTbl->find()->where(['survey_question_id' => $ques['student_list_survey_question_id'], 'parent_survey_question_id' => $stu['parent_survey_question_id'], 'institution_student_survey_id' => $successData['id']])->toArray();
                    foreach ($duplicateData11 as $dup) {
                        $InstitutionStudentSurveyAnswersTbl->delete($dup);
                    }

                    if (!empty($ques['survey_answer'])) {
                        if (($ques['student_list_survey_question_type'] == "DROPDOWN") || ($ques['student_list_survey_question_type'] == "NUMBER")) {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "number_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['student_list_survey_question_type'] == "TEXT") {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "text_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['student_list_survey_question_type'] == "DECIMAL") {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "decimal_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['student_list_survey_question_type'] == "TEXTAREA") {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "textarea_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['student_list_survey_question_type'] == "DATE") {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "date_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['student_list_survey_question_type'] == "TIME") {
                            $AnsEntity = $InstitutionStudentSurveyAnswersTbl->newEntity([
                                "time_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $stu['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStudentSurveyAnswersTbl->save($AnsEntity);
                        }
                    }
                }
            }
        }

        $this->processUpload('student_list', ['sada']);
    }

    private function uploadCheckbox($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];

        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            $checkboxValues = explode(" ", $value);
            foreach ($checkboxValues as $checkboxKey => $checkboxValue) {
                $data['number_value'] = $checkboxValue;
                $this->saveFieldValue($data, $extra);
            }
        }
    }

    private function uploadTable($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        $fieldEntity = $extra['fieldEntity'];

        $cellValueColumn = 'text_value';
        if ($fieldEntity->has('params') && !empty($fieldEntity->params)) {
            $params = json_decode($fieldEntity->params, true);

            if (array_key_exists('number', $params)) {
                $cellValueColumn = 'number_value';
            } elseif (array_key_exists('decimal', $params)) {
                $cellValueColumn = 'decimal_value';
            }
        }

        $this->deleteTableCell($data, $extra);
        foreach ($field->children() as $row => $rowObj) {
            $rowId = $rowObj->attributes()->id->__toString();
            foreach ($rowObj->children() as $col => $colObj) {
                $colId = $colObj->attributes()->id->__toString();
                if ($colId != 0) {
                    $cellValue = urldecode($colObj->__toString());
                    if (strlen($cellValue) != 0) {
                        $cellData = array_merge($data, [
                            $this->tableColumnKey => $colId,
                            $this->tableRowKey => $rowId,
                            'text_value' => '',
                            'number_value' => '',
                            'decimal_value' => ''
                        ]);
                        $cellData[$cellValueColumn] = $cellValue;

                        $this->saveTableCell($cellData, $extra);
                    }
                }
            }
        }
    }

    private function uploadDate($field, $entity, $extra)
    {
        $this->processUpload('date_value', $extra);
    }

    private function uploadTime($field, $entity, $extra)
    {
        $this->processUpload('time_value', $extra);
    }

    private function uploadCoordinates($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            if (count(explode(" ", $value)) == 2) {
                list($latitudeValue, $longitudeValue) = explode(" ", $value, 2);
                $json = json_encode([
                    'latitude' => $latitudeValue,
                    'longitude' => $longitudeValue
                ]);
                $data['text_value'] = $json;
                $this->saveFieldValue($data, $extra);
            } else {
                Log::write('debug', 'COORDINATES type answer is invalid');
            }
        }
    }

    private function uploadFile($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];

        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            // expected format received from mobile
            // filename.jpg|data:image/jpg;base64,urlencode( base64_encode( file_get_contents( $filepath) ) )
            list($fileName, $fileData) = explode("|", $value, 2);
            list($fileTypeStr, $encodedStr) = explode(";", $fileData, 2);
            list($encodeType, $encoded) = explode(",", $encodedStr, 2);
            $decoded = base64_decode($encoded);

            $answerData = array_merge($data, [
                'text_value' => $fileName,  // File Name
                'file' => $decoded  // File Content
            ]);
            $this->saveFieldValue($answerData, $extra);
        }
    }

    private function uploadRepeater($field, $entity, $extra)
    {
        $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
        $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');
        $RepeaterSurveyTableCells = TableRegistry::get('InstitutionRepeater.RepeaterSurveyTableCells');
        $repeaterRecordKey = 'institution_repeater_survey_id';

        $data = $extra['data'];
        $value = $extra['value'];
        $recordKey = $extra['recordKey'];
        $formKey = $extra['formKey'];
        $fieldKey = $extra['fieldKey'];

        $formId = null;
        $fieldId = $data[$fieldKey];
        // Get Survey Form ID
        $fieldEntity = $this->Field->get($fieldId);
        if ($fieldEntity->has('params') && !empty($fieldEntity->params)) {
            $params = json_decode($fieldEntity->params, true);
            if (array_key_exists($formKey, $params)) {
                $formId = $params[$formKey];
            }
        }
        // End

        if (!is_null($formId)) {
            foreach ($field->children() as $repeater => $repeaterObj) {
                // $repeaterId = $repeaterObj->attributes()->id->__toString();
                $repeaterData = [
                    'status_id' => $entity->status_id,
                    'institution_id' => $entity->institution_id,
                    'repeater_id' => Text::uuid(),
                    'academic_period_id' => $entity->academic_period_id,
                    $formKey => $formId,
                    'parent_form_id' => $entity->survey_form_id
                ];

                $repeaterEntity = $RepeaterSurveys->newEntity($repeaterData);
                if ($RepeaterSurveys->save($repeaterEntity)) {
                    foreach ($repeaterObj->children() as $field => $fieldObj) {
                        $fieldId = $fieldObj->attributes()->id->__toString();
                        $fieldType = $this->Field->get($fieldId)->field_type;
                        $responseValue = urldecode($fieldObj->__toString());

                        $fieldTypeFunction = "upload" . Inflector::camelize(strtolower($fieldType));
                        if (method_exists($this, $fieldTypeFunction)) {
                            $responseData = [
                                $repeaterRecordKey => $repeaterEntity->id,
                                $this->fieldKey => $fieldId
                            ];

                            $extra = new ArrayObject([]);
                            $extra['model'] = $RepeaterSurveyAnswers;
                            $extra['cellmodel'] = $RepeaterSurveyTableCells;
                            $extra['data'] = $responseData;
                            $extra['value'] = $responseValue;
                            $extra['recordKey'] = $repeaterRecordKey;
                            $extra['formKey'] = $formKey;
                            $extra['fieldKey'] = $fieldKey;

                            $this->$fieldTypeFunction($field, $entity, $extra);
                        }
                    }
                } else {
                    Log::write('debug', $repeaterEntity->errors());
                }
            }
        } else {
            Log::write('debug', 'Missing Survey Form ID id Repeater Type question #' . $fieldId);
        }
    }

    public function getXForms($instanceId, $id)
    {
        $title = $this->Form->get($id)->name;
        $description = $this->Form->get($id)->description;
        $title = htmlspecialchars($title, ENT_QUOTES);
        $description = htmlspecialchars($description, ENT_QUOTES);
        $fields = $this->getFields($id);

        $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
                <html
                    xmlns="' . NS_XHTML . '"
                    xmlns:xf="' . NS_XF . '"
                    xmlns:ev="' . NS_EV . '"
                    xmlns:xsd="' . NS_XSD . '"
                    xmlns:oe="' . NS_OE . '">
                </html>';

        $xml = Xml::build($xmlstr);

        $headNode = $xml->addChild("head", null, NS_XHTML);
        $bodyNode = $xml->addChild("body", null, NS_XHTML);
        $headNode->addChild("title", $title, NS_XHTML);
        $metaNode = $headNode->addChild("meta", null, NS_XHTML);
        $metaNode->addAttribute("name", "description");
        $metaNode->addAttribute("content", $description);
        $modelNode = $headNode->addChild("model", null, NS_XF);

        $instanceNode = $modelNode->addChild("instance", null, NS_XF);
        $instanceNode->addAttribute("id", $instanceId);
        $formNode = $instanceNode->addChild($this->Form->alias(), null, NS_OE);
        $formNode->addAttribute("id", $id);

        // need further testing if is commented out
        // $sectionBreakNode = $bodyNode;

        // set fixed Institutions Field
        $references = [$this->Form->alias(), 'Institutions'];

        $formNode->addChild('Institutions', null, NS_OE);
        $fieldNode = $bodyNode->addChild("input", null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $fieldNode->addAttribute("oe-type", "string");
        $fieldNode->addChild("label", "Institution Code", NS_XF);

        $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'string', 'required' => true]);
        // End

        // set fixed Academic Periods Field
        $references = [$this->Form->alias(), 'AcademicPeriods'];

        $formNode->addChild('AcademicPeriods', null, NS_OE);
        $fieldNode = $bodyNode->addChild("select1", null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $fieldNode->addAttribute("oe-type", "integer");
        $fieldNode->addAttribute("oe-dependency", $this->getRef($instanceId, [$this->Form->alias(), 'Institutions']));
        $fieldNode->addChild("label", "Academic Period", NS_XF);

        $SurveyForms = TableRegistry::get('Survey.SurveyForms');
        $SurveyStatuses = $SurveyForms->SurveyStatuses;
        $todayDate = date("Y-m-d");

        $periodListResults = $SurveyForms
            ->find('list', [
                'keyField' => 'academic_period_id',
                'valueField' => 'academic_period_name'
            ])
            ->select([
                'academic_period_id' => 'AcademicPeriods.id',
                'academic_period_name' => 'AcademicPeriods.name'
            ])
            ->matching('SurveyStatuses.AcademicPeriods')
            ->group(['AcademicPeriods.id'])
            ->where([
                'AND' => [
                    [$SurveyForms->aliasField('id') => $id],
                    [$SurveyStatuses->aliasField('date_disabled >= ') => $todayDate]
                ]
            ])
            ->all();

        if (!$periodListResults->isEmpty()) {
            $periodOptions = $periodListResults->toArray();

            foreach ($periodOptions as $periodId => $periodName) {
                $itemNode = $fieldNode->addChild("item", null, NS_XF);
                $itemNode->addChild("label", htmlspecialchars($periodName), NS_XF);
                $itemNode->addChild("value", htmlspecialchars($periodId), NS_XF);
            }
        }


        $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'integer', 'required' => true]);
        // End

        // used to build validation rules
        $schemaNode = $modelNode->addChild("schema", null, NS_XSD);

        // relevancy rules
        $RulesTable = TableRegistry::get('Survey.SurveyRules');
        $rules = $RulesTable
            ->find('SurveyRulesList', [
                'survey_form_id' => $id
            ])
            ->toArray();
        $rules = new ArrayObject($rules);

        $sectionName = null;
        foreach ($fields as $key => $field) {
            $extra = new ArrayObject([]);
            $extra['index'] = $key + 1;
            $extra['subIndex'] = 0;
            $extra['head'] = $headNode;
            $extra['body'] = $bodyNode;
            $extra['model'] = $modelNode;
            $extra['instance'] = $instanceNode;
            $extra['schema'] = $schemaNode;
            $extra['form'] = $formNode;
            $extra['hint'] = null;
            $extra['constraint'] = null;

            $extra['references'] = [$this->Form->alias(), $this->Field->alias() . "[" . $extra['index'] . "]"];
            $extra['default_value'] = null; // to handle default value for dropdown

            // For relevancy
            $extra['field_id'] = $field->field_id;
            $extra['rules'] = $rules;

            if (is_null($sectionName)) {
                $parentNode = $bodyNode;
            }

            // Section
            if ($field->section_name != $sectionName) {
                $sectionName = $field->section_name;
                $sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
                $sectionBreakNode->addAttribute("ref", $field->form_id . '_' . $field->field_id);
                $sectionBreakNode->addChild("label", htmlspecialchars($sectionName, ENT_QUOTES), NS_XF);

                $parentNode = $sectionBreakNode;
            }
            // End

            $fieldTypeFunction = strtolower($field->field_type);
            if (method_exists($this, $fieldTypeFunction)) {
                // here to add logic of xform
                $this->$fieldTypeFunction($field, $parentNode, $instanceId, $extra);

                // set to null to skip adding into Head > Model > Instance (e.g. for table and repeater)
                if (!is_null($extra['form'])) {
                    $this->setModelNode($field, $extra['form'], $instanceId, $extra);
                }
            }
        }

        return $xml;
    }

    //POCOR-7707
    public function getXList($instanceId, $id, $insCode, $acamic, $surveyQuesID)
    {

        $title = $this->Form->get($id)->name;
        $institutionClassStudentsTbl = TableRegistry::get('institution_class_students');
        $SurveyFormsQuestionsTbl = TableRegistry::get('survey_forms_questions');
        $institutionStudentSurveysTbl = TableRegistry::get('institution_student_surveys');
        $surveyQuestionChoicesTbl = TableRegistry::get('survey_question_choices');
        $institution_student_survey_answers_tbl = TableRegistry::get('institution_student_survey_answers');

        $institutionTbl = TableRegistry::get('Institution.Institutions');
        $insData = $institutionTbl->find('all', ['conditions' => ['code' => $insCode]])->first();
        $insId = $insData->id;
        $academicPeriodTbl = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $apData = $academicPeriodTbl->find('all', ['conditions' => ['name' => $acamic]])->first();
        $apId = $apData->id;

        $join = [];

        $join['class_students_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_classes.academic_period_id
            ,institution_classes.institution_id
            ,institution_classes.id institution_class_id
            ,institution_classes.name institution_class_name
            ,classes_student_info.student_id
            ,classes_student_info.openemis_no
            ,classes_student_info.student_name
        FROM institution_classes
        LEFT JOIN 
        (
            SELECT institution_class_students.institution_class_id
                ,security_users.id student_id
                ,security_users.openemis_no
                ,REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' ') student_name
            FROM institution_class_students
            INNER JOIN 
            (
                SELECT institution_class_students.student_id
                    ,institution_class_students.education_grade_id
                    ,institution_class_students.academic_period_id
                    ,institution_class_students.institution_id
                    ,MAX(institution_class_students.created) max_created
                FROM institution_class_students
                INNER JOIN academic_periods
                ON academic_periods.id = institution_class_students.academic_period_id
                WHERE institution_class_students.academic_period_id = $apId
                AND institution_class_students.institution_id = $insId
                AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
                GROUP BY institution_class_students.student_id
                    ,institution_class_students.education_grade_id
                    ,institution_class_students.academic_period_id
                    ,institution_class_students.institution_id
            ) latest_class
            ON latest_class.student_id = institution_class_students.student_id
            AND latest_class.education_grade_id = institution_class_students.education_grade_id
            AND latest_class.academic_period_id = institution_class_students.academic_period_id
            AND latest_class.institution_id = institution_class_students.institution_id
            AND latest_class.max_created = institution_class_students.created
            INNER JOIN security_users
            ON security_users.id = institution_class_students.student_id
            INNER JOIN academic_periods
            ON academic_periods.id = institution_class_students.academic_period_id
            WHERE institution_class_students.academic_period_id = $apId 
            AND institution_class_students.institution_id = $insId
            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
        ) classes_student_info
        ON classes_student_info.institution_class_id = institution_classes.id
        WHERE institution_classes.academic_period_id = $apId
        AND institution_classes.institution_id = $insId)",
            'conditions' => [
                'class_students_info.academic_period_id = main_query.academic_period_id',
                'class_students_info.institution_id = main_query.institution_id'
            ]
        ];

        $join['student_survey_answers_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_student_surveys.academic_period_id
            ,institution_student_surveys.institution_id
            ,institution_student_surveys.student_id
            ,institution_student_surveys.survey_form_id
            ,institution_student_surveys.parent_form_id
            ,institution_student_survey_answers.survey_question_id
            ,institution_student_survey_answers.parent_survey_question_id
            ,survey_question_choices.id answer_choice_id_for_dropdown
            ,IF(institution_student_survey_answers.id IS NULL, '', 
                IF(institution_student_survey_answers.text_value IS NOT NULL, institution_student_survey_answers.text_value, 
                    IF(institution_student_survey_answers.decimal_value IS NOT NULL, institution_student_survey_answers.decimal_value, 
                        IF(institution_student_survey_answers.textarea_value IS NOT NULL, institution_student_survey_answers.textarea_value, 
                            IF(institution_student_survey_answers.date_value IS NOT NULL, institution_student_survey_answers.date_value, 
                                IF(institution_student_survey_answers.time_value IS NOT NULL, institution_student_survey_answers.time_value, 
                                        IF(survey_question_choices.id IS NOT NULL, survey_question_choices.name, institution_student_survey_answers.number_value))))))) survey_answer_values
        FROM institution_student_survey_answers
        INNER JOIN institution_student_surveys
        ON institution_student_surveys.id = institution_student_survey_answers.institution_student_survey_id
        LEFT JOIN survey_question_choices
        ON survey_question_choices.id = institution_student_survey_answers.number_value
        WHERE institution_student_surveys.status_id = 1
        AND institution_student_surveys.academic_period_id = $apId
        AND institution_student_surveys.institution_id = $insId
        AND institution_student_surveys.parent_form_id = $id)",
            'conditions' => [
                'student_survey_answers_info.academic_period_id = class_students_info.academic_period_id',
                'student_survey_answers_info.institution_id = class_students_info.institution_id',
                'student_survey_answers_info.student_id = class_students_info.student_id',
                'student_survey_answers_info.survey_form_id = main_query.student_list_survey_form_id',
                'student_survey_answers_info.parent_form_id = main_query.institution_survey_form_id',
                'student_survey_answers_info.survey_question_id = main_query.student_list_survey_question_id',
                'student_survey_answers_info.parent_survey_question_id = main_query.institution_survey_question_id',
            ]
        ];

        $main_query  = "(SELECT institution_surveys.academic_period_id
                            ,institution_surveys.institution_id
                            ,institution_surveys.survey_form_id institution_survey_form_id
                            ,institution_forms.name institution_survey_form_name
                            ,survey_questions.id institution_survey_question_id
                            ,survey_forms_questions.section
                            ,student_list_survey_forms_questions.order
                            ,survey_questions.name institution_survey_question_name
                            ,student_list_survey_forms_questions.survey_form_id student_list_survey_form_id
                            ,survey_list_forms.name student_list_survey_form_name
                            ,student_list_survey_questions.id student_list_survey_question_id
                            ,student_list_survey_questions.name student_list_survey_question_name
                            ,student_list_survey_questions.field_type student_list_survey_question_type
                        FROM institution_surveys
                        INNER JOIN survey_forms institution_forms
                        ON institution_forms.id = institution_surveys.survey_form_id
                        INNER JOIN survey_forms_questions
                        ON survey_forms_questions.survey_form_id = institution_surveys.survey_form_id
                        INNER JOIN survey_questions
                        ON survey_questions.id = survey_forms_questions.survey_question_id
                        LEFT JOIN survey_forms_questions student_list_survey_forms_questions
                        ON student_list_survey_forms_questions.survey_form_id = JSON_EXTRACT(survey_questions.params, '$.survey_form_id')
                        LEFT JOIN survey_forms survey_list_forms
                        ON survey_list_forms.id = student_list_survey_forms_questions.survey_form_id
                        LEFT JOIN survey_questions student_list_survey_questions
                        ON student_list_survey_questions.id = student_list_survey_forms_questions.survey_question_id
                        WHERE institution_surveys.academic_period_id = $apId
                        AND institution_surveys.institution_id = $insId
                        AND institution_surveys.survey_form_id = $id
                        AND institution_surveys.status_id = 1
                        AND LENGTH(survey_questions.params) > 0
                        AND survey_questions.field_type = 'STUDENT_LIST')";


        $query =  $institutionStudentSurveysTbl->find()
            ->select([
                'academic_period_id' => 'main_query.academic_period_id', 'institution_id' => 'main_query.institution_id', 'institution_form_id' => 'main_query.institution_survey_form_id', 'institution_form_name' => 'main_query.institution_survey_form_name', 'institutiton_survey_question_id' => 'main_query.institution_survey_question_id', 'section' => 'main_query.section', 'name' => 'main_query.institution_survey_question_name', 'student_list_form_id' => 'main_query.student_list_survey_form_id', 'student_list_form_name' => 'main_query.student_list_survey_form_name', 'student_list_survey_question_id' => 'main_query.student_list_survey_question_id', 'student_list_survey_question_name' => 'main_query.student_list_survey_question_name',
                'student_list_survey_question_type' => 'main_query.student_list_survey_question_type',
                'institution_class_id' => 'class_students_info.institution_class_id',
                'class_name' => 'class_students_info.institution_class_name',
                'student_id' => 'class_students_info.student_id',
                'openemis_no' => 'class_students_info.openemis_no',
                'student_name' => 'class_students_info.student_name',
                'student_survey_answers_info.answer_choice_id_for_dropdown',
                'survey_answer' => 'student_survey_answers_info.survey_answer_values'
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);
        //  echo "<pre>";print_r($query->toArray());die;

        $query1 =  $institutionStudentSurveysTbl->find()
            ->select([
                // 'institution_form_id' => "institution_form.id",
                // 'institution_form_name' => "institution_form.name",
                // 'student_list_form_id' => "student_list_form.id",
                // 'student_list_form_name' => "student_list_form.name",
                // 'section' => "institution_forms_questions.section",
                // 'name' => "institution_forms_questions.name",
                // 'institutiton_survey_question_id' => "institution_survey_questions.id",
                // 'institutiton_survey_question_name' => "institution_survey_questions.name",
                // 'student_list_survey_question_id' => "student_list_survey_questions.id",
                // 'student_list_survey_question_name' => "student_list_survey_questions.name",
                // 'student_list_survey_question_type' => "student_list_survey_questions.field_type",
                // 'institution_id' => "institution_student_surveys.institution_id",

                // 'institution_id' => "institution_student_surveys.institution_id",
                // 'academic_period_id' => "institution_student_surveys.institution_id",
                'institution_class_id' => 'class_students_info.institution_class_id',
                'class_name' => 'class_students_info.institution_class_name'
                // 'student_id' => "institution_student_surveys.student_id",
                // 'openemis_no'=> "security_users.openemis_no",
                // 'student_name'=> "(REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' '))",
                // 'survey_answer' => "(IF(institution_student_survey_answers.id IS NULL, '', 
                // IF(institution_student_survey_answers.text_value IS NOT NULL, institution_student_survey_answers.text_value, 
                //     IF(institution_student_survey_answers.decimal_value IS NOT NULL, institution_student_survey_answers.decimal_value, 
                //         IF(institution_student_survey_answers.textarea_value IS NOT NULL, institution_student_survey_answers.textarea_value, 
                //             IF(institution_student_survey_answers.date_value IS NOT NULL, institution_student_survey_answers.date_value, 
                //                 IF(institution_student_survey_answers.time_value IS NOT NULL, institution_student_survey_answers.time_value, 
                //                         IF(survey_question_choices.id IS NOT NULL, survey_question_choices.id, institution_student_survey_answers.number_value))))))))"
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);

        $query2 =  $institutionStudentSurveysTbl->find()
            ->select([
                'institution_form_id' => 'main_query.institution_survey_form_id',
                'institution_form_name' => 'main_query.institution_survey_form_name',
                'student_list_form_id' => 'main_query.student_list_survey_form_id',
                'student_list_form_name' => 'main_query.student_list_survey_form_name',
                //'section' => "institution_forms_questions.section",
                'name' => 'main_query.institution_survey_question_name',
                //'institutiton_survey_question_id' => "institution_survey_questions.id",
                // 'institutiton_survey_question_name' => "institution_survey_questions.name",//--
                'student_list_survey_question_id' => 'main_query.student_list_survey_question_id',
                'student_list_survey_question_name' => 'main_query.student_list_survey_question_name',
                'student_list_survey_question_type' => 'main_query.student_list_survey_question_type',
                'institution_id' => 'main_query.institution_id',

                'institution_class_id' => 'class_students_info.institution_class_id',
                'class_name' => 'class_students_info.institution_class_name',
                'student_id' => 'class_students_info.student_id',
                'openemis_no' => 'class_students_info.openemis_no',
                'student_name' => 'class_students_info.student_name',

                'academic_period_id' => 'main_query.academic_period_id',

                // 'survey_answer' => "(IF(institution_student_survey_answers.id IS NULL, '', 
                // IF(institution_student_survey_answers.text_value IS NOT NULL, institution_student_survey_answers.text_value, 
                //     IF(institution_student_survey_answers.decimal_value IS NOT NULL, institution_student_survey_answers.decimal_value, 
                //         IF(institution_student_survey_answers.textarea_value IS NOT NULL, institution_student_survey_answers.textarea_value, 
                //             IF(institution_student_survey_answers.date_value IS NOT NULL, institution_student_survey_answers.date_value, 
                //                 IF(institution_student_survey_answers.time_value IS NOT NULL, institution_student_survey_answers.time_value, 
                //                         IF(survey_question_choices.id IS NOT NULL, survey_question_choices.id, institution_student_survey_answers.number_value))))))))"
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);


        $query3 =  $institutionStudentSurveysTbl->find()
            ->select([
                // 'institution_form_id' => "institution_form.id",
                // 'institution_form_name' => "institution_form.name",
                // 'student_list_form_id' => "student_list_form.id",
                // 'student_list_form_name' => "student_list_form.name",
                'question_order' => 'main_query.order',
                // 'name' => "institution_forms_questions.name",
                // 'institutiton_survey_question_id' => "institution_survey_questions.id",
                // 'institutiton_survey_question_name' => "institution_survey_questions.name",
                'student_list_survey_question_id' => 'main_query.student_list_survey_question_id',
                'student_list_survey_question_name' => 'main_query.student_list_survey_question_name',
                'student_list_survey_question_type' => 'main_query.student_list_survey_question_type',
                'institution_id' => 'main_query.institution_id'

                // 'institution_id' => "institution_student_surveys.institution_id",
                // 'academic_period_id' => "institution_student_surveys.institution_id",
                // 'institution_class_id' => "latest_class_info.institution_class_id",
                // 'class_name' => "latest_class_info.class_name",
                // 'student_id' => "institution_student_surveys.student_id",
                // 'openemis_no'=> "security_users.openemis_no",
                // 'student_name'=> "(REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' '))",
                // 'survey_answer' => "(IF(institution_student_survey_answers.id IS NULL, '', 
                // IF(institution_student_survey_answers.text_value IS NOT NULL, institution_student_survey_answers.text_value, 
                //     IF(institution_student_survey_answers.decimal_value IS NOT NULL, institution_student_survey_answers.decimal_value, 
                //         IF(institution_student_survey_answers.textarea_value IS NOT NULL, institution_student_survey_answers.textarea_value, 
                //             IF(institution_student_survey_answers.date_value IS NOT NULL, institution_student_survey_answers.date_value, 
                //                 IF(institution_student_survey_answers.time_value IS NOT NULL, institution_student_survey_answers.time_value, 
                //                         IF(survey_question_choices.id IS NOT NULL, survey_question_choices.id, institution_student_survey_answers.number_value))))))))"
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);
        // echo "<pre>";print_r($query->toArray());die;
        $tabData = $query->group(['section'])->order(['institutiton_survey_question_id' => 'ASC']);

        $class_list = $query1->group(['institution_class_id'])->toArray();

        $students = $query2->group(['student_id'])->toArray();
        //echo "<pre>";print_r($students);die;
        // $students = $query2->where(['institution_student_surveys.institutiton_survey_question_id' => 109])->toArray();

        $questions = $query3->group(['student_list_survey_question_id'])->order(['question_order' => 'ASC'])->toArray(); //POCOR-7771
        $finalData = [];

        $AnswerKeyArr = [];
        foreach ($tabData as $p => $tbDta) {
            $finalData[$tbDta->section]['parent_question_tab_id'] = $tbDta->institutiton_survey_question_id;
            $finalData[$tbDta->section]['class_list'] = $class_list;
            $finalData[$tbDta->section]['students'] = $students;
            // unset($finalData[$tbDta->section]['students'][0]);
            // unset($finalData[$tbDta->section]['students'][1]);

            foreach ($finalData[$tbDta->section]['students'] as $ke => $student) {

                $finalData[$tbDta->section]['students'][$ke]['questions'] = $questions;
                $ins_stu_survey = $institutionStudentSurveysTbl->find('all', ['conditions' => [
                    'status_id' => 1,
                    'institution_id' => $student['institution_id'],
                    'student_id' => $student['student_id'],
                    'academic_period_id' => $student['academic_period_id'],
                    'survey_form_id' => $student['student_list_form_id'],
                    'parent_form_id' => $student['institution_form_id'],
                ]])->first();
                $finalData[$tbDta->section]['students'][$ke]['institution_student_survey_id'] = $ins_stu_survey['id'];
                foreach ($finalData[$tbDta->section]['students'][$ke]['questions'] as $jk => $ques) {

                    $options = $surveyQuestionChoicesTbl->find('all', ['fields' => ['id', 'name']])
                        ->where(['survey_question_id' => $ques['student_list_survey_question_id']])->toArray();
                    $finalData[$tbDta->section]['students'][$ke]['questions'][$jk]['options'] = $options;

                    //echo "<pre>";print_r($ques);die;
                    //add selected ans value



                    if (!empty($ins_stu_survey)) {

                        $dataExistAns = $institution_student_survey_answers_tbl->find('all', ['conditions' => [
                            'survey_question_id' => $ques['student_list_survey_question_id'],
                            'parent_survey_question_id' => $tbDta['institutiton_survey_question_id'],
                            'institution_student_survey_id' => $student['institution_student_survey_id']
                        ]])->first();

                        if (!empty($dataExistAns)) {
                            if (!empty($dataExistAns['number_value'])) {
                                $selectVAlue = $dataExistAns['number_value'];
                            } elseif (!empty($dataExistAns['text_value'])) {
                                $selectVAlue = $dataExistAns['text_value'];
                            } elseif (!empty($dataExistAns['decimal_value'])) {
                                $selectVAlue = $dataExistAns['decimal_value'];
                            } elseif (!empty($dataExistAns['textarea_value'])) {
                                $selectVAlue = $dataExistAns['textarea_value'];
                            } elseif (!empty($dataExistAns['date_value'])) {
                                $selectVAlue = date('Y-m-d', strtotime($dataExistAns['date_value']));
                            } elseif (!empty($dataExistAns['time_value'])) {
                                $selectVAlue = date('h:i:s', strtotime($dataExistAns['date_value']));
                            }
                        } else {
                            $selectVAlue = $options[0]['id'];
                        }
                    }
                    $AnswerKeyArr['server_key'][$tbDta->section][$ke][$jk]['answer'][] = $selectVAlue;
                    // echo $selectVAlue."=="."<br>";

                    $finalData[$tbDta->section]['students'][$ke]['questions'][$jk]['survey_answer'] = $selectVAlue;

                    //echo "<pre>";print_r($dataExistAns->original());die;

                }
                //echo "quetion";
                //$studentData =$finalData;


            }
            //echo "student";


        }

        // die;
        //echo "<pre>";print_r($AnswerKeyArr);die;
        //$abc = $finalData[1]['students'][0]['questions'][0]->getOriginal('survey_answer');

        // foreach($tabData as $p => $tbDta){
        //     $finalData[$tbDta->section]['parent_question_tab_id'] = $tbDta->institutiton_survey_question_id;
        //     foreach($finalData[$tbDta->section]['students'] as $ke=>$student){ 

        //         $ins_stu_survey = $institutionStudentSurveysTbl->find('all',['conditions'=>[
        //             'status_id' => 1,
        //             'institution_id' => $student['institution_id'],
        //             'student_id' => $student['student_id'],
        //             'academic_period_id' => $student['academic_period_id'],
        //             'survey_form_id' => $student['student_list_form_id'],
        //             'parent_form_id' => $student['institution_form_id'],
        //         ]])->first();
        //         foreach($finalData[$tbDta->section]['students'][$ke]['questions'] as $jk=> $ques){

        //             $dataExistAns = $institution_student_survey_answers_tbl->find('all',['conditions'=>[
        //                 'survey_question_id' => $ques['student_list_survey_question_id'],
        //                 'parent_survey_question_id' => $tbDta['institutiton_survey_question_id'],
        //                 'institution_student_survey_id' => $ins_stu_survey['id']
        //             ]])->first();

        //             $ansKey = $ques->getOriginal('survey_answer');
        //             if(!empty($dataExistAns)){
        //                 $finalData[$tbDta->section]['students'][$ke]['questions'][$jk]['survey_answer'] = $ansKey['number_value'];
        //             }



        //         }
        //     }
        // }




        $final = [];
        $final['data'] = $finalData;
        $final['survey_answer_arr'] = $AnswerKeyArr;

        //echo "<pre>";print_r($final);die;

        $params = json_encode($final, true);
        echo $params;
        die;
    }
    //POCOR-7707
    private function getFields($id)
    {
        return $this->FormField
            ->find()
            ->find('order')
            ->select([
                'form_id' => $this->FormField->aliasField($this->formKey),
                'field_id' => $this->FormField->aliasField($this->fieldKey),
                'section_name' => $this->FormField->aliasField('section'),
                'name' => $this->FormField->aliasField('name'),
                'is_mandatory' => $this->FormField->aliasField('is_mandatory'),
                'is_unique' => $this->FormField->aliasField('is_unique'),
                'field_type' => $this->Field->aliasField('field_type'),
                'default_name' => $this->Field->aliasField('name'),
                'default_description' => $this->Field->aliasField('description'),
                'default_is_mandatory' => $this->Field->aliasField('is_mandatory'),
                'default_is_unique' => $this->Field->aliasField('is_unique'),
                'params' => $this->Field->aliasField('params')
            ])
            ->innerJoin(
                [$this->Field->alias() => $this->Field->table()],
                [$this->Field->aliasField('id =') . $this->FormField->aliasField($this->fieldKey)]
            )
            ->where([
                $this->FormField->aliasField($this->formKey) => $id
            ])
            ->toArray();
    }

    private function text($field, $parentNode, $instanceId, $extra)
    {
        $bindType = 'string';

        $validationType = null;
        $validations = [];
        $validationHint = '';
        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);

            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'min_length':
                        $validationType = $key;
                        $validations[$validationType] = $value;
                        $validationHint = $this->Field->getMessage('CustomField.text.minLength', ['sprintf' => $value]);
                        break;
                    case 'max_length':
                        $validationType = $key;
                        $validations[$validationType] = $value;
                        $validationHint = $this->Field->getMessage('CustomField.text.maxLength', ['sprintf' => $value]);
                        break;
                    case 'range':
                        $validationType = $key;
                        if (array_key_exists('lower', $value) && array_key_exists('upper', $value)) {
                            $validations['min_length'] = $value['lower'];
                            $validations['max_length'] = $value['upper'];
                            $validationHint = $this->Field->getMessage('CustomField.text.range', ['sprintf' => [$value['lower'], $value['upper']]]);
                        }
                }
            }
        }

        if (!is_null($validationType)) {
            $bindType = "string" . Inflector::camelize($validationType) . $extra['index'];

            // introduce subIndex to handle question inside repeater has validation
            $subIndex = $extra['subIndex'];
            if (!empty($subIndex)) {
                $bindType .= "_$subIndex";
            }
            // End

            $schemaNode = $extra['schema'];
            $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
            $simpleType->addAttribute("name", $bindType);

            $restriction = $simpleType->addChild('restriction', null, NS_XSD);
            $restriction->addAttribute("base", "xf:string");

            foreach ($validations as $key => $value) {
                $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                $condition->addAttribute("value", $value);
            }
        }

        $extra['tagName'] = 'input';
        $extra['bindType'] = $bindType;
        $extra['hint'] = !empty($validationHint) ? $validationHint : null;
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function student_list($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'student_list';
        $extra['is_student_list_field'] = 'yesss';
        $extra['bindType'] = 'string';
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function number($field, $parentNode, $instanceId, $extra)
    {
        $bindType = 'integer';
        $constraint = null;
        $validationType = null;
        $validations = [];
        $validationHint = '';

        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);

            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'min_value':
                        $validationType = $key;
                        $validations['min_inclusive'] = $value;
                        $validationHint = $this->Field->getMessage('CustomField.number.minValue', ['sprintf' => $value]);
                        break;
                    case 'max_value':
                        $validationType = $key;
                        $validations['max_inclusive'] = $value;
                        $validationHint = $this->Field->getMessage('CustomField.number.maxValue', ['sprintf' => $value]);
                        break;
                    case 'range':
                        $validationType = $key;
                        $validations['min_inclusive'] = $value['lower'];
                        $validations['max_inclusive'] = $value['upper'];
                        $validationHint = $this->Field->getMessage('CustomField.number.range', ['sprintf' => [$value['lower'], $value['upper']]]);
                        break;
                }
            }
        }

        if (!is_null($validationType)) {
            $bindType = "integer" . Inflector::camelize($validationType) . $extra['index'];

            // introduce subIndex to handle question inside repeater has validation
            $subIndex = $extra['subIndex'];
            if (!empty($subIndex)) {
                $bindType .= "_$subIndex";
            }
            // End

            $schemaNode = $extra['schema'];
            $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
            $simpleType->addAttribute("name", $bindType);

            $restriction = $simpleType->addChild('restriction', null, NS_XSD);
            $restriction->addAttribute("base", "xf:integer");

            foreach ($validations as $key => $value) {
                $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                $condition->addAttribute("value", $value);
            }
        }

        $extra['tagName'] = 'input';
        $extra['bindType'] = $bindType;
        $extra['hint'] = !empty($validationHint) ? $validationHint : null;
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function decimal($field, $parentNode, $instanceId, $extra)
    {
        $bindType = 'decimal';
        $constraint = null;
        $validationType = null;
        $validations = [];
        $validationHint = '';

        $generateRangeValues = function ($length, $precision = 0) {
            $range = str_repeat('9', $length);
            if ($precision > 0) {
                $range .= '.' . str_repeat('9', $precision);
            }
            return $range;
        };

        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);

            $length = $params['length'];
            $precision = $params['precision'];

            // for positive values
            $validations['min_inclusive'] = 0;
            $validations['max_inclusive'] = $generateRangeValues($length, $precision);

            if ($precision == 0) {
                $validationType = 'total_digits';
                $validationHint = $this->Field->getMessage('CustomField.decimal.length', ['sprintf' => [$length]]);
            } else {
                $validationType = 'fraction_digits';
                $validations['fraction_digits'] = $precision;
                $validationHint = $this->Field->getMessage('CustomField.decimal.precision', ['sprintf' => [$length, $precision]]);
            }
        }

        if (!is_null($validationType)) {
            $bindType = "decimal" . Inflector::camelize($validationType) . $extra['index'];

            // introduce subIndex to handle question inside repeater has validation
            $subIndex = $extra['subIndex'];
            if (!empty($subIndex)) {
                $bindType .= "_$subIndex";
            }
            // End

            $schemaNode = $extra['schema'];
            $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
            $simpleType->addAttribute("name", $bindType);

            $restriction = $simpleType->addChild('restriction', null, NS_XSD);
            $restriction->addAttribute("base", "xf:decimal");

            foreach ($validations as $key => $value) {
                $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                $condition->addAttribute("value", $value);
            }
        }

        $extra['tagName'] = 'input';
        $extra['bindType'] = $bindType;
        $extra['hint'] = !empty($validationHint) ? $validationHint : null;
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function textarea($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'textarea';
        $extra['bindType'] = 'string';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function dropdown($field, $parentNode, $instanceId, $extra)
    {
        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $dropdownNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select1', $extra);
        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                if ($fieldOption->is_default) {
                    // to set default value in Head > Model > instance e.g. <oe:SurveyQuestions id='5'>default value here</oe:SurveyQuestions>
                    $extra['default_value'] = $fieldOption->id;
                }

                $itemNode = $dropdownNode->addChild("item", null, NS_XF);
                $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $extra['type'] = 'integer';
        $extra['required'] = $field->default_is_mandatory;

        $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
    }

    private function checkbox($field, $parentNode, $instanceId, $extra)
    {
        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $checkboxNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select', $extra);
        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                $itemNode = $checkboxNode->addChild("item", null, NS_XF);
                $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $extra['type'] = 'integer';
        $extra['required'] = $field->default_is_mandatory;

        $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
    }

    private function table($field, $parentNode, $instanceId, $extra)
    {
        // To nested table inside xform group
        $tableBreakNode = $parentNode->addChild('group', null, NS_XF);
        $tableBreakNode->addAttribute("ref", $field->field_id);
        $tableBreakNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);
        $tableBreakNode->addAttribute("oe-type", "table");
        // End

        $tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
        $tableNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
        $tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
        $tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
        $repeatNode = $tableBody->addChild("repeat", null, NS_XF);
        $repeatNode->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], [$this->TableRow->alias()])));
        $tbodyRow = $repeatNode->addChild("tr", null, NS_XHTML);

        $tableColumnResults = $this->TableColumn
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->TableColumn->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $tableRowResults = $this->TableRow
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->TableRow->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        if (!$tableColumnResults->isEmpty() && !$tableRowResults->isEmpty()) {
            $tableColumns = $tableColumnResults->toArray();
            $tableRows = $tableRowResults->toArray();

            $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);
            $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

            // start validation constraint
            $inputType = 'string';
            $constraint = null;
            $validationType = null;
            $validations = [];
            $validationHint = '';

            if ($field->has('params') && !empty($field->params)) {
                $params = json_decode($field->params, true);

                if (array_key_exists('number', $params)) {
                    $inputType = 'integer';

                    $validationRules = $params['number'];
                    if (is_array($validationRules)) {
                        foreach ($validationRules as $key => $value) {
                            switch ($key) {
                                case 'min_value':
                                    $validationType = $key;
                                    $validations['min_inclusive'] = $value;
                                    $validationHint = $this->Field->getMessage('CustomField.number.minValue', ['sprintf' => $value]);
                                    break;
                                case 'max_value':
                                    $validationType = $key;
                                    $validations['max_inclusive'] = $value;
                                    $validationHint = $this->Field->getMessage('CustomField.number.maxValue', ['sprintf' => $value]);
                                    break;
                                case 'range':
                                    $validationType = $key;
                                    $validations['min_inclusive'] = $value['lower'];
                                    $validations['max_inclusive'] = $value['upper'];
                                    $validationHint = $this->Field->getMessage('CustomField.number.range', ['sprintf' => [$value['lower'], $value['upper']]]);
                                    break;
                            }
                        }
                    }
                } elseif (array_key_exists('decimal', $params)) {
                    $inputType = 'decimal';

                    $generateRangeValues = function ($length, $precision = 0) {
                        $range = str_repeat('9', $length);
                        if ($precision > 0) {
                            $range .= '.' . str_repeat('9', $precision);
                        }
                        return $range;
                    };

                    $validationRules = $params['decimal'];
                    $length = $validationRules['length'];
                    $precision = $validationRules['precision'];

                    // for positive values
                    $validations['min_inclusive'] = 0;
                    $validations['max_inclusive'] = $generateRangeValues($length, $precision);

                    if ($precision == 0) {
                        $validationType = 'total_digits';
                        $validationHint = $this->Field->getMessage('CustomField.decimal.length', ['sprintf' => [$length]]);
                    } else {
                        $validationType = 'fraction_digits';
                        $validations['fraction_digits'] = $precision;
                        $validationHint = $this->Field->getMessage('CustomField.decimal.precision', ['sprintf' => [$length, $precision]]);
                    }
                }
            }

            if (!is_null($validationType)) {
                $bindType = $inputType . Inflector::camelize($validationType) . $extra['index'];

                // introduce subIndex to handle question inside repeater has validation
                $subIndex = $extra['subIndex'];
                if (!empty($subIndex)) {
                    $bindType .= "_$subIndex";
                }
                // End

                $schemaNode = $extra['schema'];
                $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                $simpleType->addAttribute("name", $bindType);

                $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                $restriction->addAttribute("base", "xf:" . $inputType);

                foreach ($validations as $key => $value) {
                    $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                    $condition->addAttribute("value", $value);
                }
            } else {
                $bindType = $inputType;
            }

            $extra['type'] = $bindType;
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            // end validation constraint

            foreach ($tableRows as $row => $tableRow) {
                $rowNode = $fieldNode->addChild($this->TableRow->alias(), null, NS_OE);
                $rowNode->addAttribute("id", $tableRow->id);

                foreach ($tableColumns as $col => $tableColumn) {
                    if ($col == 0) {
                        $columnNode = $rowNode->addChild($this->TableColumn->alias() . $col, htmlspecialchars($tableRow->name, ENT_QUOTES), NS_OE);
                        $columnNode->addAttribute("id", $col);
                        $cellType = 'output';
                        $cellLabel = $tableRow->name;
                        $cellHint = null;
                    } else {
                        $columnNode = $rowNode->addChild($this->TableColumn->alias() . $col, null, NS_OE);
                        $columnNode->addAttribute("id", $tableColumn->id);
                        $cellType = 'input';
                        $cellLabel = $tableRow->name;
                        $cellHint = !is_null($extra['hint']) ? $extra['hint'] : null;
                    }

                    if ($row == 0) {
                        $tableHeader->addChild("th", htmlspecialchars($tableColumn->name, ENT_QUOTES), NS_XHTML);
                        $tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
                        $tbodyCell = $tbodyColumn->addChild($cellType, null, NS_XF);
                        $tbodyCell->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], [$this->TableColumn->alias() . $col])));

                        $tbodyCell->addChild("label", htmlspecialchars($cellLabel, ENT_QUOTES), NS_XF);
                        if (!empty($cellHint)) {
                            $tbodyCell->addChild("hint", htmlspecialchars($cellHint, ENT_QUOTES), NS_XF);
                        }

                        $this->setBindNode($extra['model'], $instanceId, array_merge($extra['references'], [$this->TableColumn->alias() . $col]), $extra);
                    }
                }
            }
        }
    }


    private function date($field, $parentNode, $instanceId, $extra)
    {
        $constraint = null;
        $validationHint = '';
        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);

            $startDate = array_key_exists('start_date', $params) ? $params['start_date'] : null;
            $endDate = array_key_exists('end_date', $params) ? $params['end_date'] : null;

            if (!is_null($startDate) && !is_null($endDate)) {
                $constraint = ". >= '" . $startDate . "'' && " . ". <= '" . $endDate . "'";
                $validationHint = $this->Field->getMessage('CustomField.date.between', ['sprintf' => [$startDate, $endDate]]);
            } elseif (!is_null($startDate)) {
                $constraint = ". >= '$startDate'";
                $validationHint = $this->Field->getMessage('CustomField.date.earlier', ['sprintf' => $startDate]);
            } elseif (!is_null($endDate)) {
                $constraint = ". <= '$endDate'";
                $validationHint = $this->Field->getMessage('CustomField.date.later', ['sprintf' => $endDate]);
            }
        }

        $extra['tagName'] = 'input';
        $extra['bindType'] = 'date';
        $extra['hint'] = !empty($validationHint) ? $validationHint : null;
        $extra['constraint'] = !empty($constraint) ? $constraint : null;

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function time($field, $parentNode, $instanceId, $extra)
    {
        $constraint = null;
        $validationHint = '';
        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);

            $startTime = array_key_exists('start_time', $params) ? $params['start_time'] : null;
            $endTime = array_key_exists('end_time', $params) ? $params['end_time'] : null;

            if (!is_null($startTime) && !is_null($endTime)) {
                $constraint = ". >= '" . $this->twentyFourHourFormat($startTime) . "'' && " . ". <= '" . $this->twentyFourHourFormat($endTime) . "'";
                $validationHint = $this->Field->getMessage('CustomField.time.between', ['sprintf' => [$startTime, $endTime]]);
            } elseif (!is_null($startTime)) {
                $constraint = ". >= '" . $this->twentyFourHourFormat($startTime) . "'";
                $validationHint = $this->Field->getMessage('CustomField.time.earlier', ['sprintf' => $startTime]);
            } elseif (!is_null($endTime)) {
                $constraint = ". <= '" . $this->twentyFourHourFormat($endTime) . "'";
                $validationHint = $this->Field->getMessage('CustomField.time.later', ['sprintf' => $endTime]);
            }
        }

        $extra['tagName'] = 'input';
        $extra['bindType'] = 'time';
        $extra['hint'] = !empty($validationHint) ? $validationHint : null;
        $extra['constraint'] = !empty($constraint) ? $constraint : null;

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function coordinates($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'input';
        $extra['bindType'] = 'geopoint';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function file($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'upload';
        $extra['bindType'] = 'file';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function repeater($field, $parentNode, $instanceId, $extra)
    {
        $repeaterNode = $this->setBodyNode($field, $parentNode, $instanceId, 'repeat', $extra);

        $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);
        $repeatNode = $fieldNode->addChild('RepeatBlock', null, NS_OE);
        $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

        $formId = null;
        // Get Survey Form ID
        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);
            if (array_key_exists($this->formKey, $params)) {
                $formId = $params[$this->formKey];
            }
        }

        if (!is_null($formId)) {
            $fields = $this->getFields($formId);

            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    $index = $key + 1;
                    $extra['subIndex'] = $index;
                    // must reset to null
                    $extra['default_value'] = null;
                    $extra['references'] = [$this->Form->alias(), $this->Field->alias() . "[" . $extra['index'] . "]", 'RepeatBlock', $this->Field->alias() . $index];
                    $extra['hint'] = null; // reset hint

                    $fieldTypeFunction = strtolower($field->field_type);
                    if (method_exists($this, $fieldTypeFunction)) {
                        $this->$fieldTypeFunction($field, $repeaterNode, $instanceId, $extra);

                        // add to Head > Model > Instance > RepeatBlock here
                        $repeatBlockNode = $repeatNode->addChild($this->Field->alias() . $index, $extra['default_value'], NS_OE);
                        $repeatBlockNode->addAttribute("id", $field->field_id);
                    }
                }
            }
        } else {
            // Survey Form ID not found
            Log::write('debug', 'Repeater Survey Form ID is not configured.');
        }
        // End
    }

    private function note($field, $parentNode, $instanceId, $extra)
    {
        $noteBreakNode = $parentNode->addChild('group', null, NS_XF);
        $noteBreakNode->addAttribute("ref", $field->field_id);
        $noteBreakNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);
        $noteBreakNode->addAttribute("oe-type", "note");
        $noteBreakNode->addChild("p", htmlspecialchars($field->default_description, ENT_QUOTES), NS_XHTML);
    }

    private function setCommonNode($field, $parentNode, $instanceId, $extra)
    {
        $tagName = array_key_exists('tagName', $extra) ? $extra['tagName'] : 'input';
        $bindType = array_key_exists('bindType', $extra) ? $extra['bindType'] : 'string';

        $this->setBodyNode($field, $parentNode, $instanceId, $tagName, $extra);
        $extra['type'] = $bindType;
        $extra['required'] = $field->default_is_mandatory;

        if (isset($extra['constraint']) && empty($extra['constraint'])) {
            unset($extra['constraint']);
        }
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
    }

    private function setBodyNode($field, $parentNode, $instanceId, $fieldType, $extra)
    {
        $fieldNode = $parentNode->addChild($fieldType, null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
        $fieldNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);

        if (!empty($extra['hint'])) {
            // <xf:hint>Text should be at least 10 characters</xf:hint>
            $fieldNode->addChild("hint", htmlspecialchars($extra['hint'], ENT_QUOTES), NS_XF);
        }

        return $fieldNode;
    }

    private function setBindNode($modelNode, $instanceId, $references = [], $attr = [])
    {
        $bindType = array_key_exists('type', $attr) ? $attr['type'] : 'string';
        $required = array_key_exists('required', $attr) ? $attr['required'] : false;
        $constraint = array_key_exists('constraint', $attr) ? $attr['constraint'] : null;

        $bindNode = $modelNode->addChild("bind", null, NS_XF);
        $bindNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $bindNode->addAttribute("type", $bindType);

        if ($required) {
            $bindNode->addAttribute("required", 'true()');
        } else {
            $bindNode->addAttribute("required", 'false()');
        }

        if (!is_null($constraint)) {
            // <xf:bind constraint=". &gt;= 5 &amp;&amp; . &lt;= 15" ref="instance('xform')/SurveyForms/SurveyQuestions[1]" required="false()" type="integer"/>
            $bindNode->addAttribute("constraint", $constraint);
        }

        if (isset($attr['rules'])) {
            $questionId = $attr['field_id'];
            $attr['rules']['dependent_question_mapping'][$questionId] = $attr['references'][1];
            if (isset($attr['rules'][$questionId])) {
                $rules = $attr['rules'][$questionId];
                $relevancy = '';
                $tmp = [];
                foreach ($rules as $key => $options) {
                    $dependentQuestion = $attr['rules']['dependent_question_mapping'][$key];
                    $options = json_decode($options);
                    foreach ($options as $option) {
                        $tmp[] = '../' . $dependentQuestion . ' eq ' . $option;
                    }
                }
                $relevancy = implode(' &#38;&#38; ', $tmp);
                $bindNode->addAttribute("relevant", $relevancy);
            }
        }

        return $bindNode;
    }

    private function setModelNode($field, $formNode, $instanceId, $extra)
    {
        $fieldNode = $formNode->addChild($this->Field->alias(), $extra['default_value'], NS_OE);
        $fieldNode->addAttribute("id", $field->field_id);

        return $fieldNode;
    }

    private function getRef($instanceId, $references = [])
    {
        $ref = "instance('" . $instanceId . "')";
        if (!empty($references)) {
            foreach ($references as $reference) {
                $ref .= "/$reference";
            }
        }

        return $ref;
    }

    private function twentyFourHourFormat($value)
    {
        $time = date("H:i:s", strtotime($value));
        return $time;
    }

    private function deleteExpiredResponse()
    {
        $SurveyResponses = TableRegistry::get('Survey.SurveyResponses');
        $expiryDate = new Time();
        $expiryDate->subDays(3);
        $SurveyResponses->deleteAll([
            $SurveyResponses->aliasField('created <') => $expiryDate
        ]);
    }

    private function addResponse($xmlResponse)
    {
        $SurveyResponses = TableRegistry::get('Survey.SurveyResponses');
        $responseData = [
            'id' => Text::uuid(),
            'response' => $xmlResponse
        ];

        $responseEntity = $SurveyResponses->newEntity($responseData);
        if (!$SurveyResponses->save($responseEntity)) {
            Log::write('debug', $responseEntity->errors());
        }
    }
    //POCOR-7857 start
    public function stafflist($format = "xform", $id = 0, $insCode = 0, $academicPeriod = 0, $surveyQuesId = 0, $output = true)
    {

        switch ($format) {
            case 'xform':
                $result = $this->getYList($format, $id, $insCode, $academicPeriod, $surveyQuesId);
                break;
            default:
                break;
        }

        if ($output) { // true = output to screen
            if (is_object($result)) {
                $this->response->body($result->asXML());
            } else {
                $this->response->body($result);
            }
            $this->response->type('xml');

            return $this->response;
        } else { // download as file
            $fileName = $format . '_' . date('Ymdhis');

            $this->response->body($result->asXML());
            $this->response->type('xml');

            // Optionally force file download
            $this->response->download($fileName . '.xml');

            // Return response object to prevent controller from trying to render a view.
            return $this->response;
        }
    }
    public function getYList($instanceId, $id, $insCode, $acamic, $surveyQuesID)
    {

        $title = $this->Form->get($id)->name;
        $institutionStaffTbl = TableRegistry::get('Institutions.StaffTable');
        $SurveyFormsQuestionsTbl = TableRegistry::get('Survey.SurveyFormsQuestions');
        $institutionStaffSurveysTbl = TableRegistry::get('Staff.StaffSurveys');
        $surveyQuestionChoicesTbl = TableRegistry::get('Survey.SurveyQuestionChoices');
        $institution_staff_survey_answers_tbl = TableRegistry::get('Staff.StaffSurveyAnswers');

        $institutionTbl = TableRegistry::get('Institution.Institutions');
        $insData = $institutionTbl->find('all', ['conditions' => ['code' => $insCode]])->first();
        $insId = $insData->id;
        $academicPeriodTbl = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $apData = $academicPeriodTbl->find('all', ['conditions' => ['name' => $acamic]])->first();
        $apId = $apData->id;

        $join = [];
        $join['staff_info'] = [
            'type' => 'left',
            'table' => "(SELECT academic_periods.id academic_period_id,
                        institutions.id institution_id,
                        institution_staff.staff_id staff_id,
                        security_users.openemis_no openemis_no,
                        REPLACE(CONCAT_WS(' ', security_users.first_name, security_users.middle_name, security_users.third_name, security_users.last_name), '  ', ' ') staff_name
                   FROM institution_staff
                   INNER JOIN academic_periods 
                       ON (((`institution_staff`.`end_date` IS NOT NULL AND 
                            `institution_staff`.`start_date` <= `academic_periods`.`start_date` AND 
                            `institution_staff`.`end_date` >= `academic_periods`.`start_date`) 
                           OR (`institution_staff`.`end_date` IS NOT NULL 
                           AND `institution_staff`.`start_date` <= `academic_periods`.`end_date`
                           AND `institution_staff`.`end_date` >= `academic_periods`.`end_date`)
                           OR (`institution_staff`.`end_date` IS NOT NULL 
                           AND `institution_staff`.`start_date` >= `academic_periods`.`start_date`
                           AND `institution_staff`.`end_date` <= `academic_periods`.`end_date`)) 
                           OR (`institution_staff`.`end_date` IS NULL 
                           AND `institution_staff`.`start_date` <= `academic_periods`.`end_date`))
                   INNER JOIN institutions
                       ON institutions.id = institution_staff.institution_id
                   INNER JOIN security_users
                       ON security_users.id = institution_staff.staff_id
                   WHERE institution_staff.staff_status_id = 1
                   AND academic_periods.id = $apId
                   AND institution_id = $insId
               )",
            'conditions' => [
                'staff_info.academic_period_id = main_query.academic_period_id',
                'staff_info.institution_id = main_query.institution_id'
            ]
        ];

        // $join['staff_info'] = [
        //     'type' => 'left',
        //     'table' => "(SELECT academic_periods.id academic_period_id
        //                 ,institutions.id institution_id
        //                 ,institution_staff.staff_id staff_is
        //                 ,security_users.openemis_no openemis_no
        //                 ,REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' ') staff_name
        //                 FROM institution_staff
        //                 INNER JOIN academic_periods 
        //                     ON (((`institution_staff`.`end_date` IS NOT NULL AND 
        //                          `institution_staff`.`start_date` <= `academic_periods`.`start_date` AND 
        //                          `institution_staff`.`end_date` >= `academic_periods`.`start_date`) 
        //                     OR (`institution_staff`.`end_date` IS NOT NULL 
        //                     AND `institution_staff`.`start_date` <= `academic_periods`.`end_date`
        //                     AND `institution_staff`.`end_date` >= `academic_periods`.`end_date`)
        //                     OR (`institution_staff`.`end_date` IS NOT NULL 
        //                     AND `institution_staff`.`start_date` >= `academic_periods`.`start_date`
        //                     AND `institution_staff`.`end_date` <= `academic_periods`.`end_date`)) 
        //                     OR (`institution_staff`.`end_date` IS NULL 
        //                     AND `institution_staff`.`start_date` <= `academic_periods`.`end_date`))
        //                INNER JOIN institutions
        //                   ON institutions.id = institution_staff.institution_id
        //                INNER JOIN security_users
        //                   ON security_users.id = institution_staff.staff_id

        //             WHERE institution_staff.staff_status_id = 1
        //             AND academic_periods.id = $apId
        //             AND institution_id = $insId,
        //         )",
        //     'conditions' => [
        //         'staff_info.academic_period_id = main_query.academic_period_id',
        //         'staff_info.institution_id = main_query.institution_id'
        //     ]
        // ];
        $join['staff_survey_answers_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_staff_surveys.academic_period_id
            ,institution_staff_surveys.institution_id
            ,institution_staff_surveys.staff_id
            ,institution_staff_surveys.survey_form_id
            ,institution_staff_surveys.parent_form_id
            ,institution_staff_survey_answers.survey_question_id
            ,institution_staff_survey_answers.parent_survey_question_id
            ,survey_question_choices.id answer_choice_id_for_dropdown
            ,IF(institution_staff_survey_answers.id IS NULL, '', 
                IF(institution_staff_survey_answers.text_value IS NOT NULL, institution_staff_survey_answers.text_value, 
                    IF(institution_staff_survey_answers.decimal_value IS NOT NULL, institution_staff_survey_answers.decimal_value, 
                        IF(institution_staff_survey_answers.textarea_value IS NOT NULL, institution_staff_survey_answers.textarea_value, 
                            IF(institution_staff_survey_answers.date_value IS NOT NULL, institution_staff_survey_answers.date_value, 
                                IF(institution_staff_survey_answers.time_value IS NOT NULL, institution_staff_survey_answers.time_value, 
                                        IF(survey_question_choices.id IS NOT NULL, survey_question_choices.name, institution_staff_survey_answers.number_value))))))) survey_answer_values
            FROM institution_staff_survey_answers
            INNER JOIN institution_staff_surveys
            ON institution_staff_surveys.id = institution_staff_survey_answers.institution_staff_survey_id
            LEFT JOIN survey_question_choices
            ON survey_question_choices.id = institution_staff_survey_answers.number_value
            WHERE institution_staff_surveys.status_id = 1
            AND institution_staff_surveys.academic_period_id = $apId
            AND institution_staff_surveys.institution_id = $insId
            AND institution_staff_surveys.parent_form_id = $id)",
            'conditions' => [
                'staff_survey_answers_info.academic_period_id = staff_info.academic_period_id',
                'staff_survey_answers_info.institution_id = staff_info.institution_id',
                'staff_survey_answers_info.staff_id = staff_info.staff_id',
                'staff_survey_answers_info.survey_form_id = main_query.staff_list_survey_form_id',
                'staff_survey_answers_info.survey_question_id = main_query.staff_list_survey_question_id',
                'staff_survey_answers_info.parent_survey_question_id = main_query.institution_survey_question_id',
            ]
        ];

        $main_query  = "(SELECT institution_surveys.academic_period_id
                            ,institution_surveys.institution_id
                            ,institution_surveys.survey_form_id institution_survey_form_id
                            ,institution_forms.name institution_survey_form_name
                            ,survey_questions.id institution_survey_question_id
                            ,survey_forms_questions.section
                            ,staff_list_survey_forms_questions.order
                            ,survey_questions.name institution_survey_question_name
                            ,staff_list_survey_forms_questions.survey_form_id staff_list_survey_form_id
                            ,survey_list_forms.name staff_list_survey_form_name
                            ,staff_list_survey_questions.id staff_list_survey_question_id
                            ,staff_list_survey_questions.name staff_list_survey_question_name
                            ,staff_list_survey_questions.field_type staff_list_survey_question_type
                        FROM institution_surveys
                        INNER JOIN survey_forms institution_forms
                        ON institution_forms.id = institution_surveys.survey_form_id
                        INNER JOIN survey_forms_questions
                        ON survey_forms_questions.survey_form_id = institution_surveys.survey_form_id
                        INNER JOIN survey_questions
                        ON survey_questions.id = survey_forms_questions.survey_question_id
                        LEFT JOIN survey_forms_questions staff_list_survey_forms_questions
                        ON staff_list_survey_forms_questions.survey_form_id = JSON_EXTRACT(survey_questions.params, '$.survey_form_id')
                        LEFT JOIN survey_forms survey_list_forms
                        ON survey_list_forms.id = staff_list_survey_forms_questions.survey_form_id
                        LEFT JOIN survey_questions staff_list_survey_questions
                        ON staff_list_survey_questions.id = staff_list_survey_forms_questions.survey_question_id
                        WHERE institution_surveys.academic_period_id = $apId
                        AND institution_surveys.institution_id = $insId
                        AND institution_surveys.survey_form_id = $id
                        AND institution_surveys.status_id = 1
                        AND LENGTH(survey_questions.params) > 0
                        AND survey_questions.field_type = 'STAFF_LIST')";


        $query =  $institutionStaffSurveysTbl->find()
            ->select([
                'academic_period_id' => 'main_query.academic_period_id', 'institution_id' => 'main_query.institution_id', 'institution_form_id' => 'main_query.institution_survey_form_id', 'institution_form_name' => 'main_query.institution_survey_form_name', 'institutiton_survey_question_id' => 'main_query.institution_survey_question_id', 'section' => 'main_query.section', 'name' => 'main_query.institution_survey_question_name', 'staff_list_form_id' => 'main_query.staff_list_survey_form_id', 'staff_list_form_name' => 'main_query.staff_list_survey_form_name', 'staff_list_survey_question_id' => 'main_query.staff_list_survey_question_id', 'staff_list_survey_question_name' => 'main_query.staff_list_survey_question_name',
                'staff_list_survey_question_type' => 'main_query.staff_list_survey_question_type',
                'staff_id' => 'staff_info.staff_id',
                'openemis_no' => 'staff_info.openemis_no',
                'staff_name' => 'staff_info.staff_name',
                'staff_survey_answers_info.answer_choice_id_for_dropdown',
                'survey_answer' => 'staff_survey_answers_info.survey_answer_values'
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);
        //  echo "<pre>";print_r($query->toArray());die;

        $query2 =  $institutionStaffSurveysTbl->find()
            ->select([
                'institution_form_id' => 'main_query.institution_survey_form_id',
                'institution_form_name' => 'main_query.institution_survey_form_name',
                'staff_list_form_id' => 'main_query.staff_list_survey_form_id',
                'staff_list_form_name' => 'main_query.staff_list_survey_form_name',
                //'section' => "institution_forms_questions.section",
                'name' => 'main_query.institution_survey_question_name',
                //'institutiton_survey_question_id' => "institution_survey_questions.id",
                // 'institutiton_survey_question_name' => "institution_survey_questions.name",//--
                'staff_list_survey_question_id' => 'main_query.staff_list_survey_question_id',
                'staff_list_survey_question_name' => 'main_query.staff_list_survey_question_name',
                'staff_list_survey_question_type' => 'main_query.staff_list_survey_question_type',
                'institution_id' => 'main_query.institution_id',
                'staff_id' => 'staff_info.staff_id',
                'openemis_no' => 'staff_info.openemis_no',
                'staff_name' => 'staff_info.staff_name',

                'academic_period_id' => 'main_query.academic_period_id',

            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);


        $query3 =  $institutionStaffSurveysTbl->find()
            ->select([
                'question_order' => 'main_query.order',
                'staff_list_survey_question_id' => 'main_query.staff_list_survey_question_id',
                'staff_list_survey_question_name' => 'main_query.staff_list_survey_question_name',
                'staff_list_survey_question_type' => 'main_query.staff_list_survey_question_type',
                'institution_id' => 'main_query.institution_id'
            ])
            ->from(['main_query' => $main_query])
            ->join($join)
            ->order(['main_query.section' => 'DESC']);
        // echo "<pre>";print_r($query->toArray());die;
        $tabData = $query->group(['section'])->order(['institutiton_survey_question_id' => 'ASC']);


        $staffs = $query2->group(['staff_id'])->toArray();
        $questions = $query3->group(['staff_list_survey_question_id'])->order(['question_order' => 'ASC'])->toArray(); //POCOR-7771
        $finalData = [];

        $AnswerKeyArr = [];
        foreach ($tabData as $p => $tbDta) {
            $finalData[$tbDta->section]['parent_question_tab_id'] = $tbDta->institutiton_survey_question_id;
            $finalData[$tbDta->section]['staffs'] = $staffs;

            foreach ($finalData[$tbDta->section]['staffs'] as $ke => $staff) {

                $finalData[$tbDta->section]['staffs'][$ke]['questions'] = $questions;

                $ins_sta_survey = $institutionStaffSurveysTbl->find('all', ['conditions' => [
                    'status_id' => 1,
                    'institution_id' => $staff['institution_id'],
                    'staff_id' => $staff['staff_id'],
                    'academic_period_id' => $staff['academic_period_id'],
                    'survey_form_id' => $staff['staff_list_form_id'],
                    'parent_form_id' => $staff['institution_form_id'],
                ]])->first();
                $finalData[$tbDta->section]['staffs'][$ke]['institution_staff_survey_id'] = $ins_sta_survey['id'];
                foreach ($finalData[$tbDta->section]['staffs'][$ke]['questions'] as $jk => $ques) {

                    $options = $surveyQuestionChoicesTbl->find('all', ['fields' => ['id', 'name']])
                        ->where(['survey_question_id' => $ques['staff_list_survey_question_id']])->toArray();
                    $finalData[$tbDta->section]['staffs'][$ke]['questions'][$jk]['options'] = $options;


                    if (!empty($ins_sta_survey)) {

                        $dataExistAns = $institution_staff_survey_answers_tbl->find('all', ['conditions' => [
                            'survey_question_id' => $ques['staff_list_survey_question_id'],
                            'parent_survey_question_id' => $tbDta['institutiton_survey_question_id'],
                            'institution_staff_survey_id' => $staff['institution_staff_survey_id']
                        ]])->first();

                        if (!empty($dataExistAns)) {
                            if (!empty($dataExistAns['number_value'])) {
                                $selectVAlue = $dataExistAns['number_value'];
                            } elseif (!empty($dataExistAns['text_value'])) {
                                $selectVAlue = $dataExistAns['text_value'];
                            } elseif (!empty($dataExistAns['decimal_value'])) {
                                $selectVAlue = $dataExistAns['decimal_value'];
                            } elseif (!empty($dataExistAns['textarea_value'])) {
                                $selectVAlue = $dataExistAns['textarea_value'];
                            } elseif (!empty($dataExistAns['date_value'])) {
                                $selectVAlue = date('Y-m-d', strtotime($dataExistAns['date_value']));
                            } elseif (!empty($dataExistAns['time_value'])) {
                                $selectVAlue = date('h:i:s', strtotime($dataExistAns['date_value']));
                            }
                        } else {
                            $selectVAlue = $options[0]['id'];
                        }
                    }
                    $AnswerKeyArr['server_key'][$tbDta->section][$ke][$jk]['answer'][] = $selectVAlue;
                    // echo $selectVAlue."=="."<br>";

                    $finalData[$tbDta->section]['staffs'][$ke]['questions'][$jk]['survey_answer'] = $selectVAlue;

                    //echo "<pre>";print_r($dataExistAns->original());die;

                }
                //echo "quetion";
                //$studentData =$finalData;


            }
            //echo "student";


        }



        $final = [];
        $final['data'] = $finalData;
        $final['survey_answer_arr'] = $AnswerKeyArr;

        // echo "<pre>";
        // print_r($final);
        // die;

        $params = json_encode($final, true);
        echo $params;
        die;
    }
    private function staff_list($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'staff_list';
        $extra['is_staff_list_field'] = 'yesss';
        $extra['bindType'] = 'string';
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }
    private function uploadStaffList($field, $entity, $extra)
    {
        $thresholdDataaa = json_decode($extra['value'], true);

        $InstitutionStaffSurveysTbl = TableRegistry::get('Staff.StaffSurveys');
        $InstitutionStaffSurveyAnswersTbl = TableRegistry::get('Staff.StaffSurveyAnswers');
        $staffData = $thresholdDataaa;
        foreach ($staffData as $w => $staff) {
            $alreadyExistData =  $InstitutionStaffSurveysTbl->find('all', ['conditions' => [
                'status_id' => 1,
                'institution_id' => $staff['institution_id'],
                'staff_id' => $staff['staff_id'],
                'academic_period_id' => $staff['academic_period_id'],
                'survey_form_id' => $staff['staff_list_form_id'],
                'parent_form_id' => $staff['institution_form_id'],
            ]])->first();
            if (empty($alreadyExistData)) {
                $nEntity = $InstitutionStaffSurveysTbl->newEntity([
                    'status_id' => 1,
                    'institution_id' => $staff['institution_id'],
                    'staff_id' => $staff['staff_id'],
                    'academic_period_id' => $staff['academic_period_id'],
                    'survey_form_id' => $staff['staff_list_form_id'],
                    'parent_form_id' => $staff['institution_form_id'],
                    'created_user_id' => 2,
                    'created' => date('Y-m-d H:i:s')
                ]);
                $successData = $InstitutionStaffSurveysTbl->save($nEntity);
            } else {
                $successData = $alreadyExistData;
            }
            if ($successData) {
                $questions = $staff['questions'];
                foreach ($questions as $t => $ques) {


                    $duplicateData11 = $InstitutionStaffSurveyAnswersTbl->find()
                        ->where([
                            'survey_question_id' => $ques['staff_list_survey_question_id'],
                            'parent_survey_question_id' => $staff['parent_survey_question_id'],
                            'institution_staff_survey_id' => $successData['id']
                        ])->toArray();
                    foreach ($duplicateData11 as $dup) {
                        $InstitutionStaffSurveyAnswersTbl->delete($dup);
                    }

                    if (!empty($ques['survey_answer'])) {
                        if (($ques['staff_list_survey_question_type'] == "DROPDOWN") || ($ques['staff_list_survey_question_type'] == "NUMBER")) {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "number_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['staff_list_survey_question_type'] == "TEXT") {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "text_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['staff_list_survey_question_type'] == "DECIMAL") {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "decimal_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['staff_list_survey_question_type'] == "TEXTAREA") {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "textarea_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['staff_list_survey_question_type'] == "DATE") {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "date_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        } else if ($ques['staff_list_survey_question_type'] == "TIME") {
                            $AnsEntity = $InstitutionStaffSurveyAnswersTbl->newEntity([
                                "time_value" => $ques['survey_answer'],
                                'survey_question_id' => $ques['staff_list_survey_question_id'],
                                'parent_survey_question_id' => $staff['parent_survey_question_id'],
                                'institution_staff_survey_id' => $successData['id'],
                                'created_user_id' => 2,
                                'created' => date('Y-m-d H:i:s')
                            ]);
                            $sucesDAta = $InstitutionStaffSurveyAnswersTbl->save($AnsEntity);
                        }
                    }
                }
            }
        }

        $this->processUpload('staff_list', ['sada']);
    }
    //POCOR-7857 end
}
