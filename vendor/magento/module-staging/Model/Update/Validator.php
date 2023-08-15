<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Validate a staging update entity data.
 */
class Validator
{
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * Validator constructor.
     *
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(DateTimeFactory $dateTimeFactory)
    {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Validate creating update.
     *
     * @param UpdateInterface $entity
     * @return void
     * @throws ValidatorException
     */
    public function validateCreate(UpdateInterface $entity)
    {
        $this->validateUpdate($entity);
        $this->validateStartTimeNotPast($entity);
    }

    /**
     * Validate updating.
     *
     * @param UpdateInterface $entity
     * @return void
     * @throws ValidatorException
     */
    public function validateUpdate(UpdateInterface $entity)
    {
        if (!$entity->getName()) {
            throw new ValidatorException(__('The Name for Future Update needs to be selected. Select and try again.'));
        }

        if (!$entity->getStartTime()) {
            throw new ValidatorException(
                __('The Start Time for Future Update needs to be selected. Select and try again.')
            );
        }

        $this->validateEndTime($entity);
    }

    /**
     * Checks start/end time year.
     *
     * @param UpdateInterface $entity
     * @return void
     * @throws ValidatorException
     */
    private function validateMaxTime(UpdateInterface $entity) : void
    {
        $currentDateTime = new \DateTime();
        $diff = abs(VersionManager::MAX_VERSION - $currentDateTime->getTimestamp());
        $years = ceil($diff / (365*60*60*24));
        if (strtotime($entity->getStartTime()) > VersionManager::MAX_VERSION) {
            throw new ValidatorException(
                __(
                    "The Future Update Start Time is invalid. It can't be later than current time + %1 years.",
                    $years
                )
            );
        }

        if ($entity->getEndTime() && strtotime($entity->getEndTime()) > VersionManager::MAX_VERSION) {
            throw new ValidatorException(
                __(
                    "The Future Update End Time is invalid. It can't be later than current time + %1 years.",
                    $years
                )
            );
        }
    }

    /**
     * Validate that start time not past.
     *
     * @param UpdateInterface $entity
     * @return void
     * @throws ValidatorException
     */
    private function validateStartTimeNotPast(UpdateInterface $entity)
    {
        $currentDateTime = new \DateTime();
        if (strtotime($entity->getStartTime()) < $currentDateTime->getTimestamp()) {
            throw new ValidatorException(
                __("The Future Update Start Time is invalid. It can't be earlier than the current time.")
            );
        }
    }

    /**
     * Validate end time.
     *
     * @param UpdateInterface $entity
     * @return void
     * @throws ValidatorException
     */
    private function validateEndTime(UpdateInterface $entity)
    {
        $currentDateTime = new \DateTime();
        $startTime = $entity->getStartTime() ?? 'now';
        $endTime = $entity->getEndTime() ?? 'now';
        $startTimeGreaterEndTime = strtotime($startTime) >= strtotime($endTime);
        if ($entity->getEndTime() && $startTimeGreaterEndTime) {
            throw new ValidatorException(
                __("The Future Update End Time is invalid. It can't be the same time or earlier than the current time.")
            );
        }

        $endTimeLessCurrentTime = strtotime($endTime) <= $currentDateTime->getTimestamp();
        if ($entity->getEndTime() && $endTimeLessCurrentTime) {
            throw new ValidatorException(
                __("The Future Update End Time is invalid. It can't be earlier than the current time.")
            );
        }
        $this->validateMaxTime($entity);
    }
}
