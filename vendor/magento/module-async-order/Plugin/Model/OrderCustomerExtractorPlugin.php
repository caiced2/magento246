<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Model;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Customer\Api\Data\AddressInterfaceFactory as AddressFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory as RegionFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\DataObject\Copy as CopyService;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\AsyncOrder\Model\Quote\Address\Validator;
use Magento\Sales\Model\Order\OrderCustomerExtractor;
use Magento\AsyncOrder\Model\Order;
use Magento\Customer\Api\Data\CustomerInterfaceFactory as CustomerFactory;
use Magento\Quote\Api\Data\CartInterface;

/**
 * After plugin for OrderCustomerExtractor to return data from quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderCustomerExtractorPlugin
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CopyService
     */
    private $objectCopyService;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var Validator
     */
    private $addressValidator;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Order $order
     * @param QuoteRepository $quoteRepository
     * @param CopyService $objectCopyService
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param RegionFactory $regionFactory
     * @param Validator $addressValidator
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        Order $order,
        QuoteRepository $quoteRepository,
        CopyService $objectCopyService,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        RegionFactory $regionFactory,
        Validator $addressValidator
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->order = $order;
        $this->quoteRepository = $quoteRepository;
        $this->objectCopyService = $objectCopyService;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->addressValidator = $addressValidator;
    }

    /**
     * After Extract
     *
     * @param OrderCustomerExtractor $subject
     * @param CustomerInterface $result
     * @param int $orderId
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExtract(
        OrderCustomerExtractor $subject,
        CustomerInterface $result,
        int $orderId
    ): CustomerInterface {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $result;
        }

        $this->order->load($orderId);
        if ($this->order->getStatus() !== OrderManagement::STATUS_RECEIVED) {
            return $result;
        }

        if (!empty($result->getAddresses())) {
            return $result;
        }

        $quote = $this->quoteRepository->get($this->order->getQuoteId());

        return $this->extractCustomerData($quote);
    }

    /**
     * Extract customer data from quote.
     *
     * @param CartInterface $quote
     * @return CustomerInterface
     */
    private function extractCustomerData(CartInterface $quote): CustomerInterface
    {
        $customerData = $this->objectCopyService->copyFieldsetToTarget(
            'order_address',
            'to_customer',
            $quote->getBillingAddress(),
            []
        );

        $processedAddressData = [];
        $customerAddresses = [];

        foreach ($quote->getAllAddresses() as $quoteAddress) {
            if (!$this->addressValidator->validateForCustomerRegistration($quoteAddress)) {
                continue;
            }
            $addressData = $this->objectCopyService
                ->copyFieldsetToTarget('order_address', 'to_customer_address', $quoteAddress, []);

            $index = array_search($addressData, $processedAddressData);
            if ($index === false) {
                // create new customer address only if it is unique
                $customerAddress = $this->addressFactory->create(['data' => $addressData]);
                $customerAddress->setIsDefaultBilling(false);
                $customerAddress->setIsDefaultShipping(false);
                if (is_string($quoteAddress->getRegion())) {
                    /** @var RegionInterface $region */
                    $region = $this->regionFactory->create();
                    $region->setRegion($quoteAddress->getRegion());
                    $region->setRegionCode($quoteAddress->getRegionCode());
                    $region->setRegionId($quoteAddress->getRegionId());
                    $customerAddress->setRegion($region);
                }

                $processedAddressData[] = $addressData;
                $customerAddresses[] = $customerAddress;
                $index = count($processedAddressData) - 1;
            }

            $customerAddress = $customerAddresses[$index];
            // make sure that address type flags from equal addresses are stored in one resulted address
            if ($quoteAddress->getAddressType() == OrderAddress::TYPE_BILLING) {
                $customerAddress->setIsDefaultBilling(true);
            }
            if ($quoteAddress->getAddressType() == OrderAddress::TYPE_SHIPPING) {
                $customerAddress->setIsDefaultShipping(true);
            }
        }

        $customerData['addresses'] = $customerAddresses;

        return $this->customerFactory->create(['data' => $customerData]);
    }
}
