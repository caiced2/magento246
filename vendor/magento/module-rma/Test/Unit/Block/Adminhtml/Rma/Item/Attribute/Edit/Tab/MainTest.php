<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Model\Session;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Config\Model\Config\Source\YesnoFactory;
use Magento\Eav\Helper\Data;
use Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Cache\LockGuardedCacheLoader;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Math\Random;
use Magento\Framework\Registry;
use Magento\Framework\Session\Generic;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Element\Template\File\Resolver;
use Magento\Framework\View\Element\Template\File\Validator;
use Magento\Framework\View\FileSystem as FilesystemView;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\TemplateEnginePool;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\Rma\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab\Main;
use Magento\Rma\Helper\Eav;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class MainTest extends TestCase
{
    /** @var Main */
    private $main;

    /** @var Http|MockObject */
    private $requestInterface;

    /** @var LayoutInterface|MockObject */
    private $layoutInterface;

    /** @var ManagerInterface|MockObject */
    private $managerInterface;

    /** @var UrlInterface|MockObject */
    private $urlInterface;

    /** @var CacheInterface|MockObject */
    private $cacheInterface;

    /** @var DesignInterface|MockObject */
    private $designInterface;

    /** @var Generic|MockObject */
    private $session;

    /** @var SidResolverInterface|MockObject */
    private $sidResolverInterface;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var Repository|MockObject */
    private $assetRepo;

    /** @var ConfigInterface|MockObject */
    private $configInterface;

    /** @var StateInterface|MockObject */
    private $cacheState;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var Escaper|MockObject */
    private $escaper;

    /** @var FilterManager|MockObject */
    private $filterManager;

    /** @var TimezoneInterface|MockObject */
    private $timezoneInterface;

    /** @var \Magento\Framework\Translate\Inline\StateInterface|MockObject */
    private $translateState;

    /** @var Filesystem|MockObject */
    private $appFilesystem;

    /** @var FilesystemView|MockObject */
    private $viewFilesystem;

    /** @var TemplateEnginePool|MockObject */
    private $templateEnginePool;

    /** @var State|MockObject */
    private $appState;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerInterface;

    /** @var AuthorizationInterface|MockObject */
    private $authorizationInterface;

    /** @var Session|MockObject */
    private $backendSession;

    /** @var Random|MockObject */
    private $random;

    /** @var FormKey|MockObject */
    private $formKey;

    /** @var Config|MockObject */
    private $pageConfig;

    /** @var NameBuilder|MockObject */
    private $nameBuilder;

    /** @var Context|MockObject */
    private $context;

    /** @var Registry|MockObject */
    private $registry;

    /** @var FormFactory|MockObject */
    private $formFactory;

    /** @var Data|MockObject */
    private $eavHelper;

    /** @var MockObject */
    private $yesnoFactory;

    /** @var MockObject */
    private $inputtypeFactory;

    /** @var \Magento\CustomAttributeManagement\Helper\Data|MockObject */
    private $customAttributeManagementHelper;

    /** @var Eav|MockObject */
    private $rmaEavHelper;

    /** @var  Resolver|MockObject */
    private $resolver;

    /** @var  Validator|MockObject */
    private $validator;

    /** @var \Magento\Eav\Model\Entity\Attribute\Config|MockObject */
    private $attributeConfig;

    /** @var NotProtectedExtension|MockObject */
    private $extensionValidator;

    /** @var LockGuardedCacheLoader|MockObject */
    private $lockQuery;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->requestInterface = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutInterface = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->managerInterface = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->urlInterface = $this->getMockForAbstractClass(UrlInterface::class);
        $this->cacheInterface = $this->getMockForAbstractClass(CacheInterface::class);
        $this->designInterface = $this->getMockForAbstractClass(DesignInterface::class);
        $this->session = $this->createMock(Generic::class);
        $this->sidResolverInterface = $this->getMockForAbstractClass(SidResolverInterface::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->assetRepo = $this->createMock(Repository::class);
        $this->configInterface = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->cacheState = $this->getMockForAbstractClass(StateInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->filterManager = $this->createMock(FilterManager::class);
        $this->timezoneInterface = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->translateState = $this->createMock(\Magento\Framework\Translate\Inline\StateInterface::class);
        $this->appFilesystem = $this->createMock(Filesystem::class);
        $this->viewFilesystem = $this->createMock(FilesystemView::class);
        $this->templateEnginePool = $this->createMock(TemplateEnginePool::class);
        $this->appState = $this->createMock(State::class);
        $this->storeManagerInterface = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->authorizationInterface = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->backendSession = $this->createMock(Session::class);
        $this->random = $this->createMock(Random::class);
        $this->formKey = $this->createMock(FormKey::class);
        $this->nameBuilder = $this->createMock(NameBuilder::class);
        $this->pageConfig = $this->createMock(Config::class);
        $this->resolver = $this->createMock(Resolver::class);
        $this->validator = $this->createMock(Validator::class);
        $this->lockQuery = $this->createMock(LockGuardedCacheLoader::class);

        $this->context = $this->getMockBuilder(Context::class)
            ->setConstructorArgs(
                [
                    'request' => $this->requestInterface,
                    'layout' => $this->layoutInterface,
                    'eventManager' => $this->managerInterface,
                    'urlBuilder' => $this->urlInterface,
                    'cache' => $this->cacheInterface,
                    'design' => $this->designInterface,
                    'session' => $this->session,
                    'sidResolver' => $this->sidResolverInterface,
                    'scopeConfig' => $this->scopeConfig,
                    'assetRepo' => $this->assetRepo,
                    'viewConfig' => $this->configInterface,
                    'cacheState' => $this->cacheState,
                    'logger' => $this->logger,
                    'escaper' => $this->escaper,
                    'filterManager' => $this->filterManager,
                    'localeDate' => $this->timezoneInterface,
                    'inlineTranslation' => $this->translateState,
                    'filesystem' => $this->appFilesystem,
                    'viewFileSystem' => $this->viewFilesystem,
                    'enginePool' => $this->templateEnginePool,
                    'appState' => $this->appState,
                    'storeManager' => $this->storeManagerInterface,
                    'pageConfig' => $this->pageConfig,
                    'resolver' => $this->resolver,
                    'validator' => $this->validator,
                    'authorization' => $this->authorizationInterface,
                    'backendSession' => $this->backendSession,
                    'mathRandom' => $this->random,
                    'formKey' => $this->formKey,
                    'nameBuilder' => $this->nameBuilder,
                    'lockQuery' => $this->lockQuery
                ]
            )
            ->getMock();
        $this->context->expects($this->any())->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->registry = $this->createMock(Registry::class);
        $this->formFactory = $this->createMock(FormFactory::class);
        $this->eavHelper = $this->createMock(Data::class);
        $this->yesnoFactory = $this->createPartialMock(
            YesnoFactory::class,
            ['create']
        );
        $this->inputtypeFactory = $this->createPartialMock(
            InputtypeFactory::class,
            ['create']
        );
        $this->attributeConfig = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Config::class);
        $this->customAttributeManagementHelper = $this->createMock(
            \Magento\CustomAttributeManagement\Helper\Data::class
        );
        $this->rmaEavHelper = $this->createMock(Eav::class);
        $this->extensionValidator = $this->createMock(NotProtectedExtension::class);

        $this->main = (new ObjectManagerHelper($this))->getObject(
            Main::class,
            [
                'context' => $this->context,
                'registry' => $this->registry,
                'formFactory' => $this->formFactory,
                'eavData' => $this->eavHelper,
                'yesnoFactory' => $this->yesnoFactory,
                'inputTypeFactory' => $this->inputtypeFactory,
                'attributeConfig' => $this->attributeConfig,
                'attributeHelper' => $this->customAttributeManagementHelper,
                'rmaEav' => $this->rmaEavHelper,
                'extensionValidator' => $this->extensionValidator,
            ]
        );
    }

    public function testUsedInFormsAndIsVisibleFieldsDependency()
    {
        $fieldset = $this->createMock(Fieldset::class);
        $fieldset->expects($this->any())->method('addField')->willReturnSelf();
        $form = $this->createPartialMock(Form::class, ['addFieldset', 'getElement']);
        $form->expects($this->any())->method('addFieldset')->willReturn($fieldset);
        $form->expects($this->any())->method('getElement')->willReturn($fieldset);
        $this->formFactory->expects($this->any())->method('create')->willReturn($form);

        $yesno = $this->createMock(Yesno::class);
        $this->yesnoFactory->expects($this->any())->method('create')->willReturn($yesno);

        $inputtype = $this->createMock(Yesno::class);
        $this->inputtypeFactory->expects($this->any())->method('create')
            ->willReturn($inputtype);

        $this->customAttributeManagementHelper->expects($this->any())->method('getAttributeElementScopes')
            ->willReturn([]);

        $this->customAttributeManagementHelper->expects($this->any())->method('getFrontendInputOptions')
            ->willReturn([]);

        $dependenceBlock = $this->createMock(Dependence::class);
        $dependenceBlock->expects($this->any())->method('addFieldMap')->willReturnSelf();

        $this->layoutInterface->expects($this->once())->method('createBlock')
            ->with(Dependence::class)
            ->willReturn($dependenceBlock);
        $this->layoutInterface->expects($this->any())->method('setChild')->with(
            null,
            null,
            'form_after'
        )->willReturnSelf();

        $this->appFilesystem->expects($this->any())->method('getDirectoryRead')
            ->willThrowException(new \Exception('test'));

        $this->main->setAttributeObject(
            new DataObject(['entity_type' => new DataObject([])])
        );

        $this->extensionValidator->expects($this->once())
            ->method('getProtectedFileExtensions')
            ->willReturn([]);

        $reflection = new \ReflectionClass(get_class($this->main));
        $reflectionProperty = $reflection->getProperty('_eventManager');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->managerInterface);
        $reflectionProperty = $reflection->getProperty('_localeDate');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->timezoneInterface);
        $reflectionProperty = $reflection->getProperty('_urlBuilder');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->urlInterface);
        $reflectionProperty = $reflection->getProperty('_layout');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->layoutInterface);
        $reflectionProperty = $reflection->getProperty('_appState');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->appState);
        $reflectionProperty = $reflection->getProperty('resolver');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->resolver);
        $reflectionProperty = $reflection->getProperty('_filesystem');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->appFilesystem);

        try {
            $this->main->toHtml();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('test', $e->getMessage());
        }
    }
}
