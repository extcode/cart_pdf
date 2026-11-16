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
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
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

    #[Test]
    public function writeHtmlCellWithConfigOnOnePageKeepsContentOnCurrentPageIfItFits(): void
    {
        $subject = $this->createPdfWithFreeSpaceAtBottom(100.0);

        $subject->writeHtmlCellWithConfigOnOnePage(self::lines(5), ['width' => 100, 'spacingY' => 5]);

        self::assertSame(['Line 1 Line 2 Line 3 Line 4 Line 5'], $this->extractPageTexts($subject));
    }

    /**
     * The configured height is lower than the height of the content, as for
     * the greetings of the documents. TCPDF checks the configured height
     * only and would split the content.
     */
    #[Test]
    public function writeHtmlCellWithConfigOnOnePageMovesContentToNextPageIfItDoesNotFit(): void
    {
        $subject = $this->createPdfWithFreeSpaceAtBottom(15.0);

        $subject->writeHtmlCellWithConfigOnOnePage(self::lines(5), ['width' => 100, 'height' => 10, 'spacingY' => 5]);

        $positionY = $subject->GetY();
        self::assertSame(['', 'Line 1 Line 2 Line 3 Line 4 Line 5'], $this->extractPageTexts($subject));

        $expected = $this->createPdfWithFreeSpaceAtBottom(15.0);
        $expected->AddPage();
        $expected->writeHtmlCellWithConfig(self::lines(5), ['width' => 100, 'height' => 10]);
        self::assertEqualsWithDelta($expected->GetY(), $positionY, 0.001, 'The vertical spacing is left out on the new page.');
    }

    #[Test]
    public function writeHtmlCellWithConfigOnOnePageWritesContentWithFixedPositionAsItIs(): void
    {
        $subject = $this->createPdfWithFreeSpaceAtBottom(15.0);
        $positionY = $subject->GetY();

        $subject->writeHtmlCellWithConfigOnOnePage(self::lines(5), ['width' => 100, 'height' => 10, 'positionY' => $positionY]);

        self::assertStringStartsWith('Line 1', $this->extractPageTexts($subject)[0]);
    }

    #[Test]
    public function writeHtmlCellWithConfigOnOnePageWritesContentLongerThanAPageWithoutEmptyPage(): void
    {
        $subject = $this->createPdfWithFreeSpaceAtBottom(15.0);
        $subject->AddPage();

        $subject->writeHtmlCellWithConfigOnOnePage(self::lines(100), ['width' => 100]);

        $pageTexts = $this->extractPageTexts($subject);
        self::assertSame('', $pageTexts[0]);
        self::assertStringStartsWith('Line 1 ', $pageTexts[1]);
        self::assertGreaterThan(2, count($pageTexts));
    }

    private function createPdfWithFreeSpaceAtBottom(float $freeSpace): TcpdfWrapper
    {
        $subject = new TcpdfWrapper('invoicePdf', ['invoicePdf' => [], 'view' => []]);
        $subject->setPrintHeader(false);
        $subject->setPrintFooter(false);
        $subject->setAutoPageBreak(true, 20);
        $subject->setFont('helvetica', '', 10);
        $subject->AddPage();
        $subject->setY($subject->getPageHeight() - 20 - $freeSpace);

        return $subject;
    }

    private static function lines(int $count): string
    {
        return implode('<br>', array_map(static fn(int $i): string => 'Line ' . $i, range(1, $count)));
    }

    /**
     * Returns the text of each page without the note TCPDF adds to the
     * last page.
     *
     * @return list<string>
     */
    private function extractPageTexts(TcpdfWrapper $pdf): array
    {
        $document = (new Parser())->parseContent($pdf->Output(dest: 'S'));

        return array_map(
            static fn(Page $page): string => trim((string)preg_replace(
                '/\s+/u',
                ' ',
                str_replace('Powered by TCPDF (www.tcpdf.org)', '', $page->getText())
            )),
            $document->getPages()
        );
    }

    private function createSubject(): FontSizeRecordingTcpdfWrapper
    {
        $subject = new FontSizeRecordingTcpdfWrapper(
            'invoicePdf',
            ['invoicePdf' => [], 'view' => []]
        );
        $subject->AddPage();

        return $subject;
    }
}
