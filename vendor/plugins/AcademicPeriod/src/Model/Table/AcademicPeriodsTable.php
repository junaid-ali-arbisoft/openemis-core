<?php

namespace AcademicPeriod\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Exception\NotFoundException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;
use Cake\I18n\Date;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use Cake\Datasource\ConnectionManager;

class AcademicPeriodsTable extends ControllerActionTable
{
    private $_fieldOrder = ['visible', 'current', 'editable', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);


        // reference to itself
        $this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'parent_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentAssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Assessments', ['className' => 'Assessment.Assessments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CalendarEvents', ['className' => 'calendar_events', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfileProcesses', ['className' => 'class_profile_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfileTemplates', ['className' => 'class_profile_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfiles', ['className' => 'class_profiles', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyCriterias', ['className' => 'competency_criterias', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyItems', ['className' => 'competency_items', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyItemsPeriods', ['className' => 'competency_items_periods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyPeriods', ['className' => 'competency_periods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyTemplates', ['className' => 'competency_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('EducationSystems', ['className' => 'education_systems', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentres', ['className' => 'Examination.ExaminationCentres', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentresExaminations', ['className' => 'Examination.ExaminationCentresExaminations', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentresExaminationsStudents', ['className' => 'Examination.ExaminationCentresExaminationsStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Examinations', ['className' => 'Examination.Examinations', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('FeedersInstitutions', ['className' => 'feeders_institutions', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityElectricities', ['className' => 'infrastructure_utility_electricities', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityInternets', ['className' => 'infrastructure_utility_internets', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityTelephones', ['className' => 'infrastructure_utility_telephones', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashHygienes', ['className' => 'infrastructure_wash_hygienes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashSanitations', ['className' => 'infrastructure_wash_sanitations', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashSewages', ['className' => 'infrastructure_wash_sewages', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashWastes', ['className' => 'infrastructure_wash_wastes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashWaters', ['className' => 'infrastructure_wash_waters', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssessmentItemResults', ['className' => 'Institution.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssociationStudent', ['className' => 'institution_association_student', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssociations', ['className' => 'institution_associations', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionBudgets', ['className' => 'institution_budgets', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true]);
        $this->hasMany('InstitutionClassAttendanceRecords', ['className' => 'institution_class_attendance_records', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyItemComments', ['className' => 'institution_competency_item_comments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyPeriodComments', ['className' => 'institution_competency_period_comments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'institution_competency_results', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionExpenditures', ['className' => 'institution_expenditures', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFees', ['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true]);
        $this->hasMany('InstitutionIncomes', ['className' => 'institution_incomes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionInstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true]);
        $this->hasMany('InstitutionMealProgrammes', ['className' => 'institution_meal_programmes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionMealStudents', ['className' => 'institution_meal_students', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionOutcomeResults', ['className' => 'institution_outcome_results', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionOutcomeSubjectComments', ['className' => 'institution_outcome_subject_comments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityRubrics', ['className' => 'institution_quality_rubrics', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRepeaterSurveys', ['className' => 'institution_repeater_surveys', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionReportCardProcesses', ['className' => 'institution_report_card_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionReportCards', ['className' => 'institution_report_cards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true]);
        $this->hasMany('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleIntervals', ['className' => 'institution_schedule_intervals', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTerms', ['className' => 'institution_schedule_terms', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTimetableCustomizes', ['className' => 'institution_schedule_timetable_customizes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTimetables', ['className' => 'institution_schedule_timetables', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffAttendances', ['className' => 'institution_staff_attendances', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffDuties', ['className' => 'institution_staff_duties', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffLeave', ['className' => 'institution_staff_leave', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentAbsenceDetails', ['className' => 'institution_student_absence_details', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'institution_student_absences', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentRisks', ['className' => 'institution_student_risks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentSurveys', ['className' => 'institution_student_surveys', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'institution_student_transfers', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'institution_student_transfers', 'foreignKey' => 'previous_academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentVisitRequests', ['className' => 'institution_student_visit_requests', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentVisits', ['className' => 'institution_student_visits', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentWithdraw', ['className' => 'institution_student_withdraw', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentsReportCards', ['className' => 'institution_students_report_cards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentsReportCardsComments', ['className' => 'institution_students_report_cards_comments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjects', ['className' => 'institution_subjects', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTextbooks', ['className' => 'institution_textbooks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTripPassengers', ['className' => 'institution_trip_passengers', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTrips', ['className' => 'institution_trips', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionVisitRequests', ['className' => 'institution_visit_requests', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('MealProgrammes', ['className' => 'meal_programmes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomeCriterias', ['className' => 'outcome_criterias', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomePeriods', ['className' => 'outcome_periods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomeTemplates', ['className' => 'outcome_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ProfileTemplates', ['className' => 'profile_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Programmes', ['className' => 'Student.Programmes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RepeaterSurveys', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCardEmailProcesses', ['className' => 'report_card_email_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCardProcesses', ['className' => 'report_card_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCards', ['className' => 'report_cards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Risks', ['className' => 'risks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RubricStatusPeriods', ['className' => 'Rubric.RubricStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientAcademicStandings', ['className' => 'scholarship_recipient_academic_standings', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientCollections', ['className' => 'scholarship_recipient_collections', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructures', ['className' => 'scholarship_recipient_payment_structures', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffExtracurriculars', ['className' => 'student_extracurriculars', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffProfileTemplates', ['className' => 'staff_profile_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCardEmailProcesses', ['className' => 'staff_report_card_email_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCardProcesses', ['className' => 'staff_report_card_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCards', ['className' => 'staff_report_cards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'student_attendance_mark_types', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendanceMarkedRecords', ['className' => 'student_attendance_marked_records', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendances', ['className' => 'Institution.StudentAttendances', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentBehaviours', ['className' => 'student_behaviours', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentClasses', ['className' => 'Student.StudentClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentExtracurriculars', ['className' => 'staff_extracurriculars', 'dependent' => true, 'cascadeCallbacks' => false]);//POCOR-6762
        $this->hasMany('StudentFees', ['className' => 'Institution.StudentFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentMarkTypeStatuses', ['className' => 'student_mark_type_statuses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentMealMarkedRecords', ['className' => 'student_meal_marked_records', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentProfileTemplates', ['className' => 'student_profile_templates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentPromotion', ['className' => 'Institution.StudentPromotion', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCardEmailProcesses', ['className' => 'student_report_card_email_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCardProcesses', ['className' => 'student_report_card_processes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCards', ['className' => 'student_report_cards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentStatusUpdates', ['className' => 'student_status_updates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransfer', ['className' => 'Institution.StudentTransfer', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferIn', ['className' => 'Institution.StudentTransferIn', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferOut', ['className' => 'Institution.StudentTransferOut', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Students', ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SummaryAssessmentItemResults', ['className' => 'summary_assessment_item_results', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SurveyStatusPeriods', ['className' => 'Survey.SurveyStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Textbooks', ['className' => 'textbooks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TransferLogs', ['className' => 'transfer_logs', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UndoStudentStatus', ['className' => 'Institution.UndoStudentStatus', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserBodyMasses', ['className' => 'user_body_masses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'user_special_needs_referrals', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserSpecialNeedsServices', ['className' => 'user_special_needs_services', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WithdrawRequests', ['className' => 'Institution.WithdrawRequests', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Tree');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index'],
            'Results' => ['index'],
            'StudentExaminationResults' => ['index'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'InstitutionStaffAttendances' => ['index', 'view'],
            'StudentAttendances' => ['index', 'view'],
            'ScheduleTimetable' => ['index']
        ]);

        $this->addBehavior('Institution.Calendar');
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $additionalParameters = ['editable = 1 AND visible > 0'];
        //POCOR-5917 starts
        return $validator
            ->add('end_date', [
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', false]
                ]//POCOR-5964 starts
                /*,'ruleCompareEndDate' => [
                    'rule' => ['compareEndDate', 'start_date', false],
                    'message' => __('End date should not be less than current date')
                ]*///POCOR-5964 ends
            ])//POCOR-5917 ends
            ->add('current', 'ruleValidateNeeded', [
                'rule' => ['validateNeeded', 'current', $additionalParameters],
            ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->start_year = date("Y", strtotime($entity->start_date));
        $entity->end_year = date("Y", strtotime($entity->end_date));
        //POCOR-5917 starts
        if (!$entity->isNew()) { //when edit academic period
            $acedmicPeriodData = $this->find()->where([$this->aliasField('id') => $entity->id])->first();
            $entity->old_end_date = (new Date($acedmicPeriodData->end_date))->format('Y-m-d');
            $entity->old_end_year = $acedmicPeriodData->end_year;
        }
        //POCOR-5917 ends
        if ($entity->current == 1) {
            $entity->editable = 1;
            $entity->visible = 1;

            // Adding condition on updateAll(), only change the one which is not the current academic period.
            $where = [];
            if (!$entity->isNew()) {
                $where['id <> '] = $entity->id; // same with $where = [0 => 'id <> ' . $entity->id];
            }
            $this->updateAll(['current' => 0], $where);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        // Webhook Academic Period Delete -- Start
        $body = array();
        $body = [
            'academic_period_id' => $entity->id,
            'parent_id' => $entity->parent_id
        ];

        $Webhooks = TableRegistry::get('Webhook.Webhooks');
        if ($this->Auth->user()) {
            $Webhooks->triggerShell('academic_period_delete', [], $body);
        }
        // Webhook Academic Period Delete -- End
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
//        $entity = $this->find()->select(['current'])->where($ids)->first();

        // die silently when a non super_admin wants to delete
        if (!$this->AccessControl->isAdmin()) {
            $event->stopPropagation();
            $this->controller->redirect($this->url('index'));
        }

        // do not allow for deleting of current
        if (!empty($entity) && $entity->current == 1) {
            $event->stopPropagation();
            $this->Alert->warning('general.currentNotDeletable');
            $this->controller->redirect($this->url('index'));
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$this->AccessControl->isAdmin()) {
            if (array_key_exists('remove', $buttons)) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {

        if ($entity->isNew()) {

            $body = array();
            $body = [
                'academic_period_level_id' => $entity->academic_period_level_id,
                'code' => $entity->code,
                'name' => $entity->name,
                'start_date' => $entity->start_date,
                'end_date' => $entity->end_date,
                'current' => $entity->start_date,
                'academic_period_id' => '',
            ];

            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $Webhooks->triggerShell('academic_period_create', ['username' => $username], $body);
            }
        }

        //webhook academic period update starts
        if (!$entity->isNew()) {
            $body = array();
            $updateBody = [
                'academic_period_level_id' => $entity->academic_period_level_id,
                'code' => $entity->code,
                'name' => $entity->name,
                'start_date' => $entity->start_date,
                'end_date' => $entity->end_date,
                'current' => $entity->start_date,
                'academic_period_id' => $entity->id,
            ];
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $Webhooks->triggerShell('academic_period_update', [], $updateBody);
            }
        }

        // webhook academic period update ends


    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {


        //POCOR-5917 starts
        if (isset($entity->old_end_date) && !empty($entity->old_end_date) && isset($entity->old_end_year) && !empty($entity->old_end_year)) { //when edit academic period
            $academic_end_date = (new Date($entity->old_end_date))->format('Y-m-d');
            $academic_end_year = $entity->old_end_year;
            $institutionStudents = TableRegistry::get('institution_students');

            $institutionStudentsData = $institutionStudents
                ->find()
                ->where([
                    $institutionStudents->aliasField('end_date') => $academic_end_date,
                    $institutionStudents->aliasField('end_year') => $academic_end_year,
                    $institutionStudents->aliasField('student_status_id') => 1
                ])->toArray();
            if (!empty($institutionStudentsData)) {

                foreach ($institutionStudentsData as $key => $val) {
                    $institution_students_end_date = (new Date($entity->end_date))->format('Y-m-d');
                    $institution_students_end_year = $entity->end_year;
                    $institutionStudentsEntity = $this->patchEntity($val, ['end_date' => $institution_students_end_date, 'end_year' => $institution_students_end_year], ['validate' => false]);

                    $institutionStudents->save($institutionStudentsEntity);
                }
            }
        }
        //POCOR-5917 ends
        //POCOR-6825[START] : this functionality is moved to Administrations > Data management >Copy

        // $canCopy = $this->checkIfCanCopy($entity);

        // $shells = ['Infrastructure', 'Shift'];
        // if ($canCopy) {
        //     // only trigger shell to copy data if is not empty
        //     if ($entity->has('copy_data_from') && !empty($entity->copy_data_from)) {
        //         $copyFrom = $entity->copy_data_from;
        //         $copyTo = $entity->id;
        //         foreach ($shells as $shell) {
        //             $this->triggerCopyShell($shell, $copyFrom, $copyTo);
        //         }
        //     }
        // }

        //POCOR-6825[END]
        if ($entity->dirty('current')) { //check whether default value has been changed
            if ($entity->current) {
                $this->triggerUpdateInstitutionShiftTypeShell($entity->id);
            }
        }

        $broadcaster = $this;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionLands');
        $listeners[] = TableRegistry::get('Institution.InstitutionBuildings');
        $listeners[] = TableRegistry::get('Institution.InstitutionFloors');
        $listeners[] = TableRegistry::get('Institution.InstitutionRooms');

        if (!empty($listeners)) {
            $this->dispatchEventToModels('Model.AcademicPeriods.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        if ($parentId != null) {
            $query->where([$this->aliasField('parent_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('parent_id') . ' IS NULL']);
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {

        $this->addAfterSave($event, $entity, $requestData);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
//        $this->log('before', 'debug');
        $this->field('academic_period_level_id');
        $this->fields['start_year']['visible'] = false;
        $this->fields['end_year']['visible'] = false;
        $this->fields['school_days']['visible'] = false;
        $this->fields['lft']['visible'] = false;
        $this->fields['rght']['visible'] = false;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
//        $this->log('after', 'debug');
        $this->field('current');
//        $this->field('copy_data_from', [
//            'type' => 'hidden',
//            'value' => 0,
//            'after' => 'current'
//        ]);
        $this->field('editable');
        foreach ($this->_fieldOrder as $key => $value) {
            if (!in_array($value, array_keys($this->fields))) {
                unset($this->_fieldOrder[$key]);
            }
        }
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain('Levels');
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['current'] = $entity->current;
        $this->field('visible');

        // set academic_period_level_id to not editable to prevent any classes/subjects to not in Year level
        $this->fields['academic_period_level_id']['type'] = 'readonly';
        $this->fields['academic_period_level_id']['value'] = $entity->academic_period_level_id;
        $this->fields['academic_period_level_id']['attr']['value'] = $entity->level->name;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
//        $this->log('indexBeforeAction', 'debug');
        // Add breadcrumb
        $toolbarElements = [
            ['name' => 'AcademicPeriod.breadcrumb', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->fields['parent_id']['visible'] = false;

        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        if ($parentId != 0) {
            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();
            $this->controller->set('crumbs', $crumbs);
        } else {
            $results = $this
                ->find('all')
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id') => 0])
                ->all();

            if ($results->count() == 1) {
                $parentId = $results
                    ->first()
                    ->id;

                $action = $this->url('index');
                $action['parent'] = $parentId;
                return $this->controller->redirect($action);
            }
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        $query->where([$this->aliasField('parent_id') => $parentId]);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        //Setup fields
        $this->_fieldOrder = ['academic_period_level_id', 'code', 'name'];

        $this->fields['parent_id']['type'] = 'hidden';
        $parentId = $this->request->query('parent');

        if (is_null($parentId)) {
            $this->fields['parent_id']['attr']['value'] = -1;
        } else {
            $this->fields['parent_id']['attr']['value'] = $parentId;

            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();

            $parentPath = '';
            foreach ($crumbs as $crumb) {
                $parentPath .= $crumb->name;
                $parentPath .= $crumb === end($crumbs) ? '' : ' > ';
            }

            $this->fields['parent']['type'] = 'readonly';
            $this->fields['parent']['attr']['value'] = $parentPath;

            array_unshift($this->_fieldOrder, 'parent');
        }
    }

    public function triggerUpdateInstitutionShiftTypeShell($params)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateInstitutionShiftType ' . $params;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateInstitutionShiftType.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function onGetCurrent(Event $event, Entity $entity)
    {
        return $entity->current == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    // For PHPOE-1916
    public function onGetEditable(Event $event, Entity $entity)
    {
        return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    // End PHPOE-1916

    public function onGetName(Event $event, Entity $entity)
    {
        return $event->subject()->HtmlField->link($entity->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => $this->alias,
            'index',
            'parent' => $entity->id
        ]);
    }

    public function onUpdateFieldAcademicPeriodLevelId(Event $event, array $attr, $action, Request $request)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        $results = $this
            ->find()
            ->select([$this->aliasField('academic_period_level_id')])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        $attr['type'] = 'select';
        if (!$results->isEmpty()) {
            $data = $results->first();
            $levelId = $data->academic_period_level_id;

            $levelResults = $this->Levels
                ->find()
                ->select([$this->Levels->aliasField('level')])
                ->where([$this->Levels->aliasField('id') => $levelId])
                ->all();

            if (!$levelResults->isEmpty()) {
                $levelData = $levelResults->first();
                $level = $levelData->level;

                $levelOptions = $this->Levels
                    ->find('list')
                    ->where([$this->Levels->aliasField('level >') => $level])
                    ->toArray();
                $attr['options'] = $levelOptions;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        $attr['onChangeReload'] = 'changeCurrent';

        return $attr;
    }

    public function onUpdateFieldCopyDataFrom(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_level_id', $request->data[$this->alias()])) {
                    $academicPeriodLevelId = $request->data[$this->alias()]['academic_period_level_id'];
                    $level = $this->Levels
                        ->find()
                        ->order([$this->Levels->aliasField('level ASC')])
                        ->first();
                    $current = $request->query('current');

                    if (!is_null($current) && $current == 1) {
                        $where = [$this->aliasField('academic_period_level_id') => $level->id];
                        if (array_key_exists('id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['id'])) {
                            $currentAcademicPeriodId = $request->data[$this->alias()]['id'];
                            $currentAcademicPeriodOrder = $this->get($currentAcademicPeriodId)->order;
                            $where[$this->aliasField('id <>')] = $currentAcademicPeriodId;
                            $where[$this->aliasField('order >')] = $currentAcademicPeriodOrder;
                        }

                        $copyDataFromOptions = $this
                            ->find('list')
                            ->find('order')
                            ->where($where)
                            ->toArray();

                        $attr['type'] = 'select';
                        $attr['options'] = $copyDataFromOptions;
                        $attr['select'] = false;
                    }
                }
            }
        }

        return $attr;
    }

    public function onUpdateFieldEditable(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['current'])) {
            if ($request->data[$this->alias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function onUpdateFieldVisible(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['current'])) {
            if ($request->data[$this->alias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function addEditOnChangeCurrent(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['current']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('current', $request->data[$this->alias()])) {
                    $request->query['current'] = $request->data[$this->alias()]['current'];
                }
            }
        }
    }

    public function getYearList($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;

        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        $data = $this
            ->find('list')
            ->find('years')
            ->find('editable', ['isEditable' => $isEditable])
            ->where($conditions)
            ->toArray();

        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }

    public function getArchivedYearList($academicPeriod, $params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;

        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriod
        ];


        $data = $this
            ->find('list')
            ->where($where)
            ->toArray();

        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }

    public function findSchoolAcademicPeriod(Query $query, array $options)
    {
        $query
            ->find('visible')
            ->find('years')
            ->find('editable', ['isEditable' => true])
            ->find('order')
            ->where([
                $this->aliasField('parent_id') . ' <> ' => 0
            ]);

        return $query;
    }

    public function findSchoolAcademicPeriodArchive(Query $query, array $options)
    {
        $currentYear = date('Y');
        $query
            ->find('years')
            ->where([
                $this->aliasField('start_year <> ') => $currentYear
            ]);
        // echo "<pre>";print_r($query->sql());die;
        return $query;
    }

    public function findAcademicPeriodArchive(Query $query, array $options)
    {
        $currentYear = date('Y');
        return $query
            ->where([$this->aliasField('current <>') => 1, $this->aliasField('start_year <') => $currentYear])
            ->formatResults(function ($results) {
                $results = $results->toArray();
                $returnArr = [];
                foreach ($results as $result) {
                    $returnArr[] = ['id' => $result['id'], 'name' => $result['name']];
                }
                return $returnArr;
            });
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     */
    public function findAcademicPeriodStaffAttendanceArchived(Query $query, array $options)
    {
//        $this->log('findAcademicPeriodStaffAttendanceArchived', 'debug');
//        $this->log($options, 'debug');
        $academicPeriodStaffAttendanceArrayId = [0];
        $academicPeriodStaffAttendanceArray = ArchiveConnections::getArchiveYears('institution_staff_attendances',
            ['institution_id' => $options['institution_id']]);
        $academicPeriodStaffLeaveArray = ArchiveConnections::getArchiveYears('institution_staff_leave',
            ['institution_id' => $options['institution_id']]);
        $academicPeriodStaffAttendanceArray = array_unique(
            array_merge(
                $academicPeriodStaffAttendanceArray, $academicPeriodStaffLeaveArray
            )
        );
        if (sizeof($academicPeriodStaffAttendanceArray) > 0) {
            $academicPeriodStaffAttendanceArrayId = $academicPeriodStaffAttendanceArray;
        }
//        $this->log('$academicPeriodStaffAttendanceArchived', 'debug');
//        $this->log("$academicPeriodStaffAttendanceArray", 'debug');
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriodStaffAttendanceArrayId
        ];
        return $query->where($where);
    }

    public function getList($params = [])
    {
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : true;
        $withSelect = array_key_exists('withSelect', $params) ? $params['withSelect'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;
        $restrictLevel = array_key_exists('restrictLevel', $params) ? $params['restrictLevel'] : null;

        if (!$withLevels) {
            $where = [
                $this->aliasField('current') => 1,
                $this->aliasField('parent_id') . ' <> ' => 0
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find('list')
                ->find('visible')
                ->find('order')
                ->where($where)
                ->toArray();

            // get all other periods
            $where[$this->aliasField('current')] = 0;
            $data += $this->find('list')
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->find('order')
                ->where($where)
                ->toArray();
        } else {
            $where = [
                $this->aliasField('parent_id') . ' <> ' => 0,
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find()
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->contain(['Levels'])
                ->where($where)
                ->order([$this->aliasField('academic_period_level_id'), $this->aliasField('order')])
                ->toArray();

            $levelName = "";
            $list = [];

            foreach ($data as $key => $obj) {
                if ($levelName != $obj->level->name) {
                    $levelName = __($obj->level->name);
                }

                $list[$levelName][$obj->id] = __($obj->name);
            }

            $data = $list;
        }

        if ($withSelect) {
            $data = ['' => '-- ' . __('Select Period') . ' --'] + $data;
        }

        return $data;
    }

    public function findEditable(Query $query, array $options)
    {
        $isEditable = array_key_exists('isEditable', $options) ? $options['isEditable'] : null;
        if (is_null($isEditable)) {
            return $query;
        } else {
            return $query->where([$this->aliasField('editable') => (bool)$isEditable]);
        }
    }

    public function getDate($dateObject)
    {
        if (is_object($dateObject)) {
            return $dateObject->toDateString();
        }
        return false;
    }

    public function getWorkingDaysOfWeek()
    {
        // $weekdays = [
        //  0 => __('Sunday'),
        //  1 => __('Monday'),
        //  2 => __('Tuesday'),
        //  3 => __('Wednesday'),
        //  4 => __('Thursday'),
        //  5 => __('Friday'),
        //  6 => __('Saturday'),
        // ];

        $weekdays = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        $week = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $week[] = $weekdays[$firstDayOfWeek++];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }
        return $week;
    }

    public function getAttendanceWeeks($id)
    {
        // $weekdays = array(
        //  0 => 'sunday',
        //  1 => 'monday',
        //  2 => 'tuesday',
        //  3 => 'wednesday',
        //  4 => 'thursday',
        //  5 => 'friday',
        //  6 => 'saturday',
        //  //7 => 'sunday'
        // );

        $period = $this->findById($id)->first();
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');

        // If First of week is sunday changed the value to 7, because sunday with the '0' value unable to be displayed
        if ($firstDayOfWeek == 0) {
            $firstDayOfWeek = 7;
        }

        $daysPerWeek = $ConfigItems->value('days_per_week');

        // If last day index is '0'-valued-sunday it will change the value to '7' so it will be displayed.
        $lastDayIndex = ($firstDayOfWeek - 1);// last day index always 1 day before the starting date.
        if ($lastDayIndex == 0) {
            $lastDayIndex = 7;
        }

        $startDate = $period->start_date;

        $weekIndex = 1;
        $weeks = [];

        do {
            $endDate = $startDate->copy()->next($lastDayIndex);
            if ($endDate->gt($period->end_date)) {
                $endDate = $period->end_date;
            }
            $weeks[$weekIndex++] = [$startDate, $endDate];
            $startDate = $endDate->copy();
            $startDate->addDay();
        } while ($endDate->lt($period->end_date));

        return $weeks;
    }

    public function getDateFrom($id)
    {
        $period = $this->findById($id)->first();
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');

        // If First of week is sunday changed the value to 7, because sunday with the '0' value unable to be displayed
        if ($firstDayOfWeek == 0) {
            $firstDayOfWeek = 7;
        }

        $daysPerWeek = $ConfigItems->value('days_per_week');

        // If last day index is '0'-valued-sunday it will change the value to '7' so it will be displayed.
        $lastDayIndex = ($firstDayOfWeek - 1);// last day index always 1 day before the starting date.
        if ($lastDayIndex == 0) {
            $lastDayIndex = 7;
        }

        $startDate = $period->start_date;

        $weekIndex = 1;
        $weeks = [];

        do {
            $endDate = $startDate->copy();
            if ($endDate->gt($period->end_date)) {
                $endDate = $period->end_date;
            }
            $weeks[$weekIndex++] = [$startDate];
            $startDate = $endDate->copy();
            $startDate->addDay();
        } while ($endDate->lt($period->end_date));

        return $weeks;
    }

    public function getEditable($academicPeriodId)
    {
        try {
            return $this->get($academicPeriodId)->editable;
        } catch (RecordNotFoundException $e) {
            return false;
        }
    }

    public function getAvailableAcademicPeriods($list = true, $order = 'DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
            $this->aliasField('editable') => 1,
            $this->aliasField('visible') . ' >' => 0,
            $this->aliasField('parent_id') . ' >' => 0
        ])
            ->order($this->aliasField('name') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    }

    //POCOR-6347 starts
    public function getAvailableAcademicPeriodsById($id, $list = true, $order = 'DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
            $this->aliasField('editable') => 1,
            $this->aliasField('visible') . ' >' => 0,
            $this->aliasField('parent_id') . ' >' => 0,
            $this->aliasField('id') => $id
        ])
            ->order($this->aliasField('name') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    }//POCOR-6347 ends

    public function getCurrent()
    {
        $query = $this->find()
            ->select([$this->aliasField('id')])
            ->where([
                $this->aliasField('editable') => 1,
                $this->aliasField('visible') . ' > 0',
                $this->aliasField('current') => 1,
                $this->aliasField('parent_id') . ' > 0',
            ])
            ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            $query = $this->find()
                ->select([$this->aliasField('id')])
                ->where([
                    $this->aliasField('editable') => 1,
                    $this->aliasField('visible') . ' > 0',
                    $this->aliasField('parent_id') . ' > 0',
                ])
                ->order(['start_date DESC']);
            $countQuery = $query->count();
            if ($countQuery > 0) {
                $result = $query->first();
                return $result->id;
            } else {
                return 0;
            }
        }
    }

    public function generateMonthsByDates($startDate, $endDate)
    {
        $result = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
        // while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
        while ($stampFirstDayOfMonth <= $stampEndDay) {
            $monthString = date('F', $stampFirstDayOfMonth);
            $monthNumber = date('m', $stampFirstDayOfMonth);
            $year = date('Y', $stampFirstDayOfMonth);

            $result[] = [
                'month' => ['inNumber' => $monthNumber, 'inString' => $monthString],
                'year' => $year
            ];

            $stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
        }

        return $result;
    }

    public function generateDaysOfMonth($year, $month, $startDate, $endDate)
    {
        $days = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
        $stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);

        if ($stampFirstDayOfMonth <= $stampStartDay) {
            $tempStamp = $stampStartDay;
        } else {
            $tempStamp = $stampFirstDayOfMonth;
        }
        // while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
        while ($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth) {

            $weekDay = date('l', $tempStamp);
            $date = date('Y-m-d', $tempStamp);
            $day = date('d', $tempStamp);

            $dateObj = new Date($tempStamp);
            $dayFormat = __($dateObj->format('l')) . ' (' . $this->formatDate($dateObj) . ') ';

            $days[] = [
                'weekDay' => $weekDay,
                'date' => $date,
                'day' => $day,
                'dayFormat' => $dayFormat
            ];

            $tempStamp = strtotime('+1 day', $tempStamp);
        }

        return $days;
    }

    public function findYears(Query $query, array $options)
    {
        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        return $query
            ->find('visible')
            ->find('order')
            ->where([$this->aliasField('academic_period_level_id') => $level->id]);
    }

    public function findWeeklist(Query $query, array $options)
    {
        $model = $this;

        $query->formatResults(function (ResultSetInterface $results) use ($model) {
            return $results->map(function ($row) use ($model) {
                $academicPeriodId = $row->id;

                $todayDate = date("Y-m-d");
                $weekOptions = [];

                $weeks = $model->getAttendanceWeeks($academicPeriodId);
                $weekStr = __('Week') . ' %d (%s - %s)';
                $currentWeek = null;

                foreach ($weeks as $index => $dates) {
                    $startDay = $dates[0]->format('Y-m-d');
                    $endDay = $dates[1]->format('Y-m-d');
                    $weekAttr = [];
                    if ($todayDate >= $startDay && $todayDate <= $endDay) {
                        $weekStr = __('Current Week') . ' %d (%s - %s)';
                        $weekAttr['current'] = true;
                        $currentWeek = $index;
                    } else {
                        $weekStr = __('Week') . ' %d (%s - %s)';
                    }

                    $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                    $weekAttr['start_day'] = $startDay;
                    $weekAttr['end_day'] = $endDay;
                    $weekOptions[$index] = $weekAttr;
                }

                $row->weeks = $weekOptions;

                return $row;
            });
        });
    }

    //POCOR-6825[START] : unwanted method for this model
    // private function checkIfCanCopy(Entity $entity)
    // {
    //     $canCopy = false;

    //     $level = $this->Levels
    //         ->find()
    //         ->order([$this->Levels->aliasField('level ASC')])
    //         ->first();

    //     // if is year level and set to current
    //     if ($entity->academic_period_level_id == $level->id && $entity->current == 1) {
    //         $canCopy = true;
    //     }

    //     return $canCopy;
    // }

    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . ' ' . $copyFrom . ' ' . $copyTo;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '_copy.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function getLatest()
    {
        $query = $this->find()
            ->select([$this->aliasField('id')])
            ->where([
                $this->aliasField('editable') => 1,
                $this->aliasField('visible') . ' > 0',
                $this->aliasField('parent_id') . ' > 0',
                $this->aliasField('academic_period_level_id') => 1
            ])
            ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            return 0;
        }
    }

    public function getAcademicPeriodId($startDate, $endDate)
    {
        // get the academic period id from startDate and endDate (e.g. delete the absence records not showing the academic period id)
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $startDate,
                $this->aliasField('end_date') . ' >= ' => $endDate,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getAcademicPeriodIdByDate($date)
    {
        // get the academic period id from date
        $date = $date->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $date,
                $this->aliasField('end_date') . ' >= ' => $date,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getMealWeeksForPeriod($academicPeriodId)
    {
        $model = $this;
        $query = $this->AcademicPeriods->find()
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->all();


        $todayDate = date("Y-m-d");
        $weekOptions = [];
        $selectedIndex = 0;

        $weeks = $model->getAttendanceWeeks($academicPeriodId);

        $weekStr = __('Week') . ' %d (%s - %s)';
        $currentWeek = null;

        foreach ($weeks as $index => $dates) {
            $startDay = $dates[0]->format('Y-m-d');
            $endDay = $dates[1]->format('Y-m-d');
            $weekAttr = [];
            if ($todayDate >= $startDay && $todayDate <= $endDay) {
                $weekStr = __('Current Week') . ' %d (%s - %s)';
                // $weekAttr['selected'] = true;
                $currentWeek = $index;
            } else {
                $weekStr = __('Week') . ' %d (%s - %s)';
            }

            $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
            $weekAttr['start_day'] = $startDay;
            $weekAttr['end_day'] = $endDay;
            $weekAttr['id'] = $index;
            $weekOptions[] = $weekAttr;

            if ($todayDate >= $startDay && $todayDate <= $endDay) {
                end($weekOptions);
                $selectedIndex = key($weekOptions);
            }
        }

        $weekOptions[$selectedIndex]['selected'] = true;


        return $weekOptions;

    }

    public function findWeeksForPeriod(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $model = $this;

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model) {
                return $results->map(function ($row) use ($model) {
                    $academicPeriodId = $row->id;

                    $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }

                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;
                        $weekOptions[] = $weekAttr;

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($weekOptions);
                            $selectedIndex = key($weekOptions);
                        }
                    }

                    $weekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $weekOptions;

                    return $row;
                });
            });
    }

    public function findWeeksForPeriodStaffAttendanceArchived(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id'];
        $model = $this;
        $distinctDateValues = ArchiveConnections::getArchiveDays('institution_staff_attendances',
            ['institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ]);
        $distinctLeaveDateValues = ArchiveConnections::getArchiveLeaveDays('institution_staff_leave',
            ['institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ]);
//        $this->log('$distinctDateValues', 'debug');
//        $this->log($distinctDateValues, 'debug');
//        $this->log('$distinctLeaveDateValues', 'debug');
//        $this->log($distinctLeaveDateValues, 'debug');
        $mergedArray = array_unique(
            array_merge(
                $distinctDateValues,
                $distinctLeaveDateValues
            )
        );
//        $this->log('$mergedArray', 'debug');
//        $this->log($mergedArray, 'debug');
// Convert the strings back to DateTime objects
        $finalArray = array_map(function ($dateString) {
            return new Date($dateString);
        }, $mergedArray);
        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model, $finalArray) {
                return $results->map(function ($row) use ($model, $finalArray) {
                    $academicPeriodId = $row->id;

                    $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }
                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;

                        foreach ($finalArray as $distinctDateValue) {
                            if ($distinctDateValue >= $dates[0] && $distinctDateValue <= $dates[1]) {
                                $weekOptions[] = $weekAttr;
                            }
                        }

                        $uniqueWeekOptions = [];
                        $ids = [];

                        foreach ($weekOptions as $subArray) {
                            $id = $subArray['id'];
                            if (!in_array($id, $ids)) {
                                $ids[] = $id;
                                $uniqueWeekOptions[] = $subArray;
                            }
                        }


//                        $this->log('$uniqueWeekOptions', 'debug');
//
//                        $this->log($uniqueWeekOptions, 'debug');

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($uniqueWeekOptions);
                            $selectedIndex = key($uniqueWeekOptions);
                        }
                    }
                    $uniqueWeekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $uniqueWeekOptions;

                    return $row;
                });
            });
    }

    public function findPeriodHasClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $currentYearId = $this->getCurrent();

        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name')
            ])
            ->find('years')
            ->matching('InstitutionClasses', function ($q) use ($institutionId) {
                return $q->where(['InstitutionClasses.institution_id' => $institutionId]);
            })
            ->group([$this->aliasField('id')])
            ->formatResults(function (ResultSetInterface $results) use ($currentYearId) {
                return $results->map(function ($row) use ($currentYearId) {
                    if ($row->id == $currentYearId) {
                        $row->selected = true;
                    }
                    return $row;
                });
            });
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     * @throws \Exception
     */
    public function findPeriodHasClassArchived(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassIds = $this->getInstitutionClasses($institutionId);
        $academicPeriodArrayOne =
            ArchiveConnections::getArchiveYears('institution_class_attendance_records',
                ['institution_class_id IN' => $institutionClassIds]);
        $academicPeriodArrayTwo =
            ArchiveConnections::getArchiveYears('institution_student_absences',
                ['institution_id' => $institutionId]);
        $academicPeriodArrayThree =
            ArchiveConnections::getArchiveYears('institution_student_absence_details',
                ['institution_id' => $institutionId]);
        $academicPeriodArrayFour =
            ArchiveConnections::getArchiveYears('student_attendance_marked_records',
                ['institution_id' => $institutionId]);

        $academicPeriodWithArchiveArrayId = [0];
        $academicPeriodWithArchiveArray = array_unique(
            array_merge($academicPeriodArrayOne,
                $academicPeriodArrayTwo,
                $academicPeriodArrayThree,
                $academicPeriodArrayFour)
        );
        if (sizeof($academicPeriodWithArchiveArray) > 0) {
            $academicPeriodWithArchiveArrayId = $academicPeriodWithArchiveArray;
        }
//        $this->log('$academicPeriodWithArchiveArrayId', 'debug');
//        $this->log($academicPeriodWithArchiveArrayId, 'debug');
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriodWithArchiveArrayId
        ];
        return $query->where($where);
    }

    /**
     * @param $institutionId
     * @return array
     */
    private function getInstitutionClasses($institutionId)
    {
        $tableClasses = TableRegistry::get('institution_classes');
        $distinctClasses = $tableClasses->find('all')
            ->where(['institution_id' => $institutionId])
            ->select(['id'])
            ->distinct(['id'])
            ->toArray();
        $distinctClassValues = array_column($distinctClasses, 'id');
        $institutionClassIds = array_unique($distinctClassValues);
        return $institutionClassIds;
    }


    public function findWorkingDayOfWeek(Query $query, array $options)
    {
        $workingDayOfWeek = $this->getWorkingDaysOfWeek();

        $dayOfWeek = [];
        foreach ($workingDayOfWeek as $index => $day) {
            $dayOfWeek[] = [
                'day_of_week' => $index + 1,
                'day' => $day
            ];
        }

        return $query->formatResults(function (ResultSetInterface $results) use ($dayOfWeek) {
            return $dayOfWeek;
        });
    }

    public function findDaysForPeriodWeek(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $current_week_number_selected = $options['current_week_number_selected']; // POCOR-6723
        $weekId = $options['week_id'];
        $institutionId = $options['institution_id'];

        // pass true if you need school closed data
        if (array_key_exists('school_closed_required', $options)) {
            $schoolClosedRequired = $options['school_closed_required'];
        } else {
            $schoolClosedRequired = false;
        }

        $model = $this;

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $weeks = $model->getAttendanceWeeks($academicPeriodId);
        $week = $weeks[$weekId];

        if (isset($options['exclude_all']) && $options['exclude_all']) {
            $dayOptions = [];
        } else {
            $dayOptions[] = [
                'id' => -1,
                'name' => __('All Days'),
                'date' => -1
            ];
        }

        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            // sunday should be '7' in order to be displayed
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }

        $firstDayOfWeek = $week[0]->copy();
        $today = null;

        do {
            if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
                if ($schoolClosedRequired == false) {
                    $schoolClosed = false;
                } else {
                    $schoolClosed = $this->isSchoolClosed($firstDayOfWeek, $institutionId);
                    //POCOR-7787 start
                    if ($schoolClosed) {
                        $connection = ConnectionManager::get('default');
                        $sql = "SELECT institution_shift_periods.period_id  FROM calendar_event_dates
                            INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id 
                            INNER JOIN institution_shifts ON calendar_events.academic_period_id = institution_shifts.academic_period_id 
                                    AND calendar_events.institution_id = institution_shifts.institution_id 
                                    AND calendar_events.institution_shift_id = institution_shifts.shift_option_id 
                            INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id
                            INNER JOIN institution_shift_periods ON institution_shift_periods.institution_shift_period_id = institution_shifts.id 
                            WHERE calendar_event_dates.date = '" . $firstDayOfWeek->format('Y-m-d') . "' AND calendar_types.is_attendance_required = 0";

                        $result = $connection->execute($sql)->fetchAll('assoc');
                        $closedPeriods = [];
                        foreach ($result as $data) {
                            $closedPeriods[] = $data['period_id'];
                        }
                    }
                    //POCOR-7787 end
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDayOfWeek->dayOfWeek,
                    'day' => __($firstDayOfWeek->format('l')),
                    'name' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $suffix,
                    'date' => $firstDayOfWeek->format('Y-m-d'),
                    'current_week_number_selected' => $current_week_number_selected, //POCOR-6723
                    'day_number' => $firstDayOfWeek->isToday() //POCOR-6723
                ];

                if ($schoolClosed) {
                    $data['closed'] = true;
                    $data['periods'] = $closedPeriods;//POCOR-7787
                }

                $dayOptions[] = $data;

                if (is_null($today) || $firstDayOfWeek->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }
            }
            $firstDayOfWeek->addDay();
        } while ($firstDayOfWeek->lte($week[1]));

        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['current_week_number_selected'] = $current_week_number_selected; //POCOR-6723
            $dayOptions[$today]['day_number'] = __($firstDayOfWeek->format('N')); //POCOR-6723
        }

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }

    public function findDaysForPeriodWeekArchive(Query $query, array $options)
    {
        $firstDay = new Date($options['start_date']);
        $lastDay = new Date($options['end_date']);
        $institutionId = $options['institution_id'];
        $today = null;

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            // sunday should be '7' in order to be displayed
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }
        do {
            if (in_array($firstDay->dayOfWeek, $schooldays)) {
                {
                    $schoolClosed = $this->isSchoolClosed($firstDay, $institutionId);
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDay->dayOfWeek,
                    'day' => __($firstDay->format('l')),
                    'name' => __($firstDay->format('l')) . ' (' . $this->formatDate($firstDay) . ') ' . $suffix,
                    'date' => $firstDay->format('Y-m-d'),
                    'day_number' => $firstDay->isToday() //POCOR-6723
                ];

                $dayOptions[] = $data;

                if (is_null($today) || $firstDay->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }
            }
            $firstDay->addDay();
        } while ($firstDay->lte($lastDay));
        // echo json_encode($dayOptions);die;
        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['day_number'] = __($firstDay->format('N')); //POCOR-6723
        }

        $query
            ->select(['id'])
            ->limit(1)
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });

    }

    public function getNextAcademicPeriodId($id)
    {
        $selectedPeriod = $id;
        $periodLevelId = $this->get($selectedPeriod)->academic_period_level_id;
        $startDate = $this->get($selectedPeriod)->start_date->format('Y-m-d');

        $where = [
            $this->aliasField('id <>') => $selectedPeriod,
            $this->aliasField('academic_period_level_id') => $periodLevelId,
            $this->aliasField('start_date >=') => $startDate
        ];

        $nextAcademicPeriodId = $this->AcademicPeriods
            ->find('visible')
            ->find('editable', ['isEditable' => true])
            ->where($where)
            ->order([$this->aliasField('order') => 'DESC'])
            ->extract('id')
            ->first();

        return $nextAcademicPeriodId;
    }
}
