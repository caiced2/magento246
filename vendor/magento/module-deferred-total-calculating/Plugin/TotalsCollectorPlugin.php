<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\DeferredTotalCalculating\Plugin;

use Magento\DeferredTotalCalculating\Setup\ConfigOptionsList;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\DeploymentConfig;
use Magento\Quote\Model\Quote\Address\Total\Subtotal;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\QuantityCollector;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Quote\Model\Quote\Address\Total;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsCollectorPlugin
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var QuantityCollector
     */
    private $quantityCollector;

    /**
     * @var Subtotal
     */
    private $subtotalCollector;

    /**
     * @var ShippingAssignmentFactory
     */
    private $shippingAssignmentFactory;

    /**
     * @var TotalFactory
     */
    private $totalFactory;

    /**
     * @var ShippingFactory
     */
    private $shippingFactory;

    /**
     * @var bool $ifSubtotalCollected;
     */
    private $ifSubtotalCollected = false;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param QuantityCollector $quantityCollector
     * @param Subtotal $subtotalCollector
     * @param ShippingAssignmentFactory $shippingAssignmentFactory
     * @param TotalFactory $totalFactory
     * @param ShippingFactory $shippingFactory
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        QuantityCollector $quantityCollector,
        Subtotal $subtotalCollector,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        TotalFactory $totalFactory,
        ShippingFactory $shippingFactory
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->quantityCollector = $quantityCollector;
        $this->subtotalCollector = $subtotalCollector;
        $this->shippingAssignmentFactory = $shippingAssignmentFactory;
        $this->totalFactory = $totalFactory;
        $this->shippingFactory = $shippingFactory;
    }

    /**
     * Around collect totals that decides whenever total should be calculated or no.
     *
     * @param Quote $quote
     * @param \Closure $proceed
     * @return Quote|mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function aroundCollectTotals(
        Quote $quote,
        \Closure $proceed
    ) {
        if ($this->deploymentConfig->get(ConfigOptionsList::CONFIG_PATH_DEFERRED_TOTAL_CALCULATING_FRONTNAME)) {
            if ($this->ifSubtotalCollected) {
                return $quote;
            }

            $this->quantityCollector->collectItemsQtys($quote);

            if ($quote->hasDataChanges() && !$this->isQtyChanged($quote)) {
                return $proceed();
            } else {
                $finalSubTotal = 0;
                $finalBaseSubTotal = 0;

                foreach ($quote->getAllAddresses() as $address) {
                    /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment */
                    $shippingAssignment = $this->shippingAssignmentFactory->create();

                    $shipping = $this->shippingFactory->create();
                    $shipping->setMethod($address->getShippingMethod());
                    $shipping->setAddress($address);
                    $shippingAssignment->setShipping($shipping);
                    $shippingAssignment->setItems($address->getAllItems());
                    $total = $this->totalFactory->create(Total::class);
                    $this->subtotalCollector->collect($quote, $shippingAssignment, $total);
                    $finalSubTotal += $total->getTotalAmount('')
                        ?: $total->getData('virtual_amount');
                    $finalBaseSubTotal += $total->getBaseTotalAmount('')
                        ?: $total->getData('base_virtual_amount');
                }

                $quote->setSubtotal($finalSubTotal);
                $quote->setBaseSubtotal($finalBaseSubTotal);
                $quote->setTotalsCollectedFlag(true);
                $this->ifSubtotalCollected = true;

                return $quote;
            }
        }

        return $proceed();
    }

    /**
     * Check if the quantity was changed.
     *
     * @param Quote $quote
     * @return bool
     */
    private function isQtyChanged(Quote $quote): bool
    {
        return $quote->getData('items_qty') != $quote->getOrigData('items_qty');
    }
}
