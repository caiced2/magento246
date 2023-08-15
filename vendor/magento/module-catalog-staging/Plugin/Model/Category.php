<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Plugin\Model;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Plugin to category model
 */
class Category
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Convert design dates to current time zone
     *
     * @param CategoryModel $category
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCustomDesignDate(CategoryModel $category, array $result): array
    {
        if (!empty($result['from'])) {
            $result['from'] = $this->timezone->scopeDate(null, $result['from'], true)
                ->format(DateTime::DATETIME_PHP_FORMAT);
        }
        if (!empty($result['to'])) {
            $result['to'] = $this->timezone->scopeDate(null, $result['from'], true)
                ->format(DateTime::DATETIME_PHP_FORMAT);
        }

        return $result;
    }
}
