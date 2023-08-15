<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation status source
 *
 */
namespace Magento\Invitation\Model\Source\Invitation;

class Status
{
    /**
     * Return list of invitation statuses as options
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            \Magento\Invitation\Model\Invitation\Status::STATUS_NEW => __('Not Sent'),
            \Magento\Invitation\Model\Invitation\Status::STATUS_SENT => __('Sent'),
            \Magento\Invitation\Model\Invitation\Status::STATUS_ACCEPTED => __('Accepted'),
            \Magento\Invitation\Model\Invitation\Status::STATUS_CANCELED => __('Discarded')
        ];
    }

    /**
     * Return list of invitation statuses as options array.
     * If $useEmpty eq to true, add empty option
     *
     * @param boolean $useEmpty
     * @return array
     */
    public function toOptionsArray($useEmpty = false)
    {
        $result = [];

        if ($useEmpty) {
            $result[] = ['value' => '', 'label' => ''];
        }
        foreach ($this->getOptions() as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];
        }

        return $result;
    }

    /**
     * Return option text by value
     *
     * @param string $option
     * @return string
     */
    public function getOptionText($option)
    {
        $options = $this->getOptions();
        if (isset($options[$option])) {
            return $options[$option];
        }

        return null;
    }
}
