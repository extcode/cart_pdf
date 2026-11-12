<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Functional\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Codappix\Typo3PhpDatasets\TestingFramework;
use Extcode\Cart\Domain\Model\Order\Item as OrderItem;
use Extcode\Cart\Domain\Repository\Order\ItemRepository as OrderItemRepository;
use Extcode\CartPdf\Service\PdfService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the PDFs with the TypoScript shipped by the extension and checks
 * the text a reader would find in the document.
 */
#[CoversClass(PdfService::class)]
final class PdfServiceTest extends FunctionalTestCase
{
    use TestingFramework;

    protected array $testExtensionsToLoad = [
        'extcode/cart',
        'extcode/cart-pdf',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/BaseDatabase.php');
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItem.php');

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => 1]);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function invoiceIsAValidPdfWithOnePage(): void
    {
        $pdfContent = $this->renderDocument('invoice');

        self::assertStringStartsWith('%PDF-', $pdfContent);
        self::assertCount(1, $this->parsePdf($pdfContent)->getPages());
    }

    #[Test]
    public function invoiceContainsDocumentNumbersAndDate(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('Invoice Number: RE-2026-0042', $text);
        self::assertStringContainsString('Order Number: ORD-2026-0815', $text);
        self::assertStringContainsString('Musterstadt, den 12.11.2026', $text);
    }

    #[Test]
    public function invoiceContainsBillingAddress(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('Müller & Söhne GmbH', $text);
        self::assertStringContainsString('Jürgen Weiß', $text);
        self::assertStringContainsString('Königsallee 5', $text);
        self::assertStringContainsString('40212 Düsseldorf', $text);
    }

    #[Test]
    public function invoiceContainsProductsWithSpecialCharacters(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('CHAIR-ÄGÄIS', $text);
        self::assertStringContainsString('Größenverstellbarer Bürostuhl „Ägäis“', $text);
        self::assertStringContainsString('CABLE-USB-C', $text);
        self::assertStringContainsString('Kabel & Adapter <USB-C> für 50% günstiger', $text);
        self::assertStringNotContainsString('&amp;', $text);
        self::assertStringNotContainsString('&lt;', $text);
    }

    #[Test]
    public function invoiceContainsProductPricesAndQuantities(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('Stock Keeping Unit Product Price (gross) Count Gross', $text);
        self::assertStringContainsString('Ägäis“ 499,00 € 2 998,00 €', $text);
        self::assertStringContainsString('günstiger 19,99 € 1 19,99 €', $text);
    }

    #[Test]
    public function invoiceContainsSummary(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('Price (net) 855,46 €', $text);
        self::assertStringContainsString('VAT (19 %) 162,53 €', $text);
        self::assertStringContainsString('Standardversand 4,90 €', $text);
        self::assertStringContainsString('Total Price (gross) 1.022,89 €', $text);
    }

    #[Test]
    public function invoiceContainsSenderAndPageNumber(): void
    {
        $text = $this->extractText($this->renderDocument('invoice'));

        self::assertStringContainsString('Muster GmbH * Musterstraße 0 * 00000 Musterstadt', $text);
        self::assertStringContainsString('Seite 1 von 1', $text);
        self::assertStringContainsString('Mit freundlichen Grüßen', $text);
    }

    /**
     * Each line of text is a separate chunk in the PDF. A line that is too
     * long for its cell is wrapped and split into several chunks.
     */
    #[Test]
    public function invoiceRendersSmallTextLinesWithoutWrapping(): void
    {
        $page = $this->parsePdf($this->renderDocument('invoice'))->getPages()[0];
        $lines = array_map(
            fn(array $chunk): string => $this->normalizeText($chunk[1]),
            $page->getDataTm()
        );

        self::assertContains('Muster GmbH * Musterstraße 0 * 00000 Musterstadt', $lines, 'Sender line fits in the address window.');
        self::assertContains('Geschäftsführer:', $lines, 'Footer labels fit in their column.');
        self::assertContains('DE00 0000 0000 0000 0000 00', $lines, 'IBAN fits in its column.');
    }

    #[Test]
    public function invoiceWithManyProductsContinuesOnFurtherPages(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItemWithManyProducts.php');

        $document = $this->parsePdf($this->renderDocument('invoice', 'order-item-with-many-products'));
        $pages = $document->getPages();
        $text = $this->normalizeText($document->getText());

        self::assertGreaterThan(1, count($pages));
        for ($i = 3; $i <= 60; $i++) {
            self::assertStringContainsString(sprintf('BULK-%03d Massenartikel Nr. %03d', $i, $i), $text);
        }

        $lastPageText = $this->normalizeText(end($pages)->getText());
        self::assertStringContainsString(sprintf('Seite %1$d von %1$d', count($pages)), $lastPageText);
        self::assertStringContainsString('Total Price (gross) 1.022,89 €', $lastPageText);

        foreach ($pages as $page) {
            self::assertStringContainsString(
                'Muster GmbH Max Mustermann Musterstraße 0 00000 Musterstadt',
                $this->normalizeText($page->getText()),
                'Every page has the header.'
            );
        }
    }

    private function renderDocument(string $documentType, string $scenario = 'order-item'): string
    {
        $pdfContent = $this->get(PdfService::class)->renderDocument($this->getOrderItem(), $documentType);
        $this->keepAsArtifact($pdfContent, $scenario . '-' . $documentType);

        return $pdfContent;
    }

    /**
     * Stores the rendered document, so it can be checked visually. CI uploads
     * the directory as artifact.
     */
    private function keepAsArtifact(string $pdfContent, string $fileName): void
    {
        $artifactPath = dirname(__DIR__, 3) . '/.Build/test-artifacts/pdf';
        if (!is_dir($artifactPath)) {
            mkdir($artifactPath, 0o775, true);
        }

        file_put_contents(
            $artifactPath . '/' . $fileName . '.pdf',
            $pdfContent
        );
    }

    private function getOrderItem(): OrderItem
    {
        $orderItem = $this->get(OrderItemRepository::class)->findByUid(1);
        self::assertInstanceOf(OrderItem::class, $orderItem);

        return $orderItem;
    }

    private function parsePdf(string $pdfContent): Document
    {
        return (new Parser())->parseContent($pdfContent);
    }

    private function extractText(string $pdfContent): string
    {
        return $this->normalizeText($this->parsePdf($pdfContent)->getText());
    }

    /**
     * The text extraction separates table cells and lines by tabs and line
     * breaks, depending on the layout. Collapse all whitespace so that the
     * assertions only depend on the reading order.
     */
    private function normalizeText(string $text): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }
}
