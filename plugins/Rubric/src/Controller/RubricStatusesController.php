<?php
namespace Rubric\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class RubricStatusesController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Rubric.QualityStatuses');
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Rubric', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses', 'action' => 'index']);
        $this->Navigation->addCrumb('Statuses');

    	$header = __('Rubric');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			// logic here
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);
	}
}