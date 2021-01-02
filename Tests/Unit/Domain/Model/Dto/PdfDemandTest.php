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
    protected $fixture = null;

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
        $this->assertSame(
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
        $this->assertSame(
            $debug,
            $this->fixture->getDebug()
        );
    }

    /**
     * @test
     */
    public function getFontSizeInitiallyReturnsDefaultFontSize()
    {
        $this->assertSame(
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
        $this->assertSame(
            $fontSize,
            $this->fixture->getFontSize()
        );
    }

    /**
     * @test
     */
    public function getFoldMarksEnabledInitiallyReturnsTrue()
    {
        $this->assertTrue(
            $this->fixture->getFoldMarksEnabled()
        );
    }

    /**
     * @test
     */
    public function setFoldMarksEnabledSetsFoldMarksEnabled()
    {
        $this->fixture->setFoldMarksEnabled(false);
        $this->assertFalse(
            $this->fixture->getFoldMarksEnabled()
        );

        $this->fixture->setFoldMarksEnabled(true);
        $this->assertTrue(
            $this->fixture->getFoldMarksEnabled()
        );
    }

    /**
     * @test
     */
    public function getAddressFieldMarksEnabledInitiallyReturnsTrue()
    {
        $this->assertTrue(
            $this->fixture->getAddressFieldMarksEnabled()
        );
    }

    /**
     * @test
     */
    public function setAddressFieldMarksEnabledSetsAddressFieldMarksEnabled()
    {
        $this->fixture->setAddressFieldMarksEnabled(false);
        $this->assertFalse(
            $this->fixture->getAddressFieldMarksEnabled()
        );

        $this->fixture->setAddressFieldMarksEnabled(true);
        $this->assertTrue(
            $this->fixture->getAddressFieldMarksEnabled()
        );
    }
}
