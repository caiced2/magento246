<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model;

/**
 * Website restrictions configuration model interface
 *
 * @api
 * @since 100.0.2
 */
interface ConfigInterface
{
    /**
     * Get generic actions list
     *
     * @return array
     */
    public function getGenericActions();

    /**
     * Get register actions list
     *
     * @return array
     */
    public function getRegisterActions();

    /**
     * Define if restriction is active
     *
     * @param \Magento\Store\Model\Store|string|int $store
     * @return bool
     */
    public function isRestrictionEnabled($store = null);

    /**
     * Get restriction mode
     *
     * @return int
     */
    public function getMode();

    /**
     * Get restriction HTTP status code
     *
     * @return int
     */
    public function getHTTPStatusCode();

    /**
     * Get restriction HTTP redirect code
     *
     * @return int
     */
    public function getHTTPRedirectCode();

    /**
     * Get restriction landing page code
     *
     * @return int
     */
    public function getLandingPageCode();
}
