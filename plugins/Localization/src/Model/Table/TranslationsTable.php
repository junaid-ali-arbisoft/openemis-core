<?php
namespace Localization\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;

class TranslationsTable extends AppTable {

	// Initialisation
	public function initialize(array $config) {
		parent::initialize($config);
	}

	// Has to be implemented before a button can be added
	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	// Search component
	public function indexBeforeAction(Event $event){
		// By default English has to be there
		$defaultLocale = 'en';

		// Get the localization option from localization component
		$localeOptions = $this->Localization->getOptions();
		
		if(array_key_exists($defaultLocale, $localeOptions)){
			unset($localeOptions[$defaultLocale]);
		}
		$this->controller->set(compact('localeOptions'));

		$selectedOption = $this->queryString('translations_id', $localeOptions);
		$this->controller->set('selectedOption', $selectedOption);

		$toolbarElements = [
			['name' => 'Localization.controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$selected = 'ar';
		if(array_key_exists($selectedOption, $localeOptions)){
			$selected = $selectedOption;
		}
		
		$this->ControllerAction->setFieldOrder([
			 $defaultLocale, $selected
		]);
		$this->ControllerAction->setFieldVisible(['index'], [
			$defaultLocale, $selected
		]);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		// Include the js file for the compiling
		$includes['localization'] = ['include' => true, 'js' => 'Localization.translations'];
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$searchField = $request->data['Search']['searchField'];
		// Append the condition to the existing condition in the options
		$options['conditions']['OR'][] = $this->aliasField('en')." LIKE '%".$searchField."%'";
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if($action == "index"){
			$toolbarButtons['download']['type'] = 'button';
			$toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
			$toolbarButtons['download']['attr'] = $attr;
			$toolbarButtons['download']['attr']['title'] = __('Compile');
			$toolbarButtons['download']['attr']['onclick'] = 'Translations.compile(this);';
			$url = "";
			$query = $this->request->query('translations_id');
			if (isset($query)) {
				$url['translations_id'] = $query;
			}
			$toolbarButtons['download']['url'] = $url;
		}
    }
}

?>