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
    public function setDebugSetsDebug()
    {
        $debug = 1;

        $this->fixture->setDebug($debug);
        self::assertSame(
            $debug,
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
    public function setFontSizeSetsFontSize()
    {
        $fontSize = 10;

        $this->fixture->setFontSize($fontSize);
        self::assertSame(
            $fontSize,
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
    public function setFoldMarksEnabledSetsFoldMarksEnabled()
    {
        $this->fixture->setFoldMarksEnabled(false);
        self::assertFalse(
            $this->fixture->getFoldMarksEnabled()
        );

        $this->fixture->setFoldMarksEnabled(true);
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

    /**
     * @test
     */
    public function setAddressFieldMarksEnabledSetsAddressFieldMarksEnabled()
    {
        $this->fixture->setAddressFieldMarksEnabled(false);
        self::assertFalse(
            $this->fixture->getAddressFieldMarksEnabled()
        );

        $this->fixture->setAddressFieldMarksEnabled(true);
        self::assertTrue(
            $this->fixture->getAddressFieldMarksEnabled()
        );
    }
}
