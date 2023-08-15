<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block;

use Magento\Framework\View\Element\Template;

class Generated extends \Magento\Framework\View\Element\Template
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Url $urlBuilder
     * @param array $data
     */
    public function __construct(Template\Context $context, \Magento\Framework\Url $urlBuilder, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_urlBuilder = $urlBuilder;
    }
}
