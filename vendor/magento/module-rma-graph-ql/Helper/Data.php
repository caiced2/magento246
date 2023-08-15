<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Store\Model\ScopeInterface;

/**
 * Helper for RMA GraphQL
 */
class Data
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Uid $idEncoder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Uid $idEncoder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->idEncoder = $idEncoder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Decode base64 encoded carrierId that consists from 'carrier_id' and 'store_id'.
     *
     * Returns an array where 0 key is carrier_id and 1 key is store_id.
     *
     * @param string $carrierId
     * @return array
     * @throws GraphQlInputException
     */
    public function decodeCarrierId(string $carrierId): array
    {
        return explode('-', $this->idEncoder->decode($carrierId));
    }

    /**
     * Encode in base64 carrierId that consists from 'carrier_id' + 'store_id'
     *
     * @param string $carrierId
     * @param int $storeId
     * @return string
     */
    public function encodeCarrierId(string $carrierId, int $storeId): string
    {
        return $this->idEncoder->encode($carrierId . '-' . $storeId);
    }

    /**
     * Get RMA config
     *
     * @return array
     */
    public function getRmaConfig(): array
    {
        return $this->scopeConfig->getValue('sales/magento_rma', ScopeInterface::SCOPE_WEBSITE);
    }
}
