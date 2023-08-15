<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Attribute\Type;

/**
 * @codeCoverageIgnore
 */
class Date extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var string
     */
    protected $_template = 'edit/type/date.phtml';

    /**
     * Select element for choosing attribute type
     *
     * @return string
     */
    public function getDateFormatSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            [
                'id' => '<%- data.prefix %>_attribute_<%- data.id %>_date_format',
                'class' => 'select global-scope',
            ]
        )->setName(
            'attributes[<%- data.prefix %>][<%- data.id %>][date_format]'
        )->setOptions(
            $this->getDateFormatOptions()
        );

        return $select->getHtml();
    }

    /**
     * Return array of date formats
     *
     * @return array
     */
    public function getDateFormatOptions()
    {
        return [
            ['value' => \IntlDateFormatter::SHORT, 'label' => __('Short')],
            ['value' => \IntlDateFormatter::MEDIUM, 'label' => __('Medium')],
            ['value' => \IntlDateFormatter::LONG, 'label' => __('Long')],
            ['value' => \IntlDateFormatter::FULL, 'label' => __('Full')]
        ];
    }
}
