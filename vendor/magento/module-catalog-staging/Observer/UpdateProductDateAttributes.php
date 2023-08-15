<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Observer to update datetime attributes related to product entity.
 *
 * Specified attributes disabled on product form for Staging, but should be synchronized with update entity date range.
 * This needs to be moved to a plugin as soon as we replace with repository in catalog save controller
 */
class UpdateProductDateAttributes implements ObserverInterface
{
    /**
     * List of start date attributes related to product entity
     *
     * @var array
     */
    private static $startDateKeys = [
        'news_from_date',
        'special_from_date',
        'custom_design_from',
    ];

    /**
     * List of end date attributes related to product entity
     *
     * @var array
     */
    private static $endDateKeys = [
        'news_to_date',
        'special_to_date',
        'custom_design_to',
    ];

    /**
     * List of date attributes
     *
     * @var array
     */
    private static $dateKeys = [
        'news_from_date' => 'is_new',
        'news_to_date' => 'is_new'
    ];

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * AfterProductRepositorySave constructor.
     * @param VersionManager $versionManager
     * @param TimezoneInterface $localeDate
     * @param DateTimeFactory|null $dateTimeFactory
     */
    public function __construct(
        VersionManager $versionManager,
        TimezoneInterface $localeDate,
        DateTimeFactory $dateTimeFactory = null
    ) {
        $this->versionManager = $versionManager;
        $this->localeDate = $localeDate;
        $this->dateTimeFactory = $dateTimeFactory ?: ObjectManager::getInstance()->get(DateTimeFactory::class);
    }

    /**
     * Set start date and end date for datetime product attributes
     *
     * The method gets object with \Magento\Catalog\Api\Data\ProductInterface interface and updates datetime
     * attributes of this object ("start date" attributes: news_from_date, special_from_date, custom_design_from;
     *  "end date" attributes: news_to_date, special_to_date, custom_design_to).
     *
     * In case when Magento has any staging updates, then "start date" and "end date" product attributes
     * will be updated with current staging version start time and end time respectively.
     *
     * In case when Magento has not yet any staging updates, start date product attributes will be updated
     * in according with states of is_new or news_from_date attributes. If is_new attribute is set,
     * then "start date" product attributes will be updated with current locale date. If is_new attribute is not set,
     * then "start date" product attributes will be updated using the value from persisted news_from_date attribute.
     * The value for "end date" product attributes will be updated with NULL.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        $createdIn = $product->getData('created_in');
        $updatedIn = $product->getData('updated_in');

        if ($createdIn && $updatedIn) {
            if ($createdIn > VersionManager::MIN_VERSION) {
                $dateTime = $this->dateTimeFactory->create()->setTimestamp($createdIn);
                $localStartTime = $this->localeDate->date($dateTime);
                $this->setDateTime(
                    $product,
                    self::$startDateKeys,
                    $localStartTime->format(DateTime::DATETIME_PHP_FORMAT)
                );
            } else {
                if ($product->getData('is_new')) {
                    $date = $product->getData('news_from_date') ??
                        $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT);
                    $this->setDateTime($product, self::$startDateKeys, $date);
                } else {
                    $this->setDateTime($product, self::$startDateKeys, null);
                }
            }

            if ($updatedIn < VersionManager::MAX_VERSION) {
                $dateTime = $this->dateTimeFactory->create()->setTimestamp($updatedIn);
                $localEndTime = $this->localeDate->date($dateTime);
                $this->setDateTime($product, self::$endDateKeys, $localEndTime->format(DateTime::DATETIME_PHP_FORMAT));
            } else {
                $date = $product->getData('is_new') && $product->getData('news_to_date') ?
                    $product->getData('news_to_date') : null;
                $this->setDateTime($product, self::$endDateKeys, $date);
            }
        }
    }

    /**
     * Update product datetime attributes with new datetime value
     *
     * The method gets object with \Magento\Catalog\Api\Data\ProductInterface interface, "keys" array with
     * product datetime attributes names (this attributes will be updated) and datetime value to update attributes.
     *
     * In case when is_new attribute value equal to '1' or when is_new attribute value is NULL
     * and product datetime attribute value is stored in database,
     * product datetime attributes will be updated with given datetime value.
     *
     * In other cases product datetime attributes will be updated with NULL.
     *
     * @param ProductInterface $product
     * @param array $keys
     * @param string $time
     * @return void
     */
    private function setDateTime(ProductInterface $product, array $keys, $time)
    {
        foreach ($keys as $key) {
            if (!isset(self::$dateKeys[$key])) {
                continue;
            }

            if ($product->getData(self::$dateKeys[$key]) === null) {
                if (!$product->getData($key)) {
                    $time = null;
                }
            } elseif ($product->getData(self::$dateKeys[$key]) === '0') {
                $time = null;
            }

            $product->setData($key, $time);
        }
    }
}
