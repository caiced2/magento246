<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\ValidatorInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;

/**
 * Attribute duplication validator.
 */
class AttributeDuplication implements ValidatorInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @param Config $eavConfig
     * @param AttributeFactory $attributeFactory
     * @param WebsiteFactory $websiteFactory
     */
    public function __construct(
        Config $eavConfig,
        AttributeFactory $attributeFactory,
        WebsiteFactory $websiteFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
        $this->websiteFactory = $websiteFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(AttributeInterface $attribute): void
    {
        $attributeId = $attribute->getId();

        if ($attributeId === null) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeObject = $this->initAttribute($attribute->getWebsite())->loadByCode(
                $this->eavConfig->getEntityType($attribute->getEntityTypeId()),
                $attributeCode
            );
            if ($attributeObject->getId()) {
                throw new LocalizedException(__('An attribute with this code already exists.'));
            }
        }
    }

    /**
     * Initialize attribute object.
     *
     * @param Website $website
     * @return Attribute
     */
    private function initAttribute(Website $website): Attribute
    {
        /** @var $attribute Attribute */
        $attribute = $this->attributeFactory->create();
        $attribute->setWebsite($website);

        return $attribute;
    }
}
