UPDATE `security_functions` SET `_view` = NULL WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Student';
UPDATE `security_functions` SET `_view` = 'view|InstitutionSiteStudent|InstitutionSiteStudent.index' WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

UPDATE `security_functions` SET `_view` = NULL WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Staff';
UPDATE `security_functions` SET `_view` = 'view|InstitutionSiteStaff|InstitutionSiteStaff.index' WHERE `module` LIKE 'Staff' AND `category` LIKE 'General' AND `name` LIKE 'Staff';