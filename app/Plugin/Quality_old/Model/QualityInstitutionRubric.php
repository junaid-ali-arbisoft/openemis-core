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

class QualityInstitutionRubric extends QualityAppModel {

	//  public $useTable = false;
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'AcademicPeriod' => array(
					'name AS Year' => '',
				),
				'InstitutionSite' => array(
				//	'name AS InstitutionSiteName' => 'Institution Name',
				//	'code AS InstitutionSiteCode' => 'Institution Code',
					'id AS InstitutionSiteId' => ''
				),
				'InstitutionSiteSection' => array(
					'name AS Class' => '',
					'id AS ClassId' => ''
				),
				'EducationGrade' => array(
					'name AS Grade' => '',
					'id AS GradeId' => ''
				),
				'QualityInstitutionRubric' => array(
					'full_name' =>'',
				),
				'FieldOptionValues' => array(
					'name AS StaffType' => ''
				),
				'RubricTemplate' => array(
					'name AS RubricName' => '',
					'id AS RubricId' => ''
				),
				'RubricTemplateHeader' => array(
					'title AS RubricHeader' => ''
				),
				'RubricTemplateColumnInfo' => array(
					//'weighting' => '',
					'COALESCE(SUM(weighting),0) AS rubric_score' => ''
				),
			),
			'fileName' => 'Report_Quality_Assurance'
		)
	);
	public $belongsTo = array(
		//'Student',
		/* 'RubricsTemplate' => array(
		  'foreignKey' => 'rubric_template_id'
		  ), */
		'AcademicPeriod',
		'RubricsTemplate' => array(
			'foreignKey' => 'rubric_template_id'
		),
		'InstitutionSiteSection',
		'Staff.Staff',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	//public $hasMany = array('RubricsTemplateColumnInfo');

	public $validate = array(
		'institution_site_section_id' => array(
			'ruleRequired' => array(
				'rule' => 'checkDropdownData',
				//  'required' => true,
				'message' => 'Please select a valid Section.'
			)
		),
		'institution_site_section_grade_id' => array(
			'ruleRequired' => array(
				'rule' => 'checkDropdownData',
				//  'required' => true,
				'message' => 'Please select a valid Grade.'
			)
		),
		'staff_id' => array(
			'ruleRequired' => array(
				'rule' => 'checkDropdownData',
				//   'required' => true,
				'message' => 'Please select a valid staff.'
			)
		),
		'rubric_template_id' => array(
			'ruleRequired' => array(
				'rule' => 'checkDropdownData',
				// 'required' => true,
				'message' => 'Please select a valid Rubric.'
			)
		),
		'comment' => array(
			'ruleRequired' => array(
				'rule' => 'checkCommentLength', //array('maxLength', 1),
				'message' => 'Maximum 150 words per comment.'
			)
		)
	);

//    public $statusOptions = array('Disabled', 'Enabled');
	public function checkDropdownData($check) {
		$value = array_values($check);
		$value = $value[0];

		return !empty($value);
	}

	public function checkCommentLength($data) {
		if (str_word_count($data['comment']) > 150) {
			return false;
		}

		return true;
	}

	public function beforeAction($controller, $action) {
		$controller->set('model', $this->name);
		if ($action != 'qualityRubric') {
			// $controller->Navigation->addCrumb('Rubrics', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin' => 'Quality'));
		}
	}

	public function getDisplayFields($controller) {
		$InstitutionSiteSectionGrade = ClassRegistry::init('InstitutionSiteSectionGrade');
		$gradeOptions = $InstitutionSiteSectionGrade->getGradesByInstitutionSiteId($controller->institutionSiteId);

		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'AcademicPeriod'),
				array('field' => 'name', 'model' => 'RubricsTemplate'),
				array('field' => 'institution_site_section_grade_id', 'type' => 'select', 'options' => $gradeOptions , 'labelKey' => 'general.grade'),
				array('field' => 'name', 'model' => 'InstitutionSiteSection', 'labelKey' => 'general.class'),
				array('field' => 'staff', 'model' => 'Staff', 'format' => 'name'),
				array('field' => 'Evaluator', 'model' => 'CreatedUser', 'format' => 'name'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function qualityRubric($controller, $params) {
		//$QualityBatchReport = ClassRegistry::init('Quality.QualityBatchReport');
		//$QualityBatchReport->generateRubricNotCompleted();
		
		
		$institutionSiteId = $controller->Session->read('InstitutionSiteId');
		$controller->Navigation->addCrumb('Rubrics');
		$header = __('Rubrics');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->find('all', array('conditions' => array('QualityInstitutionRubric.institution_site_id' => $institutionSiteId)));

		$controller->set(compact('data', 'header'));
	}

	public function qualityRubricAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Rubrics');
		$controller->set('header', __('Add Rubrics'));
		$this->_setupRubricForm($controller, $params, 'add');
	}

	public function qualityRubricEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Rubrics');
		$controller->set('header', __('Edit Rubrics'));
		$this->_setupRubricForm($controller, $params, 'edit');

		$this->render = 'add';
	}

	private function _setupRubricForm($controller, $params, $type) {
		$institutionId = $controller->Session->read('InstitutionId');
		$institutionSiteId = $controller->Session->read('InstitutionSiteId');

		if ($type == 'add') {
			$userData = $controller->Session->read('Auth.User');
			$evaluatorName = $userData['first_name'] . ' ' . $userData['last_name'];

			$paramsLocateCounter = 0;
		} else {
			if (!empty($params['pass'][0])) {
				$selectedId = $params['pass'][0];
				$this->unbindModel(array('belongsTo' => array('AcademicPeriod', 'RubricsTemplate', 'InstitutionSiteSection', 'Staff.Staff', 'ModifiedUser')));
				$data = $this->find('first', array('conditions' => array('QualityInstitutionRubric.id' => $selectedId)));
				if (!empty($data)) {
					$evaluatorName = trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']);
					$selectedstaffId = $data[$this->name]['staff_id'];
					$selectedRubricId = $data[$this->name]['rubric_template_id'];
					$selectedAcademicPeriodId = $data[$this->name]['academic_period_id'];
					$selectedSectionId = $data[$this->name]['institution_site_section_id'];
					$selectedGradeId = $data[$this->name]['institution_site_section_grade_id'];
					$institutionSiteId = $data[$this->name]['institution_site_id'];
				}
			}
			$paramsLocateCounter = 1;
		}

		if ($controller->request->is('get')) {
			if ($type == 'edit' && !empty($data)) {
				$controller->request->data = $data;
			}
		} else {
			// pr($controller->request->data); // die;

			$proceedToSave = true;
			if ($type == 'add') {
				$conditions = array(
					'QualityInstitutionRubric.institution_site_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_id'],
					'QualityInstitutionRubric.rubric_template_id' => $controller->request->data['QualityInstitutionRubric']['rubric_template_id'],
					'QualityInstitutionRubric.academic_period_id' => $controller->request->data['QualityInstitutionRubric']['academic_period_id'],
					'QualityInstitutionRubric.institution_site_section_grade_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_section_grade_id'],
					'QualityInstitutionRubric.institution_site_section_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_section_id'],
					'QualityInstitutionRubric.staff_id' => $controller->request->data['QualityInstitutionRubric']['staff_id']
				);

				if ($this->hasAny($conditions)) {
					$proceedToSave = false;
					$controller->Utility->alert($controller->Utility->getMessage('DATA_EXIST'), array('type' => 'error'));
				}
			}
			if ($proceedToSave) {
				if ($this->saveAll($controller->request->data)) {
					// pr('save');
					$id = $this->id;
					if ($type == 'add') {
						$controller->Session->write('QualityRubric.editable', 'true');
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
						return $controller->redirect(array('action' => 'qualityRubricHeader', $id, $controller->request->data['QualityInstitutionRubric']['rubric_template_id']));
					} else {
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
						return $controller->redirect(array('action' => 'qualityRubricView', $id));
					}
				}
			}
		}
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$academicPeriodOptions = $AcademicPeriod->getAcademicPeriodList();

		if (empty($academicPeriodOptions)) {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'));
			return $controller->redirect(array('action' => 'qualityVisit'));
		}

		$selectedAcademicPeriodId = !empty($selectedAcademicPeriodId) ? $selectedAcademicPeriodId : key($academicPeriodOptions);
		$selectedAcademicPeriodId = !empty($params['pass'][0 + $paramsLocateCounter]) ? $params['pass'][0 + $paramsLocateCounter] : $selectedAcademicPeriodId;

		//Process Grade
		$InstitutionSiteSectionGrade = ClassRegistry::init('InstitutionSiteSectionGrade');
		$gradeOptions = $InstitutionSiteSectionGrade->getGradesByInstitutionSiteId($institutionSiteId);
		$selectedGradeId = !empty($selectedGradeId) ? $selectedGradeId : key($gradeOptions);
		$selectedGradeId = !empty($params['pass'][1 + $paramsLocateCounter]) ? $params['pass'][1 + $paramsLocateCounter] : $selectedGradeId;
		$selectedGradeId = empty($selectedGradeId) ? 0 : $selectedGradeId;

		//Process Section
		$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
		$sectionOptions = $InstitutionSiteSection->getSectionOptions($selectedAcademicPeriodId, $institutionSiteId, $selectedGradeId);
		$selectedSectionId = !empty($selectedSectionId) ? $selectedSectionId : key($sectionOptions);
		$selectedSectionId = !empty($params['pass'][2 + $paramsLocateCounter]) ? $params['pass'][2 + $paramsLocateCounter] : $selectedSectionId;

		//Process Rubric
		$RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
		$rubricOptions = $RubricsTemplate->getEnabledRubricsOptions($academicPeriodOptions[$selectedAcademicPeriodId], $selectedGradeId);
		//   pr($academicPeriodOptions[$selectedAcademicPeriodId]); die;
		$selectedRubricId = !empty($selectedRubricId) ? $selectedRubricId : key($rubricOptions);
		$selectedRubricId = !empty($params['pass'][3 + $paramsLocateCounter]) ? $params['pass'][3 + $paramsLocateCounter] : $selectedRubricId;

		//Process staff
		$staffs = $this->InstitutionSiteSection->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'Staff.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name'
			),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteSection.staff_id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityUser.id = Staff.security_user_id')
				),
			),
			'conditions' => array('InstitutionSiteSection.id' => $selectedSectionId, 'InstitutionSiteSection.academic_period_id' => $selectedAcademicPeriodId),
			'order' => array('SecurityUser.first_name')
		));

		$staffOptions = array();
		foreach ($staffs as $obj) {
			$id = $obj['Staff']['id'];
			$staffOptions[$id] = ModelHelper::getName($obj['SecurityUser']);
		}


		$selectedstaffId = !empty($selectedstaffId) ? $selectedstaffId : key($staffOptions);
		$selectedstaffId = !empty($params['pass'][4 + $paramsLocateCounter]) ? $params['pass'][4 + $paramsLocateCounter] : $selectedstaffId;

		$controller->set('academicPeriodOptions', $this->checkArrayEmpty($academicPeriodOptions));
		$controller->set('rubricOptions', $this->checkArrayEmpty($rubricOptions));
		$controller->set('sectionOptions', $this->checkArrayEmpty($sectionOptions));
		$controller->set('gradeOptions', $this->checkArrayEmpty($gradeOptions));
		$controller->set('staffOptions', $this->checkArrayEmpty($staffOptions));
		$controller->set('type', $type);

		$controller->request->data[$this->name]['evaluator'] = $evaluatorName;
		$controller->request->data[$this->name]['academic_period_id'] = $selectedAcademicPeriodId;
		$controller->request->data[$this->name]['institution_site_id'] = empty($controller->request->data[$this->name]['institution_site_id']) ? $institutionSiteId : $controller->request->data[$this->name]['institution_site_id'];
		$controller->request->data[$this->name]['rubric_template_id'] = empty($selectedRubricId) ? 0 : $selectedRubricId;
		$controller->request->data[$this->name]['institution_site_section_id'] = empty($selectedSectionId) ? 0 : $selectedSectionId;
		$controller->request->data[$this->name]['institution_site_section_grade_id'] = $selectedGradeId;
		$controller->request->data[$this->name]['staff_id'] = empty($selectedstaffId) ? 0 : $selectedstaffId;
	}

	public function qualityRubricView($controller, $params) {
		$controller->Navigation->addCrumb('Details');
		$header = __('Details');

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->findById($id); //('first', array('conditions' => array($this->name . '.id' => $id)));

		if (empty($data)) {
			$controller->redirect(array('action' => 'qualityVisit'));
		}

		$controller->Session->write('QualityRubric.id', $id);

		$fields = $this->getDisplayFields($controller);

		$disableDelete = false;
		$QualityInstitutionRubricsAnswer = ClassRegistry::init('Quality.QualityInstitutionRubricsAnswer');
		$answerCountData = $QualityInstitutionRubricsAnswer->getTotalCount($data[$this->name]['institution_site_id'], $data[$this->name]['rubric_template_id'], $data[$this->name]['id']);

		if (!empty($answerCountData)) {
			$disableDelete = true;
		};
		$controller->set('rubric_template_id', $data[$this->name]['rubric_template_id']);

		$controller->set(compact('header', 'data', 'fields', 'id', 'disableDelete'));
	}

	public function qualityRubricDelete($controller, $params) {
		if ($controller->Session->check('QualityRubric.id')) {
			$id = $controller->Session->read('QualityRubric.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('QualityRubric.id');
			$controller->redirect(array('action' => 'qualityRubric'));
		}
	}

	public function getAssignedInstitutionRubricCount($academicPeriodId, $rubricId) {
		$options['conditions'] = array('academic_period_id' => $academicPeriodId, 'rubric_template_id' => $rubricId);
		$options['fields'] = array('COUNT(id) as Total');
		$options['recursive'] = -1;
		$data = $this->find('first', $options);
		return $data[0]['Total'];
	}

	/* =================================================
	 * 
	 * For Report Generation at InstitutionSites Only
	 * 
	 * ================================================= */
	public $reportDefaultHeader = array(array('Academic Period'), array('Section'), array('Grade'), array('Staff Name'), array('Staff Type'));
	public function reportsGetHeader($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		$this->unbindModel(array('belongsTo' => array('CreatedUser', 'ModifiedUser','RubricsTemplate' ,'InstitutionSiteSection','Staff')));
		unset($this->virtualFields['full_name']);
		$data = $this->find('first', array(
			//'fields' => array('AcademicPeriod.name', 'QualityInstitutionRubric.rubric_template_id'),
			'order' => array('AcademicPeriod.name DESC', ),
			'fields' => array('AcademicPeriod.*', 'QualityInstitutionRubric.*', 'InstitutionSiteSection.*','InstitutionSiteSectionGrade.*'),
			'group' => array('AcademicPeriod.name', 'QualityInstitutionRubric.rubric_template_id'),
			'conditions' => array('QualityInstitutionRubric.institution_site_id' => $institutionSiteId),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array('InstitutionSiteSection.id = QualityInstitutionRubric.institution_site_section_id')
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSection.institution_site_section_id',
						'InstitutionSiteSectionGrade.status = 1'
					)
				)
			)
		));

		$academicPeriod = !empty($data['AcademicPeriod']['name'])?$data['AcademicPeriod']['name'] : NULL;
		$gradeId = !empty($data['InstitutionSiteSectionGrade']['education_grade_id'])?$data['InstitutionSiteSectionGrade']['education_grade_id'] : NULL;

		$this->bindModel(array('belongsTo' => array('CreatedUser', 'ModifiedUser','RubricsTemplate' ,'InstitutionSiteSection','Staff.Staff')));
		
		$QualityBatchReport = ClassRegistry::init('Quality.QualityBatchReport');
		$headerOptions = array('academicPeriod' => $academicPeriod, 'gradeId' => $gradeId, 'header' => $this->reportDefaultHeader);
		$headers = $QualityBatchReport->getInstitutionQAReportHeader($institutionSiteId, $headerOptions);
	//	pr($headerOptions);
		//pr($headers);die;
		return $this->getCSVHeader($headers);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		// General > Overview and More
		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);

			$options['conditions'] = array('QualityInstitutionRubric.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = QualityInstitutionRubric.institution_site_id')
				),
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array('InstitutionSiteSection.id = QualityInstitutionRubric.institution_site_section_id')
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.id = QualityInstitutionRubric.institution_site_section_grade_id',
						'InstitutionSiteSectionGrade.status = 1'
						)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
				),
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'type' => 'LEFT',
					'conditions' => array('QualityInstitutionRubric.academic_period_id = AcademicPeriod.id')
				),
				
				
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = QualityInstitutionRubric.staff_id')
				),
				
				 array(
					'table' => 'institution_site_staff',
					'alias' => 'InstitutionSiteStaff',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteStaff.staff_id = Staff.id',
						//'InstitutionSiteStaff.start_year <= AcademicPeriod.start_year',
						'InstitutionSiteStaff.institution_site_id = QualityInstitutionRubric.institution_site_id',
						'OR' => array(
							'InstitutionSiteStaff.end_year >= AcademicPeriod.end_year', 'InstitutionSiteStaff.end_year is null'
						)
					)
				),
				array(
					'table' => 'field_options',
					'alias' => 'FieldOption',
					'conditions' => array('FieldOption.code = "StaffType"')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'FieldOptionValues',
					'type' => 'LEFT',
					'conditions' => array(
						'FieldOptionValues.field_option_id = FieldOption.id',
						'FieldOptionValues.id = InstitutionSiteStaff.staff_type_id',
					)
				),
				array(
					'table' => 'rubrics_templates',
					'alias' => 'RubricTemplate',
					'type' => 'LEFT',
					'conditions' => array('RubricTemplate.id = QualityInstitutionRubric.rubric_template_id')
				),
				array(
					'table' => 'quality_statuses',
					'alias' => 'QualityStatus',
					'conditions' => array('QualityStatus.rubric_template_id = RubricTemplate.id','QualityStatus.year = AcademicPeriod.name')
				),
				/* array(
				  'table' => 'quality_institution_rubrics',
				  'alias' => 'QualityInstitutionRubric',
				  'type' => 'LEFT',
				  'conditions' => array(
				  'QualityInstitutionRubric.institution_site_section_id = InstitutionSiteSection.id',
				  'RubricTemplate.id = QualityInstitutionRubric.rubric_template_id',
				  'AcademicPeriod.id = QualityInstitutionRubric.academic_period_id'
				  )
				  ), */
				array(
					'table' => 'rubrics_template_headers',
					'alias' => 'RubricTemplateHeader',
					'type' => 'LEFT',
					'conditions' => array('RubricTemplate.id = RubricTemplateHeader.rubric_template_id')
				),
				array(
					'table' => 'rubrics_template_subheaders',
					'alias' => 'RubricTemplateSubheader',
					'type' => 'LEFT',
					'conditions' => array('RubricTemplateSubheader.rubric_template_header_id = RubricTemplateHeader.id')
				),
				array(
					'table' => 'rubrics_template_items',
					'alias' => 'RubricTemplateItem',
					'type' => 'LEFT',
					'conditions' => array('RubricTemplateItem.rubric_template_subheader_id = RubricTemplateSubheader.id')
				),
				
				array(
					'table' => 'quality_institution_rubrics_answers',
					'alias' => 'QualityInstitutionRubricAnswer',
					'type' => 'LEFT',
					'conditions' => array(
						'QualityInstitutionRubricAnswer.quality_institution_rubric_id = QualityInstitutionRubric.id',
						'QualityInstitutionRubricAnswer.rubric_template_header_id = RubricTemplateHeader.id',
						'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
						//'QualityInstitutionRubricAnswer.rubric_template_answer_id = RubricTemplateAnswer.id',
						
					)
				),
				
				array(
					'table' => 'rubrics_template_answers',
					'alias' => 'RubricTemplateAnswer',
					'type' => 'LEFT',
					'conditions' => array('RubricTemplateAnswer.id = QualityInstitutionRubricAnswer.rubric_template_answer_id')
					//'conditions' => array('RubricTemplateAnswer.rubric_template_item_id = RubricTemplateItem.id')
				),
				array(
					'table' => 'rubrics_template_column_infos',
					'alias' => 'RubricTemplateColumnInfo',
					'type' => 'LEFT',
					'conditions' => array(
						//'RubricTemplateColumnInfo.rubric_template_id = RubricTemplate.id',
						'RubricTemplateColumnInfo.id = RubricTemplateAnswer.rubrics_template_column_info_id'
					),
				),
				
			);


			$options['order'] = array('AcademicPeriod.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteSection.name', 'RubricTemplate.id', 'RubricTemplateHeader.order');
			//$options['group'] = array('InstitutionSiteSection.id', 'RubricTemplate.id', 'RubricTemplateHeader.id');
			$options['group'] = array('AcademicPeriod.name', 'InstitutionSite.id', 'InstitutionSiteSection.id', 'EducationGrade.id','RubricTemplate.id', 'RubricTemplateHeader.id');
			
			//$options['contain'] = array('QualityInstitutionRubric' => array('full_name'));
			
			$this->virtualFields['full_name'] = "CONCAT(SecurityUser.first_name,' ',SecurityUser.middle_name,' ',SecurityUser.third_name,' ',SecurityUser.last_name)";
			$data = $this->find('all', $options);
			
			$QualityBatchReport = ClassRegistry::init('Quality.QualityBatchReport');
			
			$newData = $QualityBatchReport->processSchoolDataToCSVFormat($data,$this->reportMapping[$index]['fields']);
		
			$newData = $QualityBatchReport->breakReportByYear($newData, 'no', $this->reportDefaultHeader);
	
			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}