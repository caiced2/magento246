<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\CustomerCustomAttributes\Helper\Data as DataHelper;
use Magento\Framework\DataObject;

abstract class AbstractObserver
{
    const CONVERT_ALGORITM_SOURCE_TARGET_WITH_PREFIX = 1;

    const CONVERT_ALGORITM_SOURCE_WITHOUT_PREFIX = 2;

    const CONVERT_ALGORITM_TARGET_WITHOUT_PREFIX = 3;

    const CONVERT_TYPE_CUSTOMER = 'customer';

    const CONVERT_TYPE_CUSTOMER_ADDRESS = 'customer_address';

    /**
     * @var DataHelper
     */
    protected $_customerData;

    /**
     * @param DataHelper $customerData
     */
    public function __construct(DataHelper $customerData)
    {
        $this->_customerData = $customerData;
    }

    /**
     * CopyFieldset converts customer attributes from source object to target object
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @param int $algorithm
     * @param string $convertType
     * @return $this
     */
    protected function _copyFieldset(
        \Magento\Framework\Event\Observer $observer,
        $algorithm = self::CONVERT_ALGORITM_TARGET_WITHOUT_PREFIX,
        $convertType = self::CONVERT_TYPE_CUSTOMER
    ) {
        $source = $observer->getEvent()->getSource();
        $target = $observer->getEvent()->getTarget();

        if (is_array($source)) {
            $source = new DataObject($source);
        }

        if ($source instanceof DataObject && $target instanceof DataObject) {
            if ($convertType == self::CONVERT_TYPE_CUSTOMER_ADDRESS) {
                $attributes = $this->_customerData->getCustomerAddressUserDefinedAttributeCodes();
                $prefix = '';
            } else {
                $attributes = $this->_customerData->getCustomerUserDefinedAttributeCodes();
                $prefix = 'customer_';
            }

            foreach ($attributes as $attribute) {
                switch ($algorithm) {
                    case self::CONVERT_ALGORITM_SOURCE_TARGET_WITH_PREFIX:
                        $sourceAttribute = $prefix . $attribute;
                        $targetAttribute = $prefix . $attribute;
                        break;
                    case self::CONVERT_ALGORITM_SOURCE_WITHOUT_PREFIX:
                        $sourceAttribute = $attribute;
                        $targetAttribute = $prefix . $attribute;
                        break;
                    case self::CONVERT_ALGORITM_TARGET_WITHOUT_PREFIX:
                    default:
                        $sourceAttribute = $prefix . $attribute;
                        $targetAttribute = $attribute;
                        break;
                }
                $target->setData($targetAttribute, $source->getData($sourceAttribute));
            }
        }

        return $this;
    }
}
