<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer;

use Magento\Framework\Session\SessionManagerInterface;

/**
 * Customer temporary files session storage
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class TemporaryFileSessionStorage implements TemporaryFileStorageInterface
{
    const SESSION_KEY = '_tmp_files';

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @param SessionManagerInterface $session
     */
    public function __construct(
        SessionManagerInterface $session
    ) {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return $this->session->getData(self::SESSION_KEY) ?? [];
    }

    /**
     * @inheritdoc
     */
    public function set(array $value): void
    {
        $this->session->setData(self::SESSION_KEY, $value);
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        $this->session->unsetData(self::SESSION_KEY);
    }
}
