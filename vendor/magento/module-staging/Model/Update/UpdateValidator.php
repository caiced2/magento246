<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class UpdateValidator need for validation in the editing process for Update
 */
class UpdateValidator
{
    /**
     * Check if EndTime attribute was changed
     *
     * Checks whether the changed attribute and returns a boolean value.
     *
     * @param string $entityEndTime
     * @param array $stagingData
     * @return bool
     * @see \Magento\Staging\Model\Entity\Update\Action\Save\SaveAction
     */
    public function isEndTimeChanged($entityEndTime, array $stagingData)
    {
        return strtotime($entityEndTime) !== strtotime($stagingData['end_time']);
    }

    /**
     * Validate startTime attribute of Update was changed correctly
     *
     * Check if Update already started, then decline editing.
     *
     * @param UpdateInterface $update
     * @param array $stagingData
     * @throws LocalizedException if startTime attribute of started campaign was changed incorrect
     * @return void
     */
    public function validateUpdateStarted(UpdateInterface $update, array $stagingData)
    {
        /** @var \DateTime $currentDateTime */
        $currentDateTime = new \DateTime();
        $startTime = strtotime($stagingData['start_time']);
        $updateStartTime = $update->getStartTime() ? strtotime($update->getStartTime()) : 0;

        if ($updateStartTime < $currentDateTime->getTimestamp() && $updateStartTime !== $startTime) {
            throw new LocalizedException(
                __("The Start Time of this Update cannot be changed. It's been already started.")
            );
        }
    }

    /**
     * Validate input parameters
     *
     * Check if all input data are correct and all required params are present.
     *
     * @param array $params
     * @return void
     * @throws \InvalidArgumentException if input parameters are empty or incorrect
     */
    public function validateParams(array $params)
    {
        foreach (['stagingData', 'entityData'] as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The required parameter is "%1". Set parameter and try again.', $requiredParam)
                );
            }
            if (!is_array($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The "%1" parameter is invalid. Verify the parameter and try again.', $requiredParam)
                );
            }
        }
    }
}
