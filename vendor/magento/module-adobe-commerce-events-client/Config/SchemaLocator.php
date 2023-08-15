<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Exception\NotFoundException;

/**
 * IO events resources configuration schema locator
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * @var UrnResolver
     */
    protected UrnResolver $urnResolver;

    /**
     * Initialize dependencies.
     *
     * @param UrnResolver $urnResolver
     */
    public function __construct(UrnResolver $urnResolver)
    {
        $this->urnResolver = $urnResolver;
    }

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function getSchema()
    {
        return $this->urnResolver->getRealPath(
            'urn:magento:module:Magento_AdobeCommerceEventsClient:etc/io_events.xsd'
        );
    }

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function getPerFileSchema()
    {
        return $this->urnResolver->getRealPath(
            'urn:magento:module:Magento_AdobeCommerceEventsClient:etc/io_events.xsd'
        );
    }
}
