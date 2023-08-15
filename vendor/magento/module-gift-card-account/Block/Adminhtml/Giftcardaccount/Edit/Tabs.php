<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('giftcardaccount_info_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Gift Card Account'));
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'info',
            [
                'label' => __('Information'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab\Info::class
                )->initForm()->toHtml(),
                'active' => true
            ]
        );

        $this->addTab(
            'send',
            [
                'label' => __('Send Gift Card'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab\Send::class
                )->initForm()->toHtml()
            ]
        );

        $model = $this->_coreRegistry->registry('current_giftcardaccount');
        if ($model->getId()) {
            $this->addTab(
                'history',
                [
                    'label' => __('History'),
                    'content' => $this->getLayout()->createBlock(
                        \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab\History::class
                    )->toHtml()
                ]
            );
        }

        return parent::_beforeToHtml();
    }
}
