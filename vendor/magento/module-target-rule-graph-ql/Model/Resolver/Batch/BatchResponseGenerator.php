<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\Resolver\Batch;

use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;

class BatchResponseGenerator
{
    /**
     * @var BatchResponseFactory
     */
    private $batchResponseFactory;

    /**
     * @param BatchResponseFactory $batchResponseFactory
     */
    public function __construct(
        BatchResponseFactory $batchResponseFactory
    ) {
        $this->batchResponseFactory = $batchResponseFactory;
    }

    /**
     * Generate batch response with empty results for provided requests
     *
     * @param BatchRequestItemInterface[] $requests
     * @return BatchResponse
     */
    public function create(array $requests): BatchResponse
    {
        /** @var BatchResponse $response */
        $response = $this->batchResponseFactory->create();
        foreach ($requests as $request) {
            $response->addResponse($request, []);
        }

        return $response;
    }
}
