<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block;

/**
 * Check result block for a Giftcardaccount
 *
 * @api
 * @since 100.0.2
 */
class Check extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current card instance from registry
     *
     * @return \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    public function getCard()
    {
        return $this->_coreRegistry->registry('current_giftcardaccount');
    }

    /**
     * Check whether a gift card account code is provided in request
     *
     * @return string
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     */
    public function getCode()
    {
        return $this->getRequest()->getParam('giftcard-code', '');
    }

    /**
     * Get formatted expiration date
     *
     * @return string
     */
    public function getExpirationDate()
    {
        $date = new \DateTime(
            $this->getCard()->getDateExpires(),
            new \DateTimeZone($this->_localeDate->getConfigTimezone())
        );
        return parent::formatDate($date);
    }

    /**
     * @inheritDoc
     * @since 101.0.5
     */
    protected function _toHtml()
    {
        $this->setData(
            'error_message',
            $this->_coreRegistry->registry(
                'current_giftcardaccount_check_error'
            )
        );

        return parent::_toHtml();
    }
}
