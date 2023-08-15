<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Attribute\Type;

/**
 * @codeCoverageIgnore
 */
class Country extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $sourceYesNo;

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'edit/type/country.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesNo
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesNo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sourceYesNo = $sourceYesNo;
    }

    /**
     * Select element for choosing show region option
     *
     * @return string
     */
    public function getRegionShowSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            [
                'id' => '<%- data.prefix %>_attribute_<%- data.id %>_show_region',
                'class' => 'select global-scope',
            ]
        )->setName(
            'attributes[<%- data.prefix %>][<%- data.id %>][show_region]'
        )->setOptions(
            $this->sourceYesNo->toOptionArray()
        );

        return $select->getHtml();
    }
}
