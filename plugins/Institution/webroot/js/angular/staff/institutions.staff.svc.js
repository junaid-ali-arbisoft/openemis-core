angular
    .module('institutions.staff.svc', ['kd.orm.svc'])
    .service('InstitutionsStaffSvc', InstitutionsStaffSvc);

InstitutionsStaffSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc', '$window'];

function InstitutionsStaffSvc($http, $q, $filter, KdOrmSvc, $window) {

    var externalSource = null;
    var externalToken = null;
    var institutionId = null;
    var externalDataSourceMapping = {};

    var service = {
        init: init,
        initExternal: initExternal,
        getStaffRecords: getStaffRecords,
        getExternalStaffRecords: getExternalStaffRecords,
        setInstitutionId: setInstitutionId,
        getInstitutionId: getInstitutionId,
        getAcademicPeriods: getAcademicPeriods,
        getColumnDefs: getColumnDefs,
        getStaffData: getStaffData,
        postAssignedStaff: postAssignedStaff,
        postAssignedStaffShift: postAssignedStaffShift,
        getExternalSourceUrl: getExternalSourceUrl,
        addUser: addUser,
        makeDate: makeDate,
        formatDate: formatDate,
        formatDateForSaving: formatDateForSaving,
        getUserRecord: getUserRecord,
        getGenderRecord: getGenderRecord,
        getInternalIdentityTypes: getInternalIdentityTypes,
        addIdentityType: addIdentityType,
        setExternalSourceUrl: setExternalSourceUrl,
        resetExternalVariable: resetExternalVariable,
        getGenders: getGenders,
        getUniqueOpenEmisId: getUniqueOpenEmisId,
        getAddNewStaffConfig: getAddNewStaffConfig,
        getStaffTransfersByTypeConfig: getStaffTransfersByTypeConfig,
        getStaffTransfersByProviderConfig: getStaffTransfersByProviderConfig,
        getUserContactTypes: getUserContactTypes,
        getIdentityTypes: getIdentityTypes,
        getNationalities: getNationalities,
        getIdentityTypesExternalSave: getIdentityTypesExternalSave,
        getNationalitiesExternalSave: getNationalitiesExternalSave,
        getSpecialNeedTypes: getSpecialNeedTypes,
        getExternalSourceAttributes: getExternalSourceAttributes,
        getNationalityRecord: getNationalityRecord,
        getIdentityTypeRecord: getIdentityTypeRecord,
        importMappingObj: importMappingObj,
        addUserIdentity: addUserIdentity,
        addUserNationality: addUserNationality,
        getExternalSourceMapping: getExternalSourceMapping,
        getInstitution: getInstitution,
        addStaffTransferRequest: addStaffTransferRequest,
        generatePassword: generatePassword,
        translate: translate,
        getPositionTypes: getPositionTypes,
        getFtes: getFtes,
        getPositions: getPositions,
        getStaffTypes: getStaffTypes,
        getStaffPosititonGrades: getStaffPosititonGrades,//POCOR-5069
        getShifts: getShifts,
        getInternalSearchData: getInternalSearchData,
        getExternalSearchData: getExternalSearchData,
        saveStaffDetails: saveStaffDetails,
        getAddressAreaId: getAddressAreaId,
        getBirthplaceAreaId: getBirthplaceAreaId,
        getAddressArea: getAddressArea,
        getBirthplaceArea: getBirthplaceArea,
        getStaffCustomFields: getStaffCustomFields,
        checkUserAlreadyExistByIdentity: checkUserAlreadyExistByIdentity,
        checkConfigForExternalSearch: checkConfigForExternalSearch,
        getCspdData: getCspdData,
    };

    var models = {
        Genders: 'User.Genders',
        StaffTransferIn: 'Institution.StaffTransferIn',
        StaffUser: 'Institution.StaffUser',
        StaffRecord: 'Institution.Staff',
        StaffShifts: 'Institution.InstitutionStaffShifts',
        StaffTypes: 'Staff.StaffTypes',
        StaffPositionGrades: 'Institution.StaffPositionGrades',//POCOR-5069
        InstitutionShifts: 'Institution.InstitutionShifts',
        InstitutionGrades: 'Institution.InstitutionGrades',
        Institutions: 'Institution.Institutions',
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionClasses: 'Institution.InstitutionClasses',
        IdentityTypes: 'FieldOption.IdentityTypes',
        ExternalDataSourceAttributes: 'Configuration.ExternalDataSourceAttributes',
        Identities: 'User.Identities',
        UserNationalities: 'User.UserNationalities',
        ConfigItems: 'Configuration.ConfigItems',
        Nationalities: 'FieldOption.Nationalities',
        ContactTypes: 'User.ContactTypes',
        SpecialNeedTypes: 'SpecialNeeds.SpecialNeedsTypes'
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Staff');
        KdOrmSvc.init(models);
    };

    function initExternal(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('ExternalAPI');
        KdOrmSvc.init(externalModels);
    };

    function saveStaffDetails(param) {
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/saveStaffData';
        $http.post(url, param)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getAddressAreaId () {
        selectedAddressAreaId = $window.localStorage.getItem('address_area_id');
        return JSON.parse(selectedAddressAreaId);
    }

    function getAddressArea () {
        selectedAddressArea = $window.localStorage.getItem('address_area');
        return JSON.parse(selectedAddressArea);
    }

    function getBirthplaceAreaId () {
        selectedBirthplcaeAreaId = $window.localStorage.getItem('birthplace_area_id');
        return JSON.parse(selectedBirthplcaeAreaId);
    }

    function getBirthplaceArea () {
        selectedBirthplcaeArea = $window.localStorage.getItem('birthplace_area');
        return JSON.parse(selectedBirthplcaeArea);
    }

    function getStaffCustomFields(staffId){
        var params = {
            staff_id: staffId,
        };
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/staffCustomFields';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function translate(data) {
        KdOrmSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function resetExternalVariable()
    {
        externalSource = null;
        externalToken = null;
    }

    function getExternalSourceMapping()
    {
        return this.externalSourceMapping;
    }

    function getExternalSourceAttributes() {
        return ExternalDataSourceAttributes
            .find('attributes')
            .ajax({defer: true});
    }

    function getExternalSourceUrl()
    {
        return ExternalDataSourceAttributes
            .find('Uri', {record_type: 'record_uri'})
            .ajax({defer: true});
    };

    function setExternalSourceUrl(url)
    {
        var externalSource = url;
    };

    function getInternalSearchData(param) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryInternalSearch';
        $http.post(url, {params: param})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getExternalSearchData(param) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryExternalSearch';
        $http.post(url, {params: param})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getPositionTypes(){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getPositionType';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getFtes(){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getFTE';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }
    //POCOR-5069 starts
    function getPositions(params){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getPositions';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }//POCOR-5069 ends

    function getStaffPosititonGrades(){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getStaffPosititonGrades';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getStaffTypes(){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getStaffType';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getShifts(){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getShifts';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getExternalStaffRecords(options) {
        var deferred = $q.defer();
        var vm = this;

        vm.getExternalSourceAttributes()
        .then(function(attributes) {
            var attr = attributes.data;
            delete attr.private_key;
            delete attr.public_key;
            vm.externalSourceMapping = attr;
            var url = angular.baseUrl + '/Configurations/getExternalUsers?page={page}&limit={limit}&first_name={first_name}&last_name={last_name}&identity_number={identity_number}&date_of_birth={date_of_birth}';
            var pageParams = {
                limit: options['endRow'] - options['startRow'],
                page: options['endRow'] / (options['endRow'] - options['startRow']),
            };

            var params = {};
            params['super_admin'] = 0;

            // Get url from user input
            var replaceURL = url;
            var replacement = {
                "{page}": pageParams.page,
                "{limit}": pageParams.limit,
                "{first_name}": '',
                "{last_name}": '',
                "{identity_number}": '',
                "{date_of_birth}": ''
            }

            var conditionsCount = 0;

            if (options.hasOwnProperty('conditions')) {
                for (var key in options['conditions']) {
                    if (typeof options['conditions'][key] == 'string') {
                        options['conditions'][key] = options['conditions'][key].trim();
                        if (key != 'date_of_birth') {
                            params[key] = options['conditions'][key];
                        } else {
                            params[key] = vm.formatDateForSaving(options['conditions'][key]);
                        }
                        var replaceKey = '{'+key+'}';
                        replacement[replaceKey] = params[key];
                        conditionsCount++;
                    }
                }
            }
            var url = replaceURL.replace(/{\w+}/g, function(all) {
                return all in replacement ? replacement[all] : all;
            });

            var opt = {
                method: 'GET'
            }
            externalSource = true;
            return KdOrmSvc.customAjax(url, opt);
        }, function(error) {
            deferred.reject(error);
        })
        .then(function(response) {
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });

        return deferred.promise;
    };

    function getStaffRecords(options) {
        var vm = this;
        var institutionId = vm.getInstitutionId();
        var params = {
            limit: options['endRow'] - options['startRow'],
            page: options['endRow'] / (options['endRow'] - options['startRow']),
        }



        var conditionsCount = 0;

        if (options.hasOwnProperty('conditions')) {
            for (var key in options['conditions']) {
                if (typeof options['conditions'][key] == 'string') {
                    options['conditions'][key] = options['conditions'][key].trim();

                    if (options['conditions'][key] !== '') {
                        if (key == 'date_of_birth') {
                            options['conditions'][key] = vm.formatDate(options['conditions'][key]);
                        }
                        params[key] = options['conditions'][key];
                        conditionsCount++;
                    }
                }
            }
        }

        var success = function(response, deferred) {
            var studentData = response.data;
            studentData['conditionsCount'] = conditionsCount;
            if (angular.isObject(studentData)) {
                deferred.resolve(studentData);
            } else {
                deferred.reject('Error getting staff records');
            }
        };

        params['institution_id'] = institutionId;
        StaffUser.reset();
        StaffUser.find('Staff', params);
        StaffUser.contain(['Genders', 'MainIdentityTypes', 'MainNationalities']);

        return StaffUser.ajax({defer: true, success: success});
    };

    function getStaffData(id, startDate, endDate) {
        var vm = this;
        var institutionId = vm.getInstitutionId();
        var success = function(response, deferred) {
            var studentData = response.data.data;
            if (angular.isObject(studentData) && studentData.length > 0) {
                deferred.resolve(studentData[0]);
            } else {
                deferred.reject('Staff not found');
            }
        };

        StaffUser.select();
        var settings = {success: success, defer: true};
        return StaffUser
            .contain(['Genders', 'Identities.IdentityTypes'])
            .find('assignedInstitutionStaff', {'institution_id': institutionId, 'start_date': startDate, 'end_date': endDate})
            .where({
                id: id
            })
            .ajax(settings);
    };

    function addIdentityType(identityType)
    {
        var deferred = $q.defer();
        delete(identityType['id']);
        delete(identityType['created']);
        delete(identityType['modified']);
        delete(identityType['modified_user_id']);
        delete(identityType['created_user_id']);
        IdentityTypes.save(identityType)
        .then(function(response) {
            deferred.resolve(response.data.data.id);
        }, function(error) {
            deferred.reject(error);
            console.log(error);
        });
        return deferred.promise;
    }

    function getInternalIdentityTypes()
    {
        return IdentityTypes.select().ajax({defer:true});
    };

    function addUser(userRecord)
    {
        var deferred = $q.defer();
        var vm = this;
        if (externalSource == null) {
            userRecord['start_date'] = vm.formatDateForSaving(userRecord['start_date'])
            delete userRecord['gender'];
            if (userRecord['nationality_id'] != '' && userRecord['nationality_id'] != undefined) {
                userRecord['nationalities'] = [{
                    'nationality_id': userRecord['nationality_id']
                }];
            }
            if (userRecord['identity_type_id'] != '' && userRecord['identity_type_id'] != undefined && userRecord['identity_number'] != undefined && userRecord['identity_number'] != '') {
                userRecord['identities'] = [{
                    'identity_type_id': userRecord['identity_type_id'],
                    'number': userRecord['identity_number']
                }];
            }
            StaffUser.reset();
            StaffUser.save(userRecord)
            .then(function(studentRecord) {
                deferred.resolve([studentRecord.data, {}]);
            }, function(error) {
                deferred.reject(error);
                console.log(error);
            });
        } else {
            var newUserRecord = {};
            var nationality = '';
            var identityType = '';
            var genderName = '';
            var identityNumber = '';
            vm.getExternalSourceAttributes()
            .then(function(attributes) {
                var attr = attributes.data;

                // Mandatory information from the form
                newUserRecord['academic_period_id'] = userRecord['academic_period_id'];
                newUserRecord['staff_shifts_id'] = userRecord['staff_shifts_id'];
                newUserRecord['education_grade_id'] = userRecord['education_grade_id'];
                newUserRecord['start_date'] = vm.formatDateForSaving(userRecord['start_date']);
                newUserRecord['first_name'] = userRecord[attr['first_name_mapping']];
                newUserRecord['last_name'] = userRecord[attr['last_name_mapping']];
                newUserRecord['date_of_birth'] = userRecord[attr['date_of_birth_mapping']];
                newUserRecord['external_reference'] = userRecord[attr['external_reference_mapping']];
                newUserRecord['institution_position_id'] = userRecord['institution_position_id'];
                newUserRecord['position_type'] = userRecord['position_type'];
                newUserRecord['FTE'] = userRecord['FTE'];
                newUserRecord['staff_type_id'] = userRecord['staff_type_id'];
                genderName = userRecord[attr['gender_mapping']];
                // By auto generated
                newUserRecord['openemis_no'] = userRecord['openemis_no'];

                // Optional fields
                if (typeof userRecord[attr['middle_name_mapping']] != 'undefined') {
                    newUserRecord['middle_name'] = userRecord[attr['middle_name_mapping']];
                }
                if (typeof userRecord[attr['third_name_mapping']] != 'undefined') {
                    newUserRecord['third_name'] = userRecord[attr['third_name_mapping']];
                }
                if (typeof userRecord[attr['identity_number_mapping']] != 'undefined') {
                    newUserRecord['identity_number'] = userRecord[attr['identity_number_mapping']];
                }
                if (typeof userRecord[attr['nationality_mapping']] != 'undefined') {
                    nationality = userRecord[attr['nationality_mapping']];
                }
                if (typeof userRecord[attr['identity_type_mapping']] != 'undefined') {
                    identityType = userRecord[attr['identity_type_mapping']];
                }
                if (typeof userRecord[attr['address_mapping']] != 'undefined') {
                    newUserRecord['address'] = userRecord[attr['address_mapping']];
                }
                if (typeof userRecord[attr['postal_mapping']] != 'undefined') {
                    newUserRecord['postal_code'] = userRecord[attr['postal_mapping']];
                }

                vm.getUserRecord(newUserRecord['external_reference'])
                .then(function(response) {
                    if (response.data.length > 0) {
                        userData = response.data[0];
                        modifiedUser = userData;
                        delete modifiedUser['openemis_no'];
                        delete modifiedUser['created'];
                        delete modifiedUser['modified'];
                        modifiedUser['is_staff'] = 1;
                        modifiedUser['start_date'] = userRecord['start_date'];
                        modifiedUser['identity_number'] = userRecord['identity_number'];
                        modifiedUser['identity_type_id'] = userRecord['identity_type_id'];

                        modifiedUser['main_nationality_id'] = userRecord['main_nationality.id'];
                        modifiedUser['main_nationality_name'] = userRecord['main_nationality.name'];
                        modifiedUser['security_user_id'] = userRecord['id'];

                        modifiedUser['identity_type_name'] = userRecord['identity_type_name'];
                        modifiedUser['identity_type_order'] = userRecord['main_identity_type.order'];
                        modifiedUser['identity_type_visible'] = userRecord['main_identity_type.visible'];
                        modifiedUser['identity_type_editable'] = userRecord['main_identity_type.editable'];
                        modifiedUser['identity_type_default'] = userRecord['main_identity_type.default'];
                        modifiedUser['identity_type_created_user_id'] = userRecord['main_identity_type.created_user_id'];
                        modifiedUser['identity_type_created'] = vm.formatDateForSaving(userRecord['main_identity_type.created']);

                        if (modifiedUser['identity_type_id'] != null && modifiedUser['identity_number'] != null && modifiedUser['identity_number'] != '') {
                            userId = modifiedUser['id'];
                            identityTypeId = modifiedUser['identity_type_id'];
                            identityNumber = modifiedUser['identity_number'];
                            mainNationalityId = modifiedUser['main_nationality_id'];
                            identityTypeName = modifiedUser['identity_type_name'];
                            nationalityTypeName = modifiedUser['main_nationality_name'];
                            var identityTypeData = vm.getIdentityTypesExternalSave(identityTypeName);
                            var nationalityTypeData = vm.getNationalitiesExternalSave(nationalityTypeName);
                            setTimeout(()=>{
                                var identityTypeId = identityTypeData.$$state['value'];
                                vm.addUserIdentityNew(userId, identityTypeId, identityNumber, mainNationalityId)
                                .then(function(promiseArr) {
                                    }, function(error) {
                                });
                            },100);

                            setTimeout(()=>{
                                var nationalityTypeId = nationalityTypeData.$$state['value'];
                                vm.addUserNationality(userId, nationalityTypeId)
                                .then(function(promiseArr) {
                                    }, function(error) {
                                });
                            },100);
                        }

                        setTimeout(()=>{
                            StaffUser.edit(modifiedUser)
                            .then(function(response) {
                                deferred.resolve([response.data, userData]);
                            }, function(error) {
                                deferred.reject(error);
                                console.log(error);
                            });
                    },100);
                    } else {
                        newUserRecord['date_of_birth'] = vm.formatDateForSaving(newUserRecord['date_of_birth']);
                        newUserRecord['is_staff'] = 1;

                        vm.importMappingObj(genderName, nationality, identityType)
                        .then(function(promiseArr) {
                            delete newUserRecord['nationality_id'];
                            delete newUserRecord['identity_type_id'];
                            newUserRecord['gender_id'] = promiseArr[0];
                            newUserRecord['nationality_id'] = promiseArr[1];
                            newUserRecord['identity_type_id'] = promiseArr[2];
                            newUserRecord['username'] = newUserRecord['openemis_no'];
                            var identityTypeId = promiseArr[2];
                            identityNumber = newUserRecord['identity_number'];
                            StaffUser.reset();
                            StaffUser.save(newUserRecord)
                            .then(function(studentRecord) {
                                var userEntity = studentRecord.data.data;
                                var userEntityError = studentRecord.data.error;
                                if (userEntityError.length > 0) {
                                    deferred.resolve(studentRecord.data);
                                } else {
                                    var userId = userEntity.id;
                                    var promises = [];
                                    // Import identity
                                    // if (newUserRecord['identity_type_id'] != null && newUserRecord['identity_number'] != null && newUserRecord['identity_number'] != '') {
                                    //     vm.addUserIdentity(userId, newUserRecord['identity_type_id'], newUserRecord['identity_number']);
                                    // }
                                    if (identityTypeId != null && identityNumber != null && identityNumber != '') {
                                        // vm.addUserIdentity(userId, identityTypeId, identityNumber);
                                        var nationalityId = userEntity.nationality_id;
                                        vm.addUserIdentityNew(userId, identityTypeId, identityNumber, nationalityId)
                                            .then(function(promiseArr) {
                                                }, function(error) {
                                            });
                                    }
                                    // Import nationality
                                    if (userEntity.nationality_id != null) {
                                        var nationalityId = userEntity.nationality_id;
                                        vm.addUserNationality(userId, nationalityId);
                                    }
                                    deferred.resolve([studentRecord.data, {}]);
                                }
                            }, function(error) {
                                deferred.reject(error);
                                console.log(error);
                            });
                        }, function(error) {
                            deferred.reject(error);
                            console.log(error);
                        });

                    }
                }, function(error) {
                    deferred.reject(error);
                    console.log(error);
                });
            }, function(error) {
                deferred.reject(error);
            });
        }
        return deferred.promise;
    };

    function addStaffTransferRequest(data)
    {
        return StaffTransferIn.save(data);
    };

    function getUserRecord(externalRef)
    {
        var vm = this;
        return StaffUser
            .select()
            .where({
                'external_reference': externalRef
            })
            .ajax({defer: true});
    };

    function addUserNationality(userId, nationalityId)
    {
        var data = {};
        data['security_user_id'] = userId;
        data['nationality_id'] = nationalityId;
        return UserNationalities
            .save(data);
    }

    function addUserIdentity(userId, identityTypeId, identityNumber)
    {
        var data = {};
        data['security_user_id'] = userId;
        data['identity_type_id'] = identityTypeId;
        data['number'] = identityNumber;
        return Identities
            .save(data);
    }

    function addUserIdentityNew(userId, identityTypeId, identityNumber, nationality_id)
    {
        var data = {};
        data['security_user_id'] = userId;
        data['identity_type_id'] = identityTypeId;
        data['number'] = identityNumber;
        data['nationality_id'] = nationality_id;
        return Identities
            .save(data);
    }

    function importMappingObj(genderName, nationalityName, identityTypeName)
    {
        var promises = [];
        promises.push(this.getGenderRecord(genderName));
        promises.push(this.getNationalityRecord(nationalityName));
        promises.push(this.getIdentityTypeRecord(identityTypeName));
        return $q.all(promises);

    }

    function getIdentityTypeRecord(name)
    {
        var deferred = $q.defer();

        if (typeof name == 'undefined' || name == '') {
            deferred.resolve(null);
        } else {
            IdentityTypes
                .select()
                .where({
                    'name': name
                })
                .ajax({defer: true})
                .then(function(response) {
                    if (response.data.length > 0) {
                        deferred.resolve(response.data[0].id);
                    } else {
                        var data = {};
                        data['name'] = name;
                        data['visible'] = 1;
                        data['editable'] = 1;
                        IdentityTypes.reset();
                        IdentityTypes.save(data)
                        .then(function(res) {
                            deferred.resolve(res.data.data.id);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    }
                }, function(error) {
                    deferred.reject(error);
                });
        }

        return deferred.promise;
    }

    function getNationalityRecord(name)
    {
        var deferred = $q.defer();
        if (typeof name == 'undefined' || name == '') {
            deferred.resolve(null);
        } else {
            Nationalities
                .select()
                .where({
                    'name': name
                })
                .ajax({defer: true})
                .then(function(response) {
                    if (response.data.length > 0) {
                        deferred.resolve(response.data[0].id);
                    } else {
                        var data = {};
                        data['name'] = name;
                        data['visible'] = 1;
                        data['editable'] = 1;
                        Nationalities.reset();
                        Nationalities.save(data)
                        .then(function(res) {
                            deferred.resolve(res.data.data.id);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    }
                }, function(error) {
                    deferred.reject(error);
                });
        }
        return deferred.promise;
    };

    function getGenderRecord(name)
    {
        var deferred = $q.defer();
        Genders
            .select()
            .where({
                'name': name
            })
            .ajax({defer: true})
            .then(function(response) {
                if (response.data.length > 0) {
                    deferred.resolve(response.data[0].id);
                } else {
                    var data = {};
                    data['name'] = name;
                    data['code'] = name;
                    Genders.reset();
                    Genders.save(data)
                    .then(function(res) {
                        deferred.resolve(res.data.data.id);
                    }, function(error) {
                        deferred.reject(error);
                    });
                }
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
    };

    function getGenders()
    {
        var success = function(response, deferred) {
            var genderRecords = response.data.data;
            deferred.resolve(genderRecords);
        };
        return Genders
            .select()
            .ajax({success:success, defer: true});
    };

    function postAssignedStaff(data) {
        console.log(data);
        var institutionId = this.getInstitutionId();
        data['institution_id'] = institutionId;
        data['staff_status_id'] = 1;
        data['start_date'] = this.formatDateForSaving(data['start_date']);
        data['end_date'] = this.formatDateForSaving(data['end_date']);
        return StaffRecord.save(data);
    };
    
    function postAssignedStaffShift(shiftData) {
       
        angular.forEach(shiftData.shift_id, function(value, key) {
            var shift_id = {'staff_id':shiftData.staff_id,'shift_id':value}
            StaffShifts.save(shift_id);
        });
       return false;
    };
    
    function setInstitutionId(id) {
        this.institutionId = id;
    }

    function getInstitutionId() {
        return this.institutionId;
    };

    function makeDate(datetime) {
        // Only get the date part, we do not require the time portion
        if(datetime){
            if (datetime.indexOf('T') > -1) {
                datetime = datetime.split('T')[0];
            }
        }
        // Logic to handle external datasource giving the datetime in this format 2005-07-08T11:22:33+0800
        if (datetime !== undefined && datetime != '' && datetime.indexOf('-') > -1) {
            var date = datetime.split('-');
            if (date[0].length == 4) {
                // To fix timezone offset issue between server and client machine
                var offset = new Date(Date.parse(datetime)).getTimezoneOffset() * 60000;
                date = new Date(Date.parse(datetime) + offset);
            } else {
                date = new Date(date[2], date[1]-1, date[0]);
            }
            return date;
        } else {
            return null;
        }
    }

    function formatDate(datetime) {
        var date = this.makeDate(datetime);
        if (date != null) {
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = date.getDate().toString();

            return (dd[1]?dd:"0"+dd[0]) + '-' + (mm[1]?mm:"0"+mm[0])  + '-' +   yyyy;
        } else {
            return '';
        }
    };

    function formatDateForSaving(datetime) {
        var date = this.makeDate(datetime);
        if (date != null) {
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = date.getDate().toString();

            return yyyy + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + (dd[1]?dd:"0"+dd[0]);
        } else {
            return '';
        }
    };

    function getAcademicPeriods() {
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('You need to configure Assessment Periods first');
            }
        };
        return AcademicPeriods
            .select(['id', 'name', 'current', 'start_date', 'end_date'])
            .find('SchoolAcademicPeriod')
            .ajax({success: success, defer: true});
    };

    //POCOR-6460[START]
    function getIdentityTypesExternalSave(identityTypesName) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data[0]['id']);
        }
        return IdentityTypes
            .select(['id'])
            .where({name: identityTypesName})
            .ajax({success: success, defer: true});
    };

    function getNationalitiesExternalSave(nationalityTypesName) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data[0]['id']);
        }
        return Nationalities
            .select(['id'])
            .where({name: nationalityTypesName})
            .ajax({success: success, defer: true});
    };
    //POCOR-6460[END]

    function getColumnDefs() {
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams
        });
    };

    function getPositionList(institutionId, fte, startDate, endDate, openemisNo, staffUserPriId = 0)
    {
        var deferred = $q.defer();

        if (endDate == '') {
            endDate = null;
        }
        // only 4 parameters is passed to getInstitutionPositions function. Parameters openemisNo is added but not in use.
        // var url = angular.baseUrl + '/Institution/Institutions/getInstitutionPositions/' + institutionId + '/' + fte + '/' + startDate + '/' + endDate + '/' + openemisNo;
        var url = angular.baseUrl + '/Institution/Institutions/getInstitutionPositions/' + institutionId + '/' + fte + '/' + startDate + '/' + endDate + '/' + staffUserPriId;

        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }
    
    function getStaffShifts(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionShifts.find('shiftOptions', 
        {institution_id: institutionId, 
            academic_period_id: academicPeriodId})
                .ajax({success: success, defer: true});
        }
    
     function getInstitution(institutionId) {
        return Institutions
        .select()
        .where({'id': institutionId})
        .ajax({defer: true});
    }

    function getUniqueOpenEmisId() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getUniqueOpenemisId/Staff';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.openemis_no);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function generatePassword() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getAutoGeneratedPassword';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.password);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getAddNewStaffConfig() {
        return ConfigItems
            .select()
            .where({type: 'Add New Staff'})
            .ajax({defer: true});
    }

    function getStaffTransfersByTypeConfig() {
        return ConfigItems
            .select()
            .where({type: 'Staff Transfers',
                    code: 'restrict_staff_transfer_by_type'})
            .ajax({defer: true});
    }

    function getStaffTransfersByProviderConfig() {
        return ConfigItems
            .select()
            .where({type: 'Staff Transfers',
                    code: 'restrict_staff_transfer_by_provider'})
            .ajax({defer: true});
    }

    function getUserContactTypes() {
        return ContactTypes
            .select()
            .ajax({defer: true});
    }

    function getIdentityTypes() {
        return IdentityTypes
            .select()
            .ajax({defer: true});
    }

    function getNationalities() {
        return Nationalities
            .select()
            .contain(['IdentityTypes'])
            .ajax({defer: true});
    }
    function getSpecialNeedTypes() {
        return SpecialNeedTypes
            .select()
            .ajax({defer: true});
    }

    /**
     * Parameters are - identity_type_id, identity_number & nationality_id pass as a object
     * If staff exist then user_exist will be 1 otherwise 0 & show the message as warning
     * @required {identity_type_id} identity_type_id
     * @required {identity_number} identity_number
     * @required {user_id} security_user.id
     * @returns {[{"user_exist":1,"status_code":2,"message":"User already exist with this nationality, identity type & identity type. Kindly select user from below list."}]}
     */
    function checkUserAlreadyExistByIdentity(params)
    {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/checkUserAlreadyExistByIdentity';
        $http.post(url, { params: params })
            .then(function (response)
            {
                deferred.resolve(response);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }

    /**
        * Based on showExternalSearch property need to hide external search step in form wizard
        * @returns {Case 1: for None  [{"value":"None","showExternalSearch ":false}]}
       *  @returns {Case 2: for rest values [{"value":"OpenEMIS Identity","showExternalSearch ":true}]}
        */
    function checkConfigForExternalSearch()
    {
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/checkConfigurationForExternalSearch';
        $http.get(url)
            .then(function (response)
            {
                deferred.resolve(response.data[0]);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }


    /**
     * @name  Url: /Institutions/getCspdData
     * @description  Request Params: identity_number
     * @param {*} param  {identity_number}
     * @returns { "status_code":200,"message":"Get user details successfully.","data":{"third_name":"\u062d\u0633\u0646","nationality_name":"\u0627\u0631\u062f\u0646\u064a","gender_id":1,"gender_name":"Male","date_of_birth":"1979-02-15","address":"\u0627\u0644\u0630\u0631\u0627\u0639\/\u0642\u0635\u0628\u0629 \u0639\u0645\u0627\u0646","identity_type_id":160,"identity_type_name":"Birth Certificate","middle_name":"\u0635\u0627\u0644\u062d","postal_code":"0011101100601","first_name":"\u062d\u0633\u0646","identity_number":"9791048083","last_name":"\u062e\u0644\u064a\u0644","mother_national_no":"9552016857","father_national_no":"9551018573"}}
     */

    function getCspdData(params){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getCspdData';
        $http.post(url, { params: params })
            .then(function (response)
            {
                deferred.resolve(response);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }
};
