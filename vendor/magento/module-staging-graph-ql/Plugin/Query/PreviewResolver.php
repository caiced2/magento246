<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StagingGraphQl\Plugin\Query;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Webapi\Authorization;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Plugin to add preview capability to resolvers
 */
class PreviewResolver
{
    private const ADMIN_RESOURCE = 'Magento_Staging::staging';

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var array
     */
    private $supportedQueries;

    /**
     * @param Authorization $authorization
     * @param VersionManager $versionManager
     * @param array $supportedQueries
     */
    public function __construct(
        Authorization $authorization,
        VersionManager $versionManager,
        array $supportedQueries = []
    ) {
        $this->authorization = $authorization;
        $this->versionManager = $versionManager;
        $this->supportedQueries = $supportedQueries;
    }

    /**
     * Configure VersionManager before resolve
     *
     * @param ResolverInterface $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $parent = $info->parentType->name;

        if (in_array($parent, ['Query', 'Mutation']) && $this->versionManager->isPreviewVersion()) {
            if ($parent === 'Mutation') {
                throw new GraphQlInputException(__('Preview is not available for mutations.'));
            }
            if ($parent === 'Query') {
                if (in_array($field->getName(), $this->supportedQueries)) {
                    if (! $this->authorization->isAllowed([self::ADMIN_RESOURCE])) {
                        throw new GraphQlAuthorizationException(__('The current user isn\'t authorized.'));
                    }
                } else {
                    throw new GraphQlInputException(__('Preview is not available for this query.'));
                }
            }
        }

        return [$field, $context, $info, $value, $args];
    }
}
