<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Rma\Item;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionValueProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Model\ItemFactory;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\RmaGraphQl\Model\Validator;

/**
 * RMA item builder
 */
class Builder
{
    /**
     * @var Uid
     */
    private $idEncoder;
    /**
     * @var ItemFactory
     */
    private $rmaItemFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var OptionValueProvider
     */
    private $optionValueProvider;

    /**
     * @param Uid $idEncoder
     * @param ItemFactory $rmaItemFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Validator $validator
     * @param OptionValueProvider $optionValueProvider
     */
    public function __construct(
        Uid $idEncoder,
        ItemFactory $rmaItemFactory,
        AttributeRepositoryInterface $attributeRepository,
        Validator $validator,
        OptionValueProvider $optionValueProvider
    ) {
        $this->idEncoder = $idEncoder;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->attributeRepository = $attributeRepository;
        $this->validator = $validator;
        $this->optionValueProvider = $optionValueProvider;
    }

    /**
     * Build RMA items
     *
     * @param array $item
     * @return ItemInterface
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function build(array $item): ItemInterface
    {
        $rmaItem = $this->rmaItemFactory->create();
        $rmaItem->setOrderItemId($this->idEncoder->decode($item['order_item_uid']));
        $rmaItem->setQtyRequested($item['quantity_to_return']);
        $rmaItem->setStatus(Status::STATE_PENDING);

        try {
            if (isset($item['entered_custom_attributes'])) {
                $this->setEnteredAttributes($item['entered_custom_attributes'], $rmaItem);
            }
            if (isset($item['selected_custom_attributes'])) {
                $this->setSelectedAttributes($item['selected_custom_attributes'], $rmaItem);
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        return $rmaItem;
    }

    /**
     * Set entered attributes to RMA item
     *
     * @param array $enteredAttributes
     * @param ItemInterface $rmaItem
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     */
    private function setEnteredAttributes(array $enteredAttributes, ItemInterface $rmaItem): void
    {
        foreach ($enteredAttributes as $enteredAttribute) {
            $attribute = $this->getAttribute($enteredAttribute['attribute_code']);

            if ($this->checkIsSelectedAttribute($attribute)) {
                throw new GraphQlInputException(
                    __("Attribute {$attribute->getAttributeCode()} is not entered. It's selected")
                );
            }

            $attributeValue = $this->validator->validateString(
                $enteredAttribute['value'],
                "Value of {$attribute->getAttributeCode()} is incorrect"
            );

            $rmaItem->setData($attribute->getAttributeCode(), $attributeValue);
        }
    }

    /**
     * Set selected attributes to RMA item
     *
     * @param array $selectedAttributes
     * @param ItemInterface $rmaItem
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    private function setSelectedAttributes(array $selectedAttributes, ItemInterface $rmaItem): void
    {
        foreach ($selectedAttributes as $selectedAttribute) {
            $attribute = $this->getAttribute($selectedAttribute['attribute_code']);

            if (!$this->checkIsSelectedAttribute($attribute)) {
                throw new GraphQlInputException(
                    __("Attribute {$attribute->getAttributeCode()} is not selected")
                );
            }

            $attributeValue = $this->validator->validateString(
                $this->idEncoder->decode($selectedAttribute['value']),
                "Value of {$attribute->getAttributeCode()} is incorrect"
            );

            if ($this->optionValueProvider->get((int)$attributeValue)) {
                $rmaItem->setData($attribute->getAttributeCode(), $attributeValue);
            } else {
                throw new GraphQlInputException(__("Value of {$attribute->getAttributeCode()} is incorrect"));
            }
        }
    }

    /**
     * Get attribute
     *
     * @param string $attributeCode
     * @return AttributeInterface
     * @throws GraphQlNoSuchEntityException
     */
    private function getAttribute(string $attributeCode): AttributeInterface
    {
        try {
            return $this->attributeRepository->get(
                RmaAttributesManagementInterface::ENTITY_TYPE,
                $attributeCode
            );
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

    /**
     * Check is attribute selected
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function checkIsSelectedAttribute(AttributeInterface $attribute): bool
    {
        return in_array($attribute->getFrontendInput(), ['select', 'multiselect'], true);
    }
}
