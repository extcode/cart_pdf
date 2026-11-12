<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Unit\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\CartPdf\Service\TcpdfWrapper;
use Extcode\CartPdf\Tests\Unit\Fixtures\FontSizeRecordingTcpdfWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(TcpdfWrapper::class)]
final class TcpdfWrapperTest extends UnitTestCase
{
    #[Test]
    public function writeHtmlCellWithConfigWritesWithConfiguredFontSize(): void
    {
        $subject = $this->createSubject();
        $subject->SetFontSize(10);

        $subject->writeHtmlCellWithConfig('content', ['fontSize' => '6']);

        self::assertSame([6.0], $subject->fontSizesWhileWriting);
    }

    #[Test]
    public function writeHtmlCellWithConfigRestoresFontSizeAfterWriting(): void
    {
        $subject = $this->createSubject();
        $subject->SetFontSize(10);

        $subject->writeHtmlCellWithConfig('content', ['fontSize' => '6']);

        self::assertSame(10.0, (float)$subject->getFontSizePt());
    }

    public static function configWithoutFontSizeDataProvider(): \Generator
    {
        yield 'no fontSize' => [[]];
        yield 'empty fontSize' => [['fontSize' => '']];
        yield 'zero fontSize' => [['fontSize' => '0']];
        yield 'non numeric fontSize' => [['fontSize' => 'large']];
    }

    #[Test]
    #[DataProvider('configWithoutFontSizeDataProvider')]
    public function writeHtmlCellWithConfigKeepsCurrentFontSizeWithoutValidFontSize(array $config): void
    {
        $subject = $this->createSubject();
        $subject->SetFontSize(10);

        $subject->writeHtmlCellWithConfig('content', $config);

        self::assertSame([10.0], $subject->fontSizesWhileWriting);
        self::assertSame(10.0, (float)$subject->getFontSizePt());
    }

    private function createSubject(): FontSizeRecordingTcpdfWrapper
    {
        $subject = new FontSizeRecordingTcpdfWrapper(
            self::createStub(ViewFactoryInterface::class),
            'invoicePdf',
            ['invoicePdf' => [], 'view' => []]
        );
        $subject->AddPage();

        return $subject;
    }
}
