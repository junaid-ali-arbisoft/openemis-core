<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\Log\Log;
use Cake\View\Helper\IdGeneratorTrait;
use ControllerAction\Model\Traits\PickerTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;

class RenderStudentListBehavior extends RenderBehavior
{

    use IdGeneratorTrait;
    use PickerTrait;
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

	public function onGetCustomStudentListElement(Event $event, $action, $entity, $attr, $options=[]) {
        $CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
        $CustomFields = TableRegistry::get('Survey.SurveyQuestions');
        $CustomFormsFields = TableRegistry::get('Survey.SurveyFormsQuestions');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $StudentSurveys = TableRegistry::get('Student.StudentSurveys');
        $StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');

        $model = $this->_table;
        $session = $model->request->session();
        $registryAlias = $model->registryAlias();
        $debugInfo = $model->alias() . ' #'.$entity->id.' (Institution ID: ' . $entity->institution_id . ', Academic Period ID: ' . $entity->academic_period_id . ', Survey Form ID: ' . $entity->survey_form_id . ')';

        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        $customField = $attr['customField'];
        $fieldKey = $attr['attr']['fieldKey'];
        $formKey = $attr['attr']['formKey'];
        $fieldId = $customField->id;

        $form = $event->subject()->Form;
        $fieldPrefix = $attr['model'] . '.institution_student_surveys.' . $fieldId;
        $unlockFields = [];
        $unlockFields[] = $fieldPrefix.".institution_class";

        $classOptions = [];
        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;

        $formId = null;
        // Get Survey Form ID
        if ($customField->has('params') && !empty($customField->params)) {
            $params = json_decode($customField->params, true);
            if (array_key_exists('survey_form_id', $params)) {
                $formId = $params['survey_form_id'];
            }
        }
        // End
        //echo "<pre>";print_r(count($entity['institution_student_surveys']));die;
        $session->write('SurveyTabCount', count($entity['institution_student_surveys']));
        $tabCount = count($entity['institution_student_surveys']);
        $tabID = $attr['customField']['id']; //POCOR-7730
        if($tabCount == 1){ 
            if (!is_null($formId)) {
                $questions = $CustomFormsFields
                    ->find('all')
                    ->innerJoin([$CustomFields->alias() => $CustomFields->table()],
                        [
                            $CustomFields->aliasField('id = ') . $CustomFormsFields->aliasField($fieldKey),
                        ]
                    )
                    ->contain([
                        'CustomFields.CustomFieldOptions' => function($q) {
                            return $q
                                ->find('visible')
                                ->find('order');
                        }
                    ])
                    ->order([$CustomFormsFields->aliasField('order')])
                    ->where([$CustomFormsFields->aliasField($formKey) => $formId])
                    ->group([$CustomFormsFields->aliasField($fieldKey)])
                    ->toArray();
    
                if (!empty($questions)) {
                    $institutionId = $entity->institution_id;
                    $periodId = $entity->academic_period_id;
    
                    // Classes Options
                    $classQuery = $Classes
                        ->find('list')
                        ->where([
                            $Classes->aliasField('institution_id') => $institutionId,
                            $Classes->aliasField('academic_period_id') => $periodId
                        ]);
    
                    if ($model->AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
                        // All Classes
                        $classOptions = $classQuery->toArray();
                    } else if ($model->AccessControl->check(['Institutions', 'Classes', 'index'])) {
                        // My Classes
                        $userId = $model->Auth->user('id');
                        $classQuery->where([
                            $Classes->aliasField('staff_id') => $userId
                        ]);
    
                        $classOptions = $classQuery->toArray();
                    }
                    // End
    
                    // Build table header
                    $headerHtml = __('OpenEMIS ID');
                    $headerHtml .= $form->hidden("$fieldPrefix.$formKey", ['value' => $formId]);
                    $unlockFields[] = "$fieldPrefix.$formKey";
                    $tableHeaders[] = $headerHtml;
                    $tableHeaders[] = __('Student Name');
                    $colOffset = 2; // 0 -> OpenEMIS ID, 1 -> Student Name
    
                    foreach ($questions as $colKey => $question) {
                        $questionName = !is_null($question->name) ? $question->name : $question->custom_field->name;
                        $tableHeaders[$colKey + $colOffset] = $questionName;
                    }
                    // End
    
                    if (!empty($classOptions)) {
                        // Set selectedClass to session and read it back.
                        $selectedClass = key($classOptions);
                        $sessionKey = "$registryAlias.institution_student_surveys.$fieldId.institution_class";
    
                        if ($model->request->is(['get'])) {
                            // Clear session if is not redirect from save
                            $requestQuery = $model->request->query;
                            if (array_key_exists('field_id', $requestQuery) && array_key_exists('class_id', $requestQuery)) {
                                if ($requestQuery['field_id'] == $fieldId) {
                                    $session->write($sessionKey, $requestQuery['class_id']);
                                }
                            }
                        } else if ($model->request->is(['post', 'put'])) {
                            $requestData = $model->request->data;
                            $submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';
    
                            if (isset($requestData[$model->alias()]['institution_student_surveys'][$fieldId]['institution_class'])) {
                                $session->write($sessionKey, $requestData[$model->alias()]['institution_student_surveys'][$fieldId]['institution_class']);
                            }
    
                            if ($submit == 'save') {
                            } else {
                                // only reset values from sessions when reload
                                $surveySessionKey = "$registryAlias.student_surveys";
                                if ($session->check($surveySessionKey)) {
                                    $entity->institution_student_surveys = $session->read($surveySessionKey);
                                    $session->delete($surveySessionKey);
                                }
                            }
                        }
    
                        if ($session->check($sessionKey)) {
                            $selectedClass = $session->read($sessionKey);
                        }
                        // End
                        $model->advancedSelectOptions($classOptions, $selectedClass);
    
                        // Students List
                        $studentQuery = $ClassStudents
                            ->find()
                            ->contain(['Users']);
    
                        if ($action == 'view' || $action == 'edit') {
                            $studentQuery
                                ->where([
                                    $ClassStudents->aliasField('institution_class_id') => $selectedClass
                                ]);
                        }
    
                        $students = $studentQuery->toArray();
                        // End
    
                        if (!empty($students)) {
                            $fieldTypes = $CustomFieldTypes
                                ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                                ->toArray();
    
                            foreach ($students as $rowKey => $student) {
                                $studentId = $student->student_id;
                                $rowPrefix = "$fieldPrefix.$studentId";
    
                                $rowData = [];
                                $rowInput = "";
    
                                if ($action == 'view') {
                                    $rowData[] = $event->subject->Html->link($student->user->openemis_no, [
                                        'plugin' => 'Institution',
                                        'controller' => 'Institutions',
                                        'action' => 'StudentUser',
                                        'view',
                                        $model->paramsEncode(['id' => $student->user->id])
                                    ]);
                                    $rowData[] = $student->user->name;
                                } else if ($action == 'edit') {
                                    if (isset($entity->institution_student_surveys[$fieldId][$studentId]['id'])) {
                                        $rowInput .= $form->hidden($rowPrefix.".id", ['value' => $entity->institution_student_surveys[$fieldId][$studentId]['id']]);
                                        $unlockFields[] = $rowPrefix.".id";
                                    }
    
                                    $rowData[] = $student->user->openemis_no . $rowInput;
                                    $rowData[] = $student->user->name;
                                }
    
                                foreach ($questions as $colKey => $question) {
                                    $questionId = $question->custom_field->id;
                                    $questionType = $question->custom_field->field_type;
    
                                    $cellPrefix = "$rowPrefix.$questionId";
                                    $cellInput = "";
                                    $cellValue = "";
                                    $cellOptions = ['label' => false, 'value' => ''];
                                    $answerObj = null;
    
                                    // put back answer value for edit and validation failed
                                    if (isset($entity->institution_student_surveys[$fieldId][$studentId][$questionId])) {
                                        //$answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                        //POCOR-7730
                                        $studentsurvy = $StudentSurveys->find('all',['conditions'=>[
                                            'status_id' => $entity->status_id,
                                            'institution_id' => $entity->institution_id,
                                            'student_id' => $studentId,
                                            'academic_period_id' => $entity->academic_period_id,
                                            'parent_form_id' => $entity->survey_form_id
                                        ]])->first();
                                        //POCOR-7730
                                    }
    
                                    switch ($questionType) {
                                        case 'TEXT':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['text_value']) ? $answerObj['text_value'] : null;
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'TEXTAREA':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['textarea_value']) ? $answerObj['textarea_value'] : null;
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'TIME':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['time_value']) ? $answerObj['time_value'] : null;
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'NUMBER':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;
    
                                            $cellOptions['type'] = 'number';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'DECIMAL':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['decimal_value']) ? $answerObj['decimal_value'] : null;
    
                                            $cellOptions['type'] = 'number';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            if ($question->has('custom_field') && $question->custom_field->has('params')) {
                                                $params = json_decode($question->custom_field->params, true);
    
                                                $cellOptions['min'] = 0;
                                                $step = $this->getStepFromParams($params);
                                                if (!is_null($step)) {
                                                    $cellOptions['step'] = $step;
                                                }
                                            }
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'DROPDOWN':
                                           
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;
                                            $dropdownOptions = [];
                                            $dropdownDefault = null;
                                            foreach ($question->custom_field->custom_field_options as $key => $obj) {
                                                $dropdownOptions[$obj->id] = $obj->name;
                                                if ($obj->is_default == 1) {
                                                    $dropdownDefault = $obj->id;
                                                }
                                            }
                                            $dropdownDefault = !is_null($dropdownDefault) ? $dropdownDefault : key($dropdownOptions);
    
                                            // for edit
                                            $cellOptions['type'] = 'select';
                                            $cellOptions['default'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                            $cellOptions['options'] = $dropdownOptions;
    
                                            // for view
                                            $cellValue = !is_null($answerValue) ? $dropdownOptions[$answerValue] : '';
                                            break;
                                            //POCOR-7660 start
                                        case 'DATE':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['date_value']) ? $answerObj['date_value'] : null;
    
                                            $_options = [
                                                'format' => 'dd-mm-yyyy',
                                                'todayBtn' => 'linked',
                                                'orientation' => 'auto',
                                                'autoclose' => true,
                                            ];
    
                                            $attr['date_options'] = $_options;
                                            $attr['id'] = $attr['model'] . '_' . $attr['field'];
    
                                            $attr['fieldName'] = $cellPrefix . "." . $fieldTypes[$questionType];
                                            if (array_key_exists('fieldName', $attr)) {
                                                $attr['id'] = $this->_domId($attr['fieldName']);
                                            }
    
                                            $defaultDate = false;
                                            if (!isset($attr['default_date'])) {
                                                $attr['default_date'] = $defaultDate;
                                            }
    
                                            if (!array_key_exists('value', $attr)) {
                                                if (!is_null($answerValue)) {
                                                    if ($answerValue instanceof Time || $answerValue instanceof Date) {
                                                        $attr['value'] = $answerValue->format('d-m-Y');
                                                    } else {
                                                        $attr['value'] = date('d-m-Y', strtotime($answerValue));
                                                    }
                                                } else if ($attr['default_date']) {
                                                    $attr['value'] = date('d-m-Y');
                                                }
                                            } else {
                                                if ($attr['value'] instanceof Time || $answerValue instanceof Date) {
                                                    $attr['value'] = $attr['value']->format('d-m-Y');
                                                } else {
                                                    $attr['value'] = date('d-m-Y', strtotime($attr['value']));
                                                }
                                            }
    
                                            $attr['null'] = !$attr['customField']['is_mandatory'];
    
                                            $event->subject()->viewSet('datepicker', $attr);
                                            $cellInput = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);
                                            $cellValue = !is_null($answerValue) ? $this->_table->formatDate($answerValue) : '';
                                            unset($attr['value']); // Need to unset so that it will not effect other Date or Time elements.
                                            break;
    
                                        default:
                                            break;
                                    }
                                    if (empty($cellInput)) {
                                        $cellInput .= $form->input($cellPrefix . "." . $fieldTypes[$questionType], $cellOptions);
                                    }//POCOR-7660 end
                                    $unlockFields[] = $cellPrefix . "." . $fieldTypes[$questionType];
    
                                    if ($action == 'view') {
                                        $rowData[$colKey+$colOffset] = $cellValue;
                                    } else if ($action == 'edit') {
                                        $rowData[$colKey+$colOffset] = $cellInput;
                                    }
                                }
    
                                $tableCells[$rowKey] = $rowData;
                            }
                        } else {
                            // No Student for the school in the academic period.
                            Log::write('debug', $debugInfo . ': Class ID: '.$selectedClass.' has no students.');
                        }
                    } else {
                        // No Classes of login user for the school in the academic period.
                        Log::write('debug', $debugInfo . ': Classes is empty.');
                    }
                } else {
                    // Survey Questions not setup for the form or not in the supported field type.
                    Log::write('debug', $debugInfo . ': Student List Survey Form ID: '.$formId.' has no questions.');
                }
            } else {
                // Survey Form ID not found
                Log::write('debug', $debugInfo . ': Student List Survey Form ID is not configured.');
            }
        }else{
            if (!is_null($formId)) {
                $questions = $CustomFormsFields
                    ->find('all')
                    ->innerJoin([$CustomFields->alias() => $CustomFields->table()],
                        [
                            $CustomFields->aliasField('id = ') . $CustomFormsFields->aliasField($fieldKey),
                        ]
                    )
                    ->contain([
                        'CustomFields.CustomFieldOptions' => function($q) {
                            return $q
                                ->find('visible')
                                ->find('order');
                        }
                    ])
                    ->order([$CustomFormsFields->aliasField('order')])
                    ->where([$CustomFormsFields->aliasField($formKey) => $formId])
                    ->group([$CustomFormsFields->aliasField($fieldKey)])
                    ->toArray();
    
                if (!empty($questions)) {
                    $institutionId = $entity->institution_id;
                    $periodId = $entity->academic_period_id;
    
                    // Classes Options
                    $classQuery = $Classes
                        ->find('list')
                        ->where([
                            $Classes->aliasField('institution_id') => $institutionId,
                            $Classes->aliasField('academic_period_id') => $periodId
                        ]);
    
                    if ($model->AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
                        // All Classes
                        $classOptions = $classQuery->toArray();
                    } else if ($model->AccessControl->check(['Institutions', 'Classes', 'index'])) {
                        // My Classes
                        $userId = $model->Auth->user('id');
                        $classQuery->where([
                            $Classes->aliasField('staff_id') => $userId
                        ]);
    
                        $classOptions = $classQuery->toArray();
                    }
                    // End
    
                    // Build table header
                    $headerHtml = __('OpenEMIS ID');
                    $headerHtml .= $form->hidden("$fieldPrefix.$formKey", ['value' => $formId]);
                    $unlockFields[] = "$fieldPrefix.$formKey";
                    $tableHeaders[] = $headerHtml;
                    $tableHeaders[] = __('Student Name');
                    $colOffset = 2; // 0 -> OpenEMIS ID, 1 -> Student Name
    
                    foreach ($questions as $colKey => $question) {
                        $questionName = !is_null($question->name) ? $question->name : $question->custom_field->name;
                        $tableHeaders[$colKey + $colOffset] = $questionName;
                    }
                    // End
    
                    if (!empty($classOptions)) {
                        // Set selectedClass to session and read it back.
                        $selectedClass = key($classOptions);
                        $sessionKey = "$registryAlias.institution_student_surveys.$fieldId.institution_class";
    
                        if ($model->request->is(['get'])) {
                            // Clear session if is not redirect from save
                            $requestQuery = $model->request->query;
                            if (array_key_exists('field_id', $requestQuery) && array_key_exists('class_id', $requestQuery)) {
                                if ($requestQuery['field_id'] == $fieldId) {
                                    $session->write($sessionKey, $requestQuery['class_id']);
                                }
                            }
                        } else if ($model->request->is(['post', 'put'])) {
                            $requestData = $model->request->data;
                            $submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';
    
                            if (isset($requestData[$model->alias()]['institution_student_surveys'][$fieldId]['institution_class'])) {
                                $session->write($sessionKey, $requestData[$model->alias()]['institution_student_surveys'][$fieldId]['institution_class']);
                            }
    
                            if ($submit == 'save') {
                            } else {
                                // only reset values from sessions when reload
                                $surveySessionKey = "$registryAlias.student_surveys";
                                if ($session->check($surveySessionKey)) {
                                    $entity->institution_student_surveys = $session->read($surveySessionKey);
                                    $session->delete($surveySessionKey);
                                }
                            }
                        }
    
                        if ($session->check($sessionKey)) {
                            $selectedClass = $session->read($sessionKey);
                        }
                        // End
                        $model->advancedSelectOptions($classOptions, $selectedClass);
    
                        // Students List
                        $studentQuery = $ClassStudents
                            ->find()
                            ->contain(['Users']);
    
                        if ($action == 'view' || $action == 'edit') {
                            $studentQuery
                                ->where([
                                    $ClassStudents->aliasField('institution_class_id') => $selectedClass
                                ]);
                        }
    
                        $students = $studentQuery->toArray();
                        // End
    
                        if (!empty($students)) {
                            $fieldTypes = $CustomFieldTypes
                                ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                                ->toArray();
    
                            foreach ($students as $rowKey => $student) {
                                $studentId = $student->student_id;
                                $rowPrefix = "$fieldPrefix.$studentId";
    
                                $rowData = [];
                                $rowInput = "";
    
                                if ($action == 'view') {
                                    $rowData[] = $event->subject->Html->link($student->user->openemis_no, [
                                        'plugin' => 'Institution',
                                        'controller' => 'Institutions',
                                        'action' => 'StudentUser',
                                        'view',
                                        $model->paramsEncode(['id' => $student->user->id])
                                    ]);
                                    $rowData[] = $student->user->name;
                                } else if ($action == 'edit') {
                                    if (isset($entity->institution_student_surveys[$fieldId][$studentId]['id'])) {
                                        $rowInput .= $form->hidden($rowPrefix.".id", ['value' => $entity->institution_student_surveys[$fieldId][$studentId]['id']]);
                                        $unlockFields[] = $rowPrefix.".id";
                                    }
    
                                    $rowData[] = $student->user->openemis_no . $rowInput;
                                    $rowData[] = $student->user->name;
                                }
    
                                foreach ($questions as $colKey => $question) {
                                    $questionId = $question->custom_field->id;
                                    $questionType = $question->custom_field->field_type;
    
                                    $cellPrefix = "$rowPrefix.$questionId";
                                    $cellInput = "";
                                    $cellValue = "";
                                    $cellOptions = ['label' => false, 'value' => ''];
                                    $answerObj = null;
    
                                    // put back answer value for edit and validation failed
                                    if (isset($entity->institution_student_surveys[$fieldId][$studentId][$questionId])) {
                                        //$answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                        //POCOR-7730
                                        $studentsurvy = $StudentSurveys->find('all',['conditions'=>[
                                            'status_id' => $entity->status_id,
                                            'institution_id' => $entity->institution_id,
                                            'student_id' => $studentId,
                                            'academic_period_id' => $entity->academic_period_id,
                                            'parent_form_id' => $entity->survey_form_id
                                        ]])->first();
                                        //POCOR-7730
                                    }
    
                                    switch ($questionType) {
                                        case 'TEXT':
                                            // $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            // $answerValue = !is_null($answerObj['text_value']) ? $answerObj['text_value'] : null;
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->text_value) ? $existFieldOption->text_value : (!is_null($answerObj['text_value']) ? $answerObj['text_value'] : null); //POCOR-7730
                                           
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'TEXTAREA':
                                            // $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            // $answerValue = !is_null($answerObj['textarea_value']) ? $answerObj['textarea_value'] : null;
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->textarea_value) ? $existFieldOption->textarea_value : (!is_null($answerObj['textarea_value']) ? $answerObj['textarea_value'] : null); //POCOR-7730
                                           
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'TIME':
                                            // $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            // $answerValue = !is_null($answerObj['time_value']) ? $answerObj['time_value'] : null;
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->time_value) ? $existFieldOption->time_value : (!is_null($answerObj['time_value']) ? $answerObj['time_value'] : null); //POCOR-7730
                                           
    
                                            $cellOptions['type'] = 'string';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'NUMBER':
                                            // $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            // $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->number_value) ? $existFieldOption->number_value : (!is_null($answerObj['number_value']) ? $answerObj['number_value'] : null); //POCOR-7730
                                           
    
                                            $cellOptions['type'] = 'number';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'DECIMAL':
                                            // $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            // $answerValue = !is_null($answerObj['decimal_value']) ? $answerObj['decimal_value'] : null;
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->decimal_value) ? $existFieldOption->decimal_value : (!is_null($answerObj['decimal_value']) ? $answerObj['decimal_value'] : null); //POCOR-7730
    
                                            $cellOptions['type'] = 'number';
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';
    
                                            if ($question->has('custom_field') && $question->custom_field->has('params')) {
                                                $params = json_decode($question->custom_field->params, true);
    
                                                $cellOptions['min'] = 0;
                                                $step = $this->getStepFromParams($params);
                                                if (!is_null($step)) {
                                                    $cellOptions['step'] = $step;
                                                }
                                            }
    
                                            $cellValue = !is_null($answerValue) ? $answerValue : '';
                                            break;
                                        case 'DROPDOWN':
                                            $existFieldOption = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $fieldId,'institution_student_survey_id'=>$studentsurvy->id ])->first(); //POCOR-7730
                                            $answerValue = !empty($existFieldOption->number_value) ? $existFieldOption->number_value : (!is_null($answerObj['number_value']) ? $answerObj['number_value'] : null); //POCOR-7730
                                           
                                           
    
                                            $dropdownOptions = [];
                                            $dropdownDefault = null;
                                            foreach ($question->custom_field->custom_field_options as $key => $obj) {
                                                $dropdownOptions[$obj->id] = $obj->name;
                                                if ($obj->is_default == 1) {
                                                    $dropdownDefault = $obj->id;
                                                }
                                            }
                                            $dropdownDefault = !is_null($dropdownDefault) ? $dropdownDefault : key($dropdownOptions);
    
                                            // for edit
                                            $cellOptions['type'] = 'select';
                                            $cellOptions['default'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                            $cellOptions['value'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                            $cellOptions['options'] = $dropdownOptions;
    
                                            // for view
                                            $cellValue = !is_null($answerValue) ? $dropdownOptions[$answerValue] : '';
                                            break;
                                            //POCOR-7660 start
                                        case 'DATE':
                                            $answerObj = $entity->institution_student_surveys[$fieldId][$studentId][$questionId];
                                            $answerValue = !is_null($answerObj['date_value']) ? $answerObj['date_value'] : null;
    
                                            $_options = [
                                                'format' => 'dd-mm-yyyy',
                                                'todayBtn' => 'linked',
                                                'orientation' => 'auto',
                                                'autoclose' => true,
                                            ];
    
                                            $attr['date_options'] = $_options;
                                            $attr['id'] = $attr['model'] . '_' . $attr['field'];
    
                                            $attr['fieldName'] = $cellPrefix . "." . $fieldTypes[$questionType];
                                            if (array_key_exists('fieldName', $attr)) {
                                                $attr['id'] = $this->_domId($attr['fieldName']);
                                            }
    
                                            $defaultDate = false;
                                            if (!isset($attr['default_date'])) {
                                                $attr['default_date'] = $defaultDate;
                                            }
    
                                            if (!array_key_exists('value', $attr)) {
                                                if (!is_null($answerValue)) {
                                                    if ($answerValue instanceof Time || $answerValue instanceof Date) {
                                                        $attr['value'] = $answerValue->format('d-m-Y');
                                                    } else {
                                                        $attr['value'] = date('d-m-Y', strtotime($answerValue));
                                                    }
                                                } else if ($attr['default_date']) {
                                                    $attr['value'] = date('d-m-Y');
                                                }
                                            } else {
                                                if ($attr['value'] instanceof Time || $answerValue instanceof Date) {
                                                    $attr['value'] = $attr['value']->format('d-m-Y');
                                                } else {
                                                    $attr['value'] = date('d-m-Y', strtotime($attr['value']));
                                                }
                                            }
    
                                            $attr['null'] = !$attr['customField']['is_mandatory'];
    
                                            $event->subject()->viewSet('datepicker', $attr);
                                            $cellInput = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);
                                            $cellValue = !is_null($answerValue) ? $this->_table->formatDate($answerValue) : '';
                                            unset($attr['value']); // Need to unset so that it will not effect other Date or Time elements.
                                            break;
    
                                        default:
                                            break;
                                    }
                                    if (empty($cellInput)) {
                                        $cellInput .= $form->input($cellPrefix . "." . $fieldTypes[$questionType], $cellOptions);
                                    }//POCOR-7660 end
                                    $unlockFields[] = $cellPrefix . "." . $fieldTypes[$questionType];
    
                                    if ($action == 'view') {
                                        $rowData[$colKey+$colOffset] = $cellValue;
                                    } else if ($action == 'edit') {
                                        $rowData[$colKey+$colOffset] = $cellInput;
                                    }
                                }
    
                                $tableCells[$rowKey] = $rowData;
                            }
                        } else {
                            // No Student for the school in the academic period.
                            Log::write('debug', $debugInfo . ': Class ID: '.$selectedClass.' has no students.');
                        }
                    } else {
                        // No Classes of login user for the school in the academic period.
                        Log::write('debug', $debugInfo . ': Classes is empty.');
                    }
                } else {
                    // Survey Questions not setup for the form or not in the supported field type.
                    Log::write('debug', $debugInfo . ': Student List Survey Form ID: '.$formId.' has no questions.');
                }
            } else {
                // Survey Form ID not found
                Log::write('debug', $debugInfo . ': Student List Survey Form ID is not configured.');
            }
        }
        

        $attr['attr']['classOptions'] = $classOptions;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function formatStudentListEntity(Event $event, Entity $entity, ArrayObject $settings) {
        $surveysArray = $entity->has('institution_student_surveys') ? $entity->institution_student_surveys : [];

        if (isset($entity->id)) {
            $fieldKey = $settings['fieldKey'];
            $formKey = $settings['formKey'];
            $customField = $settings['customField'];

            $params = json_decode($customField->params, true);
            if (array_key_exists($formKey, $params)) {
                $StudentSurveys = TableRegistry::get('Student.StudentSurveys');
                $StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');

                $status = $entity->status_id;
                $institutionId = $entity->institution_id;
                $periodId = $entity->academic_period_id;
                $formId = $params[$formKey];

                $surveysArray[$customField->id][$formKey] = $formId;
                $surveyResults = $StudentSurveys
                    ->find()
                    ->contain(['CustomFieldValues'])
                    ->where([
                        $StudentSurveys->aliasField('status_id') => $status,
                        $StudentSurveys->aliasField('institution_id') => $institutionId,
                        $StudentSurveys->aliasField('academic_period_id') => $periodId,
                        $StudentSurveys->aliasField($formKey) => $formId,
                        $StudentSurveys->aliasField('parent_form_id') => $entity->survey_form_id
                    ])
                    ->all();

                if (!$surveyResults->isEmpty()) {
                    foreach ($surveyResults as $survey) {
                        $answersArray = [];
                        if ($survey->has('custom_field_values')) {
                            foreach ($survey->custom_field_values as $answer) {
                                $answersArray[$answer->{$fieldKey}] = [
                                    'text_value' => $answer->text_value,
                                    'number_value' => $answer->number_value,
                                    'decimal_value' => $answer->decimal_value,
                                    'textarea_value' => $answer->textarea_value,
                                    'date_value' => $answer->date_value,
                                    'time_value' => $answer->time_value
                                ];
                            }
                        }
                        $surveysArray[$customField->id][$survey->student_id] = $answersArray;
                        $surveysArray[$customField->id][$survey->student_id]['id'] = $survey->id;
                    }
                }
            }
        }

        $model = $this->_table;
        $session = $model->request->session();
        $registryAlias = $model->registryAlias();
        $sessionKey = "$registryAlias.student_surveys";
        $session->write($sessionKey, $surveysArray);

        $entity->set('institution_student_surveys', $surveysArray);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
      //echo "<pre>";print_r($_SESSION['SurveyTabCount']);die;
        $tabcount = $_SESSION['SurveyTabCount'];

        if($tabcount == 1){
            if ($entity->has('institution_student_surveys')) {
                $fieldKey = 'survey_question_id';
                $formKey = 'survey_form_id';
                $StudentSurveys = TableRegistry::get('Student.StudentSurveys');
                $StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');
    
                $status = $entity->status_id;
                $institutionId = $entity->institution_id;
                $periodId = $entity->academic_period_id;
                $parentFormId = $entity->{$formKey};
                $parentIdd = (array_key_first($entity['institution_student_surveys']));//POCOR-7730
    
                foreach ($entity->institution_student_surveys as $fieldId => $fieldObj) {
                    $formId = $fieldObj[$formKey];
                    unset($fieldObj[$formKey]);
                    unset($fieldObj['institution_class']);
    
                    // Logic to delete all answers before re-insert
                    $studentIds = array_keys($fieldObj);
                    $surveyIds = [];
                    if (!empty($studentIds)) {
                        $surveyIds = $StudentSurveys
                            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                            ->where([
                                $StudentSurveys->aliasField('status_id') => $status,
                                $StudentSurveys->aliasField('institution_id') => $institutionId,
                                $StudentSurveys->aliasField('academic_period_id') => $periodId,
                                $StudentSurveys->aliasField($formKey) => $formId,
                                $StudentSurveys->aliasField('student_id IN ') => $studentIds
                            ])
                            ->toArray();
                    }
    
                    if (!empty($surveyIds)) {
                        $StudentSurveyAnswers->deleteAll([
                            $StudentSurveyAnswers->aliasField('institution_student_survey_id IN ') => $surveyIds//,
                            //$StudentSurveyAnswers->aliasField('parent_survey_question_id') => $parentFormId //POCOR-7730
                        ]);
                    }
                    // End
    
                    foreach ($fieldObj as $studentId => $studentObj) {
                        if (is_array($studentObj)) {
                            $surveyData = [
                                'status_id' => $status,
                                'institution_id' => $institutionId,
                                'academic_period_id' => $periodId,
                                $formKey => $formId,
                                'parent_form_id' => $parentFormId,
                                'student_id' => $studentId
                            ];
                            // for edit record
                            if (array_key_exists('id', $studentObj)) {
                                $surveyData['id'] = $studentObj['id'];
                                unset($studentObj['id']);
                            }
                            // End
                            $ir=0;
                            $answers = [];
                            foreach ($studentObj as $questionId => $answerObj) {
                                // checking to skip insert if is empty
                                $textValue = isset($answerObj['text_value']) && strlen($answerObj['text_value']) > 0 ? $answerObj['text_value'] : null;
                                $numberValue = isset($answerObj['number_value']) && strlen($answerObj['number_value']) > 0 ? $answerObj['number_value'] : null;
                                $decimalValue = isset($answerObj['decimal_value']) && strlen($answerObj['decimal_value']) > 0 ? $answerObj['decimal_value'] : null;
                                $textareaValue = isset($answerObj['textarea_value']) && strlen($answerObj['textarea_value']) > 0 ? $answerObj['textarea_value'] : null;
                                $dateValue = isset($answerObj['date_value']) && strlen($answerObj['date_value']) > 0 ? $answerObj['date_value'] : null;
                                $timeValue = isset($answerObj['time_value']) && strlen($answerObj['time_value']) > 0 ? $answerObj['time_value'] : null;
                                //POCOR-7730
                                $duplicateData11 = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $parentIdd,'institution_student_survey_id'=> $surveyData['id']])->toArray();
                                foreach($duplicateData11 as $dup){
                                    $StudentSurveyAnswers->delete($dup);
                                }
                                //POCOR-7730
                                if (!is_null($textValue) || !is_null($numberValue) || !is_null($decimalValue) || !is_null($textareaValue) || !is_null($dateValue) || !is_null($timeValue)) {
                                    $answerObj = array_merge($answerObj, [
                                        $fieldKey => $questionId
                                    ]);
    
                                    $answers[] = $answerObj;
                                    //$answers[$ir]['parent_survey_question_id'] = $parentIdd; //POCOR-7730
                                }
                                $ir++;
                            }
    
                            $surveyData['custom_field_values'] = $answers;
                            $surveyEntity = $StudentSurveys->newEntity($surveyData);
                            // save student by student
                            if ($StudentSurveys->save($surveyEntity)) {
                            } else {
                                Log::write('debug', $surveyEntity->errors());
                            }
                        }
                    }
                }
            }

        }else{
            if ($entity->has('institution_student_surveys')) {
                $fieldKey = 'survey_question_id';
                $formKey = 'survey_form_id';
                $StudentSurveys = TableRegistry::get('Student.StudentSurveys');
                $StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');
    
                $status = $entity->status_id;
                $institutionId = $entity->institution_id;
                $periodId = $entity->academic_period_id;
                $parentFormId = $entity->{$formKey};
                $parentIdd = array_keys($entity['institution_student_surveys'])[0];//POCOR-7730
    
                foreach ($entity->institution_student_surveys as $fieldId => $fieldObj) {
                    $formId = $fieldObj[$formKey];
                    unset($fieldObj[$formKey]);
                    unset($fieldObj['institution_class']);
    
                    // Logic to delete all answers before re-insert
                    $studentIds = array_keys($fieldObj);
                    $surveyIds = [];
                    if (!empty($studentIds)) {
                        $surveyIds = $StudentSurveys
                            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                            ->where([
                                $StudentSurveys->aliasField('status_id') => $status,
                                $StudentSurveys->aliasField('institution_id') => $institutionId,
                                $StudentSurveys->aliasField('academic_period_id') => $periodId,
                                $StudentSurveys->aliasField($formKey) => $formId,
                                $StudentSurveys->aliasField('student_id IN ') => $studentIds
                            ])
                            ->toArray();
                    }
    
                    if (!empty($surveyIds)) {
                        $StudentSurveyAnswers->deleteAll([
                            $StudentSurveyAnswers->aliasField('institution_student_survey_id IN ') => $surveyIds,
                            $StudentSurveyAnswers->aliasField('parent_survey_question_id') => $parentFormId //POCOR-7730
                        ]);
                    }
                    // End
    
                    foreach ($fieldObj as $studentId => $studentObj) {
                        if (is_array($studentObj)) {
                            $surveyData = [
                                'status_id' => $status,
                                'institution_id' => $institutionId,
                                'academic_period_id' => $periodId,
                                $formKey => $formId,
                                'parent_form_id' => $parentFormId,
                                'student_id' => $studentId
                            ];
                            // for edit record
                            if (array_key_exists('id', $studentObj)) {
                                $surveyData['id'] = $studentObj['id'];
                                unset($studentObj['id']);
                            }
                            // End
                            $ir=0;
                            $answers = [];
                            foreach ($studentObj as $questionId => $answerObj) {
                                // checking to skip insert if is empty
                                $textValue = isset($answerObj['text_value']) && strlen($answerObj['text_value']) > 0 ? $answerObj['text_value'] : null;
                                $numberValue = isset($answerObj['number_value']) && strlen($answerObj['number_value']) > 0 ? $answerObj['number_value'] : null;
                                $decimalValue = isset($answerObj['decimal_value']) && strlen($answerObj['decimal_value']) > 0 ? $answerObj['decimal_value'] : null;
                                $textareaValue = isset($answerObj['textarea_value']) && strlen($answerObj['textarea_value']) > 0 ? $answerObj['textarea_value'] : null;
                                $dateValue = isset($answerObj['date_value']) && strlen($answerObj['date_value']) > 0 ? $answerObj['date_value'] : null;
                                $timeValue = isset($answerObj['time_value']) && strlen($answerObj['time_value']) > 0 ? $answerObj['time_value'] : null;
                                //POCOR-7730
                                $duplicateData11 = $StudentSurveyAnswers->find()->where(['survey_question_id'=> $questionId,'parent_survey_question_id'=> $parentIdd,'institution_student_survey_id'=> $surveyData['id']])->toArray();
                                foreach($duplicateData11 as $dup){
                                    $StudentSurveyAnswers->delete($dup);
                                }
                                //POCOR-7730
                                if (!is_null($textValue) || !is_null($numberValue) || !is_null($decimalValue) || !is_null($textareaValue) || !is_null($dateValue) || !is_null($timeValue)) {
                                    $answerObj = array_merge($answerObj, [
                                        $fieldKey => $questionId
                                    ]);
    
                                    $answers[] = $answerObj;
                                    $answers[$ir]['parent_survey_question_id'] = $parentIdd; //POCOR-7730
                                }
                               
                            }
                            $ir++;
                            $surveyData['custom_field_values'] = $answers;
                            $surveyEntity = $StudentSurveys->newEntity($surveyData);
                            // save student by student
                            if ($StudentSurveys->save($surveyEntity)) {
                            } else {
                                Log::write('debug', $surveyEntity->errors());
                            }
                        }
                    }
                }
            }
        }
        
    }
}
