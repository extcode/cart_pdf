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

    private const BASE_GROSS_BY_SKU = [
        'CHAIR-ÄGÄIS' => 998.00,
        'CABLE-USB-C' => 19.99,
    ];

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

        $document = $this->parsePdf($this->renderDocument('invoice', 'with-many-products'));
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

    #[Test]
    public function invoiceWithManyProductsShowsSubtotalAndBroughtForwardOnEachPage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItemWithManyProducts.php');

        $grossBySku = self::BASE_GROSS_BY_SKU;
        for ($i = 3; $i <= 60; $i++) {
            $grossBySku[sprintf('BULK-%03d', $i)] = 1.00;
        }

        $document = $this->parsePdf($this->renderDocument('invoice', 'with-many-products'));

        self::assertGreaterThan(1, count($document->getPages()));
        $this->assertEachProductOncePerPage($document, $grossBySku, true, true);
    }

    /**
     * Some products wrap over several lines, so the number of products that
     * fit on the previous page does not fit on the next one.
     */
    #[Test]
    public function invoiceWithProductsOfDifferentHeightShowsSubtotalAndBroughtForwardOnEachPage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItemWithProductsOfDifferentHeight.php');

        $grossBySku = self::BASE_GROSS_BY_SKU;
        for ($i = 3; $i <= 80; $i++) {
            $grossBySku[sprintf('BULK-%03d', $i)] = (float)$i;
        }

        $document = $this->parsePdf($this->renderDocument('invoice', 'with-products-of-different-height'));

        self::assertGreaterThan(2, count($document->getPages()));
        $this->assertEachProductOncePerPage($document, $grossBySku, true, true);
    }

    #[Test]
    public function invoiceWithManyProductsWithoutPageSubtotalShowsOnlyBroughtForward(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItemWithManyProducts.php');
        $this->addTypoScript([
            'plugin.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 0',
            'module.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 0',
        ]);

        $grossBySku = self::BASE_GROSS_BY_SKU;
        for ($i = 3; $i <= 60; $i++) {
            $grossBySku[sprintf('BULK-%03d', $i)] = 1.00;
        }

        $document = $this->parsePdf($this->renderDocument('invoice', 'with-many-products-without-page-subtotal'));

        self::assertGreaterThan(1, count($document->getPages()));
        $this->assertEachProductOncePerPage($document, $grossBySku, false, true);
    }

    #[Test]
    public function invoiceWithManyProductsWithoutPageSubtotalAndBroughtForwardHasNeither(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/OrderItemWithManyProducts.php');
        $this->addTypoScript([
            'plugin.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 0',
            'plugin.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 0',
            'module.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 0',
            'module.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 0',
        ]);

        $document = $this->parsePdf($this->renderDocument('invoice', 'with-many-products-without-page-subtotal-and-brought-forward'));
        $text = $this->normalizeText($document->getText());

        self::assertGreaterThan(1, count($document->getPages()));
        self::assertStringNotContainsString('Subtotal', $text);
        self::assertStringNotContainsString('Brought forward', $text);
        for ($i = 3; $i <= 60; $i++) {
            self::assertSame(1, substr_count($text, sprintf('BULK-%03d', $i)));
        }
    }

    /**
     * Checks the pages up to the one with the summary. The parts after the
     * product table (e.g. the greetings) may continue on a further page.
     *
     * @param array<string, float> $grossBySku
     */
    private function assertEachProductOncePerPage(
        Document $document,
        array $grossBySku,
        bool $withSubtotal,
        bool $withBroughtForward,
    ): void {
        $pages = $document->getPages();
        $lastPageIndex = null;
        foreach ($pages as $index => $page) {
            if (str_contains($this->normalizeText($page->getText()), 'Total Price (gross)')) {
                $lastPageIndex = $index;
                break;
            }
        }
        self::assertNotNull($lastPageIndex, 'The document has the summary.');

        $foundSkus = [];
        $subtotal = 0.0;

        foreach (array_slice($pages, 0, $lastPageIndex + 1) as $index => $page) {
            $text = $this->normalizeText($page->getText());
            $pageNumber = $index + 1;

            preg_match_all('/CHAIR-ÄGÄIS|CABLE-USB-C|BULK-\d{3}/u', $text, $matches);
            self::assertNotEmpty($matches[0], sprintf('Page %d has products.', $pageNumber));
            self::assertStringContainsString(
                'Stock Keeping Unit Product Price (gross) Count Gross',
                $text,
                sprintf('Page %d has the table header.', $pageNumber)
            );

            if ($index === 0 || !$withBroughtForward) {
                self::assertStringNotContainsString('Brought forward', $text, sprintf('Page %d has no amount brought forward.', $pageNumber));
            } else {
                $expected = sprintf('Brought forward from page %d %s €', $pageNumber - 1, $this->formatAmount($subtotal));
                self::assertStringContainsString($expected, $text, sprintf('Page %d starts with the amount brought forward.', $pageNumber));
                self::assertLessThan(
                    strpos($text, $matches[0][0]),
                    strpos($text, $expected),
                    sprintf('The amount brought forward on page %d precedes the first product of the page.', $pageNumber)
                );
            }

            foreach ($matches[0] as $sku) {
                $foundSkus[] = $sku;
                $subtotal += $grossBySku[$sku];
            }

            if ($index === $lastPageIndex || !$withSubtotal) {
                self::assertStringNotContainsString('Subtotal', $text, sprintf('Page %d has no subtotal.', $pageNumber));
            } else {
                $expected = sprintf('Subtotal %s €', $this->formatAmount($subtotal));
                self::assertStringContainsString($expected, $text, sprintf('Page %d ends with the subtotal.', $pageNumber));
                self::assertGreaterThan(
                    strrpos($text, (string)end($matches[0])),
                    strpos($text, $expected),
                    sprintf('The subtotal on page %d follows the last product of the page.', $pageNumber)
                );
            }

            if ($index === $lastPageIndex) {
                self::assertStringContainsString('Total Price (gross)', $text, 'The last page has the summary.');
            } else {
                self::assertStringNotContainsString('Total Price (gross)', $text, sprintf('Page %d has no summary.', $pageNumber));
            }
        }

        self::assertSame(array_keys($grossBySku), $foundSkus, 'Each product is shown once and in order.');
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    #[Test]
    public function orderIsAValidPdfWithOnePage(): void
    {
        $pdfContent = $this->renderDocument('order');

        self::assertStringStartsWith('%PDF-', $pdfContent);
        self::assertCount(1, $this->parsePdf($pdfContent)->getPages());
    }

    #[Test]
    public function orderContainsOrderNumberAndDate(): void
    {
        $text = $this->extractText($this->renderDocument('order'));

        self::assertStringContainsString('Order Number: ORD-2026-0815', $text);
        self::assertStringContainsString('Musterstadt, den 10.11.2026', $text);
        self::assertStringNotContainsString('RE-2026-0042', $text);
    }

    #[Test]
    public function orderContainsBillingAddressProductsAndSummary(): void
    {
        $text = $this->extractText($this->renderDocument('order'));

        self::assertStringContainsString('Müller & Söhne GmbH', $text);
        self::assertStringContainsString('Ägäis“ 499,00 € 2 998,00 €', $text);
        self::assertStringContainsString('günstiger 19,99 € 1 19,99 €', $text);
        self::assertStringContainsString('VAT (19 %) 162,53 €', $text);
        self::assertStringContainsString('Total Price (gross) 1.022,89 €', $text);
    }

    /**
     * The template DeliveryPdf/Order/Summary.html is empty. Rendering it
     * must not break the document.
     *
     * @see https://github.com/extcode/cart_pdf/issues/47
     */
    #[Test]
    public function deliveryNoteIsAValidPdfWithOnePage(): void
    {
        $pdfContent = $this->renderDocument('delivery');

        self::assertStringStartsWith('%PDF-', $pdfContent);
        self::assertCount(1, $this->parsePdf($pdfContent)->getPages());
    }

    #[Test]
    public function deliveryNoteContainsDeliveryNumberAndDate(): void
    {
        $text = $this->extractText($this->renderDocument('delivery'));

        self::assertStringContainsString('Delivery Number: LS-2026-0007', $text);
        self::assertStringContainsString('Musterstadt, den 13.11.2026', $text);
    }

    #[Test]
    public function deliveryNoteContainsProductTableHeader(): void
    {
        $text = $this->extractText($this->renderDocument('delivery'));

        self::assertStringContainsString('Count Product', $text);
    }

    #[Test]
    public function deliveryNoteContainsBillingAddressAndProductsWithoutPrices(): void
    {
        $text = $this->extractText($this->renderDocument('delivery'));

        self::assertStringContainsString('Müller & Söhne GmbH', $text);
        self::assertStringContainsString('2 Größenverstellbarer Bürostuhl „Ägäis“', $text);
        self::assertStringContainsString('1 Kabel & Adapter <USB-C> für 50% günstiger', $text);
        self::assertStringNotContainsString('€', $text);
    }

    #[Test]
    public function deliveryNoteWithEmptyOrderHeaderAndSummaryTemplatesContainsProducts(): void
    {
        $this->addTemplateRootPath('EXT:cart_pdf/Tests/Functional/Fixtures/Templates/EmptyOrderHeaderAndSummary/');

        $pdfContent = $this->renderDocument('delivery', 'with-empty-order-header-and-summary');
        $text = $this->extractText($pdfContent);

        self::assertStringStartsWith('%PDF-', $pdfContent);
        self::assertStringContainsString('2 Größenverstellbarer Bürostuhl „Ägäis“', $text);
        self::assertStringContainsString('1 Kabel & Adapter <USB-C> für 50% günstiger', $text);
        self::assertStringNotContainsString('Count', $text, 'The header of the product table is not rendered.');
    }

    private function addTemplateRootPath(string $templateRootPath): void
    {
        $this->addTypoScript([
            'plugin.tx_cartpdf.view.templateRootPaths.10 = ' . $templateRootPath,
            'module.tx_cartpdf.view.templateRootPaths.10 = ' . $templateRootPath,
        ]);
    }

    /**
     * @param list<string> $lines
     */
    private function addTypoScript(array $lines): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->update(
            'sys_template',
            ['config' => implode(PHP_EOL, $lines)],
            ['uid' => 1]
        );
    }

    private function renderDocument(string $documentType, string $scenario = ''): string
    {
        $pdfContent = $this->get(PdfService::class)->renderDocument($this->getOrderItem(), $documentType);
        $this->keepAsArtifact($pdfContent, $scenario === '' ? $documentType : $documentType . '-' . $scenario);

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
