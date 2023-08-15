<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma;

/**
 * Admin RMA edit.
 *
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Variable to store RMA instance
     *
     * @var null|\Magento\Rma\Model\Rma
     */
    protected $_rma = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize RMA edit page. Set management buttons
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_controller = 'adminhtml_rma';
        $this->_blockGroup = 'Magento_Rma';

        parent::_construct();

        if (!$this->getRma()) {
            return;
        }
        $statusIsClosed = in_array(
            $this->getRma()->getStatus(),
            [
                \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED,
                \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED
            ]
        );

        if (!$statusIsClosed) {
            $this->buttonList->add(
                'save_and_edit_button',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                100
            );

            $this->buttonList->add(
                'close',
                [
                    'label' => __('Close'),
                    'class' => 'close',
                    'onclick' => 'confirmSetLocation(\'' . __(
                        'Are you sure you want to close this returns request?'
                    ) . '\', \'' . $this->getCloseUrl() . '\')'
                ]
            );
        } else {
            $this->buttonList->remove('save');
            $this->buttonList->remove('reset');
        }

        $this->buttonList->add(
            'print',
            [
                'label' => __('Print'),
                'class' => 'print',
                'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
            ],
            101
        );

        $this->buttonList->remove('delete');
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        $referer = $this->getRequest()->getServer('HTTP_REFERER', '');

        if (strpos($referer, 'sales/order') !== false) {
            return $this->getUrl(
                'sales/order/view/',
                ['order_id' => $this->getRma()->getOrderId(), 'active_tab' => 'order_rma']
            );
        } elseif (strpos($referer, 'customer') !== false) {
            return $this->getUrl(
                'customer/index/edit/',
                ['id' => $this->getRma()->getCustomerId(), 'active_tab' => 'customer_edit_tab_rma']
            );
        } else {
            return parent::getBackUrl();
        }
    }

    /**
     * Declare rma instance
     *
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function getRma()
    {
        if ($this->_rma === null) {
            $this->_rma = $this->_coreRegistry->registry('current_rma');
        }
        return $this->_rma;
    }

    /**
     * Get header text for RMA edit page
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeaderText()
    {
        if ($this->getRma()->getId()) {
            return __('Return #%1 - %2', (int) $this->getRma()->getIncrementId(), $this->getRma()->getStatusLabel());
        }

        return '';
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save', ['rma_id' => $this->getRma()->getId()]);
    }

    /**
     * Get print RMA action URL
     *
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('adminhtml/*/print', ['rma_id' => $this->getRma()->getId()]);
    }

    /**
     * Get close RMA action URL
     *
     * @return string
     */
    public function getCloseUrl()
    {
        return $this->getUrl('adminhtml/*/close', ['entity_id' => $this->getRma()->getId()]);
    }
}
