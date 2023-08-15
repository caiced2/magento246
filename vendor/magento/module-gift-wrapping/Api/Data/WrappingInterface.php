<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Api\Data;

/**
 * Interface WrappingInterface
 * @api
 * @since 100.0.2
 */
interface WrappingInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const WRAPPING_ID = 'wrapping_id';
    const DESIGN = 'design';
    const STATUS = 'status';
    const BASE_PRICE = 'base_price';
    const IMAGE_NAME = 'image_name';
    const IMAGE_BASE64_CONTENT = 'image_base64_content';
    const BASE_CURRENCY_CODE = 'base_currency_code';
    const WEBSITE_IDS = 'website_ids';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getWrappingId();

    /**
     * @param int|null id
     * @return $this
     */
    public function setWrappingId($id);

    /**
     * @return string
     */
    public function getDesign();

    /**
     * @param string $design
     * @return $this
     */
    public function setDesign($design);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return float
     */
    public function getBasePrice();

    /**
     * @param float $price
     * @return $this
     */
    public function setBasePrice($price);

    /**
     * @return string|null
     */
    public function getImageName();

    /**
     * @param string|null $name
     * @return $this
     */
    public function setImageName($name);

    /**
     * @return string|null
     */
    public function getImageBase64Content();

    /**
     * @param string|null $content
     * @return $this
     */
    public function setImageBase64Content($content);

    /**
     * @return string|null
     */
    public function getBaseCurrencyCode();

    /**
     * @param string|null $code
     * @return $this
     */
    public function setBaseCurrencyCode($code);

    /**
     * @return int[]|null
     */
    public function getWebsiteIds();

    /**
     * @param int[]|null $ids
     * @return $this
     */
    public function setWebsiteIds(array $ids = null);

    /**
     * Retrieve wrapping image URL.
     * Function returns URL of a temporary wrapping image if it exists.
     *
     * @return string|null
     */
    public function getImageUrl();

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface $extensionAttributes
    );
}
