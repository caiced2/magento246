<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Util\_files;

use Magento\Framework\DataObject;

class SampleClass extends DataObject
{
    public function isAvailable(): bool
    {
        return $this->_getData('available');
    }

    public function getItemName(): string
    {
        return $this->getData('item_name');
    }

    public function getCount($index): mixed
    {
        return $this->data['count'][$index];
    }

    public function resetName(): void
    {
        $this->data['item_name'] = '';
    }

    public function getQuantity(): int
    {
        return $this->data['quantity'];
    }
    
    public function getPrice(): float
    {
        return $this->_data['price'];
    }
}
