<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Banner\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validator\HTML\WYSIWYGValidatorInterface;

/**
 * Enterprise banner model
 *
 * @method string getName()
 * @method \Magento\Banner\Model\Banner setName(string $value)
 * @method int getIsEnabled()
 * @method \Magento\Banner\Model\Banner setIsEnabled(int $value)
 * @method \Magento\Banner\Model\Banner setTypes(string $value)
 * @api
 * @since 100.0.2
 */
class Banner extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Representation value of enabled banner
     *
     */
    const STATUS_ENABLED = 1;

    /**
     * Representation value of disabled banner
     *
     */
    const STATUS_DISABLED = 0;

    /**
     * Representation value of disabled banner
     *
     */
    const CACHE_TAG = 'banner';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magento_banner';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getBanner() in this case
     *
     * @var string
     */
    protected $_eventObject = 'banner';

    /**
     * Store banner contents per store view
     *
     * @var array
     */
    protected $_contents = [];

    /**
     * @var WYSIWYGValidatorInterface
     */
    private $wysiwygValidator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param WYSIWYGValidatorInterface|null $wysiwygValidator
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        ?WYSIWYGValidatorInterface $wysiwygValidator = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->wysiwygValidator = $wysiwygValidator
            ?? ObjectManager::getInstance()->get(WYSIWYGValidatorInterface::class);
    }

    /**
     * Initialize banner model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Banner\Model\ResourceModel\Banner::class);
    }

    /**
     * Retrieve array of sales rules id's for banner
     *
     * @return array
     */
    public function getRelatedSalesRule()
    {
        if (!$this->getId()) {
            return [];
        }
        $array = $this->getData('related_sales_rule');
        if ($array === null) {
            $array = $this->getResource()->getRelatedSalesRule($this->getId());
            $this->setData('related_sales_rule', $array);
        }
        return $array;
    }

    /**
     * Retrieve array of catalog rules id's for banner
     *
     * @return array
     */
    public function getRelatedCatalogRule()
    {
        if (!$this->getId()) {
            return [];
        }
        $array = $this->getData('related_catalog_rule');
        if ($array === null) {
            $array = $this->getResource()->getRelatedCatalogRule($this->getId());
            $this->setData('related_catalog_rule', $array);
        }
        return $array;
    }

    /**
     * Get all existing banner contents
     *
     * @return array|null
     */
    public function getStoreContents()
    {
        if (!$this->hasStoreContents()) {
            $contents = $this->_getResource()->getStoreContents($this->getId());
            $this->setStoreContents($contents);
        }
        return $this->_getData('store_contents');
    }

    /**
     * Get banners ids by catalog rule id
     *
     * @param int $ruleId
     * @return array|null
     */
    public function getRelatedBannersByCatalogRuleId($ruleId)
    {
        if (!$this->hasRelatedCatalogRuleBanners()) {
            $banners = $this->_getResource()->getRelatedBannersByCatalogRuleId($ruleId);
            $this->setRelatedCatalogRuleBanners($banners);
        }
        return $this->_getData('related_catalog_rule_banners');
    }

    /**
     * Get banners ids by sales rule id
     *
     * @param int $ruleId
     * @return array|null
     */
    public function getRelatedBannersBySalesRuleId($ruleId)
    {
        if (!$this->hasRelatedSalesRuleBanners()) {
            $banners = $this->_getResource()->getRelatedBannersBySalesRuleId($ruleId);
            $this->setRelatedSalesRuleBanners($banners);
        }
        return $this->_getData('related_sales_rule_banners');
    }

    /**
     * Save banner content, bind banner to catalog and sales rules after banner save
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->hasStoreContents()) {
            $this->_getResource()->saveStoreContents(
                $this->getId(),
                $this->getStoreContents(),
                $this->getStoreContentsNotUse()
            );
        }
        if ($this->hasBannerCatalogRules()) {
            $this->_getResource()->saveCatalogRules($this->getId(), $this->getBannerCatalogRules());
        }
        if ($this->hasBannerSalesRules()) {
            $this->_getResource()->saveSalesRules($this->getId(), $this->getBannerSalesRules());
        }
        return parent::afterSave();
    }

    /**
     * Validate some data before saving
     *
     * @return $this
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeSave()
    {
        if ('' == trim($this->getName())) {
            throw new LocalizedException(__('Please enter a name.'));
        }

        $bannerContents = $this->getStoreContents();
        $containsCurrentId = false;
        $originalContents = $this->getId() ? $this->_getResource()->getStoreContents($this->getId()) : [];

        foreach ($bannerContents as $storeId => $content) {
            $content = trim((string)$content);
            if ($content) {
                if (!array_key_exists($storeId, $originalContents) || $content !== trim($originalContents[$storeId])) {
                    try {
                        $this->wysiwygValidator->validate($content);
                    } catch (ValidationException $exception) {
                        throw new ValidationException(
                            __('Content includes restricted HTML elements. %1', $exception->getMessage()),
                            $exception
                        );
                    }
                }

                preg_match('/banner_ids="([\d,]*)"/', $content, $bannerIdMatches);
                $containsCurrentId = !empty($bannerIdMatches)
                    && in_array($this->getId(), explode(',', $bannerIdMatches[1]));
            }
        }

        if ($containsCurrentId) {
            throw new LocalizedException(
                __('Make sure that dynamic blocks rotator does not reference the dynamic block itself.')
            );
        }

        return parent::beforeSave();
    }

    /**
     * Collect store ids in which current banner has content
     *
     * @return array|null
     */
    public function getStoreIds()
    {
        $contents = $this->getStoreContents();
        if (!$this->hasStoreIds()) {
            $this->setStoreIds(array_keys($contents));
        }
        return $this->_getData('store_ids');
    }

    /**
     * Make types getter always return array
     *
     * @return array
     */
    public function getTypes()
    {
        $types = $this->_getData('types');
        if (is_array($types)) {
            return $types;
        }
        if (empty($types)) {
            $types = [];
        } else {
            $types = explode(',', $types);
        }
        $this->setData('types', $types);
        return $types;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
