<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\ContainerInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @api
 * @since 100.1.0
 */
class Toolbar extends Template implements ContainerInterface
{
    /**
     * @var ButtonList
     * @since 100.1.0
     */
    protected $buttonList;

    /**
     * @var ToolbarInterface
     * @since 100.1.0
     */
    protected $toolbar;

    /**
     * Container constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->buttonList = $context->getButtonList();
        $this->toolbar = $context->getButtonToolbar();
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function updateButton($buttonId, $key, $data)
    {
        $this->buttonList->update($buttonId, $key, $data);
        return $this;
    }

    /**
     * Create add button and grid blocks
     *
     * @return AbstractBlock
     * @since 100.1.0
     */
    protected function _prepareLayout()
    {
        $params = $this->hasData('requestFieldName')
            ? [$this->getData('requestFieldName') => $this->getRequest()->getParam($this->getData('requestFieldName'))]
            : [];
        $modalPath = $this->hasData('modalPath') ? $this->getData('modalPath') : '';
        $loaderPath = $this->hasData('loaderPath') ? $this->getData('loaderPath') : '';

        $this->buttonList->add(
            'staging_update_new',
            [
                'label' => __('Schedule New Update'),
                'class' => 'action action-secondary',
                'region' => 'staging.schedule.title',
                'data_attribute' => [
                    'mage-init' => [
                        'Magento_Ui/js/form/button-adapter' => [
                            'actions' => [
                                [
                                    'targetName' => $modalPath,
                                    'actionName' => 'openModal',
                                ],
                                [
                                    'targetName' => $loaderPath,
                                    'actionName' => 'destroyInserted',
                                ],
                                [
                                    'targetName' => $loaderPath,
                                    'actionName' => 'render',
                                    'params' => [
                                        $params
                                    ],
                                ],
                            ],
                        ]
                    ],
                ]
            ]
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        return parent::_prepareLayout();
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar')
    {
        $this->buttonList->add($buttonId, $data, $level, $sortOrder, $region);
        return $this;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function removeButton($buttonId)
    {
        $this->buttonList->remove($buttonId);
        return $this;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function canRender(\Magento\Backend\Block\Widget\Button\Item $item)
    {
        return !$item->isDeleted();
    }
}
