
angular
    .module('institution.student.attendances.archive.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentAttendancesArchiveSvc', InstitutionStudentAttendancesArchiveSvc);

InstitutionStudentAttendancesArchiveSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStudentAttendancesArchiveSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    const attendanceType = {
        'NOTMARKED': {
            code: 'NOTMARKED',
            icon: 'fa fa-minus',
            color: '#999999'
        },
        'PRESENT': {
            code: 'PRESENT',
            icon: 'fa fa-check',
            color: '#77B576'
        },
        'LATE': {
            code: 'LATE',
            icon: 'fa fa-check-circle-o',
            color: '#999'
        },
        'UNEXCUSED': {
            code: 'UNEXCUSED',
            icon: 'fa fa-circle-o',
            color: '#CC5C5C'
        },
        'EXCUSED': {
            code: 'EXCUSED',
            icon: 'fa fa-circle-o',
            color: '#CC5C5C'
        },
        'NoScheduledClicked': {
            code: 'NoScheduledClicked',
            icon: 'fa fa-times',
            color: '#999',
        }
    };

    const icons = {
        'REASON': 'kd kd-reason reason',
        'COMMENT': 'kd kd-comment comment',
        'PRESENT': 'fa fa-minus present',
    };

    const ALL_DAY_VALUE = -1;

    var translateText = {
        'original': {
            'OpenEmisId': 'OpenEMIS ID',
            'Name': 'Name',
            'Attendance': 'Attendance',
            'ReasonComment': 'Reason / Comment',
            'Monday': 'Monday',
            'Tuesday': 'Tuesday',
            'Wednesday': 'Wednesday',
            'Thursday': 'Thursday',
            'Friday': 'Friday',
            'Saturday': 'Saturday',
            'Sunday': 'Sunday'
        },
        'translated': {}
    };

    var controllerScope;

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionClassGrades: 'Institution.InstitutionClassGrades',
        StudentAttendanceTypes: 'Attendance.StudentAttendanceTypes',
        InstitutionClassSubjects: 'Institution.InstitutionClassSubjects',
        AbsenceTypes: 'Institution.AbsenceTypes',
        StudentAbsenceReasons: 'Institution.StudentAbsenceReasons',
        StudentAbsencesPeriodDetails: 'Institution.StudentAbsencesPeriodDetails',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes',
        StudentAttendanceMarkedRecordsArchived: 'Attendance.StudentAttendanceMarkedRecordsArchived'
    };

    var service = {
        init: init,
        translate: translate,

        getAttendanceTypeList: getAttendanceTypeList,
        getAbsenceTypeOptions: getAbsenceTypeOptions,
        getStudentAbsenceReasonOptions: getStudentAbsenceReasonOptions,

        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getEducationGradeOptions: getEducationGradeOptions,
        getSubjectOptions: getSubjectOptions,
        getPeriodOptions: getPeriodOptions,
        getIsMarked: getIsMarked,
        getNoScheduledClassMarked: getNoScheduledClassMarked,
        getClassStudent: getClassStudent,

        getSingleDayColumnDefs: getSingleDayColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,

        saveAbsences: saveAbsences,
        savePeriodMarked: savePeriodMarked,
        getsavePeriodMarked: getsavePeriodMarked,//POCOR-6658
        isMarkableSubjectAttendance: isMarkableSubjectAttendance
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentAttendances');
        KdDataSvc.init(models);
    }

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function (response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    }

    function getAttendanceTypeList() {
        return attendanceType;
    }

    // data service
    function getTranslatedText() {
        var success = function (response, deferred) {
            var translatedObj = response.data;
            // console.log("response.data", response.data)
            if (angular.isDefined(translatedObj)) {
                translateText = translatedObj;
            }
            deferred.resolve(angular.isDefined(translatedObj));
        };

        KdDataSvc.init({translation: 'translate'});
        return translation.translate(translateText.original, {
            success: success,
            defer: true
        });
    }

    function getAbsenceTypeOptions() {
        var success = function (response, deferred) {
            var absenceType = response.data.data;
            if (angular.isObject(absenceType) && absenceType.length > 0) {
                deferred.resolve(absenceType);
            } else {
                deferred.reject('There was an error when retrieving the absence types');
            }
        };

        return AbsenceTypes
            .find('absenceTypeList')
            .ajax({success: success, defer: true});
    }

    function getStudentAbsenceReasonOptions() {
        var success = function (response, deferred) {
            var studentAbsenceReasons = response.data.data;
            if (angular.isObject(studentAbsenceReasons) && studentAbsenceReasons.length > 0) {
                deferred.resolve(studentAbsenceReasons);
            } else {
                deferred.reject('There was an error when retrieving the student absence reasons');
            }
        };

        return StudentAbsenceReasons
            .select(['id', 'name'])
            //.order(['order']) //POCOR-5815
            .ajax({success: success, defer: true});
    }

    function getAcademicPeriodOptions(institutionId) {
        var success = function (response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };

        return AcademicPeriods
            .find('periodHasClassArchived', {
                institution_id: institutionId
            })
            .ajax({success: success, defer: true});
    }

    function getWeekListOptions(academicPeriodId) {
        var success = function (response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks; // find only 1 academic period entity

                if (angular.isDefined(weeks) && weeks.length > 0) {
                    deferred.resolve(weeks);
                } else {
                    deferred.reject('There was an error when retrieving the week list');
                }
            } else {
                deferred.reject('There was an error when retrieving the week list');
            }
        };

        return AcademicPeriods
            .find('weeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId, institutionId) {
        var success = function (response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };

        return AcademicPeriods
            .find('daysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId,
                institution_id: institutionId,
                school_closed_required: true
            })
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId) {
        var success = function (response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList)) {
                if (classList.length > 0) {
                    deferred.resolve(classList);
                } else {
                    AlertSvc.warning(controllerScope, 'You do not have any classes');
                    deferred.reject('You do not have any classess');
                }
            } else {
                deferred.reject('There was an error when retrieving the class list');
            }
        };

        return InstitutionClasses
            .find('classesByInstitutionAndAcademicPeriod', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getEducationGradeOptions(institutionId, academicPeriodId, classId) {
        var success = function (response, deferred) {
            var educationGradeList = response.data.data;
            // console.log("educationGradeList", educationGradeList)
            if (angular.isObject(educationGradeList)) {
                if (educationGradeList.length > 0) {
                    deferred.resolve(educationGradeList);
                } else {
                    AlertSvc.warning(controllerScope, 'You do not have any education grade');
                    deferred.reject('You do not have any education grades');
                }
            } else {
                deferred.reject('There was an error when retrieving the education grade list');
            }
        };
        return InstitutionClasses
            .find('gradesByInstitutionAndAcademicPeriodAndInstitutionClass', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId,
                institution_class_id: classId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getSubjectOptions(institutionId, institutionClassId, academicPeriodId, day_id, educationGradeId) {
        var success = function (response, deferred) {
            var subjectList = response.data.data;
            if (angular.isObject(subjectList)) {
                deferred.resolve(subjectList);
            } else {
                deferred.reject('There was an error when retrieving the subject list');
            }
        };

        return InstitutionClassSubjects
            .find('allSubjectsByClassPerAcademicPeriod', {
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day_id,
                education_grade_id: educationGradeId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getPeriodOptions(institutionClassId, academicPeriodId, day_id, educationGradeId, weekStartDay, weekEndDay) {//POCOR-7183 add params weekStartDay, weekEndDay
        var success = function (response, deferred) {
            var attendancePeriodList = response.data.data;
            // console.log("attendancePeriodList", attendancePeriodList)
            if (angular.isObject(attendancePeriodList) && attendancePeriodList.length > 0) {
                deferred.resolve(attendancePeriodList);
            } else {
                deferred.reject('There was an error when retrieving the attendance period list');
            }
        };

        return StudentAttendanceMarkTypes
            .find('periodByClass', {
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day_id,
                education_grade_id: educationGradeId,
                week_start_day: weekStartDay,//POCOR-7183
                week_end_day: weekEndDay//POCOR-7183
            })
            .ajax({success: success, defer: true});
    }

    function getClassStudent(params) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            attendance_period_id: params.attendance_period_id,
            day_id: params.day_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
            subject_id: params.subject_id
        };
        // console.log(extra)
        if (extra.attendance_period_id == '' || extra.institution_class_id == '' || extra.academic_period_id == '') {
            return $q.reject('There was an error when retrieving the class student list');
        }

        var success = function (response, deferred) {
            // console.log(response);
            var classStudents = response.data.data;
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentAttendances
            .find('classStudentsWithAbsenceArchive', extra)
            .ajax({success: success, defer: true});
    }

    function getIsMarked(params) {
        // console.log("parms", params)
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            day_id: params.day_id,
            attendance_period_id: params.attendance_period_id,
            subject_id: params.subject_id
        };

        if (extra.day_id == ALL_DAY_VALUE) {
            return $q.resolve(false);
        }

        var success = function (response, deferred) {
            var count = response.data.total;
            // console.log("response.data", response.data)
            if (angular.isDefined(count)) {
                var isMarked = count > 0;
                deferred.resolve(isMarked);
            } else {
                deferred.reject('There was an error when retrieving the is_marked record');
            }
        };

        return StudentAttendanceMarkedRecordsArchived
            .find('periodIsMarked', extra)
            .ajax({success: success, defer: true});
    }

    function getNoScheduledClassMarked(params) {
        // console.log("parms", params)
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            day_id: params.day_id,
            attendance_period_id: params.attendance_period_id,
            subject_id: params.subject_id
        };

        if (extra.day_id == ALL_DAY_VALUE) {
            return $q.resolve(false);
        }

        var success = function (response, deferred) {
            var count = response.data.total;
            // console.log("response.data", response.data)
            if (angular.isDefined(count)) {
                var isMarked = count > 0;
                deferred.resolve(isMarked);
            } else {
                deferred.reject('There was an error when retrieving the is_marked record');
            }
        };

        return StudentAttendanceMarkedRecordsArchived
            .find('NoScheduledClass', extra)
            .ajax({success: success, defer: true});
    }

    // save error
    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        angular.forEach(data.save_error, function (error, key) {
            if (key != skipKey) {
                data.save_error[key] = false;
            }
        })
    }

    function hasError(data, key) {
        return (angular.isDefined(data.save_error) && angular.isDefined(data.save_error[key]) && data.save_error[key]);
    }

    // save
    function saveAbsences(data, context) {
        var studentAbsenceData = {
            student_id: data.student_id,
            institution_id: data.institution_id,
            academic_period_id: data.academic_period_id,
            institution_class_id: data.institution_class_id,
            absence_type_id: data.absence_type_id,
            student_absence_reason_id: data.student_absence_reason_id,
            comment: data.comment,
            period: context.period,
            date: context.date,
            subject_id: context.subject_id,
            education_grade_id: context.education_grade_id
        };

        return StudentAbsencesPeriodDetails.save(studentAbsenceData);
    }

    function savePeriodMarked(params, scope) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            date: params.day_id,
            period: params.attendance_period_id,
            subject_id: params.subject_id
        };

        UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
        StudentAttendanceMarkedRecords.save(extra)
            .then(
                function (response) {
                    AlertSvc.info(scope, 'Attendances will be automatically saved.');
                },
                function (error) {
                    AlertSvc.error(scope, 'There was an error when saving the record');
                }
            )
            .finally(function () {
                UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            });
    }

    /*
     * PCOOR-6658 STARTS
     * Create function for save attendance for multigrade class also.
     * author : Anubhav Jain <anubhav.jain@mail.vinove.com>
     */
    function getsavePeriodMarked(params) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            attendance_period_id: params.attendance_period_id,
            day_id: params.day_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
            subject_id: params.subject_id
        };

        var success = function (response, deferred) {
            // console.log('getsavePeriodMarked');
            // console.log(response);
            var classStudents = response;
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when saving the record');
            }
        };

        return StudentAttendances
            .find('classStudentsWithAbsenceSave', extra)
            .ajax({success: success, defer: true});
    }

    // column definitions
    function getAllDayColumnDefs(dayList, attendancePeriodList) {
        // console.log('getAllDayColumnDefs');
        // console.log(dayList);
        // console.log(attendancePeriodList);
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }
        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: "openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.Name,
            field: "student_name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        angular.forEach(dayList, function (dayObj, dayKey) {
            if (dayObj.id != -1) {
                var childrenColDef = [];
                angular.forEach(attendancePeriodList, function (periodObj, periodKey) {
                    var fieldstr = 'week_attendance.' + dayObj.day + '.' + periodObj.id;
                    // console.log(fieldstr);
                    childrenColDef.push({
                        headerName: periodObj.id,
                        field: fieldstr,
                        filterParams: filterParams,
                        suppressSorting: true,
                        suppressResize: true,
                        menuTabs: [],
                        minWidth: 30,
                        headerClass: 'children-period',
                        cellClass: 'children-cell',
                        cellRenderer: function (params) {
                            if (angular.isDefined(params.value)) {
                                var code = params.value;
                                return getViewAllDayAttendanceElement(code);
                            }
                        }
                    });
                });

                var dayText = dayObj.name;

                var colDef = {
                    headerName: dayText,
                    children: childrenColDef
                };

                columnDefs.push(colDef);
            }
        });

        return columnDefs;
    }

    function getSingleDayColumnDefs(noScheduledClicked) {
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") ||
            navigator.userAgent.indexOf("Android") != -1 ||
            navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: 'openemis_no',
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        });
        columnDefs.push({
            headerName: translateText.translated.Name,
            field: 'student_name',
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        });

        columnDefs.push({
            headerName: translateText.translated.Attendance,
            field: 'absence_type_code',
            suppressSorting: true,
            menuTabs: [],
            cellRenderer: function (params) {
                // console.log('absence_type_code');
                // console.log(params);
                if (angular.isDefined(params.value)) {
                    var context = params.context;
                    var absenceTypeList = context.absenceTypes;
                    var isMarked = context.isMarked;
                    var isSchoolClosed = params.context.schoolClosed;
                    var mode = params.context.mode;
                    var data = params.data;
                    // console.log(data);
                    if (mode == 'view') {
                        return getViewAttendanceElement(data,
                            absenceTypeList,
                            isMarked,
                            isSchoolClosed,
                            noScheduledClicked);
                    } else if (mode == 'edit') {
                        var api = params.api;
                        return getEditAttendanceElement(data, absenceTypeList, api, context);
                    }
                }
            }
        });

        columnDefs.push({
            headerName: translateText.translated.ReasonComment,
            field: "2",
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function (params) {
                // console.log('student_absence_reason_id');
                // console.log(params);
                if (angular.isDefined(params.data)) {
                    var data = params.data;
                    var context = params.context;
                    var studentAbsenceReasonList = context.studentAbsenceReasons;
                    var mode = context.mode;
                    if (angular.isDefined(data.absence_type_id)) {
                        var absence_type_code = (data.absence_type_code === null) ? "" : data.absence_type_code;
                        var absence_type_name = (data.absence_type_name === null) ? "" : data.absence_type_name;
                        if (noScheduledClicked) {
                            absence_type_code = 'NoScheduledClicked';
                            absence_type_name = 'No Lessons';
                        }

                        if (mode == 'view') {
                            switch (absence_type_code) {
                                case attendanceType.PRESENT.code:
                                    return '<i class="' + icons.PRESENT + '"></i>';
                                    break;
                                case attendanceType.LATE.code:
                                case attendanceType.UNEXCUSED.code:
                                    var html = '';
                                    html += getViewCommentsElement(data);
                                    return html;
                                    break;
                                case attendanceType.EXCUSED.code:
                                    var html = '';
                                    html += getViewAbsenceReasonElement(data, studentAbsenceReasonList);
                                    html += getViewCommentsElement(data);
                                    return html;
                                    break;
                            }
                        } else if (mode == 'edit') {
                            var api = params.api;
                            switch (absence_type_code) {
                                case attendanceType.PRESENT.code:
                                    return '<i class="' + icons.PRESENT + '"></i>';
                                    break;
                                case attendanceType.LATE.code:
                                case attendanceType.UNEXCUSED.code:
                                    var eCell = document.createElement('div');
                                    eCell.setAttribute("class", "reason-wrapper");
                                    var eTextarea = getEditCommentElement(data, context, api);
                                    eCell.appendChild(eTextarea);
                                    return eCell;
                                    break;
                                case attendanceType.EXCUSED.code:
                                    var eCell = document.createElement('div');
                                    eCell.setAttribute("class", "reason-wrapper");
                                    var eSelect = getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api);
                                    var eTextarea = getEditCommentElement(data, context, api);
                                    eCell.appendChild(eSelect);
                                    eCell.appendChild(eTextarea);
                                    return eCell;
                                    break;
                                default:
                                    return "";
                                    break;
                            }
                        }
                    }
                }
            }
        });

        return columnDefs;
    }

    // cell renderer elements
    function getEditAttendanceElement(data, absenceTypeList, api, context) {
        // console.log[data];
        var dataKey = 'absence_type_id';
        var scope = context.scope;
        var eCell = document.createElement('div');
        eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eCell.setAttribute("id", dataKey);
        // console.log(data[dataKey])
        if (data[dataKey] == null) {
            data[dataKey] = 0;
        }

        var eSelect = document.createElement("select");
        angular.forEach(absenceTypeList, function (obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });

        if (hasError(data, dataKey)) {
            eSelect.setAttribute("class", "error");
        }

        eSelect.value = data[dataKey];
        eSelect.addEventListener('change', function () {
            setTimeout(function () {
                setRowDatas(context, data)
            }, 200)
            var oldValue = data[dataKey];
            var newValue = eSelect.value;
            //POCOR-5846 start
            var div = document.querySelector('.ag-body-viewport');
            var scrollbar_value = div.scrollTop;
            localStorage.setItem("scrollbar-value", scrollbar_value);
            //POCOR-5846 end
            var absenceTypeObj = absenceTypeList.find(obj => obj.id == newValue);
            // console.log("absenceTypeObj", absenceTypeObj)
            // data.institution_student_absences.absence_type_id = newValue;

            if (newValue != oldValue) {
                var oldParams = {
                    absence_type_id: oldValue
                };

                // reset not related data, store old params for reset purpose
                switch (data.absence_type_code) {
                    case attendanceType.PRESENT.code:
                        oldParams.student_absence_reason_id = data.student_absence_reason_id;
                        oldParams.comment = data.comment;
                        data.student_absence_reason_id = null;
                        data.comment = null;
                        data.absence_type_id = null;
                        break;
                    case attendanceType.LATE.code:
                    case attendanceType.UNEXCUSED.code:
                        oldParams.student_absence_reason_id = data.student_absence_reason_id;
                        oldParams.comment = data.comment;
                        data.student_absence_reason_id = null;
                        data.comment = null;
                        break;
                    case attendanceType.EXCUSED.code:
                        oldParams.comment = data.comment;
                        data.comment = null;
                        break;
                }

                oldValue = newValue;
                data.absence_type_id = newValue;
                data.absence_type_code = absenceTypeObj.code;

                var refreshParams = {
                    columns: ['student_absence_reason_id'],
                    force: true
                }
                api.refreshCells(refreshParams);
            }

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            saveAbsences(data, context)
                .then(
                    function (response) {
                        clearError(data, dataKey);
                        if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
                            data.save_error[dataKey] = true;
                            angular.forEach(oldParams, function (value, key) {
                                data[key] = value;
                            });
                            AlertSvc.error(scope, 'There was an error when saving the record');
                        } else {
                            data.save_error[dataKey] = false;
                            AlertSvc.info(scope, 'Attendances will be automatically saved.');
                        }
                    },
                    function (error) {
                        clearError(data, dataKey);
                        console.error(error);
                        data.save_error[dataKey] = true;
                        angular.forEach(oldParams, function (value, key) {
                            data.key = value;
                        });
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    }
                )
                .finally(function () {
                    var refreshParams = {
                        columns: [
                            'student_absence_reason_id',
                            'absence_type_id'
                        ],
                        force: true
                    };
                    //POCOR-5846 start
                    var scrollbar_value2 = localStorage.getItem("scrollbar-value");
                    var div = document.querySelector('.ag-body-viewport');
                    div.scrollTop = scrollbar_value2;
                    //POCOR-5846 end
                    api.refreshCells(refreshParams);
                    UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
                });
        });

        eCell.appendChild(eSelect);
        return eCell;
    }

    function setRowDatas(context, data) {
        var studentList = context.scope.$ctrl.classStudentList;
        studentList.forEach(function (dataItem, index) {

            if (dataItem.absence_type_code == 0
                || dataItem.absence_type_code == null
                || dataItem.absence_type_code == "PRESENT") {
                dataItem.rowHeight = 60;
            } else {
                dataItem.rowHeight = 120;
            }
        });
        context.scope.$ctrl.gridOptions.api.setRowData(studentList);
    }

    function getEditCommentElement(data, context, api) {
        var dataKey = 'comment';
        var scope = context.scope;
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("placeholder", "Comments");
        eTextarea.setAttribute("id", dataKey);

        if (hasError(data, dataKey)) {
            eTextarea.setAttribute("class", "error");
        }

        eTextarea.value = data[dataKey];
        eTextarea.addEventListener('blur', function () {
            var oldValue = data[dataKey];
            data[dataKey] = eTextarea.value;

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            // console.log(data);
            saveAbsences(data, context)
                .then(
                    function (response) {
                        // console.log(response);

                        clearError(data, dataKey);
                        if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
                            data.save_error[dataKey] = true;
                            data[dataKey] = oldValue;
                            AlertSvc.error(scope, 'There was an error when saving the record');
                        } else {
                            data.save_error[dataKey] = false;
                            AlertSvc.info(scope, 'Attendances will be automatically saved.');
                        }
                    },
                    function (error) {
                        clearError(data, dataKey);
                        console.error(error);
                        data.save_error[dataKey] = true;
                        AlertSvc.error(scope, 'There was an error when saving the record');
                        data[dataKey] = oldValue;
                    }
                )
                .finally(function () {
                    var refreshParams = {
                        columns: [
                            'student_absence_reason_id',
                            'absence_type_id'
                        ],
                        force: true
                    };
                    api.refreshCells(refreshParams);
                    UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
                });
        });

        return eTextarea;
    }

    function getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api) {
        var dataKey = 'student_absence_reason_id';
        var scope = context.scope;
        var eSelectWrapper = document.createElement('div');
        eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eSelectWrapper.setAttribute("id", dataKey);

        var eSelect = document.createElement("select");
        if (hasError(data, dataKey)) {
            eSelect.setAttribute("class", "error");
        }

        if (data[dataKey] == null) {
            data[dataKey] = studentAbsenceReasonList[0].id;
        }

        angular.forEach(studentAbsenceReasonList, function (obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });

        eSelect.value = data[dataKey];
        eSelect.addEventListener('change', function () {
            var oldValue = data[dataKey];
            data[dataKey] = eSelect.value;

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            saveAbsences(data, context).then(
                function (response) {
                    clearError(data, dataKey);
                    if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
                        data.save_error[dataKey] = true;
                        data[dataKey] = oldValue;
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    } else {
                        data.save_error[dataKey] = false;
                        AlertSvc.info(scope, 'Attendances will be automatically saved.');
                    }
                },
                function (error) {
                    console.error(error);
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    AlertSvc.error(scope, 'There was an error when saving the record');
                    data[dataKey] = oldValue;
                }
            ).finally(function () {
                var refreshParams = {
                    columns: [
                        'student_absence_reason_id',
                        'absence_type_id'
                    ],
                    force: true
                };
                api.refreshCells(refreshParams);
                UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            });
        })

        eSelectWrapper.appendChild(eSelect);
        return eSelectWrapper;
    }

    function getViewAttendanceElement(data, absenceTypeList, isMarked, isSchoolClosed, noScheduledClicked) {

        if (angular.isDefined(data.absence_type_id)) {
            var html = '';
            if (isMarked) {

                var absence_type_code = (data.absence_type_code === null) ? "" : data.absence_type_code;
                var absence_type_name = (data.absence_type_name === null) ? "" : data.absence_type_name;
                if (noScheduledClicked) {
                    absence_type_code = 'NoScheduledClicked';
                    absence_type_name = 'No Lessons';
                }
                // console.log('absence_type_code');
                // console.log(absence_type_code);
                // console.log('absence_type_name');
                // console.log(absence_type_name);
                switch (absence_type_code) {
                    case attendanceType.PRESENT.code:
                        html = '<div style="color: ' + attendanceType.PRESENT.color + ';">' +
                            '<i class="' + attendanceType.PRESENT.icon + '">' +
                            '</i> <span> ' + absence_type_name + ' </span></div>';
                        break;
                    case attendanceType.LATE.code:
                        html = '<div style="color: ' + attendanceType.LATE.color + ';">' +
                            '<i class="' + attendanceType.LATE.icon + '">' +
                            '</i> <span> ' + absence_type_name + ' </span></div>';
                        break;
                    case attendanceType.UNEXCUSED.code:
                        html = '<div style="color: ' + attendanceType.UNEXCUSED.color + '">' +
                            '<i class="' + attendanceType.UNEXCUSED.icon + '">' +
                            '</i> <span> ' + absence_type_name + ' </span></div>';
                        break;
                    case attendanceType.EXCUSED.code:
                        html = '<div style="color: ' + attendanceType.EXCUSED.color + '">' +
                            '<i class="' + attendanceType.EXCUSED.icon + '">' +
                            '</i> <span> ' + absence_type_name + ' </span></div>';
                        break;
                    case attendanceType.NoScheduledClicked.code:
                        html = '<div style="color: ' + attendanceType.NoScheduledClicked.color + '">' +
                            '<span> ' + absence_type_name + ' </span></div>';
                        break;
                    default:
                        break;
                }
                return html;
            }
            if (!isMarked) {
                if (isSchoolClosed) {
                    // console.log('in')
                    html = '<i style="color: #999999;" class="fa fa-minus"></i>';
                }
                if (!isSchoolClosed) {
                    // console.log(data)
                    if (data.no_scheduled_class == 1) {
                        html = '<i style="color: #000000;"><span>No Lessons</span></i>';
                    } else {
                        html = '<i class="' + icons.PRESENT + '"></i>';
                    }
                }
            }
            return html;
        }
    }

    function getViewAbsenceReasonElement(data, studentAbsenceReasonList) {
        // console.log(data);
        var absenceReasonId = data.student_absence_reason_id;
        var absenceReasonObj = studentAbsenceReasonList.find(obj => obj.id == absenceReasonId);
        var html = '';
        // console.log(absenceReasonId);
        if (absenceReasonId === null) {
            html = '<i class="' + icons.PRESENT + '"></i>';
        } else {
            var reasonName = absenceReasonObj.name;
            html = '<div class="absence-reason"><i class="' + icons.REASON + '"></i><span>' + reasonName + '</span></div>';
        }

        return html;
    }

    function getViewCommentsElement(data) {
        var comment = data.comment;
        var html = '';
        if (comment != null) {
            html = '<div class="absences-comment"><i class="' + icons.COMMENT + '"></i><span>' + comment + '</span></div>';
        }
        return html;
    }

    function getViewAllDayAttendanceElement(code) {
        var html = '';
        switch (code) {
            case attendanceType.NoScheduledClicked.code:
                html = '<i style="color: ' + attendanceType.NoScheduledClicked.color + ';" class="' + attendanceType.NoScheduledClicked.icon + '"></i>';
                break;
            case attendanceType.NOTMARKED.code:
                html = '<i class="' + attendanceType.NOTMARKED.icon + '"></i>';
                break;
            case attendanceType.PRESENT.code:
                html = '<i style="color: ' + attendanceType.PRESENT.color + ';" class="' + attendanceType.PRESENT.icon + '"></i>';
                break;
            case attendanceType.LATE.code:
                html = '<i style="color: ' + attendanceType.LATE.color + ';" class="' + attendanceType.LATE.icon + '"></i>';
                break;
            case attendanceType.UNEXCUSED.code:
                html = '<i style="color: ' + attendanceType.UNEXCUSED.color + ';" class="' + attendanceType.UNEXCUSED.icon + '"></i>';
                break;
            case attendanceType.EXCUSED.code:
                html = '<i style="color: ' + attendanceType.EXCUSED.color + ';" class="' + attendanceType.EXCUSED.icon + '"></i>';
                break;
            default:
                break;
        }
        return html;
    }

    function isMarkableSubjectAttendance(institutionId, academicPeriodId, selectedClass, selectedDay) {
        var success = function (response, deferred) {
            if (angular.isDefined(response.data.data[0].code)) {
                var isMarkableSubjectAttendance = false;
                if (response.data.data[0].code == 'SUBJECT') {
                    isMarkableSubjectAttendance = true;
                } else {
                    isMarkableSubjectAttendance = false;
                }
                deferred.resolve(isMarkableSubjectAttendance);
            } else {
                deferred.reject('There was an error when retrieving the isMarkableSubjectAttendance record');
            }
        };

        return StudentAttendanceTypes
            .find('attendanceTypeCode', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId,
                institution_class_id: selectedClass,
                day_id: selectedDay
            })
            .ajax({success: success, defer: true});

        return [];
    }
};