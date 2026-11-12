<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Unit\Domain\Model\Dto;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\CartPdf\Domain\Model\Dto\PdfDemand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(PdfDemand::class)]
final class PdfDemandTest extends UnitTestCase
{
    #[Test]
    public function getDebugInitiallyReturnsZero(): void
    {
        self::assertSame(0, (new PdfDemand())->getDebug());
    }

    #[Test]
    public function getFontSizeInitiallyReturnsDefaultFontSize(): void
    {
        self::assertSame(8, (new PdfDemand())->getFontSize());
    }

    #[Test]
    public function getFoldMarksEnabledInitiallyReturnsTrue(): void
    {
        self::assertTrue((new PdfDemand())->getFoldMarksEnabled());
    }

    #[Test]
    public function getAddressFieldMarksEnabledInitiallyReturnsTrue(): void
    {
        self::assertTrue((new PdfDemand())->getAddressFieldMarksEnabled());
    }

    #[Test]
    public function createFromSettingsTakesValuesFromSettings(): void
    {
        $subject = PdfDemand::createFromSettings([
            'debug' => '1',
            'fontSize' => '10',
            'enableFoldMarks' => '1',
            'enableAddressFieldMarks' => '0',
        ]);

        self::assertSame(1, $subject->getDebug());
        self::assertSame(10, $subject->getFontSize());
        self::assertTrue($subject->getFoldMarksEnabled());
        self::assertFalse($subject->getAddressFieldMarksEnabled());
    }

    #[Test]
    public function createFromSettingsUsesDefaultsForMissingSettings(): void
    {
        $subject = PdfDemand::createFromSettings([]);

        self::assertSame(0, $subject->getDebug());
        self::assertSame(11, $subject->getFontSize());
        self::assertFalse($subject->getFoldMarksEnabled());
        self::assertFalse($subject->getAddressFieldMarksEnabled());
    }
}
