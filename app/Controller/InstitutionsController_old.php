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

App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class InstitutionsController extends AppController {    
    public $uses = Array(
		'Area',
		'AreaLevel',
		'AreaEducation',
		'AreaEducationLevel',
		'Institution',
		'InstitutionSite',
		'InstitutionCustomField',
		'InstitutionCustomFieldOption',
		'InstitutionCustomValue',
		'InstitutionProvider',
		'InstitutionSector',
		'InstitutionStatus',
		'InstitutionAttachment',
		'InstitutionHistory',
                'InstitutionSiteType',
                'SecurityGroupUser'
    );

    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
		'Paginator',
		'FileAttachment' => array(
			'model' => 'InstitutionAttachment',
			'foreignKey' => 'institution_id'
		),'AccessControl'
                
	);

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index'));
		
		$actions = array('index', 'advanced', 'add', 'listSites','getCustomFieldsSearch');
		if(in_array($this->action, $actions)) {
			$this->bodyTitle = 'Institutions';
		} else {
			if($this->Session->check('InstitutionId')) {
				$institutionId = $this->Session->read('InstitutionId');
				$institutionName = $this->Institution->field('name', array('Institution.id' => $institutionId));
				$this->bodyTitle = $institutionName;
				$this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view'));
			} else {
				$this->redirect(array('action' => 'index'));
			}
		}
    }
	
	public function index() {
		$this->AccessControl->init($this->Auth->user('id'));

        $this->Navigation->addCrumb('List of Institutions');
        if ($this->request->is('post')){
			if(isset($this->request->data['Institution']['SearchField'])){
				$this->request->data['Institution']['SearchField'] = Sanitize::escape(trim($this->request->data['Institution']['SearchField']));
				
				if($this->request->data['Institution']['SearchField'] != $this->Session->read('Search.SearchField')) {
					$this->Session->delete('Search.SearchField');
					$this->Session->write('Search.SearchField', $this->request->data['Institution']['SearchField']);
				}
			}
			
			if(isset($this->request->data['sortdir']) && isset($this->request->data['order']) ){
				if($this->request->data['sortdir'] != $this->Session->read('Search.sortdir')) {
					$this->Session->delete('Search.sortdir');
					$this->Session->write('Search.sortdir', $this->request->data['sortdir']);
				}
				if($this->request->data['order'] != $this->Session->read('Search.order')) {
					$this->Session->delete('Search.order');
					$this->Session->write('Search.order', $this->request->data['order']);
				}
			}
        }
		
		$fieldordername = ($this->Session->read('Search.order'))?$this->Session->read('Search.order'):'Institution.name';
		$fieldorderdir = ($this->Session->read('Search.sortdir'))?$this->Session->read('Search.sortdir'):'asc';
		
		$searchKey = stripslashes($this->Session->read('Search.SearchField'));
                
		$conditions = array(
			'SearchKey' => $searchKey, 
			'AdvancedSearch' => $this->Session->check('Institution.AdvancedSearch') ? $this->Session->read('Institution.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id'),
                        'order' => array($fieldordername => $fieldorderdir)
		);
		
		$order = array('order' => array($fieldordername => $fieldorderdir));
        $limit = ($this->Session->read('Search.perpage')) ? $this->Session->read('Search.perpage') : 30;
        $this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);
		
        $data = $this->paginate('Institution', $conditions);
		if(!$this->Session->check('Search.SearchField') && !$this->Session->check('Institution.AdvancedSearch')) {
			// if user do not have access to add institution and the records is 1, redirect to list of sites
			if(count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'listSites', $data[0]['Institution']['id']));
			}
		}
		
		if(empty($data) && !$this->request->is('ajax')) {
			$this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
        $this->set('institutions', $data);
		$this->set('sortedcol', $fieldordername);
		$this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
		$this->set('searchField', stripslashes($this->Session->read('Search.SearchField'))); 
        if ($this->request->is('post')){
			$this->render('index_records','ajax');
        }
    }
	
	public function advanced() {
		$key = 'Institution.AdvancedSearch';
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('List of Institutions', array('controller' => 'Institutions', 'action' => 'index'));
				$this->Navigation->addCrumb('Advanced Search');
				
				if(isset($this->params->pass[0])) {
					if(intval($this->params->pass[0])===0) {
						$this->Session->delete($key);
						$this->redirect(array('action' => 'index'));
					}
				}
			}
		} else {
                   
			//$search = $this->data['Search'];
                        $search = $this->data;
			if(!empty($search)) {
                           //pr($this->data);die;
				$this->Session->write($key, $search);
			}
			$this->redirect(array('action' => 'index'));
		}
                
                
                
	}
        
        public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Institution'){
             $this->layout = false;
             $arrSettings = array(
                                                            'CustomField'=>$customfields.'CustomField',
                                                            'CustomFieldOption'=>$customfields.'CustomFieldOption',
                                                            'CustomValue'=>$customfields.'CustomValue',
                                                            'Year'=>''
                                                        );
             if($this->{$customfields}->hasField('institution_site_type_id')){
                 $arrSettings = array_merge(array('institutionSiteTypeId'=>$sitetype),$arrSettings);
             }
             $arrCustFields = array($customfields => $arrSettings);
             
            $instituionSiteCustField = $this->Components->load('CustomField',$arrCustFields[$customfields]);
            $dataFields[$customfields] = $instituionSiteCustField->getCustomFields();
            $types = $this->InstitutionSiteType->findList(1);
            $this->set("customfields",array($customfields));
            $this->set('types',  $types);        
            $this->set('typeSelected',  $sitetype);
            $this->set('dataFields',  $dataFields);
            $this->render('/Elements/customfields/search');
        }
	
	public function listSites() {
		$id = 0;
		if(!isset($this->params['pass'][0])) {
			if(!$this->Session->check('InstitutionId')) {
				$this->redirect(array('action' => 'index'));
			} else {
				$id = $this->Session->read('InstitutionId');
			}
		} else {
			$id = $this->params['pass'][0];
		}
		
		$data = $this->Institution->find('first', array('conditions' => array('Institution.id' => $id)));
		if($data) {
            $sites = $data['InstitutionSite'];
            
            $this->Session->write('InstitutionId', $id);
			
			foreach($sites as $k => &$arr){
				$found = false;
				if($this->Auth->user('super_admin')==0) {
					if($this->AccessControl->isInstitutionSiteAccessible($arr['id'])) {
                                                if($this->Session->check('permissions')) {
                                                    $permissions = $this->Session->read('permissions');
                                                    $check = $permissions['check'];
                                                    $anyActionAccessible = false;
                                                    
                                                    foreach($check['INSTITUTIONSITES'] AS $action => $value){
                                                        $checkWithRoleId = $this->AccessControl->newCheck('INSTITUTIONSITES', $action, $arr['id']);
                                                        if($checkWithRoleId){
                                                            $anyActionAccessible = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if(!$anyActionAccessible){
                                                        foreach($check['CENSUS'] AS $action => $value){
                                                            $checkWithRoleId = $this->AccessControl->newCheck('CENSUS', $action, $arr['id']);
                                                            if($checkWithRoleId){
                                                                $anyActionAccessible = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    
                                                    if($anyActionAccessible){
                                                        $found = true;
                                                    }else{
                                                        unset($sites[$k]);
                                                    }
                                                }else{
                                                    unset($sites[$k]);
                                                }
					} else {
						unset($sites[$k]);
					}
				} else {
					$found = true;
				}
				if($found) {
					$p = $this->InstitutionSite->find('first',array('conditions'=>array('InstitutionSite.id' => $arr['id'])));
					$sites[$k] = $p;
				}
			}//pr($sites);

			$this->bodyTitle = $data['Institution']['name'];
			$this->Navigation->addCrumb($data['Institution']['name'], array('controller' => 'Institutions', 'action' => 'view'));
			$this->Navigation->addCrumb('List of Institution Sites');
			
			if(empty($sites)) {
				$this->Utility->alert($this->Utility->getMessage('NO_SITES'), array('type' => 'info', 'dismissOnClick' => false));
			}
			$this->set('sites', $sites);
			
			// Checking if user has access to institution sites
			$_view_sites = false;
//			if($this->AccessControl->newCheck('InstitutionSites', 'view')) {
//				$_view_sites = true;
//			}
                        $_view_sites = true;
			$this->set('_view_sites', $_view_sites);
			// End Access Control
		} else {
			$this->redirect(array('action' => 'index'));
		}
    }
	
    public function view() {
		$this->Navigation->addCrumb('Overview');
		$id = $this->Session->read('InstitutionId');
        $this->Institution->id = $id;
        $data = $this->Institution->read();
		
		/* perform custom date format
        $dateOpened = $data['Institution']['date_opened'];
		$data['Institution']['date_opened'] = $dateOpened;
        $dateClosed = $data['Institution']['date_closed'];
		$data['Institution']['date_closed'] = $dateClosed;
		*/
        $this->set('data', $data);
    }

    public function edit() {
		$id = $this->Session->read('InstitutionId');
		$this->Institution->id = $id;
		$this->Navigation->addCrumb('Edit');
		if($this->request->is('post')) {
			$this->Institution->set($this->data);
			if($this->Institution->validates()) {
				$this->Institution->save($this->data);
				$this->redirect(array('action' => 'view'));
			}
		}else{
			$data = $this->Institution->find('first', array('conditions' => array('Institution.id' => $id)));
			$this->set('data', $data);
		}
		
		$visible = true;
		$sector = $this->InstitutionSector->findList($visible);
		$provider = $this->InstitutionProvider->findList($visible);
		$status = $this->InstitutionStatus->findList($visible);
		$this->Utility->unshiftArray($sector, array('0'=>__('--Select--')));
		$this->Utility->unshiftArray($provider, array('0'=>__('--Select--')));
		$this->Utility->unshiftArray($status, array('0'=>__('--Select--')));
		
		$this->set('sector_options', $sector);
		$this->set('provider_options',$provider);
		$this->set('status_options',$status);
    }
	
    public function add() {
		$this->Navigation->addCrumb('Add new Institution');
		
		if($this->request->is('post')) {
			$this->Institution->set($this->data);
			if($this->Institution->validates()) {
				$newInstitutionRec =  $this->Institution->save($this->data);
				$institutionId = $newInstitutionRec['Institution']['id'];
				$this->Session->write('InstitutionId', $institutionId);
				$this->redirect(array('action' => 'view'));
			}
		}
		
		$visible = true;
		$sector = $this->InstitutionSector->findList($visible);
		$provider = $this->InstitutionProvider->findList($visible);
		$status = $this->InstitutionStatus->findList($visible);
		
		$this->Utility->unshiftArray($sector, array('0'=>__('--Select--')));
		$this->Utility->unshiftArray($provider, array('0'=>__('--Select--')));
		$this->Utility->unshiftArray($status, array('0'=>__('--Select--')));
		
		$this->set('sector_options', $sector);
		$this->set('provider_options',$provider);
		$this->set('status_options',$status);
    }
	
	public function delete() {
		$id = $this->Session->read('InstitutionId');
		$name = $this->Institution->field('name', array('Institution.id' => $id));
		$this->Institution->delete($id);
		$this->Utility->alert($name . ' have been deleted successfully.');
		$this->redirect(array('action' => 'index'));
	}
	
	public function attachments() {
		$this->Navigation->addCrumb('Attachments');
		$id = $this->Session->read('InstitutionId');
		$data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Elements/attachment/view');
    }
	
    public function attachmentsEdit() {
		$this->Navigation->addCrumb('Edit Attachments');
		$id = $this->Session->read('InstitutionId');
        
		if($this->request->is('post')) { // save
			$errors = $this->FileAttachment->saveAll($this->data, $_FILES, $id);
			if(sizeof($errors) == 0) {
				$this->Utility->alert('Files have been saved successfully.');
				$this->redirect(array('action' => 'attachments'));
			} else {
				$this->Utility->alert('Some errors have been encountered while saving files.', array('type' => 'error'));
			}
        }
		
        $data = $this->FileAttachment->getList($id);
        $this->set('data',$data);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Elements/attachment/edit');
    }
	
	public function attachmentsAdd() {
		$this->layout = 'ajax';
		$this->set('params', $this->params->query);
		$this->render('/Elements/attachment/add');
	}
       
    public function attachmentsDelete() {
		$this->autoRender = false;
        if($this->request->is('post')) {
			$result = array('alertOpt' => array());
			$this->Utility->setAjaxResult('alert', $result);
			$id = $this->params->data['id'];
			
			if($this->FileAttachment->delete($id)) {
				$result['alertOpt']['text'] = __('File was deleted successfully.');
			} else {
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = __('Error occurred while deleting file.');
			}
			
			return json_encode($result);
        }
    }
        
    public function attachmentsDownload($id) {
        $this->FileAttachment->download($id);
    }
    
    public function additional() {
		$this->Navigation->addCrumb('More');
		$id = $this->Session->read('InstitutionId');
		
        $datafields = $this->InstitutionCustomField->find('all',array('conditions'=>array('InstitutionCustomField.visible'=>1),'order'=>'InstitutionCustomField.order'));
        $this->InstitutionCustomValue->unbindModel(array('belongsTo' => array('Institution')));
        $datavalues = $this->InstitutionCustomValue->find('all',array('conditions'=>array('InstitutionCustomValue.institution_id'=>$id)));
		
        $tmp=array();
        foreach($datavalues as $arrV){
			$tmp[$arrV['InstitutionCustomField']['id']][] = $arrV['InstitutionCustomValue'];
        }
        $datavalues = $tmp;
        
        $this->set('datafields',$datafields);
        $this->set('datavalues',$tmp);
    }
	
    public function additionalEdit() {
        $this->Navigation->addCrumb('Edit More');
		$id = $this->Session->read('InstitutionId');
        
        if ($this->request->is('post')) {
            //pr($this->data);
            //die();
            $arrFields = array('textbox','dropdown','checkbox','textarea');
            /**
             * Note to Preserve the Primary Key to avoid exhausting the max PK limit
             */
            foreach($arrFields as $fieldVal){

                if(!isset($this->request->data['InstitutionCustomValue'][$fieldVal]))  continue;
               // pr($this->request->data);die;
                foreach($this->request->data['InstitutionCustomValue'][$fieldVal] as $key => $val){

                    if($fieldVal == "checkbox"){
                        $arrCustomValues = $this->InstitutionCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('InstitutionCustomValue.institution_id' => $id,'InstitutionCustomValue.institution_custom_field_id' => $key)));

                        $tmp = array();
                        if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                        foreach($arrCustomValues as $pk => $intVal){
                            //pr($val['value']); echo "$intVal";
                            if(!in_array($intVal, $val['value'])){
                                //echo "not in db so remove \n";
                               $this->InstitutionCustomValue->delete($pk);
                            }
                        }
                        $ctr = 0;
                        if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                        foreach($val['value'] as $intVal){
                            //pr($val['value']); echo "$intVal";
                            if(!in_array($intVal, $arrCustomValues)){
                                $this->InstitutionCustomValue->create();
                                $arrV['institution_custom_field_id']  = $key;
                                $arrV['value']  = $val['value'][$ctr];
                                $arrV['institution_id'] = $id;
                                $this->InstitutionCustomValue->save($arrV);
                                unset($arrCustomValues[$ctr]);
                            }
                             $ctr++;
                        }
                    }else{ // if editing reuse the Primary KEY; so just update the record  

                        $x = $this->InstitutionCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('InstitutionCustomValue.institution_id' => $id,'InstitutionCustomValue.institution_custom_field_id' => $key)));
                        $this->InstitutionCustomValue->create();
                        if($x) $this->InstitutionCustomValue->id = $x['InstitutionCustomValue']['id'];
                        $arrV['institution_custom_field_id']  = $key;
                        $arrV['value']  = $val['value'];
                        $arrV['institution_id'] = $id;
                        $this->InstitutionCustomValue->save($arrV);
                    }
                }
            }
            $this->redirect(array('action' => 'additional'));
        }


        $datafields = $this->InstitutionCustomField->find('all',array('conditions'=>array('InstitutionCustomField.visible'=>1),'order'=>'InstitutionCustomField.order'));
        $this->InstitutionCustomValue->unbindModel(
            array('belongsTo' => array('Institution'))
        );
        $datavalues = $this->InstitutionCustomValue->find('all',array('conditions'=>array('InstitutionCustomValue.institution_id'=>$id)));
		
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['InstitutionCustomField']['id']][] = $arrV['InstitutionCustomValue'];
        }
        $datavalues = $tmp;
        
        $this->set('datafields',$datafields);
        $this->set('datavalues',$tmp);
    }
	
    public function history(){
        $this->Navigation->addCrumb('History');
		$id = $this->Session->read('InstitutionId');
        
        $arrTables = array('History','Status','Provider','Sector');
        $historyData = $this->InstitutionHistory->find('all',array('conditions'=> array('InstitutionHistory.institution_id'=>$id),'order'=>array('InstitutionHistory.created' => 'desc')));
        $data = $this->Institution->findById($id);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {
            
            foreach($arrTables as $table){
            //pr($arrVal);die;
                foreach($arrVal['Institution'.$table] as $k => $v){
                    
                    $keyVal = ($k == 'name')?$table.'_name':$k;
                    //echo $k.'<br>';
                    $data2[$keyVal][$v] = $arrVal['InstitutionHistory']['created'];
                }
            }
            
        }
        
        // manipulation for dates
		/*
        if (isset($data['Institution']['date_opened'])) {
            $data['Institution']['date_opened'] = $this->formatDate($data['Institution']['date_opened']);
        }

        if (isset($data['Institution']['date_closed'])) {
            $data['Institution']['date_closed'] = $this->formatDate($data['Institution']['date_closed']);
        }

        if (isset($data2['date_opened'])) {
            foreach($data2['date_opened'] as $key => $val) {
                unset($data2['date_opened'][$key]);
                $key = $this->formatDate($key);
                $data2['date_opened'][$key] = $val;
            }
        }

        if (isset($data2['date_closed'])) {
            foreach($data2['date_closed'] as $key => $val) {
                unset($data2['date_closed'][$key]);
                $key = $this->formatDate($key);
                $data2['date_closed'][$key] = $val;
            }
        }
		*/
		
		if(empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		}

        $this->set('data',$data);
        $this->set('institution', $data);
        $this->set('data2',$data2);
    }
	
	public function viewAreaChildren($id,$arrMap = array('Area','AreaLevel')) {
		//if ajax
		if($this->RequestHandler->isAjax()){
			$arrMap = ($arrMap == 'admin')?  array('AreaEducation','AreaEducationLevel') : array('Area','AreaLevel') ;
		}
		$this->autoRender = false;
		$value =$this->{$arrMap[0]}->find('list',array('conditions'=>array($arrMap[0].'.parent_id' => $id,$arrMap[0].'.visible' => 1)));
		$this->Utility->unshiftArray($value, array('0'=>'--'.__('Select').'--'));
		echo json_encode($value);
	}
	
	private function fetchtoParent($lowest,$arrMap = array('Area','AreaLevel')){
		
		$AreaLevelfk = Inflector::underscore($arrMap[1]);
		$arrVals = Array();
		//pr($lowest);die;
		//$this->autoRender = false; // AJAX
		
		$list = $this->{$arrMap[0]}->find('first', array(
								'fields' => array($arrMap[0].'.id', $arrMap[0].'.name', $arrMap[0].'.parent_id', $arrMap[0].'.'.$AreaLevelfk.'_id',$arrMap[1].'.name'),
								'conditions' => array($arrMap[0].'.id' => $lowest)));
		
		//check if not false
		if($list){ 
			$arrVals[$list[$arrMap[0]][$AreaLevelfk.'_id']] = Array('level_id'=>$list[$arrMap[0]][$AreaLevelfk.'_id'],'id'=>$list[$arrMap[0]]['id'],'name'=>$list[$arrMap[0]]['name'],'parent_id'=>$list[$arrMap[0]]['parent_id'],'AreaLevelName'=>$list[$arrMap[1]]['name']);
		
			if($list[$arrMap[0]][$AreaLevelfk.'_id'] > 1){
				if($list[$arrMap[0]][$AreaLevelfk.'_id']){
					do {
						$list = $this->{$arrMap[0]}->find('first', array(
								'fields' => array($arrMap[0].'.id', $arrMap[0].'.name', $arrMap[0].'.parent_id', $arrMap[0].'.'.$AreaLevelfk.'_id',$arrMap[1].'.name', $arrMap[0].'.visible'),
								'conditions' => array($arrMap[0].'.id' => $list[$arrMap[0]]['parent_id'])));
						$arrVals[$list[$arrMap[0]][$AreaLevelfk.'_id']] = Array('visible'=>$list[$arrMap[0]]['visible'],'level_id'=>$list[$arrMap[0]][$AreaLevelfk.'_id'],'id'=>$list[$arrMap[0]]['id'],'name'=>$list[$arrMap[0]]['name'],'parent_id'=>$list[$arrMap[0]]['parent_id'],'AreaLevelName'=>$list[$arrMap[1]]['name']);
					} while ($list[$arrMap[0]][$AreaLevelfk.'_id'] != 1);
				}
			}
			
		}
		
		
		return $arrVals;
	}
}