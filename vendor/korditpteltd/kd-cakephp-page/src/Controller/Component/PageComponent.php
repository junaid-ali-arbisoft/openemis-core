<?php
namespace Page\Controller\Component;

use ArrayObject;
use Exception;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Controller\Exception\MissingActionException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Controller\Component;

use Page\Model\Entity\PageElement;
use Page\Model\Entity\PageFilter;
use Page\Model\Entity\PageTab;
use Page\Model\Entity\PageStatus;
use Page\Traits\EncodingTrait;

class PageComponent extends Component
{
    use EncodingTrait;

    private $debug = false;
    private $controller = null;
    private $mainTable = null;
    private $order = [];
    private $moveSourceField;
    private $queryOptions;
    private $paginateOptions;
    private $limitOptions = [10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50];
    private $autoContain = true;
    private $autoRender = true;
    private $actions = [
        'index' => true,
        'add' => true,
        'view' => true,
        'edit' => true,
        'delete' => true,
        'download' => false,
        'search' => true
    ];

    private $cakephpReservedPassKeys = [
        'controller',
        'action',
        'plugin',
        'pass',
        '_matchedRoute',
        '_Token',
        '_csrfToken',
        'paging'
    ];

    // page elements
    private $showElements = false;
    private $header = '';
    private $breadcrumbs = [];
    private $elements;
    private $filters;
    private $toolbar;
    private $tabs;
    private $viewVars;
    private $status;

    private $excludedFields = ['order', 'modified', 'modified_user_id', 'created', 'created_user_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();

        $this->elements = new ArrayObject();
        $this->filters = new ArrayObject();
        $this->queryOptions = new ArrayObject();
        $this->paginateOptions = new ArrayObject(['limit' => 10]);
        $this->toolbar = new ArrayObject();
        $this->tabs = new ArrayObject();
        $this->viewVars = new ArrayObject();
        $this->status = new PageStatus();

        $this->setHeader(Inflector::humanize(Inflector::underscore($this->controller->name)));
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.beforeRender'] = ['callable' => 'beforeRender', 'priority' => 5];
        return $events;
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $request = $this->request;

        if ($this->getQueryString('limit')) {
            $this->setPaginateOption('limit', $this->getQueryString('limit'));
        }
    }

    // Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
    public function beforeRender(Event $event)
    {
        $controller = $this->controller;
        $request = $this->request;
        $requestQueries = $request->query;
        $session = $request->session();
        $action = $request->action;
        $isGet = $request->is(['get']);
        $isAjax = $request->is(['ajax']);

        $data = $this->getData();
        if (!is_null($data)) {
            if ($action == 'view' || $action == 'delete') {
                $this->loadDataToElements($data);
            } elseif ($action == 'edit' || $action == 'add') {
                $this->loadDataToElements($data, false);
            } elseif ($action == 'index') {
                foreach ($data as $entity) {
                    $disabledActions = [];
                    $event = $this->controller->dispatchEvent('Controller.Page.getEntityDisabledActions', [$entity], $this);

                    if ($event->result) {
                        $disabledActions = $event->result;
                    }
                    if ($entity instanceof Entity) {
                        $entity->disabledActions = $disabledActions;
                    } else {
                        $entity['disabledActions'] = $disabledActions;
                    }
                }
            }
        }

        if ($isGet || $isAjax || $this->showElements()) {
            $this->setVar('header', $this->getHeader());
            $this->setVar('breadcrumbs', $this->breadcrumbs);

            if ($this->elements->count() > 0) {
                $elements = $this->elementsToJSON();
                $this->setVar('elements', $elements);
            }

            if ($this->filters->count() > 0) {
                $querystring = $this->getQueryString();

                foreach ($this->filters as $filter) {
                    $dependentOn = $filter->getDependentOn();
                    if ($dependentOn && array_key_exists($dependentOn, $querystring)) {
                        $filterOptions = $this->getFilterOptions($filter->getParams());
                        $filter->setOptions($filterOptions);
                    }
                }
                $this->setVar('filters', $this->filtersToJSON());
            }

            if ($this->toolbar->count() > 0) {
                $this->setVar('toolbar', $this->toolbar->getArrayCopy());
            }

            if ($this->tabs->count() > 0) {
                $this->setVar('tabs', $this->tabsToArray());
            }

            $disabledActions = [];
            foreach ($this->actions as $action => $value) {
                if ($value == false) {
                    $disabledActions[] = $action;
                }
            }
            $this->setVar('disabledActions', $disabledActions);

            if ($this->hasMainTable()) {
                $table = $this->getMainTable();
                if (array_key_exists('paging', $request->params)) {
                    $paging = $request->params['paging'][$table->alias()];
                    $paging['limitOptions'] = $this->limitOptions;
                    $this->setVar('paging', $paging);
                }
            }
        }

        if ($session->check('alert')) {
            $this->setVar('alert', $session->read('alert'));
            $session->delete('alert');
        }

        if ($this->viewVars->count() > 0) {
            $this->controller->set($this->viewVars->getArrayCopy());
        }

        $this->controller->set('status', $this->status->toArray());

        if ($this->isDebugMode()) {
            pr($this->controller->viewVars);die;
        }
    }

    public function getFilterOptions($params)
    {
        $params = explode('/', $params);
        $querystring = $this->getQueryString();

        $model = $params[0];
        $finder = 'OptionList';
        if (count($params) == 2) {
            $finder = $params[1];
        }

        $table = $this->controller->{$model};

        if ($table === false) {
            $table = TableRegistry::get($model);
        }

        $options = [];
        $conditions = [];
        $finderOptions = ['limit' => 1000, 'querystring' => $querystring];

        foreach ($querystring as $key => $value) {
            if (in_array($key, $table->schema()->columns())) {
                $conditions[$key] = $querystring[$key];
            }
        }
        if (!empty($conditions)) {
            $finderOptions['conditions'] = $conditions;
        }

        if ($table->hasFinder($finder)) {
            $options = $table->find($finder, $finderOptions)->toArray();
        }

        return $options;
    }

    public function debug()
    {
        $this->debug = true;
    }

    public function isDebugMode()
    {
        $request = $this->request;
        $debugConfig = Configure::read('debug');
        $debugRequest = $this->request->query('debug') === 'true';
        $httpGET = $request->is('get');
        $httpPOST = $request->is('post');

        if (($debugConfig && $this->debug && $httpGET)
        ||  ($debugConfig && $debugRequest && $httpPOST)) {
            return true;
        }
        return false;
    }

    public function isJson()
    {
        $ext = $this->request->params['_ext'];
        return $ext === 'json';
    }

    public function redirect($url, $params = true /* 'PASS' | 'QUERY' | false */)
    {
        if (!$this->isJson()) { // only allow redirect if request type is not json
            $url = $this->getUrl($url, $params);
            $response = $this->controller->redirect($url);

            return $response;
        } else {
            return $this->controller->response;
        }
    }

    public function setAlert($message, $type = 'success', $reset = false)
    {
        $session = $this->request->session();

        $alert = [];

        if ($session->check('alert') && $reset == false) {
            $alert = $session->read('alert');
        }

        $alert[] = [
            'type' => $type,
            'message' => $message
        ];
        $session->write('alert', $alert);
    }

    public function setVar($name, $value)
    {
        $this->viewVars->offsetSet($name, $value);
        return $this;
    }

    public function getVar($name)
    {
        if ($this->viewVars->offsetExists($name)) {
            return $this->viewVars->offsetGet($name);
        }
        return null;
    }

    public function getData()
    {
        return $this->getVar('data');
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function enable($actions)
    {
        foreach ($actions as $action) {
            if (array_key_exists($action, $this->actions)) {
                $this->actions[$action] = true;
            }
        }
    }

    public function disable($actions)
    {
        foreach ($actions as $action) {
            if (array_key_exists($action, $this->actions)) {
                $this->actions[$action] = false;
            }
        }
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function action($action, $attr = [])
    {
        $this->actions[$action] = $attr;
    }

    public function throwMissingActionException()
    {
        $request = $this->request;
        throw new MissingActionException([
            'controller' => $this->controller->name . "Controller",
            'action' => $request->params['action'],
            'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
            'plugin' => $request->params['plugin'],
        ]);
    }

    public function isActionAllowed($action)
    {
        return array_key_exists($action, $this->actions) && $this->actions[$action];
    }

    public function isAutoContain()
    {
        return $this->autoContain;
    }

    public function setAutoContain($bool)
    {
        $this->autoContain = $bool;
        return $this;
    }

    public function isAutoRender()
    {
        return $this->autoRender;
    }

    public function setAutoRender($bool)
    {
        $this->autoRender = $bool;
        return $this;
    }

    public function autoConditions(Table $table)
    {
        $conditions = [];
        $columns = $table->schema()->columns();
        $querystring = $this->getQueryString();
        foreach ($querystring as $key => $value) {
            if (in_array($key, $columns)) {
                $conditions[$table->aliasField($key)] = $value;
            }
        }
        if (!empty($conditions)) {
            $this->queryOptions->offsetSet('conditions', $conditions);
        }
    }

    public function autoContains(Table $table)
    {
        if ($this->autoContain) {
            $contain = [];
            foreach ($table->associations() as $assoc) {
                if ($assoc->type() == 'manyToOne') { // belongsTo associations
                    $columns = $assoc->schema()->columns();
                    if (in_array('name', $columns)) {
                        $contain[$assoc->name()] = ['fields' => [
                            $assoc->aliasField('id'),
                            $assoc->aliasField('name')
                        ]];
                    } else {
                        $contain[] = $assoc->name();
                    }
                }
            }
            $this->queryOptions->offsetSet('contain', $contain);
        }
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    public function addCrumb($title, $options = [])
    {
        $item = array(
            'title' => __($title),
            'link' => ['url' => $options],
            'selected' => sizeof($options) == 0
        );
        $this->breadcrumbs[] = $item;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function hasMainTable()
    {
        return !is_null($this->mainTable);
    }

    public function getMainTable()
    {
        return $this->mainTable;
    }

    public function addTab($name)
    {
        $tab = new PageTab();
        $this->tabs->offsetSet($name, $tab);
        return $tab;
    }

    public function getTab($name)
    {
        $tab = null;
        if ($this->tabs->offsetExists($name)) {
            $tab = $this->tabs->offsetGet($name);
        }
        return $tab;
    }

    public function addToToolbar($name, $attr)
    {
        $this->toolbar->offsetSet($name, $attr);
        return $this;
    }

    public function getToolbar()
    {
        return $this->toolbar;
    }

    public function attachPrimaryKey(Table $table, &$entity)
    {
        $primaryKey = $table->primaryKey();

        if ($entity instanceof Entity) {
            if (!is_array($primaryKey)) { // primary key is not composite key
                $key = [$primaryKey => $entity->$primaryKey];
                $entity->primaryKey = $this->strToHex(json_encode($key));
            } else {
                $keyArray = [];
                foreach ($primaryKey as $key) {
                    $keyArray[$key] = $entity->$key;
                }
                $entity->primaryKey = $this->encode($keyArray);
            }
        } else {
            if (!is_array($primaryKey)) { // primary key is not composite key
                $key = [$primaryKey => $entity[$primaryKey]];
                $entity['primaryKey'] = $this->strToHex(json_encode($key));
            } else {
                $keyArray = [];
                foreach ($primaryKey as $key) {
                    $keyArray[$key] = $entity[$key];
                }
                $entity['primaryKey'] = $this->encode($keyArray);
            }
        }
    }

    public function defaultSearch(Table $table, Query $query, ArrayObject $options)
    {
        $value = $options['searchText'];
        $types = ['string', 'text'];
        $schema = $table->schema();
        $columns = $schema->columns();
        $wildcard = $options['wildcard'];

        $OR = [];
        foreach ($columns as $name) {
            $columnInfo = $schema->column($name);
            if ($name == 'id' || $name == 'password') continue;

            // if the field is of a searchable type and it is part of the table schema
            if (in_array($columnInfo['type'], $types)) {
                $searchValue = $value;
                if ($wildcard === true) {
                    $searchValue = '%' . $value . '%';
                } elseif ($wildcard == 'left') {
                    $searchValue = '%' . $value;
                } elseif ($wildcard == 'right') {
                    $searchValue = $value . '%';
                }
                $OR[$table->aliasField($name) . ' LIKE'] = $searchValue;
            }
        }

        // may have problems with complicated conditions
        if (!empty($OR)) {
            $query->where(['OR' => $OR]);
        }
    }

    public function setQueryString($key, $value, $replace = false /* set value only if the key does not exists */)
    {
        $querystring = $this->request->query('querystring');
        if ($querystring) {
            $querystring = json_decode($this->hexToStr($querystring), true);
            if (is_null($value) && array_key_exists($key, $querystring)) { // if value is null, the key will be removed from querystring
                unset($querystring[$key]);
            } elseif ($replace || !array_key_exists($key, $querystring)) {
                $querystring[$key] = $value;
            }
        } else {
            if (!is_null($value)) {
                $querystring = [$key => $value];
            }
        }

        if (!empty($querystring)) {
            $this->request->query['querystring'] = $this->encode($querystring);
        } else {
            unset($this->request->query['querystring']);
        }
    }

    public function getQueryString($key = null)
    {
        $querystring = $this->request->query('querystring');
        if ($querystring) {
            $querystring = json_decode($this->hexToStr($querystring), true);
            if (!is_null($key)) {
                if (array_key_exists($key, $querystring)) {
                    $querystring = $querystring[$key];
                } else {
                    $querystring = null;
                }
            }
        } else {
            if (!is_null($key)) {
                $querystring = null;
            } else {
            $querystring = [];
        }
        }
        return $querystring;
    }

    public function hasSearchText()
    {
        $querystring = $this->getQueryString();
        return array_key_exists('search', $querystring) && strlen($querystring['search']) > 0;
    }

    public function getSearchText()
    {
        $querystring = $this->getQueryString();
        return $querystring['search'];
    }

    public function setQueryOption($key, $value)
    {
        $this->queryOptions->offsetSet($key, $value);
        return $this;
    }

    public function getQueryOptions()
    {
        $querystring = $this->getQueryString();
        $this->queryOptions->offsetSet('querystring', $querystring);
        return $this->queryOptions;
    }

    public function setPaginateOption($key, $value)
    {
        $this->paginateOptions->offsetSet($key, $value);
        return $this;
    }

    public function getPaginateOptions()
    {
        return $this->paginateOptions;
    }

    public function getUrl(array $url = [], $params = true /* 'PASS' | 'QUERY' | false */)
    {
        $controller = $this->controller;
        $request = $this->request;

        $this->mergeRequestParams($url);

        if ($params === true) {
            $url = array_merge($url, $request->pass, $request->query);
        } elseif ($params === 'PASS') {
            $url = array_merge($url, $request->pass);
        } elseif ($params === 'QUERY') {
            $url = array_merge($url, $request->query);
        }
        return $url;
    }

    private function mergeRequestParams(array &$url)
    {
        $requestParams = $this->request->params;
        foreach ($requestParams as $key => $value) {
            if (is_numeric($key) || in_array($key, $this->cakephpReservedPassKeys)) {
                unset($requestParams[$key]);
            }
        }
        $url = array_merge($url, $requestParams);
    }

    public function showElements($show = null)
    {
        if (is_null($show)) {
            $requestQueries = $this->request->query;
            if ($this->showElements == false) {
                if (array_key_exists('elements', $requestQueries) && $requestQueries['elements'] == 'true') {
                    return true;
                }
            }
            return $this->showElements;
        } else {
            $this->showElements = $show;
        }
    }

    public function loadElementsFromTable(Table $table)
    {
        $this->mainTable = $table;
        $schema = $table->schema();
        $columns = $schema->columns();

        $this->attachDefaultValidation($table);

        foreach ($columns as $columnName) {
            if (!in_array($columnName, $this->excludedFields)) {
                $attributes = $schema->column($columnName);
                $foreignKey = $this->isForeignKey($table, $columnName);
                $attributes['foreignKey'] = $foreignKey;
                $attributes['model'] = $table->alias();
                $element = new PageElement($columnName, $attributes);
                if (in_array($attributes['type'], ['string', 'integer', 'text']) && !$foreignKey) {
                    $element->setSortable(true);
                }
                // setup displayFrom
                if ($foreignKey) {
                    $belongsTo = $table->{$foreignKey['name']};
                    $entity = $belongsTo->newEntity();

                    $columns = array_merge($entity->visibleProperties(), $belongsTo->schema()->columns());

                    if (in_array('name', $columns)) {
                        $element->setDisplayFrom($foreignKey['property'].'.name');
                    }
                }

                $this->add($element);
            }
        }

        $primaryKey = $table->primaryKey();
        if (!is_array($primaryKey)) {
            $this->get($primaryKey)->setControlType('hidden');
        }
    }

    public function loadDataToElements(Entity $entity, $callback = true)
    {
        foreach ($this->elements as $element) {
            $key = $element->getKey();
            $controlType = $element->getControlType();
            $value = $element->getValue();

            if ($this->isExcluded($key) || !empty($value)) continue; // skip excluded elements or if element already has a value

            if ($callback) {
                $prefix = 'Controller.Page.onRender';
                $eventName = $prefix . ucfirst($controlType);
                $eventParams = [$entity, $element];
                $event = $this->controller->dispatchEvent($eventName, $eventParams, $this);
                if ($event->result) { // trigger render<Format>
                    $value = $event->result;
                } else {
                    $eventName = $prefix . Inflector::camelize($key);
                    $event = $this->controller->dispatchEvent($eventName, $eventParams, $this);
                    if ($event->result) { // trigger render<Field>
                        $value = $event->result;
                    } elseif ($entity->has($key)) { // lastly, get value from Entity
                        $displayFrom = $element->getDisplayFrom();
                        if ($displayFrom) {
                            $data = Hash::flatten($entity->toArray());
                            if (array_key_exists($displayFrom, $data)) {
                                $value = $data[$displayFrom];
                            } else {
                                Log::write('error', 'DisplayFrom: ' . $displayFrom . ' does not exists in $data');
                            }
                        } else {
                            $value = $entity->$key;
                        }
                    }
                }
            } else {
                if ($entity->has($key)) {
                    $value = $entity->$key;
                }
            }

            $element->setValue($value);
        }
    }

    public function attachDefaultValidation(Table $table)
    {
        $validator = $table->validator();
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $key) {
            $attr = $schema->column($key);
            if ($validator->hasField($key)) {
                $set = $validator->field($key);

                if (!$set->isEmptyAllowed()) {
                    $set->add('notBlank', ['rule' => 'notBlank']);
                }
                if (!$set->isPresenceRequired()) {
                    if ($this->isForeignKey($table, $key)) {
                        $validator->requirePresence($key);
                    }
                }
            } else { // field not presence in validator
                if (array_key_exists('null', $attr)) {
                    if ($attr['null'] === false // not nullable
                        && (array_key_exists('default', $attr) && strlen($attr['default']) == 0) // don't have a default value in database
                        && $key !== 'id' // not a primary key
                        && !in_array($key, $this->excludedFields)) // fields not excluded
                    {
                        $validator->add($key, 'notBlank', ['rule' => 'notBlank']);
                        if ($this->isForeignKey($table, $key)) {
                            $validator->requirePresence($key);
                        }
                    }
                }
            }
        }
    }

    public function get($key)
    {
        $element = null;
        if (array_key_exists($key, $this->order)) {
            if ($this->elements->offsetExists($this->order[$key])) {
                $element = $this->elements->offsetGet($this->order[$key]);
            }
        } else {
            pr($key . ' does not exists');die;
        }
        return $element;
    }

    public function addNew($key, $attributes = [])
    {
        if (!array_key_exists('model', $attributes)) {
            if ($this->hasMainTable()) {
                $attributes['model'] = $this->mainTable->alias();
            }
        }

        $element = PageElement::create($key, $attributes);
        $this->add($element);
        return $element;
    }

    public function add(PageElement $element)
    {
        $this->elements->offsetSet($this->elements->count(), $element);
        $this->order[$element->getKey()] = count($this->order);
    }

    public function addFilter($name)
    {
        $filter = new PageFilter($name);
        $this->filters->offsetSet($name, $filter);
        return $filter;
    }

    private function elementsToJSON()
    {
        $json = [];

        foreach ($this->elements as $element) {
            $key = $element->getKey();
            if (!$this->isExcluded($key)) {
                $controlType = $element->getControlType();
                $isDropdownType = $controlType == 'dropdown';
                $noDropdownOptions = empty($element->getOptions());

                // auto populate dropdown options based on foreign keys if no options are provided
                if ($isDropdownType && $noDropdownOptions) {
                    $this->populateDropdownOptions($element);
                    if (empty($element->getValue())) {
                        $querystring = $this->getQueryString();
                        if (array_key_exists($key, $querystring)) {
                            $element->setValue($querystring[$key]);
                        }
                    }
                }

                $json[$key] = $element->getJSON();
            }
        }
        return $json;
    }

    private function filtersToJSON()
    {
        $json = [];
        foreach ($this->filters as $filter) {
            $json[$filter->getName()] = $filter->getJSON();
        }
        return $json;
    }

    private function tabsToArray()
    {
        $array = [];
        foreach ($this->tabs as $name => $tab) {
            $array[$name] = $tab->toArray();
        }
        return $array;
    }

    private function populateDropdownOptions(PageElement $element)
    {
        if ($this->hasMainTable()) {
            $table = $this->getMainTable();
            $foreignKey = $element->getForeignKey();
            if (!is_null($element->getParams())) {
                $element->setOptions($this->getFilterOptions($element->getParams()));
            } elseif ($foreignKey) {
                $association = $foreignKey['name'];
                // default limit to 1000 to prevent out of memory error

                $options = [];
                // if finder OptionList exists, call finder
                // else call findList and format results
                if ($table->$association->hasFinder('optionList')) {
                    $options = $table->$association
                        ->find('optionList')
                        ->limit(1000)
                        ->toArray();
                } else {
                    $options = $table->$association->find('list')
                        ->limit(1000)
                        ->formatResults(function ($results) {
                            $results = $results->toArray();
                            $returnResults = [];
                            foreach ($results as $key => $value) {
                                $returnResults[] = [
                                    'text' => __($value),
                                    'value' => $key
                                ];
                            }
                            return $returnResults;
                        })
                        ->toArray();
                }
                $element->setOptions($options);
            }
        }
    }

    public function exclude($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $name) {
            if (array_key_exists($name, $this->order)) {
                $this->get($name)->setVisible(false);
                $this->excludedFields[] = $name;
            }
        }
    }

    public function isExcluded($name)
    {
        return in_array($name, $this->excludedFields);
    }

    public function move($source)
    {
        if (!array_key_exists($source, $this->order)) {
            pr($source . ' does not exists');die;
        }
        $this->moveSourceField = $source;
        return $this;
    }

    public function first()
    {
        $elements = $this->elements;
        $count = $elements->count();
        // move all items + 1 from first position
        for ($i=$count-1; $i>=0; $i--) {
            $element = $elements->offsetGet($i);
            $elements->offsetSet($i+1, $element);
            $this->order[$element->getKey()] = $i+1;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $elements->offsetSet(0, $elements->offsetGet($sourceOrder));
        $this->order[$this->moveSourceField] = 0;

        // remove the extra source item in the array
        $elements->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $element = $elements->offsetGet($i+1);
            $elements->offsetSet($i, $element);
            $elements->offsetUnset($i+1);
            $this->order[$element->getKey()] = $i;
        }

        $this->moveSourceField = null;
        return $elements->offsetGet(0);
    }

    public function after($destination)
    {
        $elements = $this->elements;
        $destinationOrder = $this->order[$destination];
        $count = $elements->count();

        // move all items + 1 from destination position
        for ($i=$count; $i>$destinationOrder+1; $i--) {
            $element = $elements->offsetGet($i-1);
            $elements->offsetSet($i, $element);
            $this->order[$element->getKey()] = $i;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $elements->offsetSet($destinationOrder+1, $elements->offsetGet($sourceOrder));

        // updates $this->order with new position of source
        $sourceField = $elements->offsetGet($this->order[$this->moveSourceField]);
        $this->order[$sourceField->getKey()] = $destinationOrder+1;

        // remove the extra source item in the array
        $elements->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $element = $elements->offsetGet($i+1);
            $elements->offsetSet($i, $element);
            $elements->offsetUnset($i+1);
            $this->order[$element->getKey()] = $i;
        }

        // reset the source value
        $this->moveSourceField = null;
        return $sourceField;
    }

    public function isForeignKey(Table $table, $field)
    {
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return ['name' => $assoc->name(), 'property' => $assoc->property()];
                }
            }
        }
        return false;
    }
}