<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class SearchType implements ResolverInterface
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var Search
     */
    private $search;

    /**
     * @param Uid $idEncoder
     * @param Search $search
     */
    public function __construct(
        Uid $idEncoder,
        Search $search
    ) {
        $this->search = $search;
        $this->idEncoder = $idEncoder;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws GraphQlInputException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $args['search'] = Search::SEARCH_TYPE;
        $args['firstname'] = $args['firstName'];
        unset($args['firstName']);
        $args['lastname'] = $args['lastName'];
        unset($args['lastName']);
        if (isset($args['giftRegistryTypeUid'])) {
            $args['type_id'] = (int)$this->idEncoder->decode($args['giftRegistryTypeUid']);
            unset($args['giftRegistryTypeUid']);
        }

        return $this->search->search($args);
    }
}
