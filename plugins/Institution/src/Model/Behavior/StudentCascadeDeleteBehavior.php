<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class StudentCascadeDeleteBehavior extends Behavior {
	private $classIds = [];
	private $subjectIds = [];

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$this->classIds = $this->getClassIds($entity);
		$this->subjectIds = $this->getSubjectIds($entity);

		if ($this->noStudentRecords($entity, true)) { // delete only if student no longer in this grade/academic period
			$this->deleteClassStudents($entity);
			$this->deleteSubjectStudents($entity);
			$this->deleteStudentResults($entity);
			$this->deleteStudentFees($entity);
		}

		if ($this->noStudentRecords($entity)) { // delete all other records if student no longer in school
			$this->deleteStudentAbsences($entity);
			$this->deleteStudentBehaviours($entity);
			$this->deleteStudentSurveys($entity);
			$this->deleteStudentDropoutRecords($entity);
			$this->deleteStudentAdmissionRecords($entity);
		}
	}

	private function deleteClassStudents(Entity $entity) {
		if (!empty($this->classIds)) {
			$ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
			$ClassStudents->deleteAll([
				$ClassStudents->aliasField('student_id') => $entity->student_id,
				$ClassStudents->aliasField('education_grade_id') => $entity->education_grade_id,
				$ClassStudents->aliasField('institution_class_id IN ') => $this->classIds
			]);
		}
	}

	private function deleteSubjectStudents(Entity $entity) {
		if (!empty($this->subjectIds) && !empty($this->classIds)) {
			$SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
			$SubjectStudents->deleteAll([
				$SubjectStudents->aliasField('student_id') => $entity->student_id,
				$SubjectStudents->aliasField('institution_class_id IN ') => $this->classIds,
				$SubjectStudents->aliasField('institution_subject_id IN ') => $this->subjectIds
			]);
		}
	}

	private function deleteStudentResults(Entity $entity) {
		$Assessments = TableRegistry::get('Assessment.Assessments');
		$Results = TableRegistry::get('Assessment.AssessmentItemResults');

		$institutionId = $entity->institution_id;
		$periodId = $entity->academic_period_id;
		$gradeId = $entity->education_grade_id;
		$studentId = $entity->student_id;

		$assessmentIds = $Assessments
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([
				$Assessments->aliasField('academic_period_id') => $periodId,
				$Assessments->aliasField('education_grade_id') => $gradeId
			])
			->toArray();

		if (!empty($assessmentIds)) {
			$Results->deleteAll([
				$Results->aliasField('institution_id') => $institutionId,
				$Results->aliasField('academic_period_id') => $periodId,
				$Results->aliasField('student_id') => $studentId,
				$Results->aliasField('assessment_id IN') => $assessmentIds
			]);
		}
	}

	private function deleteStudentFees(Entity $entity) {
		$InstitutionFees = TableRegistry::get('Institution.InstitutionFees');
		$StudentFees = TableRegistry::get('Institution.StudentFeesAbstract');

		$institutionFeeResults = $InstitutionFees
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([
				$InstitutionFees->aliasField('institution_id') => $entity->institution_id,
				$InstitutionFees->aliasField('academic_period_id') => $entity->academic_period_id,
				$InstitutionFees->aliasField('education_grade_id') => $entity->education_grade_id
			])
			->all();

		if (!$institutionFeeResults->isEmpty()) {
			$institutionFeeIds = $institutionFeeResults->toArray();
			$StudentFees->deleteAll([
				$StudentFees->aliasField('institution_fee_id IN') => $institutionFeeIds,
				$StudentFees->aliasField('student_id') => $entity->student_id
			]);
		}
	}

	private function deleteStudentAbsences(Entity $entity) {
		$StudentAbsences = TableRegistry::get('Student.Absences');
		$StudentAbsences->deleteAll([
			$StudentAbsences->aliasField('institution_id') => $entity->institution_id,
			$StudentAbsences->aliasField('student_id') => $entity->student_id
		]);
	}

	private function deleteStudentBehaviours(Entity $entity) {
		$StudentBehaviours = TableRegistry::get('Institution.StudentBehaviours');
		$StudentBehaviours->deleteAll([
			$StudentBehaviours->aliasField('institution_id') => $entity->institution_id,
			$StudentBehaviours->aliasField('student_id') => $entity->student_id
		]);
	}

	private function deleteStudentSurveys(Entity $entity) {
		$StudentSurveys = TableRegistry::get('Student.StudentSurveys');
		$StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');
		$StudentSurveyTableCells = TableRegistry::get('Student.StudentSurveyTableCells');

		$institutionId = $entity->institution_id;
		$studentId = $entity->student_id;

		$studentSurveyResults = $StudentSurveys
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([
				$StudentSurveys->aliasField('institution_id') => $institutionId,
				$StudentSurveys->aliasField('student_id') => $studentId
			])
			->all();

		if (!$studentSurveyResults->isEmpty()) {
			$studentSurveyIds = $studentSurveyResults->toArray();
			$StudentSurveyAnswers->deleteAll([
				$StudentSurveyAnswers->aliasField('institution_student_survey_id IN ') => $studentSurveyIds
			]);
			$StudentSurveyTableCells->deleteAll([
				$StudentSurveyTableCells->aliasField('institution_student_survey_id IN ') => $studentSurveyIds
			]);
		}

		$StudentSurveys->deleteAll([
			$StudentSurveys->aliasField('institution_id') => $institutionId,
			$StudentSurveys->aliasField('student_id') => $studentId
		]);
	}

	private function deleteStudentDropoutRecords(Entity $entity) {
		$StudentDropout = TableRegistry::get('Institution.StudentDropout');
		$StudentDropout->deleteAll([
			$StudentDropout->aliasField('institution_id') => $entity->institution_id,
			$StudentDropout->aliasField('academic_period_id') => $entity->academic_period_id,
			$StudentDropout->aliasField('education_grade_id') => $entity->education_grade_id,
			$StudentDropout->aliasField('student_id') => $entity->student_id
		]);
	}

	private function deleteStudentAdmissionRecords(Entity $entity) {
		$StudentAdmission = TableRegistry::get('Institution.StudentAdmission');

		$institutionId = $entity->institution_id;
		$periodId = $entity->academic_period_id;
		$gradeId = $entity->education_grade_id;
		$studentId = $entity->student_id;

		// delete pending admission
		$StudentAdmission->deleteAll([
			$StudentAdmission->aliasField('institution_id') => $institutionId,
			$StudentAdmission->aliasField('academic_period_id') => $periodId,
			$StudentAdmission->aliasField('education_grade_id') => $gradeId,
			$StudentAdmission->aliasField('student_id') => $studentId,
			$StudentAdmission->aliasField('previous_institution_id') => 0
		]);

		// delete transfer request
		$StudentAdmission->deleteAll([
			$StudentAdmission->aliasField('previous_institution_id') => $institutionId,
			$StudentAdmission->aliasField('academic_period_id') => $periodId,
			$StudentAdmission->aliasField('education_grade_id') => $gradeId,
			$StudentAdmission->aliasField('student_id') => $studentId
		]);
	}

	private function noStudentRecords(Entity $entity , $includeGrade=false) {
		$Students = $this->_table;
		$conditions = [
			$Students->aliasField('institution_id') => $entity->institution_id,
			$Students->aliasField('student_id') => $entity->student_id	
		];

		if ($includeGrade) {
			$conditions[$Students->aliasField('academic_period_id')] = $entity->academic_period_id;
			$conditions[$Students->aliasField('education_grade_id')] = $entity->education_grade_id;
		}

		$results = $Students
			->find()
			->where($conditions)
			->all();

		return $results->isEmpty();
	}

	private function getClassIds(Entity $entity) {
		$Classes = TableRegistry::get('Institution.InstitutionClasses');
		$ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');

		return $Classes
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->innerJoin(
				[$ClassGrades->alias() => $ClassGrades->table()],
				[
					$ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id'),
					$ClassGrades->aliasField('education_grade_id') => $entity->education_grade_id
				]
			)
			->where([
				$Classes->aliasField('institution_id') => $entity->institution_id,
				$Classes->aliasField('academic_period_id') => $entity->academic_period_id
			])
			->toArray();
	}

	private function getSubjectIds(Entity $entity) {
		$subjectIds = [];

		if (!empty($this->classIds)) {
			$Subjects = TableRegistry::get('Institution.InstitutionSubjects');
			$ClassesSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');

			$subjectIds = $Subjects
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->innerJoin(
					[$ClassesSubjects->alias() => $ClassesSubjects->table()],
					[
						$ClassesSubjects->aliasField('institution_subject_id = ') . $Subjects->aliasField('id'),
						$ClassesSubjects->aliasField('institution_class_id IN ') => $this->classIds
					]
				)
				->where([
					$Subjects->aliasField('institution_id') => $entity->institution_id,
					$Subjects->aliasField('academic_period_id') => $entity->academic_period_id
				])
				->toArray();
		}

		return $subjectIds;
	}
}