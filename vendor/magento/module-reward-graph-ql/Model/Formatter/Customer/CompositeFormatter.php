<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Formatter\Customer;

use Magento\Customer\Model\Customer;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Collect and supply formatters where needed
 */
class CompositeFormatter implements FormatterInterface
{
    /**
     * @var FormatterInterface[]
     */
    private $formatters;

    /**
     * @param FormatterInterface[] $formatters
     */
    public function __construct(
        array $formatters
    ) {
        $this->formatters = $formatters;
    }

    /**
     * Format Reward Points Field Output
     *
     * @param Customer $customer
     * @param StoreInterface $store
     * @param Reward $rewardInstance
     * @return array
     */
    public function format(Customer $customer, StoreInterface $store, Reward $rewardInstance): array
    {
        $resultData = [];

        /**
         * @var string $formatterCode
         * @var FormatterInterface $formatter
         */
        foreach ($this->formatters as $formatterCode => $formatter) {
            if ($formatter instanceof FormatterInterface) {
                $resultData[$formatterCode] = $formatter->format($customer, $store, $rewardInstance);
            }
        }

        return $resultData;
    }
}
