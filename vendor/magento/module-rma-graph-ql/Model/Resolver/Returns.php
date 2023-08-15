<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\RmaGraphQl\Model\Formatter\Returns as ReturnsFormatter;
use Magento\RmaGraphQl\Model\ResolverAccess;

/**
 * Returns Resolver
 */
class Returns implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var ReturnsFormatter
     */
    private $returnsFormatter;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RmaRepositoryInterface $rmaRepository
     * @param ReturnsFormatter $returnsFormatter
     * @param GetCustomer $getCustomer
     * @param ResolverAccess $resolverAccess
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RmaRepositoryInterface $rmaRepository,
        ReturnsFormatter $returnsFormatter,
        GetCustomer $getCustomer,
        ResolverAccess $resolverAccess
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rmaRepository = $rmaRepository;
        $this->returnsFormatter = $returnsFormatter;
        $this->getCustomer = $getCustomer;
        $this->resolverAccess = $resolverAccess;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);

        $customer = $this->getCustomer->execute($context);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());

        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $this->returnsFormatter->format($searchResults);
    }
}
