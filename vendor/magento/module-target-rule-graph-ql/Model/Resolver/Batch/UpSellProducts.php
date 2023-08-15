<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\Resolver\Batch;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\RelatedProductGraphQl\Model\Resolver\Batch\UpSellProducts as ResolverUpSellProducts;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\Rule;

/**
 * Target Rule UpSell Products Resolver
 */
class UpSellProducts implements BatchResolverInterface
{
    /**
     * Query node
     */
    public const NODE = 'upsell_products';

    /**
     * @var TargetRuleProducts
     */
    private $targetRuleProducts;

    /**
     * @var ResolverUpSellProducts
     */
    private $relatedResolver;

    /**
     * @var Data
     */
    private $targetRuleHelper;

    /**
     * @var BatchResponseGenerator
     */
    private $batchResponseGenerator;

    /**
     * @param TargetRuleProducts $targetRuleProducts
     * @param ResolverUpSellProducts $relatedResolver
     * @param Data $targetRuleHelper
     * @param BatchResponseGenerator $batchResponseGenerator
     */
    public function __construct(
        TargetRuleProducts $targetRuleProducts,
        ResolverUpSellProducts $relatedResolver,
        Data $targetRuleHelper,
        BatchResponseGenerator $batchResponseGenerator
    ) {
        $this->targetRuleProducts = $targetRuleProducts;
        $this->relatedResolver = $relatedResolver;
        $this->targetRuleHelper = $targetRuleHelper;
        $this->batchResponseGenerator = $batchResponseGenerator;
    }

    /**
     * @inheritdoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $behavior = $this->targetRuleHelper->getShowProducts(Rule::UP_SELLS);
        if (in_array($behavior, [Rule::BOTH_SELECTED_AND_RULE_BASED, Rule::SELECTED_ONLY])) {
            $responses = $this->relatedResolver->resolve($context, $field, $requests);
        } else {
            $responses = $this->batchResponseGenerator->create($requests);
        }

        if (in_array($behavior, [Rule::BOTH_SELECTED_AND_RULE_BASED, Rule::RULE_BASED_ONLY])) {
            $responses = $this->targetRuleProducts->applyTargetRuleResponses(
                $context,
                $requests,
                $responses,
                self::NODE,
                Rule::UP_SELLS
            );
        }

        return $responses;
    }
}
