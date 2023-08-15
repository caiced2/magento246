<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Controller\Address;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\CustomAttributesProcessor;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\CheckoutAddressSearch\Model\AddressSearch;
use Psr\Log\LoggerInterface;

/**
 * Controller to search shipping or billing address for ui-select component
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Search extends \Magento\Customer\Controller\Address implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var AddressSearch
     */
    private $addressSearch;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomAttributesProcessor
     */
    private $customAttributesProcessor;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param JsonFactory $resultFactory
     * @param AddressSearch $addressSearch
     * @param CustomerRepositoryInterface $customerRepository
     * @param UserContextInterface $userContext
     * @param LoggerInterface $logger
     * @param CustomAttributesProcessor $customAttributesProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        JsonFactory $resultFactory,
        AddressSearch $addressSearch,
        CustomerRepositoryInterface $customerRepository,
        UserContextInterface $userContext,
        LoggerInterface $logger,
        CustomAttributesProcessor $customAttributesProcessor
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $formKeyValidator,
            $formFactory,
            $addressRepository,
            $addressDataFactory,
            $regionDataFactory,
            $dataProcessor,
            $dataObjectHelper,
            $resultForwardFactory,
            $resultPageFactory
        );
        $this->resultJsonFactory = $resultFactory;
        $this->addressSearch = $addressSearch;
        $this->customerRepository = $customerRepository;
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->customAttributesProcessor = $customAttributesProcessor;
    }

    /**
     * @inheritdoc
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        if (!$this->_request->isAjax()) {
            $this->_redirect('checkout');
        }
        $searchKey = (string) $this->getRequest()->getParam('searchKey');
        $pageNum = (int) $this->getRequest()->getParam('page');
        $customerId = (int) $this->userContext->getUserId();
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        try {
            $customer = $this->customerRepository->getById($customerId);
            $defaultShippingAddress = $customer->getDefaultShipping();
            $defaultBillingAddress = $customer->getDefaultBilling();
            /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $addressCollection */
            $addressCollection = $this->addressSearch->search($searchKey, $customerId, $pageNum);
        } catch (\Exception $e) {
            $this->logger->error('Unable to get customer with id ' . $customerId);
            $resultJson->setData([
                'error' => 1,
                'message' => 'Unable to process the request',
            ]);
            return $resultJson;
        }

        $totalValues = $addressCollection->getSize();
        $addressesById = [];
        foreach ($addressCollection as $address) {
            $addressId = $address->getId();
            $addressesById[$addressId] = [
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'country_id' => $address->getCountryId(),
                'postcode' => $address->getPostcode(),
                'telephone' => $address->getTelephone(),
                'customer_id' => $address->getParentId(),
                'id' => $addressId,
                'region_id' => $address->getRegionId(),
                'company' => $address->getCompany(),
                'fax' => $address->getFax(),
                'middlename' => $address->getMiddlename(),
                'prefix' => $address->getPrefix(),
                'suffix' => $address->getSuffix(),
                'vat_id' => $address->getVatId(),
                'region' => [
                    'region' => $address->getRegion(),
                    'region_id' => $address->getRegionId(),
                    'region_code' => $address->getRegionCode()
                ],
                'extension_attributes' => $address->getExtensionAttributes()
            ];
            $addressesById[$addressId]['default_shipping'] = $addressId === $defaultShippingAddress;
            $addressesById[$addressId]['default_billing'] = $addressId === $defaultBillingAddress;
            // add custom customer address attributes
            $customAttributes = $address->getCustomAttributes();
            if (!empty($customAttributes)) {
                $customerAddressAttributes = [];
                foreach ($customAttributes as $customAttribute) {
                    $customerAddressAttributes[$customAttribute->getAttributeCode()] = $customAttribute->__toArray();
                }
                $customAttributes = $this->customAttributesProcessor->filterNotVisibleAttributes(
                    $customerAddressAttributes
                );
                $addressesById[$addressId]['custom_attributes'] = $customAttributes;
            }
        }

        $resultJson->setData([
            'options' => $addressesById,
            'total' => $totalValues
        ]);
        return $resultJson;
    }
}
