<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Field;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\ResourceModel\Person\Collection as PersonCollection;
use Magento\GiftRegistry\Model\ResourceModel\Person\CollectionFactory;

/**
 * Resolves the gift registry registrants
 */
class Registrants implements ResolverInterface
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var CollectionFactory
     */
    private $personCollectionFactory;

    /**
     * @param Uid $idEncoder
     * @param CollectionFactory $personCollectionFactory
     */
    public function __construct(
        Uid $idEncoder,
        CollectionFactory $personCollectionFactory
    ) {
        $this->idEncoder = $idEncoder;
        $this->personCollectionFactory = $personCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['model']
            ));
        }

        /** @var Entity $model */
        $model = $value['model'];

        /** @var PersonCollection $registrants */
        $registrants = $this->personCollectionFactory->create();
        $registrants->addRegistryFilter($model->getId());
        $data = [];
        $isOwner = false;
        $customerId = (int) $context->getUserId() ?? null;

        if (((int) $customerId) === ((int) $model->getCustomerId())) {
            $isOwner = true;
        }

        foreach ($registrants as $registrant) {
            $data[] = [
                'uid' => $this->idEncoder->encode(
                    (string) $registrant->getId()
                ),
                'firstname' => $registrant->getFirstname(),
                'lastname' => $registrant->getLastname(),
                'email' => $isOwner ? $registrant->getEmail() : '',
                'model' => $model,
                'personModel' => $registrant
            ];
        }

        return $data;
    }
}
