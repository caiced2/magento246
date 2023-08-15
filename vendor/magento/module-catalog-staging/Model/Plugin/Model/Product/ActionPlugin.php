<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\Plugin\Model\Product;

use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class ActionPlugin
 *
 * @package Magento\CatalogStaging\Model\Plugin\Model\Product
 */
class ActionPlugin
{
    /** @var TimezoneInterface */
    private $localeDate;

    /** @var array */
    private $attributeCodes;

    /** @var VersionManager */
    private $versionManager;

    /**
     * ActionPlugin constructor.
     * @param VersionManager $versionManager
     * @param TimezoneInterface $localeDate
     * @param array $attrCodes
     */
    public function __construct(VersionManager $versionManager, TimezoneInterface $localeDate, array $attrCodes = [])
    {
        $this->versionManager = $versionManager;
        $this->localeDate = $localeDate;
        $this->attributeCodes = $attrCodes;
    }

    /**
     * Updates attributes
     *
     * @param Action $subject
     * @param array $productIds
     * @param array $attrData
     * @param mixed $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateAttributes(Action $subject, $productIds, $attrData, $storeId): array
    {
        return [
            $productIds,
            $this->updateDatetimeAttributes($attrData),
            $storeId
        ];
    }

    /**
     * Update datetime attributes
     *
     * @param array $attrData
     * @return array
     */
    private function updateDatetimeAttributes(array $attrData): array
    {
        foreach ($this->attributeCodes as $attrCode => $endAttrCode) {
            if (isset($attrData[$attrCode]) && !empty($attrData[$attrCode])) {
                $attrData = $this->updateAttributeValues($attrData, $attrCode, $endAttrCode);
            }
        }
        return $attrData;
    }

    /**
     * Update attributes
     *
     * @param array $attrData
     * @param string $attrCode
     * @param string $endAttrCode
     * @return array
     */
    private function updateAttributeValues(array $attrData, $attrCode, $endAttrCode): array
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $version */
        $version = $this->versionManager->getCurrentVersion();

        if ($version->getStartTime()) {
            $attrData[$attrCode] = $this->localeDate
                ->date($version->getStartTime())
                ->format(DateTime::DATETIME_PHP_FORMAT);
        } else {
            $attrData[$attrCode] = $this->localeDate
                ->date()
                ->format(DateTime::DATETIME_PHP_FORMAT);
        }

        if ($version->getEndTime()) {
            $attrData[$endAttrCode] = $this->localeDate
                ->date($version->getEndTime())
                ->format(DateTime::DATETIME_PHP_FORMAT);
        } else {
            $attrData[$endAttrCode] = null;
        }

        return $attrData;
    }
}
