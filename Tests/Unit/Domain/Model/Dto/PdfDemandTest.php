<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Domain\Model\Dto;

use Extcode\CartPdf\Domain\Model\Dto\PdfDemand;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PdfDemandTest extends UnitTestCase
{
    /**
     * @var PdfDemand
     */
    protected $fixture;

    protected function setUp(): void
    {
        $this->fixture = new PdfDemand();
    }

    protected function tearDown(): void
    {
        unset($this->fixture);
    }

    /**
     * @test
     */
    public function getDebugInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->fixture->getDebug()
        );
    }

    /**
     * @test
     */
    public function getFontSizeInitiallyReturnsDefaultFontSize()
    {
        self::assertSame(
            8,
            $this->fixture->getFontSize()
        );
    }

    /**
     * @test
     */
    public function getFoldMarksEnabledInitiallyReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->getFoldMarksEnabled()
        );
    }

    /**
     * @test
     */
    public function getAddressFieldMarksEnabledInitiallyReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->getAddressFieldMarksEnabled()
        );
    }
}
