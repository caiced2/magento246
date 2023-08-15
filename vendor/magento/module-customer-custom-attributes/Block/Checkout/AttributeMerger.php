<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Block\Checkout;

use Magento\Checkout\Block\Checkout\AttributeMerger as CheckoutAttributesMerger;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Url;

/**
 * Class for preparing metadata for custom attributes ui components
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AttributeMerger extends CheckoutAttributesMerger
{
    /**
     * Map form element
     *
     * @var array
     */
    protected $formElementMap = [
        'checkbox' => 'Magento_Ui/js/form/element/select',
        'select' => 'Magento_Ui/js/form/element/select',
        'textarea' => 'Magento_Ui/js/form/element/textarea',
        'multiline' => 'Magento_Ui/js/form/components/group',
        'multiselect' => 'Magento_Ui/js/form/element/multiselect',
        'image' => 'Magento_Ui/js/form/element/image-uploader',
        'file' => 'Magento_Ui/js/form/element/file-uploader',
    ];

    /**
     * Map template
     *
     * @var array
     */
    protected $templateMap = [
        'image' => 'uploader/uploader',
        'file' => 'uploader/uploader',
    ];

    /**
     * @var Url
     */
    private $url;

    /**
     * @param AddressHelper $addressHelper
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param DirectoryHelper $directoryHelper
     * @param Url $url
     */
    public function __construct(
        AddressHelper $addressHelper,
        Session $customerSession,
        CustomerRepository $customerRepository,
        DirectoryHelper $directoryHelper,
        Url $url
    ) {
        parent::__construct($addressHelper, $customerSession, $customerRepository, $directoryHelper);
        $this->url = $url;
    }

    /**
     * Retrieve UI field configuration for given attribute
     *
     * @param string $attributeCode
     * @param array $attributeConfig
     * @param array $additionalConfig field configuration provided via layout XML
     * @param string $providerName name of the storage container used by UI component
     * @param string $dataScopePrefix
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getFieldConfig(
        $attributeCode,
        array $attributeConfig,
        array $additionalConfig,
        $providerName,
        $dataScopePrefix
    ) {
        $result = parent::getFieldConfig(
            $attributeCode,
            $attributeConfig,
            $additionalConfig,
            $providerName,
            $dataScopePrefix
        );

        if (in_array($attributeConfig['formElement'], ['file', 'image'])) {
            $result['config']['uploaderConfig'] = [
                'url' => $this->url->getUrl('customer_custom_attributes/address_file/upload')
            ];
        }

        $providerName = $result['provider'];

        if (in_array($attributeConfig['formElement'], ['select', 'multiselect'])) {
            $result['deps'] = [$providerName];
            $result['exports']['options'] =
                'index = ' . $providerName . ':customAttributes.' . $attributeCode;
        }

        return $result;
    }
}
