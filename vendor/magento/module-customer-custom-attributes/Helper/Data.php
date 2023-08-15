<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Helper;

/**
 * Enterprise Customer Data Helper
 */
class Data extends \Magento\CustomAttributeManagement\Helper\Data
{
    /**
     * Customer customer
     *
     * @var Customer
     */
    protected $_customerCustomer = null;

    /**
     * Customer address
     *
     * @var Address
     */
    protected $_customerAddress = null;

    /**
     * Field types with allowed length validation.
     *
     * @var array
     */
    private static $allowLengthValidationTypes = ['text', 'multiline'];

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param Address $customerAddress
     * @param Customer $customerCustomer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Filter\FilterManager $filterManager,
        Address $customerAddress,
        Customer $customerCustomer
    ) {
        $this->_customerAddress = $customerAddress;
        $this->_customerCustomer = $customerCustomer;
        parent::__construct($context, $eavConfig, $localeDate, $filterManager);
    }

    /**
     * Return available customer attribute form as select options
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeFormOptions()
    {
        throw new \Magento\Framework\Exception\LocalizedException(__('Use helper with defined EAV entity.'));
    }

    /**
     * Default attribute entity type code
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getEntityTypeCode()
    {
        throw new \Magento\Framework\Exception\LocalizedException(__('Use helper with defined EAV entity.'));
    }

    /**
     * Return available customer attribute form as select options
     *
     * @return array
     */
    public function getCustomerAttributeFormOptions()
    {
        return $this->_customerCustomer->getAttributeFormOptions();
    }

    /**
     * Return available customer address attribute form as select options
     *
     * @return array
     */
    public function getCustomerAddressAttributeFormOptions()
    {
        return $this->_customerAddress->getAttributeFormOptions();
    }

    /**
     * Returns array of user defined attribute codes for customer entity type
     *
     * @return array
     */
    public function getCustomerUserDefinedAttributeCodes()
    {
        return $this->_customerCustomer->getUserDefinedAttributeCodes();
    }

    /**
     * Returns array of user defined attribute codes for customer address entity type
     *
     * @return array
     */
    public function getCustomerAddressUserDefinedAttributeCodes()
    {
        return $this->_customerAddress->getUserDefinedAttributeCodes();
    }

    /**
     * @inheritdoc
     */
    public function getAttributeInputTypes($inputType = null): array
    {
        $inputTypeData = parent::getAttributeInputTypes($inputType);

        if (isset($inputTypeData['validate_filters']) &&
            in_array($inputType, self::$allowLengthValidationTypes, true)) {
            $inputTypeData['validate_filters'][] = 'length';
        } else {
            foreach (self::$allowLengthValidationTypes as $type) {
                if (isset($inputTypeData[$type]['validate_filters'])) {
                    $inputTypeData[$type]['validate_filters'][] = 'length';
                }
            }
        }

        return $inputTypeData;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeValidateFilters(): array
    {
        $filters = parent::getAttributeValidateFilters();
        $filters = array_merge($filters, ['length' => __('Length Only')]);
        return $filters;
    }
}
