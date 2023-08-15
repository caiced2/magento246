<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block;

/**
 * RMA Return Block
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaHelper = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Rma\Helper\Data $rmaHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_rmaHelper = $rmaHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _toHtml()
    {
        if ($this->_rmaHelper->isEnabled()) {
            return parent::_toHtml();
        } else {
            return '';
        }
    }
}
