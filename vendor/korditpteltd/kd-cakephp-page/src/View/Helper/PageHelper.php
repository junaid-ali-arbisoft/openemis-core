<?php
namespace Page\View\Helper;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\View\Helper;

use Page\Traits\EncodingTrait;

class PageHelper extends Helper
{
    use EncodingTrait;

    public $helpers = ['Form', 'Html', 'Paginator', 'Url'];

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

    public $includes = [
        'datepicker' => [
            'include' => false,
            'css' => 'Page.../plugins/datepicker/css/bootstrap-datepicker.min',
            'js' => 'Page.../plugins/datepicker/js/bootstrap-datepicker.min',
            'element' => 'Page.datepicker_js'
        ],
        'timepicker' => [
            'include' => false,
            'css' => 'Page.../plugins/timepicker/css/bootstrap-timepicker.min',
            'js' => 'Page.../plugins/timepicker/js/bootstrap-timepicker.min',
            'element' => 'Page.timepicker_js'
        ],
        'chosen' => [
            'include' => false,
            'css' => 'Page.../plugins/chosen/css/chosen.min',
            'js' => 'Page.../plugins/chosen/js/chosen.jquery.min'
        ],
        'jasny' => [
            'include' => false,
            'css' => 'Page.../plugins/jasny/css/jasny-bootstrap.min',
            'js' => 'Page.../plugins/jasny/js/jasny-bootstrap.min'
        ]
    ];

    public function includes()
    {
        $includes = new ArrayObject($this->includes);

        foreach ($includes as $include) {
            if ($include['include'] == false) continue;

            if (array_key_exists('css', $include)) {
                if (is_array($include['css'])) {
                    foreach ($include['css'] as $css) {
                        echo $this->Html->css($css, ['block' => true]);
                    }
                } else {
                    echo $this->Html->css($include['css'], ['block' => true]);
                }
            }
            if (array_key_exists('js', $include)) {
                if (is_array($include['js'])) {
                    foreach ($include['js'] as $js) {
                        echo $this->Html->script($js, ['block' => true]);
                    }
                } else {
                    echo $this->Html->script($include['js'], ['block' => true]);
                }
            }
            if (array_key_exists('element', $include)) {
                $this->_View->element($include['element']);
            }
        }
    }

    public function getFormTemplate()
    {
        return [
            'select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>',
            'radio'  => '<input type="radio" class = "iradio_minimal-grey icheck-input" name="{{name}}" value="{{value}}"{{attrs}}>'
        ];
    }

    public function getFormOptions()
    {
        $options = [
            'id' => 'content-main-form',
            'class' => 'form-horizontal',
            'novalidate' => true,
            'onSubmit' => '$(\'button[type="submit"]\').click(function() { return false; });'
        ];

        $elements = $this->_View->get('elements');
        if (!empty($elements)) {
            $types = ['binary', 'image'];
            foreach ($elements as $key => $attr) {
                if (in_array($attr['controlType'], $types)) {
                    $options['type'] = 'file';
                    break;
                }
            }
        }

        return $options;
    }

    public function getFormButtons()
    {
        $buttons = new ArrayObject([]);

        // save button
        $buttons[] = [
            'name' => '<i class="fa fa-check"></i> ' . __('Save'),
            'attr' => ['class' => 'btn btn-default btn-save', 'div' => false, 'name' => 'submit', 'value' => 'save']
        ];

        // cancel button
        $backBtn = null;//$this->_View->get('backButton');
        // $buttons[] = [
        //     'name' => '<i class="fa fa-close"></i> ' . __('Cancel'),
        //     'attr' => [
        //         'class' => 'btn btn-outline btn-cancel',
        //         'onclick' => 'console.log("asd"); return false',
        //         'escape' => false
        //     ],
        //     'url' => !is_null($backBtn) ? $backBtn['url'] : []
        // ];

        // $config = $this->_View->get('ControllerAction');
        // $table = $config['table'];

        // attach event for updating form buttons
        // $eventKey = 'ControllerAction.Model.onGetFormButtons';
        // $event = $this->dispatchEvent($table, $eventKey, null, [$buttons]);
        // end attach event

        $html = '';
        if ($buttons->count() > 0) {
            $html = '<div class="form-buttons"><div class="button-label"></div>';
            foreach ($buttons as $btn) {
                if (!array_key_exists('url', $btn)) {
                    $html .= $this->Form->button($btn['name'], $btn['attr']);
                } else {
                    $html .= $this->Html->link($btn['name'], $btn['url'], $btn['attr']);
                }
            }
            $html .= $this->_View->element('Page.cancel');
            // $html .= $this->Form->button('reload', ['id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden']);
            $html .= '</div>';
        }
        return $html;
    }

    public function getPaginatorButtons($type = 'prev')
    {
        $icon = array('prev' => '', 'next' => '');
        $html = $this->Paginator->{$type}(
            $icon[$type],
            array('tag' => 'li', 'escape' => false, 'url' => $this->getUrl(['action' => $this->request->param('action')], true)),
            null,
            array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a', 'escape' => false, 'url' => $this->getUrl(['action' => $this->request->param('action')], true))
        );
        return $html;
    }

    public function getPaginatorNumbers()
    {
        $html = $this->Paginator->numbers(array(
            'tag' => 'li',
            'currentTag' => 'a',
            'currentClass' => 'active',
            'separator' => '',
            'modulus' => 4,
            'first' => 2,
            'last' => 2,
            'ellipsis' => '<li><a>...</a></li>',
            'url' => $this->getUrl(['action' => $this->request->param('action')], true)
        ));
        return $html;
    }

    public function locale($locale = null)
    {
        if (!empty($locale)) {
            return I18n::locale($locale);
        } else {
            return I18n::locale();
        }
    }

    public function getTableHeaders()
    {
        $headers = [];
        $elements = $this->_View->get('elements');

        foreach ($elements as $field => $attr) {
            $label = $attr['label'];

            if ($attr['sortable']) {
                $url = $this->getUrl(['action' => $this->request->param('action')], true);
                $label = $this->Paginator->sort($field, $label, ['url' => $url]);
            }

            $headers[] = $label;
        }

        $headers[] = [__('Actions') => ['class' => 'cell-action']];
        return $headers;
    }

    public function getTableData()
    {
        $tableData = [];
        $data = $this->_View->get('data');
        $fields = $this->_View->get('elements');

        foreach ($data as $entity) {
            $row = [];
            foreach ($fields as $field => $attr) {
                $row[] = $this->getValue($entity, $attr);
            }
            $row[] = $this->_View->element('Page.actions', ['data' => $entity]);

            $tableData[] = $row;
        }
        return $tableData;
    }

    public function highlight($value)
    {
        $search = $this->getQueryString('search');
        if ($search !== false) {
            $value = Text::highlight($value, $search, ['html' => true]);
        }
        return $value;
    }

    public function getLimitOptions()
    {
        $paging = $this->_View->get('paging');
        $limitOptions = $paging['limitOptions'];

        $html = '';
        $limit = $this->getQueryString('limit') !== false ? $this->getQueryString('limit') : '';
        if (!empty($limitOptions)) {
            $html .= $this->Form->input('Search.limit', [
                'label' => false,
                'options' => $limitOptions,
                'value' => $limit,
                'templates' => ['select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>'],
                'onchange' => "Page.querystring('limit', this.value)"
            ]);
        }
        return $html;
    }

    public function getQueryString($key)
    {
        $querystring = $this->request->query('querystring');
        $value = false;
        if ($querystring) {
            $object = $this->decode($querystring);
            $value = array_key_exists($key, $object) ? $object[$key] : '';
        }
        return $value;
    }

    public function getUrl($route, $toArray = false)
    {
        $request = $this->request;
        $url = array_merge($route, $request->query);
        $this->mergeRequestParams($url);
        return $toArray ? $url : $this->Url->build($url);
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

    private function getValue($entity, $field)
    {
        $controlType = $field['controlType'];

        $array = $entity instanceof Entity ? $entity->toArray() : $entity;
        $data = Hash::flatten($array);
        $value = array_key_exists($field['key'], $data) ? $data[$field['key']] : '';

        if (array_key_exists('displayFrom', $field)) { // if displayFrom exists, always get value based on displayFrom
            $key = $field['displayFrom'];
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            }
        } else {
            $isDropdownType = $controlType == 'dropdown';
            $isOptionsExists = array_key_exists('options', $field);
            if ($isDropdownType && $isOptionsExists) {
                $options = $field['options'];
                $valueExistsInOptions = array_key_exists($value, $options);
                if ($valueExistsInOptions) {
                    $value = $options[$value];
                }
            }
        }

        $isDateTimeType = in_array($controlType, ['date', 'time']);
        $isStringType = in_array($controlType, ['string', 'textarea']);
        $hasDateTimeFormat = array_key_exists('format', $field);
        $valueIsNotEmpty = !empty($value);

        if ($isDateTimeType && $hasDateTimeFormat && $valueIsNotEmpty) {
            $valueIsDateObject = $value instanceof Date;
            if ($valueIsDateObject) {
                $value = $value->format($field['format']);
            } else {
                $value = date($field['format'], strtotime($value));
            }
        } elseif ($isStringType && $valueIsNotEmpty) {
            $value = $this->highlight($value);
        }
        return $value;
    }

    public function renderInputElements()
    {
        $html = '';
        $fields = $this->_View->get('elements');
        $data = $this->_View->get('data');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $controlType = $field['controlType'];
                if (method_exists($this, $controlType)) {
                    $html .= $this->$controlType($field, $data);
                } else {
                    Log::write('error', 'Missing control type implementation: ' . $controlType);
                }
            }
        } else {
            pr('There are no elements');
        }

        return $html;
    }

    public function renderViewElements($fields)
    {
        $html = '';

        $row = <<<EOT
<div class="row">
    <div class="col-xs-6 col-md-3 form-label">%s</div>
    <div class="form-input">%s</div>
</div>
EOT;

        $excludedTypes = ['hidden'];

        foreach ($fields as $field => $attr) {
            $controlType = $attr['controlType'];
            $isVisible = $attr['visible'];

            if (in_array($controlType, $excludedTypes) || $isVisible == false) {
                continue;
            }

            $label = $attr['label'];
            $value = '';
            if (array_key_exists('value', $attr['attributes'])) {
                $value = $attr['attributes']['value'];
            }

            if ($controlType == 'link' && array_key_exists('href', $attr['attributes'])) {
                $value = $this->Html->link($value, $attr['attributes']['href']);
            }

            $html .= sprintf($row, $label, $value);
        }
        return $html;
    }

    private function extractHtmlAttributes(array $field, $data)
    {
        $options = $field['attributes'];
        if (array_key_exists('name', $options)) {
            unset($options['name']);
        }

        if (array_key_exists('label', $field)) {
            $options['label'] = $field['label'];
        }

        if (array_key_exists('options', $field)) {
            $options['options'] = $field['options'];
        }
        return $options;
    }

    private function binary(array $field, $data)
    {
        $options = ['type' => 'file', 'class' => 'form-control', 'label' => false];
        $required = $field['attributes']['required'];
        $fileNameColumn = isset($field['fileNameColumn']) ? $field['fileNameColumn'] : 'file_name';
        $fileSizeLimit = isset($field['fileSizeLimit']) ? $field['fileSizeLimit'] : 1;
        $formatSupported = isset($field['supportedFileFormat']) ? $field['supportedFileFormat'] : ['jpeg', 'jpg', 'gif', 'png', 'rtf', 'txt', 'csv', 'pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods', 'key', 'pages', 'numbers'];
        $fileContent = '';
        if (is_resource($data[$field['key']])) {
            $streamedContent = stream_get_contents($data[$field['key']]);
            $fileContent = base64_encode($streamedContent);
            $fileContentSize = strlen($streamedContent);
        } else {
            $fileContent = isset($data[$field['key'].'_content']) ? $data[$field['key'].'_content'] : null;
            $fileContentSize = isset($data[$field['key'].'_file_size']) ? $data[$field['key'].'_file_size'] : null;
        }


        $comments = '';
        $fileSizeMessage = str_replace('%size', $fileSizeLimit, __('* File should not be larger than %size MB'));
        $extensionSupported = '';
        $fileFormatMessage = __('* Format Supported: ') . implode(', ', $formatSupported);
        foreach ($formatSupported as &$format) {
            $format = '\''.$format.'\'';
        }
        $extensionSupported = implode(', ', $formatSupported);
        $comments .= $fileSizeMessage . '<br/>' . $fileFormatMessage;
        $fileName = '';
        if ($data instanceof Entity) {
            $fileName = $data->offsetExists($fileNameColumn) ? $data->$fileNameColumn : null;
        } elseif (is_array($data)) {
            $fileName = isset($data[$fileNameColumn]) ? $data[$fileNameColumn] : null;
        }

        if ($required) {
            $options['required'] = 'required';
        }

        $alias = explode('.', $field['attributes']['name'])[0];

        $attr = [
            'id' => str_replace('.', '_', $field['attributes']['name']),
            'key' => $field['key'],
            'alias' => $alias,
            'name' => $field['attributes']['name'],
            'label' => $field['label'],
            'options' => $options,
            'required' => $required ? ' required' : '',
            'comments' => $comments ? $comments : '',
            'fileNameColumn' => $fileNameColumn,
            'fileName' => $fileName,
            'fileSizeLimit' => $fileSizeLimit,
            'fileContent' => $fileContent,
            'fileContentSize' => $fileContentSize,
            'extensionSupported' => $extensionSupported
        ];
        $this->includes['jasny']['include'] = true;
        return $this->_View->element('Page.file_upload', $attr);
    }

    private function string(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'string';

        $value = $this->Form->input($field['attributes']['name'], $options);
        return $value;
    }

    private function integer(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'number';
        $html = '';

        if (array_key_exists('disabled', $options) && array_key_exists('displayFrom', $field)) {
            $options['type'] = 'hidden';
            unset($options['disabled']);
            $value = $this->getValue($data, $field);
            $html .= $this->Form->input($field['key'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
        }

        $html .= $this->Form->input($field['attributes']['name'], $options);

        return $html;
    }

    private function float(array $field, $data)
    {
        return $this->integer($field, $data);
    }

    private function decimal(array $field, $data)
    {
        return $this->integer($field, $data);
    }

    private function textarea(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'textarea';

        return $this->Form->input($field['attributes']['name'], $options);
    }

    private function dropdown(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'select';

        if (array_key_exists('dependentOn', $field) && array_key_exists('params', $field)) {
            $options['dependent-on'] = $field['dependentOn'];
            $options['params'] = $field['params'];
        }

        return $this->Form->input($field['attributes']['name'], $options);
    }

    private function hidden(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'hidden';

        return $this->Form->input($field['attributes']['name'], $options);
    }

    private function date(array $field, $data)
    {
        $options = ['type' => 'text', 'class' => 'form-control', 'label' => false, 'error' => false];
        $required = isset($field['attributes']['required']) ? $field['attributes']['required'] : false;
        $value = isset($field['attributes']['value']) ? $field['attributes']['value'] : '';
        $disabled = isset($field['attributes']['disabled']) ? $field['attributes']['disabled'] : false;
        $dateOptions = [];

        if ($required) {
            $options['required'] = 'required';
        }

        if ($disabled) {
            $options['disabled'] = 'disabled';
        }

        $dateProperties = ['minDate' => 'startDate', 'maxDate' => 'endDate'];

        foreach ($dateProperties as $prop => $mapped) {
            if (array_key_exists($prop, $field)) {
                $propValue = $field[$prop];
                $dateOptions[$mapped] = implode('-', [$propValue['day'], $propValue['month'], $propValue['year']]);
            }
        }

        if (!empty($value)) {
            if ($value instanceof Date) {
                $options['value'] = $value->format('d-m-Y');
            } else {
                $options['value'] = date('d-m-Y', strtotime($value));
            }
        } else {
            if ($required) {
                $options['value'] = date('d-m-Y', time());
            }
        }

        $attr = [
            'id' => str_replace('.', '_', $field['attributes']['name']),
            'name' => $field['attributes']['name'],
            'label' => $field['label'],
            'options' => $options,
            'date_options' => $dateOptions,
            'required' => $required ? ' required' : ''
        ];

        // datepicker variable is used for initialising javascript in datepicker.ctp
        if (!$disabled) {
            if (!is_null($this->_View->get('datepicker'))) {
                $datepickers = $this->_View->get('datepicker');
                $datepickers[] = $attr;
                $this->_View->set('datepicker', $datepickers);
            } else {
                $this->_View->set('datepicker', [$attr]);
            }
        }

        $this->includes['datepicker']['include'] = true;

        return $this->_View->element('Page.date', $attr);
    }

    public function time(array $field, $data)
    {
        $value = '';

        $options = ['type' => 'text', 'class' => 'form-control', 'label' => false, 'error' => false];

        $_options = [
            'defaultTime' => false
        ];

        $required = isset($field['attributes']['required']) ? $field['attributes']['required'] : false;
        $disabled = isset($field['attributes']['disabled']) ? $field['attributes']['disabled'] : false;

        if (!isset($field['time_options'])) {
            $options['time_options'] = [];
        }
        if (!isset($field['default_time'])) {
            $options['default_time'] = true;
        }

        if ($disabled) {
            $options['disabled'] = 'disabled';
        }

        if (!isset($field['id'])) {
            $field['id'] = $field['attributes']['name'];
        }

        $options['time_options'] = array_merge($_options, $options['time_options']);

        if (($data instanceof Entity && $data->offsetExists($field['key'])) || (is_array($data) && isset($data[$field['key']])) && $data[$field['key']] instanceof Time) {
            $options['value'] = $data[$field['key']]->format('h:i A');
            $options['time_options']['defaultTime'] = $options['value'];
        } else {
            $options['value'] = date('h:i A', strtotime($data[$field['key']]));
            $options['time_options']['defaultTime'] = $data[$field['key']];
        }

        $attr = [
            'id' => str_replace('.', '_', $field['attributes']['name']),
            'name' => $field['attributes']['name'],
            'label' => $field['label'],
            'required' => $required ? ' required' : '',
            'options' => $options
        ];

        $options['id'] = $attr['id'];
        $options['name'] = $attr['name'];

        if (!is_null($this->_View->get('timepicker'))) {
            $timepickers = $this->_View->get('timepicker');
            $timepickers[] = $options;
            $this->_View->set('timepicker', $timepickers);
        } else {
            $this->_View->set('timepicker', [$options]);
        }
        $this->includes['timepicker']['include'] = true;
        $value = $this->_View->element('Page.time', $attr);

        return $value;
    }

    public function table(array $field, $data)
    {
        $html = '
            <div class="input clearfix">
                <label>%s</label>
                <div class="table-wrapper">
                    <div class="table-in-view">
                        <table class="table">
                            <thead>%s</thead>
                            <tbody>%s</tbody>
                        </table>
                    </div>
                </div>
            </div>
        ';

        $headers = $this->Html->tableHeaders($field['headers']);
        $cells = $this->Html->tableCells($field['cells']);

        $html = sprintf($html, $field['label'], $headers, $cells);
        return $html;
    }

    public function afterRender()
    {
        $this->includes();
    }
}