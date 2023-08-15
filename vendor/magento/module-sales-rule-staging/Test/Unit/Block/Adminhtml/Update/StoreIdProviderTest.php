<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Block\Adminhtml\Update;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRuleStaging\Block\Adminhtml\Update\StoreIdProvider;
use Magento\SalesRuleStaging\Model\Staging\PreviewStoreIdResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\TestCase;

/**
 * Test cart price rule staging preview store id provider
 */
class StoreIdProviderTest extends TestCase
{
    /**
     * @var StoreIdProvider
     */
    private $model;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var PreviewStoreIdResolver
     */
    private $previewStoreIdResolver;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request = $this->createMock(RequestInterface::class);
        $this->ruleRepository = $this->createMock(RuleRepositoryInterface::class);
        $this->previewStoreIdResolver = $this->createMock(PreviewStoreIdResolver::class);
        $previewUpdateIdProvider = $this->createMock(IdProvider::class);
        $versionManager = $this->createMock(VersionManager::class);
        $versionManager->method('getCurrentVersion')
            ->willReturn($this->createMock(UpdateInterface::class));
        $this->model = new StoreIdProvider(
            $request,
            $this->ruleRepository,
            $this->previewStoreIdResolver,
            $previewUpdateIdProvider,
            $versionManager
        );
    }

    /**
     * Test that non null value is returned if rule exists
     */
    public function testShouldReturnNonNullValue(): void
    {
        $storeId = 3;
        $this->ruleRepository->expects($this->once())
            ->method('getById')
            ->willReturn($this->createMock(RuleInterface::class));
        $this->previewStoreIdResolver->expects($this->once())
            ->method('execute')
            ->willReturn($storeId);
        $this->assertEquals($storeId, $this->model->getStoreId());
    }

    /**
     * Test that null value is returned if rule does not exist
     */
    public function testShouldReturnNullValue(): void
    {
        $this->ruleRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(new NoSuchEntityException());
        $this->assertNull($this->model->getStoreId());
    }
}
