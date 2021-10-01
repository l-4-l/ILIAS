<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

/**
 * Class ilMailTemplateTableGUI
 * @author Nadia Ahmad <nahmad@databay.de>
 */
class ilMailTemplateTableGUI extends ilTable2GUI
{
    /** @var ilMailTemplateContext[] */
    protected array $contexts = [];
    protected bool $readOnly = false;
    protected Factory $uiFactory;
    protected Renderer $uiRenderer;
    /** @var ILIAS\UI\Component\Component[] */
    protected array $uiComponents = [];

    public function __construct(
        object $a_parent_obj,
        string $a_parent_cmd,
        Factory $uiFactory,
        Renderer $uiRenderer,
        bool $readOnly = false
    ) {
        $this->uiFactory = $uiFactory;
        $this->uiRenderer = $uiRenderer;
        $this->readOnly = $readOnly;

        $this->setId('mail_man_tpl');
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setTitle($this->lng->txt('mail_templates'));
        $this->setDefaultOrderDirection('ASC');
        $this->setDefaultOrderField('title');

        if (!$this->readOnly) {
            $this->addColumn('', '', '1%', true);
            $this->setSelectAllCheckbox('tpl_id');
            $this->addMultiCommand('confirmDeleteTemplate', $this->lng->txt('delete'));
        }

        $this->addColumn($this->lng->txt('title'), 'title', '30%');
        $this->addColumn($this->lng->txt('mail_template_context'), 'context', '20%');
        $this->addColumn($this->lng->txt('action'), '', '10%');

        $this->setRowTemplate('tpl.mail_template_row.html', 'Services/Mail');
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));

        $this->contexts = ilMailTemplateContextService::getTemplateContexts();
    }

    protected function formatCellValue(string $column, array $row) : string
    {
        if ('tpl_id' === $column) {
            return ilUtil::formCheckbox(false, 'tpl_id[]', $row[$column]);
        }
        if ('lang' === $column) {
            return $this->lng->txt('meta_l_' . $row[$column]);
        }
        if ($column === 'context') {
            if (isset($this->contexts[$row[$column]])) {
                $isDefaultSuffix = '';
                if ($row['is_default']) {
                    $isDefaultSuffix = $this->lng->txt('mail_template_default');
                }

                return implode('', [
                    $this->contexts[$row[$column]]->getTitle(),
                    $isDefaultSuffix,
                ]);
            }

            return $this->lng->txt('mail_template_orphaned_context');
        }

        return $row[$column];
    }

    protected function fillRow($a_set) : void
    {
        foreach ($a_set as $column => $value) {
            if ($column === 'tpl_id' && $this->readOnly) {
                continue;
            }

            $value = $this->formatCellValue($column, $a_set);
            $this->tpl->setVariable('VAL_' . strtoupper($column), $value);
        }

        $this->tpl->setVariable('VAL_ACTION', $this->formatActionsDropDown($a_set));
    }

    protected function formatActionsDropDown(array $row) : string
    {
        $this->ctrl->setParameter($this->getParentObject(), 'tpl_id', $row['tpl_id']);

        $buttons = [];

        if (count($this->contexts) > 0) {
            if (!$this->readOnly) {
                $buttons[] = $this->uiFactory
                    ->button()
                    ->shy(
                        $this->lng->txt('edit'),
                        $this->ctrl->getLinkTarget($this->getParentObject(), 'showEditTemplateForm')
                    );
            } else {
                $buttons[] = $this->uiFactory
                    ->button()
                    ->shy(
                        $this->lng->txt('view'),
                        $this->ctrl->getLinkTarget($this->getParentObject(), 'showEditTemplateForm')
                    );
            }
        }

        if (!$this->readOnly) {
            $deleteModal = $this->uiFactory
                ->modal()
                ->interruptive(
                    $this->lng->txt('delete'),
                    $this->lng->txt('mail_tpl_sure_delete_entry'),
                    $this->ctrl->getFormAction($this->getParentObject(), 'deleteTemplate')
                );

            $this->uiComponents[] = $deleteModal;

            $buttons[] = $this->uiFactory
                ->button()
                ->shy(
                    $this->lng->txt('delete'),
                    $this->ctrl->getLinkTarget($this->getParentObject(), 'confirmDeleteTemplate')
                )->withOnClick($deleteModal->getShowSignal());

            if ($row['is_default']) {
                $buttons[] = $this->uiFactory
                    ->button()
                    ->shy(
                        $this->lng->txt('mail_template_unset_as_default'),
                        $this->ctrl->getLinkTarget($this->getParentObject(), 'unsetAsContextDefault')
                    );
            } else {
                $buttons[] = $this->uiFactory
                    ->button()
                    ->shy(
                        $this->lng->txt('mail_template_set_as_default'),
                        $this->ctrl->getLinkTarget($this->getParentObject(), 'setAsContextDefault')
                    );
            }
        }

        $this->ctrl->setParameter($this->getParentObject(), 'tpl_id', null);

        $dropDown = $this->uiFactory
            ->dropdown()
            ->standard($buttons)
            ->withLabel($this->lng->txt('actions'));

        return $this->uiRenderer->render([$dropDown]);
    }

    public function getHTML() : string
    {
        return parent::getHTML() . $this->uiRenderer->render($this->uiComponents);
    }
}
