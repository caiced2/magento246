<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\RmaGraphQl\Helper\Data as RmaHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * RMA shipping formatter
 */
class Shipping
{
    /**
     * @var RmaHelper
     */
    private $rmaHelper;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * @var CountryInformationAcquirerInterface
     */
    private $countryInformationAcquirer;

    /**
     * @var array
     */
    private $requiredFields = [
        'street',
        'city',
        'region',
        'postcode',
        'country'
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param RegionFactory $regionFactory
     * @param DataObjectProcessor $dataProcessor
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param RmaHelper $rmaHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        RegionFactory $regionFactory,
        DataObjectProcessor $dataProcessor,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        RmaHelper $rmaHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->regionFactory = $regionFactory;
        $this->dataProcessor = $dataProcessor;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->rmaHelper = $rmaHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Format shipping data according to the GraphQL schema
     *
     * @param RmaInterface $rma
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function format(RmaInterface $rma): array
    {
        $rmaConfig = $this->rmaHelper->getRmaConfig();

        if ($rmaConfig['use_store_address']) {
            $storeInfo = $this->scopeConfig->getValue(
                'shipping/origin',
                ScopeInterface::SCOPE_WEBSITE
            );

            $shippingAddress = [
                'contact_name' => null,
                'street' => [
                    $storeInfo['street_line1'] ?? '',
                    $storeInfo['street_line2'] ?? '',
                ],
                'city' => $storeInfo['city'] ?? '',
                'region' => $this->formatRegion((int)($storeInfo['region_id'] ?? '')),
                'postcode' => $storeInfo['postcode'] ?? '',
                'country' => $this->formatCountry($storeInfo['country_id'] ?? ''),
                'telephone' => null,
            ];
        } else {
            $shippingAddress = [
                'contact_name' => $rmaConfig['store_name'],
                'street' => [$rmaConfig['address'], $rmaConfig['address1']],
                'city' => $rmaConfig['city'],
                'region' => isset($rmaConfig['region_id']) ? $this->formatRegion((int)$rmaConfig['region_id']) : null,
                'postcode' => $rmaConfig['zip'],
                'country' => $this->formatCountry($rmaConfig['country_id']),
                'telephone' => null
            ];
        }

        foreach ($this->requiredFields as $field) {
            if (empty($shippingAddress[$field])) {
                throw new GraphQlNoSuchEntityException(
                    __('Address for returns is not configured in admin.')
                );
            }
        }

        return [
            'address' => $shippingAddress,
            'model' => $rma
        ];
    }

    /**
     * Format region according to the GraphQL schema
     *
     * @param int $regionId
     * @return array
     */
    public function formatRegion(int $regionId): array
    {
        $region = $this->regionFactory->create();
        $region->load($regionId);
        return $this->dataProcessor->buildOutputDataArray($region, RegionInformationInterface::class);
    }

    /**
     * Format country according to the GraphQL schema
     *
     * @param string|null $countryId
     * @return array|null
     */
    public function formatCountry(string $countryId = null): ?array
    {
        try {
            $country = $this->countryInformationAcquirer->getCountryInfo($countryId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $this->dataProcessor->buildOutputDataArray($country, CountryInformationInterface::class);
    }
}
