<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Gift wrapping options model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GiftWrapping\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

/**
 * Gift wrapping options model.
 *
 * @deprecated 101.0.0 Currently doesn't used, will be removed in the nearest backward incompatible release.
 */
class Options extends \Magento\Framework\DataObject
{
    /**
     * Serializer for converting JSON to string and vise verse.
     *
     * @var Json
     */
    private $serializer;

    /**
     * Options constructor.
     *
     * @param Json|null $serializer Parameter is optional for backward compatibility.
     */
    public function __construct(Json $serializer = null)
    {
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Current data object
     */
    protected $_dataObject = null;

    /**
     * Set gift wrapping options data object
     *
     * @param DataObject $item
     * @return \Magento\GiftWrapping\Model\Options
     */
    public function setDataObject($item)
    {
        if ($item instanceof DataObject && $item->getGiftwrappingOptions()) {
            $this->addData($this->serializer->unserialize($item->getGiftwrappingOptions()));
            $this->_dataObject = $item;
        }
        return $this;
    }

    /**
     * Update gift wrapping options data object
     *
     * @return \Magento\GiftWrapping\Model\Options
     */
    public function update()
    {
        $this->_dataObject->setGiftwrappingOptions($this->serializer->serialize($this->getData()));
        return $this;
    }
}
