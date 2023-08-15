<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Backup\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * @var \Magento\Support\Model\Backup
     */
    protected $backup;

    /**
     * Constructor
     *
     * @param \Magento\Support\Model\Backup $backup
     */
    public function __construct(\Magento\Support\Model\Backup $backup)
    {
        $this->backup = $backup;
    }

    /**
     * Get status options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->backup->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
