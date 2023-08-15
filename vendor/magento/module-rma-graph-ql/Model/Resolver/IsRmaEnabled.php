<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Helper\Data as RmaHelper;

/**
 * Resolver for is enabled RMA config
 */
class IsRmaEnabled implements ResolverInterface
{
    /**
     * @var RmaHelper
     */
    private $helper;

    /**
     * @param RmaHelper $helper
     */
    public function __construct(RmaHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return $this->helper->isEnabled() ? 'enabled' : 'disabled';
    }
}
