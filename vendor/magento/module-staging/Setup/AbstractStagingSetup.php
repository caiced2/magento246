<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime as MageDateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Abstract setup class for create staging update for entities.
 */
abstract class AbstractStagingSetup
{
    /**
     * Update repository interface.
     *
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * Factory class for @see \Magento\Staging\Api\Data\UpdateInterface.
     *
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param UpdateInterfaceFactory $updateFactory
     * @param TimezoneInterface|null $timezone
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        UpdateInterfaceFactory $updateFactory,
        TimezoneInterface $timezone = null
    ) {
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
        $this->timezone = $timezone ?: ObjectManager::getInstance()->get(TimezoneInterface::class);
    }

    /**
     * Create staging update for entity.
     *
     * @param array $entity
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    protected function createUpdateForEntity(array $entity)
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($entity['name']);

        $fromDate = $entity['from_date'] ? $entity['from_date'] . ' 00:00:00' : 'now';
        $update->setStartTime($this->formatInUtc($fromDate));

        $fromDateInUtc = $this->dateInUtc($fromDate);
        if ($fromDateInUtc->getTimestamp() < strtotime('now')) {
            $update->setStartTime($this->formatInUtc('now +1 minutes'));
        }

        if ($entity['to_date']) {
            $update->setEndTime($this->formatInUtc($entity['to_date'] . ' 23:59:59'));
        }

        $isCampaign = isset($entity['is_campaign']) ? (bool)$entity['is_campaign'] : false;
        $update->setIsCampaign($isCampaign);

        $this->updateRepository->save($update);

        return $update;
    }

    /**
     * Get date in UTC time zone
     *
     * @param string $dateStr
     * @return \DateTime
     */
    private function dateInUtc(string $dateStr): \DateTime
    {
        $configTimeZone = new \DateTimeZone($this->timezone->getConfigTimezone());
        $date = new \DateTime($dateStr, $configTimeZone);

        return $date->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Format date in UTC time zone
     *
     * @param string $dateStr
     * @param string $format
     * @return string
     */
    private function formatInUtc(string $dateStr, string $format = MageDateTime::DATETIME_PHP_FORMAT): string
    {
        return $this->dateInUtc($dateStr)->format($format);

    }
}
