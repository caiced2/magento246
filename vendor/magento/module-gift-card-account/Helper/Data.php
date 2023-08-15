<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Maximal gift card code length according to database table definitions (longer codes are truncated)
     */
    const GIFT_CARD_CODE_MAX_LENGTH = 255;

    /**
     * Instance of serializer.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Json|null $serializer
     */
    public function __construct(Context $context, Json $serializer = null)
    {
        parent::__construct($context);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Unserialize and return gift card list from specified object
     *
     * @param \Magento\Framework\DataObject $from
     * @return mixed
     */
    public function getCards(\Magento\Framework\DataObject $from)
    {
        $value = $from->getGiftCards();
        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }

    /**
     * Serialize and set gift card list to specified object
     *
     * @param \Magento\Framework\DataObject $to
     * @param mixed $value
     * @return void
     */
    public function setCards(\Magento\Framework\DataObject $to, $value)
    {
        $serializedValue = $this->serializer->serialize($value);
        $to->setGiftCards($serializedValue);
    }
}
