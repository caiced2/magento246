<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Block\Adminhtml\Invitation\View\Tab;

/**
 * Invitation view status history tab block
 *
 */
class History extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'view/tab/history.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Invitation History Factory
     *
     * @var \Magento\Invitation\Model\Invitation\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->_historyFactory = $historyFactory;
    }

    /**
     * Returns the Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Status History');
    }

    /**
     * Returns the Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Status History');
    }

    /**
     * Return whether the tab can be shown
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Return whether the tab is hidden
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Return Invitation for view
     *
     * @return \Magento\Invitation\Model\Invitation
     */
    public function getInvitation()
    {
        return $this->_coreRegistry->registry('current_invitation');
    }

    /**
     * Return invitation status history collection
     *
     * @return \Magento\Invitation\Model\ResourceModel\Invitation\History\Collection
     */
    public function getHistoryCollection()
    {
        return $this->_historyFactory->create()->getCollection()->addFieldToFilter(
            'invitation_id',
            $this->getInvitation()->getId()
        )->addOrder(
            'history_id'
        );
    }

    /**
     * Retrieve formatting time
     *
     * @param   string $date
     * @param   int $format
     * @param   bool $showDate
     * @return  string
     */
    public function formatTime($date = null, $format = \IntlDateFormatter::SHORT, $showDate = false)
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $this->_localeDate->formatDateTime(
            $date,
            $showDate ? $format : \IntlDateFormatter::NONE,
            $format
        );
    }
}
