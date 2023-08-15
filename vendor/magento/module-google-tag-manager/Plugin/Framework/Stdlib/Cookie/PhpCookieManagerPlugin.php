<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Plugin\Framework\Stdlib\Cookie;

use Closure;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\GoogleTagManager\Helper\CookieData;

/**
 * Plugin for public cookies with size over 4096 bytes
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class PhpCookieManagerPlugin
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager
    ) {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Save product list to session instead of cookie for cookie size > 4096 bytes
     *
     * @param PhpCookieManager $subject
     * @param Closure $proceed
     * @param array $args
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSetPublicCookie(PhpCookieManager $subject, Closure $proceed, ...$args): void
    {
        if ($args[0] == CookieData::GOOGLE_ANALYTICS_COOKIE_NAME) {
            if ($this->sizeOfCookie($args[0], $args[1]) < PhpCookieManager::MAX_COOKIE_SIZE) {
                $proceed(...$args);
            } else {
                $proceed(CookieData::GOOGLE_ANALYTICS_COOKIE_ADVANCED_NAME, true, $args[2]);
                $this->sessionManager->setAddToCartAdvanced($args[1]);
            }
        } else {
            $proceed(...$args);
        }
    }

    /**
     * Retrieve the size of a cookie.
     *
     * The size of a cookie is determined by the length of 'name=value' portion of the cookie.
     *
     * @param string $name
     * @param string $value
     * @return int
     */
    private function sizeOfCookie(string $name, string $value): int
    {
        // The constant '1' is the length of the equal sign in 'name=value'.
        return strlen($name) + 1 + strlen($value);
    }
}
