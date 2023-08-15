<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Model;

/**
 * @api
 * @since 100.1.0
 */
class DateResolver
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     * @since 100.1.0
     */
    protected $localeDate;

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     * @since 100.1.0
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->localeDate = $localeDate;
        $this->_storeManager = $storeManager;
    }

    /**
     * Retrieve configuration timezone
     *
     * @return string
     * @since 100.1.0
     */
    public function getConfigTimezone()
    {
        return $this->localeDate->getConfigTimezone();
    }

    /**
     * Retrieve store timezone from configuration
     *
     * @return string
     * @since 100.1.0
     */
    public function getConfigStoreTimezone()
    {
        return $this->localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore($this->_storeManager->getStore()->getId())->getCode()
        );
    }

    /**
     * Converts date to UTC.
     *
     * Converts date to UTC according to store timezone if $toUtc is true
     * or to store timezone if $toUtc is false
     *
     * @param string $date
     * @param bool $toUtc
     * @param null|string $format
     * @return string
     * @since 100.1.0
     */
    public function convertDate($date, $toUtc = true, $format = null)
    {
        if ($toUtc) {
            /** @phpstan-ignore-next-line */
            $dateObj = new \DateTime($date ?? 'now', new \DateTimeZone($this->getConfigTimezone()));
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
        } else {
            /** @phpstan-ignore-next-line */
            $dateObj = new \DateTime($date ?? 'now', new \DateTimeZone('UTC'));
            $dateObj->setTimezone(new \DateTimeZone($this->getConfigTimezone()));
        }

        if ($format) {
            return  $dateObj->format($format);
        }

        return  $dateObj->format('Y-m-d H:i:s');
    }
}
