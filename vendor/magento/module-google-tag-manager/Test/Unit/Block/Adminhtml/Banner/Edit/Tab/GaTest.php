<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Banner\Model\Banner;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Block\Adminhtml\Banner\Edit\Tab\Ga;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GaTest extends TestCase
{
    /** @var Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var FormFactory|MockObject */
    protected $formFactory;

    /** @var Data|MockObject */
    protected $googleTagManagerHelper;

    /**
     * @var TagManagerConfig|MockObject
     */
    private $tagManagerConfig;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->formFactory = $this->createPartialMock(FormFactory::class, ['create']);
        $this->googleTagManagerHelper = $this->createMock(Data::class);
        $directory = $this->getMockForAbstractClass(ReadInterface::class);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->any())->method('getDirectoryRead')->willReturn($directory);
        $this->tagManagerConfig = $this->getMockBuilder(TagManagerConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            Ga::class,
            [
                'registry' => $this->registry,
                'formFactory' => $this->formFactory,
                'helper' => $this->googleTagManagerHelper,
                'filesystem' => $filesystem,
                'tagManagerConfig' => $this->tagManagerConfig
            ]
        );
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\Adminhtml\Banner\Edit\Tab\Ga::_prepareForm
     */
    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn(false);
        $this->tagManagerConfig->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $fieldset = $this->createMock(Fieldset::class);
        $fieldset->expects($this->any())->method('addField')->withConsecutive(
            [
                'is_ga_enabled',
                'select',
                [
                    'label' => 'Send to Google',
                    'name' => 'is_ga_enabled',
                    'required' => false,
                    'options' => [
                        1 => 'Yes',
                        0 => 'No',
                    ],
                ]
            ],
            [
                'ga_creative',
                'text',
                [
                    'label' => 'Creative',
                    'name' => 'ga_creative',
                    'required' => false,
                ]
            ]
        )->willReturnSelf();

        $banner = $this->createMock(Banner::class);
        $banner->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $banner->expects($this->atLeastOnce())->method('setData')->with('is_ga_enabled', 1)->willReturnSelf();
        $banner->expects($this->atLeastOnce())->method('getData')->willReturn(['name' => 'test']);

        $form = $this->getMockBuilder(Form::class)
            ->addMethods(['setHtmlIdPrefix'])
            ->onlyMethods(['addFieldset', 'setValues'])
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())->method('setHtmlIdPrefix')->with('banner_googleanalytics_settings_')
            ->willReturnSelf();
        $form->expects($this->once())->method('addFieldset')->with(
            'ga_fieldset',
            ['legend' => 'Google Analytics Enhanced Ecommerce Settings']
        )->willReturn($fieldset);
        $form->expects($this->once())->method('setValues')->with(['name' => 'test']);

        $this->registry->expects($this->any())->method('registry')->with('current_banner')->willReturn($banner);

        $this->formFactory->expects($this->any())->method('create')->willReturn($form);
        $this->ga->toHtml();
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\Adminhtml\Banner\Edit\Tab\Ga::_prepareForm
     */
    public function testToHtmlGaDisabled()
    {
        $this->googleTagManagerHelper->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn(false);
        $this->tagManagerConfig->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn(false);
        $this->formFactory->expects($this->never())->method('create');
    }
}
