<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Category;

use Magento\VisualMerchandiser\Controller\Adminhtml\Category\Tile;

class TileTest extends AbstractGrid
{
    /**
     * Defines which controller is to be used
     * @var string
     */
    protected $controllerClass = Tile::class;

    /**
     * Set up expected parameters and call super
     * @return void
     */
    public function testExecute()
    {
        $expectedBlock = \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Tile::class;
        $expectedId = 'tile';
        $this->progressTest($expectedBlock, $expectedId);
    }
}
