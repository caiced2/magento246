<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Deploy\Console\Command\App;

use Exception;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplicationDumpCommandEETest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DeploymentConfig\FileReader
     */
    private $reader;

    /**
     * @var ConfigFilePool
     */
    private $configFilePool;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DeploymentConfig\Writer
     */
    private $writer;

    /**
     * @var array
     */
    private $envConfig;

    /**
     * @var array|null
     */
    private $config;

    /**
     * @inheritdoc
     * @throws FileSystemException
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->reader = $this->objectManager->get(DeploymentConfig\FileReader::class);
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->configFilePool = $this->objectManager->get(ConfigFilePool::class);
        $this->reader = $this->objectManager->get(DeploymentConfig\Reader::class);
        $this->writer = $this->objectManager->get(DeploymentConfig\Writer::class);
        $this->configFilePool = $this->objectManager->get(ConfigFilePool::class);

        // Snapshot of configuration.
        $this->config = $this->loadConfig();
        $this->envConfig = $this->loadEnvConfig();
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    private function loadConfig(): array
    {
        return $this->reader->load(ConfigFilePool::APP_CONFIG);
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    private function loadEnvConfig(): array
    {
        return $this->reader->load(ConfigFilePool::APP_ENV);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws FileSystemException
     * @throws Exception
     */
    public function testExecute()
    {
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        /** @var ApplicationDumpCommand command */
        $command = $this->objectManager->create(ApplicationDumpCommand::class);
        $command->run($this->getMockForAbstractClass(InputInterface::class), $outputMock);

        $config = $this->loadConfig();

        $this->validateThemesSection($config);
    }

    /**
     * Validates 'themes' section in configuration data.
     *
     * @param array $config The configuration array
     * @return void
     */
    private function validateThemesSection(array $config)
    {
        $this->assertEquals(
            [
                'parent_id' => 'Magento/backend',
                'theme_path' => 'Magento/spectrum',
                'theme_title' => 'Magento Spectrum',
                'is_featured' => '0',
                'area' => 'adminhtml',
                'type' => '0',
                'code' => 'Magento/spectrum',
            ],
            $config['themes']['adminhtml/Magento/spectrum']
        );
    }

    /**
     * @inheritdoc
     * @throws FileSystemException
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $directoryListWrite = $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG);
        $directoryListWrite->writeFile(
            $this->configFilePool->getPath(ConfigFilePool::APP_CONFIG),
            "<?php\n return array();\n"
        );
        $directoryListWrite->writeFile(
            $this->configFilePool->getPath(ConfigFilePool::APP_ENV),
            "<?php\n return array();\n"
        );

        $this->writer->saveConfig([ConfigFilePool::APP_CONFIG => $this->config]);
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $this->envConfig]);

        /** @var DeploymentConfig $deploymentConfig */
        $deploymentConfig = $this->objectManager->get(DeploymentConfig::class);
        $deploymentConfig->resetData();
    }
}
