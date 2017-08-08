<?php
namespace Institution\Controller;

use Cake\Event\Event;

use Page\Controller\PageController;

class CounsellingsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Counsellings);

        $this->loadComponent('RenderDate'); // will get the date format from config
        $this->loadComponent('Page.RenderLink'); // will get the date format from config

        $this->Page->enable(['download']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderCounselorId'] = 'onRenderCounselorId';
        return $events;
     }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        $page = $this->Page;

        $page->get('student_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id

        $page->move('file_name')->after('guidance_type_id'); // move file_content after guidance type
        $page->move('file_content')->after('file_name'); // move file_name after file_content

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $page->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $page->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
        $page->addCrumb('Counselling');

        // set header
        $header = $page->getHeader();
        $page->setHeader($studentName . ' - ' . $header);

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('student_id', $studentId);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['file_name', 'file_content', 'counselor_id', 'student_id']);

        parent::index();
    }

    public function add()
    {
        $this->addEditCounselling();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEditCounselling();
        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        $page->get('file_name')->setControlType('link');
        parent::view($id);
    }

    // to display the counselor name with id
    public function onRenderCounselorId(Event $event, $entity, $key)
    {
        return $entity->counselor->name_with_id;
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    private function addEditCounselling()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);
        $institutionId = $page->getQueryString('institution_id');
        $studentId = $page->getQueryString('student_id');

        // set the options for guidance_type_id, should be auto create the options, but reorder and visible not working.
        $guidanceTypesOptions = $this->Counsellings->getGuidanceTypesOptions($institutionId);
        $page->get('guidance_type_id')->setControlType('dropdown')->setOptions($guidanceTypesOptions);

        // set the options for counselor_id
        $counselorOptions = $this->Counsellings->getCounselorOptions($institutionId);
        $page->get('counselor_id')->setControlType('dropdown')->setOptions($counselorOptions);

        // set the file upload for attachment
        $page->get('file_content')->set('fileName', 'file_name')->set('fileSizeLimit', '2');
    }
}