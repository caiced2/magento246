<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Adobe console workspace runtime data
 *
 * @api
 * @since 1.1.0
 */
class Runtime
{
    /**
     * @var RuntimeNamespace[]
     */
    private array $namespaces;

    /**
     * Get the runtime namespace
     *
     * @return RuntimeNamespace[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Set the project namespace
     *
     * @param RuntimeNamespace[] $namespaces
     */
    public function setNamespaces(array $namespaces): void
    {
        $this->namespaces = $namespaces;
    }
}
