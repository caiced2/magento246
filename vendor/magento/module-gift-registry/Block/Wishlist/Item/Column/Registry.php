<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Wishlist\Item\Column;

/**
 * Wishlist item "Add to gift registry" column block
 *
 * @api
 * @since 100.0.2
 */
class Registry extends \Magento\Wishlist\Block\Customer\Wishlist\Item\Column
{
    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        array $data = []
    ) {
        $this->_giftRegistryData = $giftRegistryData;
        parent::__construct(
            $context,
            $httpContext,
            $data
        );
    }

    /**
     * Truncate string
     *
     * @param string $value
     * @param int $length
     * @param string $etc
     * @param string $remainder
     * @param bool $breakWords
     * @return string
     * @codeCoverageIgnore
     */
    public function truncateString($value, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        return $this->filterManager->truncate(
            $value,
            ['length' => $length, 'etc' => $etc, 'remainder' => $remainder, 'breakWords' => $breakWords]
        );
    }

    /**
     * Check whether module is available
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isEnabled()
    {
        return $this->_giftRegistryData->isEnabled() && count($this->getGiftRegistryList());
    }

    /**
     * Return list of current customer gift registries
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\GiftRegistry\Collection
     * @codeCoverageIgnore
     */
    public function getGiftRegistryList()
    {
        return $this->_giftRegistryData->getCurrentCustomerEntityOptions();
    }

    /**
     * Check if wishlist item can be added to gift registry
     *
     * @param \Magento\Catalog\Model\Product $item
     * @return bool
     * @codeCoverageIgnore
     */
    public function checkProductType($item)
    {
        return $this->_giftRegistryData->canAddToGiftRegistry($item);
    }

    /**
     * Retrieve column related javascript code
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getJs()
    {
        $addUrl = $this->getUrl('magento_giftregistry/index/wishlist');
        return "
        function addProductToGiftregistry(itemId, giftregistryId) {
            var form = new Element('form', {method: 'post', action: '" .
            $addUrl .
            "'});
            form.insert(new Element('input', {type: 'hidden', name: 'item', value: itemId}));
            form.insert(new Element('input', {type: 'hidden', name: 'entity', value: giftregistryId}));
            $(document.body).insert(form);
            $(form).trigger('submit');
            return false;
        }
        ";
    }
}
