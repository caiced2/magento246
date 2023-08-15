<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\NotEmpty;
use Magento\Framework\Validator\ValidatorChain;

/**
 * Entity registrants data model
 *
 * @method \Magento\GiftRegistry\Model\Person setEntityId(int $value)
 * @method string getFirstname()
 * @method \Magento\GiftRegistry\Model\Person setFirstname(string $value)
 * @method string getLastname()
 * @method \Magento\GiftRegistry\Model\Person setLastname(string $value)
 * @method string getEmail()
 * @method \Magento\GiftRegistry\Model\Person setEmail(string $value)
 * @method string getRole()
 * @method \Magento\GiftRegistry\Model\Person setRole(string $value)
 * @method string getCustomValues()
 * @method \Magento\GiftRegistry\Model\Person setCustomValues(string $value)
 *
 * @api
 * @since 100.0.2
 */
class Person extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData = null;

    /**
     * Object for serialization / unserailization of the data.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\GiftRegistry\Model\ResourceModel\Person $resource
     * @param Entity $entity
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\GiftRegistry\Model\ResourceModel\Person $resource,
        Entity $entity,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        $this->_giftRegistryData = $giftRegistryData;
        $this->entity = $entity;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Init resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\ResourceModel\Person::class);
    }

    /**
     * Validate registrant attribute values
     *
     * @return array|bool
     */
    public function validate()
    {
        // not Checking entityId !!!
        $errors = [];

        if (!ValidatorChain::is($this->getFirstname(), NotEmpty::class)) {
            $errors[] = __('Please enter the first name.');
        }

        if (!ValidatorChain::is($this->getLastname(), NotEmpty::class)) {
            $errors[] = __('Please enter the last name.');
        }

        if (!ValidatorChain::is($this->getEmail(), EmailAddress::class)) {
            $errors[] = __('Please enter a valid email address(for example, daniel@x.com).');
        }

        $customValues = $this->getCustom();
        $attributes = $this->entity->getRegistrantAttributes();

        $errorsCustom = $this->_giftRegistryData->validateCustomAttributes($customValues, $attributes);
        if ($errorsCustom !== true) {
            $errors = empty($errors) ? $errorsCustom : array_merge($errors, $errorsCustom);
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Unpack "custom" value array
     *
     * @return $this
     */
    public function unserialiseCustom()
    {
        if ($this->getCustomValues()) {
            $customValues = $this->serializer->unserialize($this->getCustomValues());
            $this->setCustom($customValues);
        }
        return $this;
    }
}
