<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Functional\Integration;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Codappix\Typo3PhpDatasets\TestingFramework;
use Extcode\Cart\Controller\Backend\Order\DocumentController;
use Extcode\Cart\Domain\Model\Order\Item as OrderItem;
use Extcode\Cart\Domain\Repository\Order\ItemRepository as OrderItemRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Generates the documents the way the order backend module of cart does
 * and checks that they are written to the storage and attached to the order.
 */
final class CartDocumentControllerTest extends FunctionalTestCase
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
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/BackendUserAndFileStorage.php');
        $this->setUpBackendUser(1);

        foreach (['order', 'invoice', 'delivery'] as $pdfType) {
            GeneralUtility::mkdir_deep($this->instancePath . '/fileadmin/tx_cart/' . $pdfType . '_pdf');
        }

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => 1]);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    public static function pdfTypeDataProvider(): \Generator
    {
        yield 'order' => ['order', 'ORD-2026-0815.pdf'];
        yield 'invoice' => ['invoice', 'RE-2026-0042.pdf'];
        yield 'delivery' => ['delivery', 'LS-2026-0007.pdf'];
    }

    #[Test]
    #[DataProvider('pdfTypeDataProvider')]
    public function generatesDocumentAndAttachesItToOrderItem(string $pdfType, string $expectedFileName): void
    {
        $orderItem = $this->get(OrderItemRepository::class)->findByUid(1);
        self::assertInstanceOf(OrderItem::class, $orderItem);

        $documentController = $this->get(DocumentController::class);
        $generatePdfDocument = new \ReflectionMethod($documentController, 'generatePdfDocument');
        $generatePdfDocument->invoke($documentController, $orderItem, $pdfType);

        $getPdfs = 'get' . ucfirst($pdfType) . 'Pdfs';
        $pdfs = $orderItem->$getPdfs()->toArray();
        self::assertCount(1, $pdfs);

        $file = $pdfs[0]->getOriginalResource();
        self::assertSame($expectedFileName, $file->getName());
        self::assertSame('/tx_cart/' . $pdfType . '_pdf/', $file->getParentFolder()->getIdentifier());
        self::assertStringStartsWith('%PDF-', $file->getContents());
    }
}
