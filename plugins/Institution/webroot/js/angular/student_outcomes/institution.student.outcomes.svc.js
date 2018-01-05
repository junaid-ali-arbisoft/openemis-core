angular
    .module('institution.student.outcomes.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentOutcomesSvc', InstitutionStudentOutcomesSvc);

InstitutionStudentOutcomesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc'];

function InstitutionStudentOutcomesSvc($http, $q, $filter, KdDataSvc, AlertSvc) {

    var service = {
        init: init,
        translate: translate,
        getClassDetails: getClassDetails,
        getStudentStatusId: getStudentStatusId,
        getClassStudents: getClassStudents,
        getOutcomeTemplate: getOutcomeTemplate,
        getOutcomeGradingTypes: getOutcomeGradingTypes,
        getStudentOutcomeResults: getStudentOutcomeResults,
        getStudentOutcomeComments: getStudentOutcomeComments,
        getColumnDefs: getColumnDefs,
        renderInput: renderInput,
        saveOutcomeResults: saveOutcomeResults,
        saveOutcomeComments: saveOutcomeComments
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentStatuses: 'Student.StudentStatuses',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        OutcomeTemplates: 'Outcome.OutcomeTemplates',
        OutcomeGradingTypes: 'Outcome.OutcomeGradingTypes',
        InstitutionOutcomeResults: 'Institution.InstitutionOutcomeResults',
        OutcomeSubjectComments: 'Institution.InstitutionOutcomeSubjectComments'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentOutcomes');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getClassDetails(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClasses
            .get(classId)
            .find('translateItem')
            .contain(['AcademicPeriods'])
            .ajax({success: success, defer:true});
    }

    function getStudentStatusId(statusCode) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return StudentStatuses
            .select(['id'])
            .where({code: statusCode})
            .ajax({success: success, defer:true});
    }

    function getClassStudents(classId, enrolledStatusId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClassStudents
            .select()
            .contain(['Users'])
            .where({institution_class_id: classId, student_status_id: enrolledStatusId})
            .order(['Users.first_name', 'Users.last_name'])
            .ajax({success: success, defer:true});
    }

    function getOutcomeTemplate(academicPeriodId, outcomeTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: outcomeTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeTemplates
            .get(primaryKey)
            .contain(['Periods', 'Criterias', 'EducationGrades.EducationSubjects'])
            .ajax({success: success, defer:true});
    }

    function getOutcomeGradingTypes() {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeGradingTypes
            .select()
            .contain(['GradingOptions'])
            .ajax({success: success, defer:true});
    }

    function getStudentOutcomeResults(studentId, templateId, periodId, gradeId, subjectId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionOutcomeResults
            .find('studentResults', {
                student_id: studentId,
                outcome_template_id: templateId,
                outcome_period_id: periodId,
                education_grade_id: gradeId,
                education_subject_id: subjectId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getStudentOutcomeComments(studentId,  templateId, periodId, gradeId, subjectId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeSubjectComments
            .find('studentComments', {
                student_id: studentId,
                outcome_template_id: templateId,
                outcome_period_id: periodId,
                education_grade_id: gradeId,
                education_subject_id: subjectId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getColumnDefs(period, subject, student, periodOptions, subjectOptions, studentOptions) {
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };

        // dynamic table headers
        var criteriaHeader = 'Outcome Criteria';
        var resultHeader = 'Result';
        if (periodOptions.length > 0 && period != null && subjectOptions.length > 0 && subject != null && studentOptions.length > 0 && student != null) {
            var subjectObj = $filter('filter')(subjectOptions, {'id':subject});
            if (subjectObj.length > 0) {
                criteriaHeader = subjectObj[0].code_name + ' Criteria';
            }
            var studentObj = $filter('filter')(studentOptions, {'student_id':student});
            if (studentObj.length > 0) {
                resultHeader = studentObj[0].user.name_with_id;
            }
        }

        var columnDefs = [];
        columnDefs.push({
            headerName: criteriaHeader,
            field: "outcome_criteria_name",
            filterParams: filterParams,
            menuTabs: menuTabs,
            filter: 'text'
        });

        var columnDef = {
            headerName: resultHeader,
            field: "result",
            filterParams: filterParams,
            menuTabs: menuTabs
        };
        var extra = {};
        columnDef = this.renderInput(columnDef, extra);
        columnDefs.push(columnDef);

        return {data: columnDefs};
    }

    function renderInput(cols, extra) {
        var vm = this;

        cols = angular.merge(cols, {
            cellClassRules: {
                'oe-cell-error': function(params) {
                    return params.data.save_error[params.colDef.field];
                }
            },
            cellRenderer: function(params) {
                var periodEditable = params.data.period_editable;
                var gradingOptions = {0 : '-- Select --'};
                if (angular.isDefined(params.data.grading_options)) {
                    angular.forEach(params.data.grading_options, function(obj, key) {
                        gradingOptions[obj.id] = obj.code_name;
                    });
                }

                if (periodEditable) {
                    var oldValue = params.value;

                    var eCell = document.createElement('div');
                    eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

                    var eSelect = document.createElement("select");
                    angular.forEach(gradingOptions, function(value, key) {
                        var eOption = document.createElement("option");
                        eOption.setAttribute("value", key);
                        eOption.innerHTML = value;
                        eSelect.appendChild(eOption);
                    });
                    eSelect.value = params.value;

                    eSelect.addEventListener('blur', function () {
                        var newValue = eSelect.value;

                        if (newValue != oldValue || params.data.save_error[params.colDef.field]) {
                            params.data[params.colDef.field] = newValue;

                            var controller = params.context._controller;
                            vm.saveOutcomeResults(params)
                            .then(function(response) {
                                params.data.save_error[params.colDef.field] = false;
                                AlertSvc.info(controller, "Changes will be automatically saved when any value is changed");
                                params.api.refreshCells({
                                    rowNodes: [params.node],
                                    columns: [params.colDef.field],
                                    force: true
                                });

                            }, function(error) {
                                params.data.save_error[params.colDef.field] = true;
                                console.log(error);
                                AlertSvc.error(controller, "There was an error when saving the results");
                                params.api.refreshCells({
                                    rowNodes: [params.node],
                                    columns: [params.colDef.field],
                                    force: true
                                });
                            });
                        }
                    });

                    eCell.appendChild(eSelect);

                } else {
                    // don't allow input if period is not editable
                    var cellValue = '';
                    if (angular.isDefined(params.value) && params.value.length != 0 && params.value != 0) {
                        cellValue = gradingOptions[params.value];
                    }

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);
                }

                return eCell;
            },
            pinnedRowCellRenderer: function(params) {
                var periodEditable = params.data.period_editable;

                if (periodEditable) {
                    var oldValue = params.value;

                    var eCell = document.createElement('div');
                    var textInput = document.createElement('input');
                    textInput.setAttribute("type", "text");
                    textInput.setAttribute("class", "oe-cell-editable");
                    textInput.value = params.value;
                    eCell.appendChild(textInput);

                    // allow keyboard shortcuts
                    textInput.addEventListener('keydown', function(event) {
                        event.stopPropagation();
                    });

                    textInput.addEventListener('blur', function() {
                        var newValue = textInput.value;

                        if (newValue != oldValue || params.data.save_error[params.colDef.field]) {
                            params.data[params.colDef.field] = newValue;

                            var controller = params.context._controller;
                            vm.saveOutcomeComments(params)
                            .then(function(response) {
                                params.data.save_error[params.colDef.field] = false;
                                AlertSvc.info(controller, "Changes will be automatically saved when any value is changed");
                                params.api.refreshCells({
                                    rowNodes: [params.node],
                                    columns: [params.colDef.field],
                                    force: true
                                });

                            }, function(error) {
                                params.data.save_error[params.colDef.field] = true;
                                console.log(error);
                                AlertSvc.error(controller, "There was an error when saving the comments");
                                params.api.refreshCells({
                                    rowNodes: [params.node],
                                    columns: [params.colDef.field],
                                    force: true
                                });
                            });
                        }
                    });

                } else {
                    // don't allow input if period is not editable
                    var cellValue = '';
                    if (angular.isDefined(params.value) && params.value.length != 0) {
                        cellValue = params.value;
                    }

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);
                }
                return eCell;
            },
            suppressMenu: true
        });
        return cols;
    }

    function saveOutcomeResults(params) {
        var outcomeGradingOptionId = params.data.result;
        var studentId = params.data.student_id;
        var outcomeTemplateId = params.context.outcome_template_id;
        var outcomePeriodId = params.data.outcome_period_id;
        var educationGradeId = params.context.education_grade_id;
        var educationSubjectId = params.data.education_subject_id;
        var outcomeCriteriaId = params.data.outcome_criteria_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;

        var saveObj = {
            outcome_grading_option_id: parseInt(outcomeGradingOptionId),
            student_id: studentId,
            outcome_template_id: outcomeTemplateId,
            outcome_period_id: outcomePeriodId,
            education_grade_id: educationGradeId,
            education_subject_id: educationSubjectId,
            outcome_criteria_id: outcomeCriteriaId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return InstitutionOutcomeResults.save(saveObj);
    }

    function saveOutcomeComments(params) {
        var comments = params.data.result;
        var studentId = params.data.student_id;
        var outcomeTemplateId = params.context.outcome_template_id;
        var outcomePeriodId = params.data.outcome_period_id;
        var educationGradeId = params.context.education_grade_id;
        var educationSubjectId = params.data.education_subject_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;

        var saveObj = {
            comments: comments,
            student_id: studentId,
            outcome_template_id: outcomeTemplateId,
            outcome_period_id: outcomePeriodId,
            education_grade_id: educationGradeId,
            education_subject_id: educationSubjectId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return OutcomeSubjectComments.save(saveObj);
    }
};