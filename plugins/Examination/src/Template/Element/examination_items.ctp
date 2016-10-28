<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Code') ?></th>
                <th><?= __('Name') ?></th>
                <th><?= __('Weight') ?></th>
                <th><?= __('Grading Type') ?></th>
                <th><?= __('Date') ?></th>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time') ?></th>
            </thead>
            <?php if (isset($data['examination_items'])) : ?>
                <tbody>
                    <?php foreach ($data['examination_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code ?></td>
                            <td><?= $item->education_subject->name ?></td>
                            <td><?= $item->weight ?></td>
                            <td><?= $item->examination_grading_type->name ?></td>
                            <td><?= !is_null($item->examination_date) ? $item->examination_date->format('d-m-Y') : '' ?></td>
                            <td><?= !is_null($item->start_time) ? $item->start_time->format('H:i A') : '' ?></td>
                            <td><?= !is_null($item->end_time) ? $item->end_time->format('H:i A') : '' ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-in-view">
                    <table class="table">
                        <thead>
                            <th><?= __('Code') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Weight') ?></th>
                            <th><?= __('Grading Type') ?></th>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                            <th class="cell-delete"></th>
                        </thead>
                        <?php if (isset($data['examination_items'])) : ?>
                            <tbody>
                                <?php foreach ($data['examination_items'] as $i => $item) : ?>
                                    <?php
                                        $fieldPrefix = "$alias.examination_items.$i";
                                        $joinDataPrefix = $fieldPrefix . '._joinData';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                                if ($ControllerAction['action'] == 'edit') {
                                                    echo $this->Form->hidden("$fieldPrefix.id");
                                                }

                                                echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item['education_subject_id']]);
                                                echo $item->education_subject->code;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                echo $item->education_subject->name;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                echo $this->Form->input("$fieldPrefix.weight", [
                                                    'type' => 'float',
                                                    'label' => false,
                                                    'onblur' => "return utility.checkDecimal(this, 2);",
                                                    'onkeypress' => "return utility.floatCheck(event)",
                                                ]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $emptySelect = '-- ' . __('Select') . ' --';
                                                echo $this->Form->input("$fieldPrefix.examination_grading_type_id", [
                                                    'type' => 'select',
                                                    'label' => false,
                                                    'empty' => $emptySelect,
                                                    'options' => $examinationGradingTypeOptions
                                                ]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_date'] = false;
                                                $attr['class'] = 'margin-top-10 no-margin-bottom';
                                                $attr['field'] = 'examination_date';
                                                $attr['fieldName'] = "$fieldPrefix.examination_date";
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'date_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->date('edit', $item, $attr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_time'] = false;
                                                $attr['class'] = 'no-margin-bottom';
                                                $attr['field'] = 'start_time';
                                                $attr['fieldName'] = "$fieldPrefix.start_time";
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'start_time_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->time('edit', $item, $attr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_time'] = false;
                                                $attr['class'] = 'no-margin-bottom';
                                                $attr['field'] = 'end_time';
                                                $attr['fieldName'] = "$fieldPrefix.end_time";
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'end_time_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->time('edit', $item, $attr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                if ($ControllerAction['action'] == 'add') {
                                                    echo "<button onclick='jsTable.doRemove(this); SurveyForm.updateSection();' aria-expanded='true' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa fa-trash'></i>&nbsp;<span>Delete</span></button>";
                                                }
                                            ?>
                                        </td>

                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        <?php endif ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>