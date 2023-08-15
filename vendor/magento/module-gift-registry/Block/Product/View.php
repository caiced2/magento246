<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Product;

/**
 * Front end helper block to show GiftRegistry mark
 *
 * @api
 * @since 100.0.2
 */
class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * GiftRegistry param flag value in url option params
     * @var string
     */
    const FLAG = 'giftregistry';

    /**
     * Set template to specified block
     *
     * @param string $blockName
     * @param string $template
     * @return void
     * @throws \LogicException
     */
    public function setGiftRegistryTemplate($blockName, $template)
    {
        $block = $this->getLayout()->getBlock($blockName);
        if (!$block) {
            throw new \LogicException("Could not find block '{$blockName}'");
        }
        if ($this->_isGiftRegistryRedirect()) {
            $block->setTemplate($template);
        }
    }

    /**
     * Set GiftRegistry URL for the template
     *
     * @param string $blockName
     * @return void
     * @throws \LogicException
     */
    public function setGiftRegistryUrl($blockName)
    {
        $block = $this->getLayout()->getBlock($blockName);
        if (!$block) {
            throw new \LogicException("Could not find block '{$blockName}'");
        }
        if ($this->_isGiftRegistryRedirect()) {
            $block->setAddToGiftregistryUrl($this->getAddToGiftregistryUrl());
        }
    }

    /**
     * Return giftregistry add cart items url
     *
     * @return string
     */
    public function getAddToGiftregistryUrl()
    {
        return $this->getUrl(
            'magento_giftregistry/index/cart',
            ['entity' => $this->getRequest()->getParam('entity')]
        );
    }

    /**
     * Return gift registry redirect flag.
     *
     * @return bool
     */
    protected function _isGiftRegistryRedirect()
    {
        return $this->getRequest()->getParam('options') == self::FLAG;
    }
}
