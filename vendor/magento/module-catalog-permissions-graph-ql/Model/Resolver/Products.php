<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products as Subject;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Plugin before actual products search resolver runs
 */
class Products
{
    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @param ConfigInterface $permissionsConfig
     * @param GroupProcessor $groupProcessor
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        GroupProcessor $groupProcessor
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->groupProcessor = $groupProcessor;
    }

    /**
     * Consider disallowed catalog search configuration
     *
     * @param Subject $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        Subject $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if ($this->permissionsConfig->isEnabled()) {
            $customerGroupId = $this->groupProcessor->getCustomerGroup($context);
            $catalogSearchDeniedGroups = $this->permissionsConfig->getCatalogSearchDenyGroups();
            if (in_array($customerGroupId, $catalogSearchDeniedGroups)) {
                $args['searchAllowed'] = false;
            }
        }

        return [$field, $context, $info, $value, $args];
    }
}
