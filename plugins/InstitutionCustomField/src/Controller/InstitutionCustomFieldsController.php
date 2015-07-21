<?php
namespace InstitutionCustomField\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class InstitutionCustomFieldsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Fields' => ['className' => 'InstitutionCustomField.InstitutionCustomFields'],
			'Pages' => ['className' => 'InstitutionCustomField.InstitutionCustomForms'],
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Fields' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Fields'],
				'text' => __('Fields')
			],
			'Pages' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Pages'],
				'text' => __('Pages')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

    public function onInitialize(Event $event, Table $model) {
		$header = __('Custom Field (Institution)');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Custom Field (Institution)', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}