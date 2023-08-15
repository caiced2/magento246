<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Block\Adminhtml\Sales\Order\View;

use Magento\Sales\Model\ConfigInterface;

/**
 * Class Buttons
 *
 * @api
 * @since 100.0.2
 */
class Buttons extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * @var \Magento\SalesArchive\Model\Config
     */
    protected $config;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ConfigInterface $salesConfig
     * @param \Magento\Sales\Helper\Reorder $reorderHelper
     * @param \Magento\SalesArchive\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        ConfigInterface $salesConfig,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \Magento\SalesArchive\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

    /**
     * Add "Move to Order Management" button
     *
     * @return void
     */
    protected function addMoveToArchiveButton()
    {
        $archiveUrl = $this->getUrl(
            'sales/archive/add',
            ['order_id' => $this->getOrder()->getId()]
        );
        $this->getToolbar()->addChild(
            'move_to_archive',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Move to Archive'),
                'onclick' => 'setLocation(\'' . $archiveUrl . '\')'
            ]
        );
    }

    /**
     * Add "Move to Order Management" button
     *
     * @return void
     */
    protected function addRestoreFromArchiveButton()
    {
        $restoreUrl = $this->getUrl(
            'sales/archive/remove',
            ['order_id' => $this->getOrder()->getId()]
        );
        $this->getToolbar()->addChild(
            'restore_from_archive',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Move to Order Management'),
                'onclick' => 'setLocation(\'' . $restoreUrl . '\')'
            ]
        );
    }

    /**
     * Add SalesArchive buttons on toolbar
     *
     * @return $this
     */
    public function addButtons()
    {
        if ($this->getOrder()->getIsArchived()
            && $this->_authorization->isAllowed('Magento_SalesArchive::remove')
        ) {
            $this->addRestoreFromArchiveButton();
        } elseif ($this->getOrder()->getIsMoveable() !== false
            && $this->config->isArchiveActive()
            && $this->_authorization->isAllowed('Magento_SalesArchive::add')
        ) {
            $this->addMoveToArchiveButton();
        }
        return $this;
    }
}
