<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

class QualityStatus extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        //'RubricsTemplateHeader',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //  public $hasMany = array('RubricsTemplateColumnInfo');

    public $validate = array(
        'rubric_template_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Name.'
            )
        ),
        'date_enabled' => array(
            'ruleNotLater' => array(
                'rule' => array('compareDate', 'date_disabled'),
                'message' => 'Date Enabled cannot be later than Date Disabled'
            ),
        )
    );
    public $statusOptions = array('Date Disabled', 'Date Enabled');

    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }

    public function compareDate($field = array(), $compareField = null) {
        $startDate = new DateTime(current($field));
        $endDate = new DateTime($this->data[$this->name][$compareField]);
        return $endDate > $startDate;
    }

    public function status($controller, $params) {
        $institutionId = $controller->Session->read('InstitutionId');

        $controller->Navigation->addCrumb('Status');
        $controller->set('subheader', 'Status');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->getQualityStatuses(); //$this->find('all');

        $controller->set('data', $data);
        $controller->set('statusOptions', $this->statusOptions);

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();

        $controller->set('rubricOptions', $rubricOptions);
    }

    public function statusView($controller, $params) {
        $controller->Navigation->addCrumb('Status');
        $controller->set('subheader', 'Status');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'status'));
        }

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $RubricsTemplate->recursive = -1;
        $rubricTemplateInfo = $RubricsTemplate->findById($data[$this->name]['rubric_template_id']);



        $SchoolYear = ClassRegistry::init('SchoolYear');
        $schoolyearId = $SchoolYear->getSchoolYearId($data[$this->name]['year']);

        $disableDelete = false;
        $QualityInstitutionRubric = ClassRegistry::init('Quality.QualityInstitutionRubric');
        if ($QualityInstitutionRubric->getAssignedInstitutionRubricCount($schoolyearId, $id) > 0) {
            $disableDelete = true;
        }


        $rubricName = $rubricTemplateInfo['RubricsTemplate']['name'];
        $controller->Session->write('QualityStatus.id', $id);
        $controller->set('rubricName', $rubricName);
        $controller->set('disableDelete', $disableDelete);
        $controller->set('data', $data);
        $controller->set('statusOptions', $this->statusOptions);
    }

    public function statusAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Status');
        $controller->set('subheader', 'Add Status');
        $controller->set('modelName', $this->name);
        $controller->set('displayType', 'add');
        $controller->set('selectedYear', date("Y"));

        $this->_setupStatusForm($controller, $params, 'add');
    }

    public function statusEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Status');
        $controller->set('subheader', 'Edit Status');
        $controller->set('modelName', $this->name);
        $controller->set('selectedYear', date("Y"));
        $controller->set('displayType', 'edit');
        $this->_setupStatusForm($controller, $params, 'edit');
        $this->render = 'add';
    }

    private function _setupStatusForm($controller, $params, $type) {
        $controller->set('statusOptions', $this->statusOptions);
        // $institutionId = $controller->Session->read('InstitutionId');

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();

        $controller->set('rubricOptions', $rubricOptions);

        $SchoolYear = ClassRegistry::init('SchoolYear');
        $yearOptions = $SchoolYear->getYearListValues();

        $controller->set('yearOptions', $yearOptions);
        if ($controller->request->is('get')) {

            $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

            $this->recursive = -1;
            $data = $this->findById($id);

            if (!empty($data)) {
                $controller->request->data = $data;
                $controller->set('selectedYear', $data[$this->name]['year']);
            } else {
                $controller->request->data['QualityStatus']['date_disabled'] = date('Y-m-d', time() + 86400);
                //$controller->request->data[$this->name]['institution_id'] = $institutionId;
            }
        } else {
            // $controller->request->data[$this->name]['student_id'] = $controller->studentId;
            // pr($controller->request->data);
            // die;
            $proceedToSave = true;
            if ($type == 'add') {
                $conditions = array(
                    'QualityStatus.rubric_template_id' => $controller->request->data['QualityStatus']['rubric_template_id'],
                    'QualityStatus.year' => $controller->request->data['QualityStatus']['year']
                );
                if ($this->hasAny($conditions)) {
                    $proceedToSave = false;
                    $controller->Utility->alert($controller->Utility->getMessage('DATA_EXIST'), array('type' => 'error'));
                }
            }

            if ($proceedToSave) {
                if ($this->save($controller->request->data)) {
                    if (empty($controller->request->data[$this->name]['id'])) {
                        $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                    } else {
                        $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    }
                    return $controller->redirect(array('action' => 'status'));
                }
            }
        }
    }

    public function statusDelete($controller, $params) {
        if ($controller->Session->check('QualityStatus.id')) {
            $id = $controller->Session->read('QualityStatus.id');

            $options['conditions'] = array($this->name . '.id' => $id);
            $options['joins'] = array(
                array(
                    'table' => 'rubrics_templates',
                    'alias' => 'RubricsTemplate',
                    'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
                )
            );
            $options['fields'] = array('QualityStatus.*', 'RubricsTemplate.name');
            $data = $this->find('first', $options);

            //$SchoolYear = ClassRegistry::init('SchoolYear');
            // $schoolyearId = $SchoolYear->getSchoolYearId($data[$this->name]['year']);

            $name = $data['RubricsTemplate']['name'] . " (" . $data[$this->name]['year'] . ")";

            //  $QualityInstitutionRubric = ClassRegistry::init('Quality.QualityInstitutionRubric');
            //   $QualityInstitutionRubric->deleteAllInstitutionRubrics($schoolyearId, $id);
            // pr($name);die;
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('QualityStatus.id');
            $controller->redirect(array('action' => 'status'));
        }
    }

    //SQL Function 
    public function getQualityStatuses() {
        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_templates',
                'alias' => 'RubricsTemplate',
                'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
            )
        );
        $options['order'] = array('QualityStatus.year DESC', 'RubricsTemplate.name');
        $options['fields'] = array('QualityStatus.*', 'RubricsTemplate.*');
        $data = $this->find('all', $options);

        return $data;
    }

    public function getRubricStatus($year, $rubricId) {
        $date = date('Y-m-d', time());
        
        $conditions = array(
            'QualityStatus.rubric_template_id' => $rubricId,
            'QualityStatus.year' => $year,
            'QualityStatus.date_enabled <= ' => $date,
            'QualityStatus.date_disabled >= ' => $date
        );
        

        return $this->hasAny($conditions);
    }

    public function getCreatedRubricCount($rubricId) {
        $options['conditions'] = array('rubric_template_id' => $rubricId);
        $options['fields'] = array('COUNT(id) as Total');
        $options['recursive'] = -1;
        $data = $this->find('first', $options);

        return $data[0]['Total'];
    }

}