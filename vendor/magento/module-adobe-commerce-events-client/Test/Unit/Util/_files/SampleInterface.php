<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Util\_files;

interface SampleInterface
{
    public function getId(): int;

    public function setName($name): string;

    public function getAttributeSetId(): int;

    public function isAvailable(): bool;

    public function resetName(): void;
}
