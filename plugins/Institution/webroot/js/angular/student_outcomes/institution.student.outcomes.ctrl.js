angular.module('institution.student.outcomes.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.outcomes.svc'])
    .controller('InstitutionStudentOutcomesCtrl', InstitutionStudentOutcomesController);

InstitutionStudentOutcomesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentOutcomesSvc'];

function InstitutionStudentOutcomesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentOutcomesSvc) {

    var Controller = this;

    // Constants
    Controller.dataReady = false;

    // Variables
    Controller.classId = null;
    Controller.outcomeTemplateId = null;

    Controller.gridOptions = {};
    Controller.className = '';
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.institutionId = null;
    Controller.outcomeTemplateName = '';
    Controller.educationGradeId = null;
    Controller.criterias = [];
    Controller.gradingOptions = [];
    Controller.studentResults = [];
    Controller.studentComments = '';

    // Filters
    Controller.studentOptions = [];
    Controller.selectedStudent = null;
    Controller.periodOptions = [];
    Controller.selectedPeriod = null;
    Controller.selectedPeriodStatus = null;
    Controller.subjectOptions = [];
    Controller.selectedSubject = null;

    // Function mapping
    Controller.initGrid = initGrid;
    Controller.formatResults = formatResults;
    Controller.resetColumnDefs = resetColumnDefs;
    Controller.changeOutcomeOptions = changeOutcomeOptions;

    angular.element(document).ready(function () {
        InstitutionStudentOutcomesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        if (Controller.classId != null && Controller.outcomeTemplateId != null) {
            InstitutionStudentOutcomesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.institutionId = response.institution_id;

                return InstitutionStudentOutcomesSvc.getStudentStatusId("CURRENT");
            }, function(error) {
                console.log(error);
            })
            .then(function (response) {
                var enrolledStatusId = response[0].id;
                return InstitutionStudentOutcomesSvc.getClassStudents(Controller.classId, enrolledStatusId);
            }, function(error) {
                console.log(error);
            })
            .then(function (classStudents) {
                Controller.studentOptions = classStudents;
                if (Controller.studentOptions.length > 0) {
                    Controller.selectedStudent = Controller.studentOptions[0].student_id;
                } else {
                    AlertSvc.warning(Controller, "Please setup students for this class");
                }
                return InstitutionStudentOutcomesSvc.getOutcomeTemplate(Controller.academicPeriodId, Controller.outcomeTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (outcomeTemplate) {
                Controller.outcomeTemplateName = outcomeTemplate.code_name;
                Controller.educationGradeId = outcomeTemplate.education_grade_id;
                Controller.criterias = outcomeTemplate.criterias;

                Controller.periodOptions = outcomeTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                } else {
                    AlertSvc.warning(Controller, "Please setup outcome periods for the selected template");
                }

                Controller.subjectOptions = outcomeTemplate.education_grade.education_subjects;
                if (Controller.subjectOptions.length > 0) {
                    Controller.selectedSubject = outcomeTemplate.education_grade.education_subjects[0].id;
                } else {
                    AlertSvc.warning(Controller, "Please setup subjects for the selected template");
                }
                return InstitutionStudentOutcomesSvc.getOutcomeGradingTypes();
            }, function (error) {
                console.log(error);
            })
            .then(function (gradingTypes) {
                angular.forEach(gradingTypes, function(value, key) {
                    Controller.gradingOptions[value.id] = value.grading_options;
                });
                return InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (outcomeResults) {
                Controller.formatResults(outcomeResults);
                return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (outcomeComments) {
                Controller.studentComments = outcomeComments.length > 0 ? outcomeComments[0].comments : '';
                return Controller.initGrid();
            }, function (error) {
                console.log(error);
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    function formatResults(outcomeResults) {
        var studentResults = [];
        angular.forEach(outcomeResults, function (value, key) {
            if (studentResults[value.outcome_criteria_id] == undefined) {
                studentResults[value.outcome_criteria_id] = 0;
            }
            studentResults[value.outcome_criteria_id] = value.outcome_grading_option_id;
        });
        Controller.studentResults = studentResults;
    }

    function resetColumnDefs(criterias, gradingOptions, period, selectedPeriodStatus, subject, student) {
        var response = InstitutionStudentOutcomesSvc.getColumnDefs(period, subject, student, Controller.periodOptions, Controller.subjectOptions, Controller.studentOptions);

        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if (Controller.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function(value, key) {
                    textToTranslate.push(value.headerName);
                });
                textToTranslate.push('Comments'); // translate comments title in pinned row

                InstitutionStudentOutcomesSvc.translate(textToTranslate)
                .then(function(res){
                    var commentTranslation = res.pop();
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    Controller.gridOptions.api.setColumnDefs(response.data);

                    if (period != null && subject != null && student != null) {
                        var rowData = [];
                        angular.forEach(criterias, function (value, key) {
                            if (value.education_subject_id == subject) {
                                var row = {
                                    student_id: student,
                                    outcome_period_id: period,
                                    period_editable: selectedPeriodStatus,
                                    education_subject_id: subject,
                                    outcome_criteria_id: value.id,
                                    outcome_criteria_name: value.code_name,
                                    grading_options: {},
                                    result: 0,
                                    save_error: {
                                        result: false
                                    }
                                };

                                if (angular.isDefined(gradingOptions[value.outcome_grading_type_id])) {
                                    row['grading_options'] = gradingOptions[value.outcome_grading_type_id];
                                }

                                if (angular.isDefined(Controller.studentResults[value.id])) {
                                    row['result'] = Controller.studentResults[value.id];
                                }
                                this.push(row);
                            }
                        }, rowData);

                        if (rowData.length > 0) {
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                            Controller.gridOptions.api.setRowData(rowData);
                        } else {
                            AlertSvc.warning(Controller, "Please setup outcome criterias for the selected subject");
                            Controller.gridOptions.api.hideOverlay();
                            var emptyRow = [{
                                period_editable: false,
                                outcome_criteria_name: 'No Outcome Criterias',
                                result: '',
                                save_error: {
                                    result: false
                                }
                            }];
                            Controller.gridOptions.api.setRowData(emptyRow);
                        }

                        // subject comments (pinned row at bottom)
                        var pinnedRowData = [{
                            student_id: student,
                            outcome_period_id: period,
                            period_editable: selectedPeriodStatus,
                            education_subject_id: subject,
                            outcome_criteria_name: commentTranslation,
                            result: Controller.studentComments,
                            save_error: {
                                result: false
                            }
                        }];
                        Controller.gridOptions.api.setPinnedBottomRowData(pinnedRowData);
                    }

                    Controller.gridOptions.api.sizeColumnsToFit();
                }, function(error){
                    console.log(error);
                });
                return true;
            } else {
                return true;
            }
        }
    }

    function changeOutcomeOptions(periodChange) {
        if (periodChange) {
            angular.forEach(Controller.periodOptions, function(value, key) {
                if (value.id == Controller.selectedPeriod) {
                    Controller.selectedPeriodStatus = value.editable;
                }
            });
        }

        InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
            Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (outcomeComments) {
            Controller.studentComments = outcomeComments.length > 0 ? outcomeComments[0].comments : '';
            Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent);
        }, function (error) {
        });
    }

    function initGrid() {
        return AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    outcome_template_id: Controller.outcomeTemplateId,
                    education_grade_id: Controller.educationGradeId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    outcome_template_id: Controller.outcomeTemplateId,
                    education_grade_id: Controller.educationGradeId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent);
                }
            };
        });
    }
}