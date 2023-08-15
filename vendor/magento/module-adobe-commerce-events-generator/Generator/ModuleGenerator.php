<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php;

/**
 * Generates Magento module skeleton: composer.json, registration.php, etc/module.xml
 */
class ModuleGenerator
{
    public const MODULE_VENDOR = 'Magento';
    public const MODULE_NAME = 'AdobeCommerceEvents';

    public const MODULE_PLUGIN_SPACE = 'Plugin';

    private const PLUGIN_API_INTERFACE_TPL = 'pluginApiInterface.phtml';
    private const PLUGIN_RESOURCE_MODEL_TPL = 'pluginResourceModel.phtml';
    private const OBSERVER_EVENT_PLUGIN_TPL = 'observerEventPlugin.phtml';

    private const PHP_REQ = '~7.4.0||~8.1.0||~8.2.0';

    /**
     * @var array
     */
    private array $composerSkeleton = [
        "name" => null,
        "description" => "N/A",
        "config" => ["sort-packages" => true],
        "require" => null,
        "type" => "magento2-module",
        "version" => null,
        "license" => ["OSL-3.0", "AFL-3.0"],
        "autoload" => [
            "files" => ["registration.php"],
            "psr-4" => null
        ]
    ];

    /**
     * @var Php
     */
    private Php $templateEngine;

    /**
     * @var BlockFactory
     */
    private BlockFactory $blockFactory;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var string
     */
    private string $templatesPath;

    /**
     * @var string
     */
    private string $outputDir;

    /**
     * @param Php $templateEngine
     * @param BlockFactory $blockFactory
     * @param File $file
     * @param string|null $templatesPath
     */
    public function __construct(
        Php $templateEngine,
        BlockFactory $blockFactory,
        File $file,
        string $templatesPath = null
    ) {
        $this->templateEngine = $templateEngine;
        $this->blockFactory = $blockFactory;
        $this->file = $file;
        $this->templatesPath = $templatesPath ?: __DIR__ . '/../templates';
    }

    /**
     * Generates module skeleton including `composer.json`, `registration.php`, `etc/module.xml`.
     *
     * @param Module $module
     * @param string|null $version
     * @return void
     * @throws FileSystemException
     */
    public function run(
        Module $module,
        ?string $version
    ): void {
        $path = $this->getPath($module);
        if ($this->file->isDirectory($path)) {
            $this->file->deleteDirectory($path);
        }
        /** @var ModuleBlock $moduleBlock */
        $moduleBlock = $this->blockFactory->createBlock(ModuleBlock::class, [
            'module' => $module
        ]);

        $fileMap = [
            'registrationPhp.phtml' => 'registration.php',
            'readmeMd.phtml' => 'README.md',
            'moduleXml.phtml' => 'etc/module.xml',
            'license.phtml' => 'LICENSE.txt',
            'licenseAfl.phtml' => 'LICENSE_AFL.txt',
            'di.phtml' => 'etc/di.xml',
        ];

        foreach ($fileMap as $templateFile => $targetFilePath) {
            $this->createFileFromTemplate(
                $moduleBlock,
                $this->templatesPath . DIRECTORY_SEPARATOR . $templateFile,
                $path . DIRECTORY_SEPARATOR . $targetFilePath,
            );
        }

        $this->generatePluginList($moduleBlock);
        $this->generateObserverList($moduleBlock);

        $this->generatePlugins($moduleBlock);
        $this->generateObserverEventPlugin($moduleBlock);

        $this->generateComposer(
            $module->getVendor(),
            $module->getName(),
            $path,
            $module->getDependencies(),
            $version
        );
    }

    /**
     * Generates composer json file.
     *
     * @param string $vendor
     * @param string $module
     * @param string $path
     * @param array $dependencies
     * @param string|null $version
     * @return void
     * @throws FileSystemException
     */
    private function generateComposer(
        string $vendor,
        string $module,
        string $path,
        array $dependencies,
        ?string $version
    ): void {
        $name = $this->getModuleName($module);
        $version = $version ?? '0.0.1';

        $composerContent = $this->composerSkeleton;
        $composerContent['name'] = strtolower($vendor) . '/module-' . $name;
        $composerContent['version'] = $version;
        $composerContent['autoload']['psr-4'] = [$vendor . '\\' . $module . '\\' => ""];
        $composerContent['require'] = [
            'php' => self::PHP_REQ,
            'magento/framework' => '*'
        ];
        $composerContent['suggest'] = $this->generateSuggestedList($dependencies);

        $composerContent = json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->createFile($path . '/composer.json', $composerContent . "\n");
    }

    /**
     * Converts camel case name to hyphen separated lower case words.
     *
     * @param string $value
     * @return string
     */
    private function getModuleName(string $value): string
    {
        $pattern = '/(?:^|[A-Z])[a-z]+/';
        preg_match_all($pattern, $value, $matches);
        return strtolower(implode('-', $matches[0]));
    }

    /**
     * Returns module base path
     *
     * @param Module $module
     * @return string
     */
    private function getPath(Module $module): string
    {
        return $this->outputDir . '/' . $module->getVendor() . '/' . $module->getName();
    }

    /**
     * Generates plugins for api interfaces
     *
     * @param ModuleBlock $moduleBlock
     * @return void
     * @throws FileSystemException
     */
    private function generatePlugins(ModuleBlock $moduleBlock): void
    {
        $module = $moduleBlock->getModule();
        $basePath = $this->getPath($module);
        foreach ($module->getPlugins() as $plugin) {
            $template = $plugin['type'] == PluginConverter::TYPE_RESOURCE_MODEL ?
                self::PLUGIN_RESOURCE_MODEL_TPL : self::PLUGIN_API_INTERFACE_TPL;

            $this->createFileFromTemplate(
                $moduleBlock,
                $this->templatesPath . DIRECTORY_SEPARATOR . $template,
                $basePath . $plugin['path'],
                $plugin
            );
        }
    }

    /**
     * Generates plugin for emitting observer event data
     *
     * @param ModuleBlock $moduleBlock
     * @return void
     * @throws FileSystemException
     */
    private function generateObserverEventPlugin(ModuleBlock $moduleBlock): void
    {
        $module = $moduleBlock->getModule();
        $basePath = $this->getPath($module);
        $plugin = $module->getObserverEventPlugin();

        if (!empty($plugin)) {
            $this->createFileFromTemplate(
                $moduleBlock,
                $this->templatesPath . DIRECTORY_SEPARATOR . self::OBSERVER_EVENT_PLUGIN_TPL,
                $basePath . $plugin['path'],
                $plugin
            );
        }
    }

    /**
     * Generates a class with a list of subscribed plugin events.
     *
     * @param ModuleBlock $moduleBlock
     * @return void
     * @throws FileSystemException
     */
    private function generatePluginList(ModuleBlock $moduleBlock): void
    {
        $this->createFileFromTemplate(
            $moduleBlock,
            $this->templatesPath . DIRECTORY_SEPARATOR . 'eventCodeList.phtml',
            $this->getPath($moduleBlock->getModule()) . '/EventCode/Plugin.php',
            [
                'name' => 'Plugin',
                'plugins' => $moduleBlock->getModule()->getPlugins()
            ]
        );
    }

    /**
     * Generates a class with a list of all subscribed observer events.
     *
     * @param ModuleBlock $moduleBlock
     * @return void
     * @throws FileSystemException
     */
    private function generateObserverList(ModuleBlock $moduleBlock): void
    {
        if (empty($moduleBlock->getModule()->getObserverEvents())) {
            return;
        }

        $this->createFileFromTemplate(
            $moduleBlock,
            $this->templatesPath . DIRECTORY_SEPARATOR . 'eventCodeList.phtml',
            $this->getPath($moduleBlock->getModule()) . '/EventCode/Observer.php',
            [
                'name' => 'Observer',
                'observers' => $moduleBlock->getModule()->getObserverEvents()
            ]
        );
    }

    /**
     * Sets output directory where the module will be generated.
     *
     * @param string $outputDir
     * @return void
     */
    public function setOutputDir(string $outputDir): void
    {
        $this->outputDir = $outputDir;
    }

    /**
     * Generates the suggest section for a generated composer.json file given an array containing module dependencies.
     *
     * @param array $dependencies
     * @return string[]
     */
    private function generateSuggestedList(array $dependencies): array
    {
        $suggested = [];

        foreach ($dependencies as $dependency) {
            $suggested[$dependency['packageName']] = '*';
        }

        return $suggested;
    }

    /**
     * Creates output file. Creates directory recursively if the directory does not exist.
     *
     * @param string $path
     * @param string $content
     * @return void
     * @throws FileSystemException
     */
    private function createFile(string $path, string $content): void
    {
        $dir = $this->file->getParentDirectory($path);

        $this->file->createDirectory($dir);

        $resource = $this->file->fileOpen($path, 'w');
        $this->file->fileWrite($resource, $content);
        $this->file->fileClose($resource);
    }

    /**
     * Creates output file. Creates directory recursively if the directory does not exist.
     *
     * @param BlockInterface $block
     * @param string $templatePath
     * @param string $filePath
     * @param array $dictionary
     * @return void
     * @throws FileSystemException
     */
    private function createFileFromTemplate(
        BlockInterface $block,
        string $templatePath,
        string $filePath,
        array $dictionary = []
    ): void {
        $content = $this->templateEngine->render($block, $templatePath, $dictionary);

        $this->createFile($filePath, $content);
    }
}
