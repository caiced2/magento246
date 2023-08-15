<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address;

/**
 * Class for saving customer address attributes
 */
class SalesQuoteAddressAfterSave implements ObserverInterface
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Form\Attribute\Collection|null
     */
    private $attributesList = null;

    /**
     * @param \Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory $quoteAddressFactory
     * @param AttributeMetadataDataProvider|null $attributeMetadataDataProvider
     */
    public function __construct(
        \Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory $quoteAddressFactory,
        AttributeMetadataDataProvider $attributeMetadataDataProvider = null
    ) {
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider ?:
            ObjectManager::getInstance()->get(AttributeMetadataDataProvider::class);
    }

    /**
     * After save observer for quote address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteAddress = $observer->getEvent()->getQuoteAddress();
        if ($quoteAddress instanceof \Magento\Framework\Model\AbstractModel) {
            $this->processComplexAttributes($quoteAddress);
            /** @var $quoteAddressModel \Magento\CustomerCustomAttributes\Model\Sales\Quote\Address */
            $quoteAddressModel = $this->quoteAddressFactory->create();
            $quoteAddressModel->saveAttributeData($quoteAddress);
        }
        return $this;
    }

    /**
     * Prepare values for complex custom attributes
     *
     * @param Address $quoteAddress
     */
    private function processComplexAttributes(\Magento\Framework\Model\AbstractModel $quoteAddress)
    {
        $attributesList = $this->getAttributesList();

        foreach ($attributesList as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (!$quoteAddress->hasData($attributeCode)) {
                continue;
            }

            $attributeValue = $quoteAddress->getData($attributeCode);

            switch ($attribute->getFrontendInput()) {
                case 'file':
                    if (is_array($attributeValue)) {
                        $fileInfo = reset($attributeValue);
                        $attributeValue = $fileInfo['file'];
                    }
                    break;

                case 'multiselect':
                    $attributeValue = $attributeValue !== null ? str_replace("\n", ',', $attributeValue) : '';
                    break;
            }

            $quoteAddress->setData($attributeCode, $attributeValue);
        }
    }

    /**
     * Get list of attributes.
     *
     * @return \Magento\Customer\Model\ResourceModel\Form\Attribute\Collection|null
     */
    private function getAttributesList()
    {
        if (!$this->attributesList) {
            $attributesList = $this->attributeMetadataDataProvider->loadAttributesCollection(
                'customer_address',
                'customer_register_address'
            );
            $attributesList->addFieldToFilter('is_user_defined', 1);
            $attributesList->addFieldToFilter('frontend_input', ['in' => ['file', 'multiselect']]);

            $this->attributesList = $attributesList;
        }
        return $this->attributesList;
    }
}
