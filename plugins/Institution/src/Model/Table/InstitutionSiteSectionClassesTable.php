<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class InstitutionSiteSectionClassesTable extends AppTable {
	private $_selectedSection = 0;
	private $_selectedAcademicPeriod = 0;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);

		$this->Institutions = $this->InstitutionSiteSections->Institutions;
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) { 
		$query = $this->request->query;
 		
 		$institutionsId = $this->Session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);
		$academicPeriodOptions = $this->Institutions->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$this->_selectedAcademicPeriod = isset($query['academic_period']) ? $query['academic_period'] : key($academicPeriodOptions);
		$this->_selectedAcademicPeriod = $this->checkIdInOptions($this->_selectedAcademicPeriod, $academicPeriodOptions);

		$sectionOptions = $this->InstitutionSiteSections
					->find('list')
					->where([
						'academic_period_id'=>$this->_selectedAcademicPeriod, 
						'institution_site_id'=>$institutionsId
					])
					->toArray();
		if (empty($sectionOptions)) {
			$this->Alert->warning('Institutions.noSections');
		} else {
			$this->_selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);
			$this->_selectedSection = $this->checkIdInOptions($this->_selectedSection, $sectionOptions);
		}

		$toolbarElements = [
            ['name' => 'Institution.Classes/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions, 
	            	'selectedAcademicPeriod'=>$this->_selectedAcademicPeriod, 
	            	'sectionOptions'=>$sectionOptions, 
	            	'selectedSection'=>$this->_selectedSection, 
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
    }

	public function indexBeforePaginate($event, $model, $paginateOptions) {

		$paginateOptions['contain']['InstitutionSiteClasses'] = ['EducationSubjects'];
		$paginateOptions['conditions'][] = ['institution_site_section_id' => $this->_selectedSection];
		// pr($paginateOptions);die;
		return $paginateOptions;
	}

	public function indexAfterAction(Event $event, $data) {
		// pr($data->toArray());die;
		return $data;
	}


}