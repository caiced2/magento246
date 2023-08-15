<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\Catalog\Test\Fixture\Attribute as AttributeFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class AttributeReservationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->fixtures = $objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->productAttributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
    }

    /**
     * Tests that is not possible to save user-defined product attribute with reserved code.
     *
     * @return void
     */
    #[
        DataFixture(AttributeFixture::class, [], 'testAttribute')
    ]
    public function testReservedAttributeSaving()
    {
        $reservedCode = 'event';
        /** @var ProductAttributeInterface $attribute */
        $attribute = $this->fixtures->get('testAttribute');
        $attribute->setAttributeId(null);
        $attribute->setAttributeCode($reservedCode);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            "The attribute code '$reservedCode' is reserved by system. Please try another attribute code"
        );

        $this->productAttributeRepository->save($attribute);
    }
}
