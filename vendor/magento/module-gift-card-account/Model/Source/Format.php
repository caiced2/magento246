<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Source;

class Format extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return list of gift card account code formats
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            \Magento\GiftCardAccount\Model\Pool::CODE_FORMAT_ALPHANUM => __('Alphanumeric'),
            \Magento\GiftCardAccount\Model\Pool::CODE_FORMAT_ALPHA => __('Alphabetical'),
            \Magento\GiftCardAccount\Model\Pool::CODE_FORMAT_NUM => __('Numeric')
        ];
    }

    /**
     * Return list of gift card account code formats as options array.
     * If $addEmpty true - add empty option
     *
     * @param boolean $addEmpty
     * @return array
     */
    public function toOptionArray($addEmpty = false)
    {
        $result = [];

        if ($addEmpty) {
            $result[] = ['value' => '', 'label' => ''];
        }

        foreach ($this->getOptions() as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];
        }

        return $result;
    }
}
