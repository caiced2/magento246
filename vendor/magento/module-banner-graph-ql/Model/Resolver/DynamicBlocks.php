<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerGraphQl\Model\Resolver;

use Magento\BannerGraphQl\Model\DynamicBlockFormatter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\BannerGraphQl\Model\DynamicBlocks as DynamicBlocksModel;

/**
 * DynamicBlocks resolver
 */
class DynamicBlocks implements ResolverInterface
{
    /**
     * @var DynamicBlocksModel
     */
    private $dynamicBlocks;

    /**
     * @var DynamicBlockFormatter
     */
    private $formatter;

    /**
     * @param DynamicBlocksModel $dynamicBlocks
     * @param DynamicBlockFormatter $formatter
     */
    public function __construct(
        DynamicBlocksModel $dynamicBlocks,
        DynamicBlockFormatter $formatter
    ) {
        $this->dynamicBlocks = $dynamicBlocks;
        $this->formatter = $formatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customerId = $context->getUserId();
        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();

        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        $dynamicBlockCollection = $this->dynamicBlocks->getList(
            $args['input'],
            $customerId,
            $args['pageSize'],
            $args['currentPage'],
            $websiteId
        );

        $dynamicBlocks = [];
        foreach ($dynamicBlockCollection->getItems() as $dynamicBlock) {
            $dynamicBlocks[] = $this->formatter->format($dynamicBlock);
        }

        $pageSize = $dynamicBlockCollection->getPageSize();
        $totalCount = $dynamicBlockCollection->getSize();

        return [
            'items' => $dynamicBlocks,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $dynamicBlockCollection->getCurPage(),
                'total_pages' => $pageSize ? ((int)ceil($totalCount / $pageSize)) : 0,
            ],
            'total_count' => $totalCount
        ];
    }
}
