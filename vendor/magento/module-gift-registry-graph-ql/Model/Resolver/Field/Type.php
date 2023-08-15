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
use Magento\GiftRegistry\Model\ResourceModel\Type as RegistryTypeResourceModel;
use Magento\GiftRegistry\Model\Type as TypeModel;
use Magento\GiftRegistry\Model\TypeFactory;

/**
 * Resolves the gift registry type
 */
class Type implements ResolverInterface
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * @var RegistryTypeResourceModel
     */
    private $registryTypeResourceModel;

    /**
     * @param Uid $idEncoder
     * @param TypeFactory $typeFactory
     * @param RegistryTypeResourceModel $registryTypeResourceModel
     */
    public function __construct(
        Uid $idEncoder,
        TypeFactory $typeFactory,
        RegistryTypeResourceModel $registryTypeResourceModel
    ) {
        $this->idEncoder = $idEncoder;
        $this->typeFactory = $typeFactory;
        $this->registryTypeResourceModel = $registryTypeResourceModel;
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
            throw new GraphQlInputException(__('"%1" value should be specified', ['model']));
        }

        /** @var Entity $model */
        $model = $value['model'];

        /** @var TypeModel $typeModel */
        $typeModel = $this->typeFactory->create();
        $this->registryTypeResourceModel->load($typeModel, $model->getData('type_id'));

        return [
            'uid' => $this->idEncoder->encode((string) $model->getData('type_id')),
            'label' => $typeModel->getLabel(),
            'typeId' => $model->getData('type_id')
        ];
    }
}
