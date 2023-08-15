<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws;

use Magento\AdminGws\Model\Role as GwsRole;
use Magento\Authorization\Model\Role as AdminRole;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Magento\Framework\MessageQueue\Envelope;
use Magento\Framework\MessageQueue\QueueRepository;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoDataFixture Magento/AdminGws/_files/two_users_on_different_websites.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 * @magentoAppArea adminhtml
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerCollectionTest extends AbstractBackendController
{
    private const TOPIC_NAME = 'import_export.export';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AdminRole
     */
    private $adminRole;

    /**
     * @var GwsRole
     */
    private $adminGwsRole;

    /**
     * @var SerializerInterface
     */
    private $json;

    /**
     * @var ClearQueueProcessor
     */
    private $clearQueueProcessor;

    /**
     * @var QueueRepository
     */
    private $queueRepository;

    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->adminRole = $this->objectManager->get(AdminRole::class);
        $this->adminGwsRole = $this->objectManager->get(GwsRole::class);
        $this->json = $this->objectManager->get(SerializerInterface::class);
        $this->clearQueueProcessor = $this->objectManager->get(ClearQueueProcessor::class);
        $this->clearQueueProcessor->execute('exportProcessor');
        $this->queueRepository = $this->objectManager->get(QueueRepository::class);
        $this->defaultValueProvider = $this->objectManager->get(DefaultValueProvider::class);

        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->adminRole->load('role_has_general_access', 'role_name');
        $this->adminGwsRole->setAdminRole($this->adminRole);
        $this->clearQueueProcessor->execute('exportProcessor');

        parent::tearDown();
    }

    /**
     * @param string $websiteId
     * @return void
     * @dataProvider websiteFilteredForRestrictedAdminWhenCustomerExportDataProvider
     * @magentoConfigFixture default_store admin/security/use_form_key 1
     */
    public function testWebsiteFilteredForRestrictedAdminWhenCustomerExport(string $websiteId) : void
    {
        $filter = ['website_id' => $websiteId];
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST)
            ->setPostValue(['export_filter' => $filter])
            ->setParams(
                [
                    'entity' =>  \Magento\Customer\Model\Customer::ENTITY,
                    'file_format' => 'csv',
                ]
            );
        $this->dispatch('backend/admin/export/export');

        $expectedSessionMessage = (string)__('Message is added to queue, wait to get your file soon.'
            . ' Make sure your cron job is running to export the file');
        $this->assertSessionMessages($this->containsEqual($expectedSessionMessage));
        $this->assertRedirect($this->stringContains('/export/index/key/'));
        $queue = $this->queueRepository->get($this->defaultValueProvider->getConnection(), 'export');
        /** @var Envelope $message */
        $message = $queue->dequeue();
        $body = $this->json->unserialize($message->getBody());
        $exportFilter = $this->json->unserialize($body['export_filter']);
        $this->assertEquals($exportFilter['website_id'], [$this->websiteRepository->get('test_website')->getId()]);
    }

    protected function _getAdminCredentials()
    {
        $this->adminRole->load('role_has_test_website_access_only', 'role_name');
        $roleId = $this->adminRole->getId();
        return [
            'user' => 'johnAdmin' . $roleId,
            'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        ];
    }

    public function websiteFilteredForRestrictedAdminWhenCustomerExportDataProvider(): array
    {
        return [
            'Website Id is not allowed for Admin User' => ['13579'],
            'Website Id is not provided' => [''],
        ];
    }
}
