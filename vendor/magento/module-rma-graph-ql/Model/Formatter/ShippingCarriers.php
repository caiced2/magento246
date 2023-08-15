<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\RmaGraphQl\Helper\Data as RmaGraphQlHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * RMA shipping carriers formatter
 */
class ShippingCarriers
{
    /**
     * @var RmaHelper
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RmaGraphQlHelper
     */
    private $rmaGraphQlHelper;

    /**
     * @param RmaHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param RmaGraphQlHelper $rmaGraphQlHelper
     */
    public function __construct(
        RmaHelper $helper,
        StoreManagerInterface $storeManager,
        RmaGraphQlHelper $rmaGraphQlHelper
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->rmaGraphQlHelper = $rmaGraphQlHelper;
    }

    /**
     * Format available shipping carriers
     *
     * @param RmaInterface $rma
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function getAvailableShippingCarriers(RmaInterface $rma): array
    {
        $carriers = $this->helper->getAllowedShippingCarriers($rma->getStoreId());
        $availableCarriers = [];

        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        foreach ($carriers as $carrierCode => $carrier) {
            $availableCarriers[] = [
                'uid' => $this->rmaGraphQlHelper->encodeCarrierId($carrierCode, $storeId),
                'label' => $carrier,
            ];
        }

        return $availableCarriers;
    }
}
