<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Block\Adminhtml\Widget\Grid\Column\Renderer;

/**
 * Column renderer for customer id
 */
class Id extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Url Builder
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\UrlInterface $url
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\UrlInterface $url,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_urlBuilder = $url;
    }

    /**
     * Render customer id linked to its account edit page
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $customerId = $this->escapeHtml($row->getData($this->getColumn()->getIndex()));
        return '<a href="' . $this->_urlBuilder->getUrl(
            'customer/index/edit',
            ['id' => $customerId]
        ) . '">' . $customerId . '</a>';
    }
}
