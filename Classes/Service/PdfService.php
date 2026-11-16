<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Order\Item as OrderItem;
use Extcode\Cart\Domain\Model\Order\Product;
use Extcode\CartPdf\Domain\Model\Dto\PdfDemand;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

readonly class PdfService implements DocumentRenderServiceInterface
{
    private array $configuration;
    private array $viewSettings;

    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ViewFactoryInterface $viewFactory,
    ) {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $pageId = (int)($GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? 1);

            $frameworkConfiguration = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            );
            $persistenceConfiguration = ['persistence' => ['storagePid' => $pageId]];
            $this->configurationManager->setConfiguration(
                array_merge($frameworkConfiguration, $persistenceConfiguration)
            );
        }

        $this->configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'CartPdf'
        );

        $viewSettings = $this->configuration['view'] ?? [];
        $this->viewSettings = is_array($viewSettings) ? $viewSettings : [];
    }

    private function createView(string $format = 'html'): ViewInterface
    {
        return $this->viewFactory->create(new ViewFactoryData(
            $this->viewSettings['templateRootPaths'] ?? [],
            $this->viewSettings['partialRootPaths'] ?? [],
            $this->viewSettings['layoutRootPaths'] ?? [],
            null,
            $GLOBALS['TYPO3_REQUEST'] ?? null,
            $format,
        ));
    }

    private function renderView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = [],
    ): string {
        $view = $this->createView();

        if (!empty($config['file'])) {
            $file = GeneralUtility::getFileAbsFileName($config['file']);
            $view->assign('file', $file);
            $view->assign('file_base64', base64_encode(file_get_contents($file)));
            $view->assign('file_mimetype', mime_content_type($file));

            if (!empty($config['width'])) {
                $view->assign('width', $config['width']);
            }

            if (!empty($config['height'])) {
                $view->assign('height', $config['height']);
            }
        }

        $view->assignMultiple($assignToView);

        $content = (string)$view->render($templatePath . ucfirst($type));
        return trim(preg_replace('~[\\n]+~', '', $content));
    }

    public function renderDocument(OrderItem $orderItem, string $pdfType): string
    {
        $pdfType .= 'Pdf';
        $pdfSettings = $this->configuration[$pdfType] ?? [];
        $pdfSettings = is_array($pdfSettings) ? $pdfSettings : [];
        $pdfDemand = PdfDemand::createFromSettings($pdfSettings);

        $pdf = TcpdfWrapper::createWithTypeAndSettings(
            $pdfType,
            $this->configuration,
        );

        foreach ($pdfSettings['header']['html'] ?? [] as $partName => $partConfig) {
            $assignToView = [];
            if ($partName === 'page') {
                $assignToView['numPage'] = $pdf->getAliasNumPage();
                $assignToView['numPages'] = $pdf->getAliasNbPages();
            }

            $content = $this->renderView(
                $pdfSettings['header']['html'][$partName]['templatePath'],
                $partName,
                $partConfig,
                $assignToView,
            );

            $pdf->addHeaderPart($content, $partConfig);
        }

        foreach ($pdfSettings['footer']['html'] ?? [] as $partName => $partConfig) {
            $assignToView = [];
            if ($partName === 'page') {
                $assignToView['numPage'] = $pdf->getAliasNumPage();
                $assignToView['numPages'] = $pdf->getAliasNbPages();
            }

            $content = $this->renderView(
                $pdfSettings['footer']['html'][$partName]['templatePath'],
                $partName,
                $partConfig,
                $assignToView,
            );

            $pdf->addFooterPart($content, $partConfig);
        }

        if (empty($pdfSettings['header'])) {
            $pdf->setPrintHeader(false);
        } else {
            if (!empty($pdfSettings['header']['margin'])) {
                $pdf->setHeaderMargin((int)$pdfSettings['header']['margin']);
                $pdf->setMargins(PDF_MARGIN_LEFT, (float)$pdfSettings['header']['margin'], PDF_MARGIN_RIGHT);
            } else {
                $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            }
        }

        if (empty($pdfSettings['footer'])) {
            $pdf->setPrintFooter(false);
        } else {
            if (!empty($pdfSettings['footer']['margin'])) {
                $pdf->setFooterMargin((int)$pdfSettings['footer']['margin']);
                $pdf->setAutoPageBreak(true, (int)$pdfSettings['footer']['margin']);
            } else {
                $pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            }
        }

        $pdf->AddPage();

        $font = 'Helvetica';

        if (!empty($pdfSettings['font'])) {
            $font = $pdfSettings['font'];
        }

        $fontStyle = '';

        if (!empty($pdfSettings['fontStyle'])) {
            $fontStyle = $pdfSettings['fontStyle'];
        }

        $fontSize = 8;

        if (!empty($pdfSettings['fontSize'])) {
            $fontSize = $pdfSettings['fontSize'];
        }

        $pdf->setFont($font, $fontStyle, $fontSize);

        $colorArray = [0, 0, 0];

        if (!empty($pdfSettings['drawColor'])) {
            $colorArray = explode(',', $pdfSettings['drawColor']);
        }

        $pdf->setDrawColorArray($colorArray);
        $this->renderMarker($pdf, $pdfDemand);

        if (is_array($pdfSettings['letterhead']['html'] ?? null)) {
            foreach ($pdfSettings['letterhead']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Letterhead/';
                $assignToView = ['orderItem' => $orderItem];
                $content = $this->renderView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfig($content, $partConfig);
            }
        }

        if (is_array($pdfSettings['body']['before']['html'] ?? null)) {
            foreach ($pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $content = $this->renderView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfigOnOnePage($content, $partConfig);
            }
        }

        $this->renderCart($pdf, $orderItem, $pdfType);

        if (is_array($pdfSettings['body']['after']['html'] ?? null)) {
            foreach ($pdfSettings['body']['after']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/After/';
                $assignToView = ['orderItem' => $orderItem];
                $content = $this->renderView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfigOnOnePage($content, $partConfig);
            }
        }

        return $pdf->Output(dest: 'S');
    }

    private function renderMarker(TcpdfWrapper $pdf, PdfDemand $pdfDemand): void
    {
        if ($pdfDemand->getFoldMarksEnabled()) {
            $pdf->setLineWidth(0.1);
            $pdf->Line(6.0, 105.0, 8.0, 105.0);
            $pdf->Line(6.0, 148.5, 10.0, 148.5);
            $pdf->Line(6.0, 210.0, 8.0, 210.0);
            $pdf->setLineWidth(0.2);
        }

        if ($pdfDemand->getAddressFieldMarksEnabled()) {
            $pdf->setLineWidth(0.1);

            $pdf->Line(20.0, 45.0, 21.0, 45.0);
            $pdf->Line(20.0, 45.0, 20.0, 46.0);
            $pdf->Line(105.0, 45.0, 104.0, 45.0);
            $pdf->Line(105.0, 45.0, 105.0, 46.0);

            $pdf->Line(20.0, 90.0, 21.0, 90.0);
            $pdf->Line(20.0, 90.0, 20.0, 89.0);

            $pdf->Line(105.0, 90.0, 104.0, 90.0);
            $pdf->Line(105.0, 90.0, 105.0, 89.0);

            $pdf->setLineWidth(0.2);
        }
    }

    private function renderCart(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType): void
    {
        $config = $this->configuration[$pdfType]['body']['order'] ?? [];
        $config['height'] = 0;

        if (empty($config['spacingY']) && empty($config['positionY'])) {
            $config['spacingY'] = 5;
        }

        $headerOut = $this->renderCartHeader($orderItem, $pdfType);
        $rowsOut = $this->renderCartRows($orderItem, $pdfType);
        $summaryOut = $this->renderCartSummary($orderItem, $pdfType);

        $this->renderCartPaginated($pdf, $orderItem, $pdfType, $config, $headerOut, $rowsOut, $summaryOut);
    }

    /**
     * Splits the products into one table per page. Each table but the last
     * ends with the subtotal (Order/Subtotal.html, option pageSubtotal), the
     * last one with the summary of the order (Order/Summary.html). Each table
     * but the first starts with the amount brought forward from the previous
     * page (Order/BroughtForward.html, option pageBroughtForward).
     *
     * The summary is never split and never stands alone: if it does not fit
     * below the last product, this product moves to the next page as well.
     *
     * The number of products that fit on a page is determined by writing the
     * table on trial. The search starts with an estimate on the first two
     * pages and with the number of the previous page on all further pages,
     * and counts up or down from there. Products can differ in height, so the
     * number of the previous page is not guaranteed to fit.
     *
     * @param list<string> $rowsOut
     */
    private function renderCartPaginated(
        TcpdfWrapper $pdf,
        OrderItem $orderItem,
        string $pdfType,
        array $config,
        string $headerOut,
        array $rowsOut,
        string $summaryOut,
    ): void {
        $products = array_values($orderItem->getProducts()->toArray());
        $total = count($rowsOut);
        $withSubtotal = !empty($config['pageSubtotal']);
        $withBroughtForward = !empty($config['pageBroughtForward']);

        $continuationConfig = $config;
        unset($continuationConfig['spacingY'], $continuationConfig['positionY']);

        $start = 0;
        $previousCount = null;
        $previousPage = null;
        $isFreshPage = false;

        while (true) {
            $remaining = $total - $start;

            $openingOut = '';
            if ($withBroughtForward && $start > 0) {
                $openingOut = $this->renderCartPageRow(
                    'BroughtForward',
                    $orderItem,
                    $pdfType,
                    array_slice($products, 0, $start),
                    ['previousPage' => $previousPage]
                );
            }

            $finalTable = $this->buildCartTable($headerOut, $openingOut, array_slice($rowsOut, $start), $summaryOut);
            if ($remaining === 0) {
                $pdf->writeHtmlCellWithConfig($finalTable, $config);
                return;
            }

            $pageTable = fn(int $count): string => $this->buildCartTable(
                $headerOut,
                $openingOut,
                array_slice($rowsOut, $start, $count),
                $withSubtotal
                    ? $this->renderCartPageRow('Subtotal', $orderItem, $pdfType, array_slice($products, 0, $start + $count))
                    : ''
            );
            $measure = fn(int $count): ?float => $pdf->measureHtmlCellWithConfig($pageTable($count), $config);

            $guess = $previousCount ?? $this->estimateCartRowCount($pdf, $measure, $remaining);
            $count = $this->countFittingCartRows($measure, $remaining, $guess);

            if ($count === $remaining) {
                if ($pdf->measureHtmlCellWithConfig($finalTable, $config) !== null) {
                    $pdf->writeHtmlCellWithConfig($finalTable, $config);
                    return;
                }
                // The summary does not fit. Move the last product to the next
                // page, so the summary does not stand alone.
                $count--;
            }

            if ($count === 0) {
                if (!$isFreshPage) {
                    $pdf->AddPage();
                    $isFreshPage = true;
                    $config = $continuationConfig;
                    continue;
                }
                // Not even one product fits on an empty page. Write it anyway
                // and let it break across pages.
                if ($remaining === 1) {
                    $pdf->writeHtmlCellWithConfig($finalTable, $config);
                    return;
                }
                $count = 1;
            }

            $pdf->writeHtmlCellWithConfig($pageTable($count), $config);

            $previousCount = $isFreshPage ? $count : null;
            $previousPage = $pdf->getPage();
            $start += $count;

            $pdf->AddPage();
            $isFreshPage = true;
            $config = $continuationConfig;
        }
    }

    /**
     * Estimates the number of products that fit on the current page from the
     * height of a table with one and with two products.
     *
     * @param callable(int): ?float $measure
     */
    private function estimateCartRowCount(TcpdfWrapper $pdf, callable $measure, int $max): int
    {
        if ($max < 2) {
            return 1;
        }

        $heightOne = $measure(1);
        $heightTwo = $measure(2);
        if ($heightOne === null || $heightTwo === null) {
            return 1;
        }

        $rowHeight = $heightTwo - $heightOne;
        if ($rowHeight <= 0) {
            return $max;
        }

        $freeHeight = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY();
        $tableHeight = $heightOne - $rowHeight;

        return max(1, min($max, (int)floor(($freeHeight - $tableHeight) / $rowHeight)));
    }

    /**
     * Returns the highest number of products (at most $max) whose table fits
     * on the current page, or 0 if not even one product fits.
     *
     * @param callable(int): ?float $measure
     */
    private function countFittingCartRows(callable $measure, int $max, int $guess): int
    {
        $count = max(1, min($max, $guess));

        if ($measure($count) !== null) {
            while ($count < $max && $measure($count + 1) !== null) {
                $count++;
            }
            return $count;
        }

        while (--$count > 0) {
            if ($measure($count) !== null) {
                return $count;
            }
        }

        return 0;
    }

    /**
     * @param list<string> $rowsOut
     */
    private function buildCartTable(string $headerOut, string $openingOut, array $rowsOut, string $closingOut): string
    {
        return '<table cellpadding="3">' . $headerOut . $openingOut . implode('', $rowsOut) . $closingOut . '</table>';
    }

    private function renderCartHeader(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->createView();
        $view->assign('orderItem', $orderItem);
        $header = $view->render('/' . ucfirst($pdfType) . '/Order/Header');

        return trim(preg_replace('~[\\n]+~', '', $header));
    }

    /**
     * @return list<string>
     */
    private function renderCartRows(OrderItem $orderItem, string $pdfType): array
    {
        $view = $this->createView();
        $view->assign('orderItem', $orderItem);

        $rowsOut = [];

        foreach ($orderItem->getProducts() as $product) {
            $view->assign('product', $product);
            $rowOut = $view->render('/' . ucfirst($pdfType) . '/Order/Product');

            $rowsOut[] = trim(preg_replace('~[\\n]+~', '', $rowOut));
        }

        return $rowsOut;
    }

    /**
     * Renders a row of the product table with the sum of the given products:
     * Order/Subtotal.html at the end of each page but the last one, and
     * Order/BroughtForward.html at the start of each page but the first one.
     *
     * @param 'Subtotal'|'BroughtForward' $templateName
     * @param list<Product> $products the products up to this row
     * @param array<string, mixed> $variables
     */
    private function renderCartPageRow(
        string $templateName,
        OrderItem $orderItem,
        string $pdfType,
        array $products,
        array $variables = [],
    ): string {
        $subtotal = ['net' => 0.0, 'gross' => 0.0];
        foreach ($products as $product) {
            $subtotal['net'] += $product->getNet();
            $subtotal['gross'] += $product->getGross();
        }

        $view = $this->createView();
        $view->assign('orderSettings', $this->configuration[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $view->assign('products', $products);
        $view->assign('subtotal', $subtotal);
        $view->assignMultiple($variables);
        $rowOut = $view->render('/' . ucfirst($pdfType) . '/Order/' . $templateName);

        return trim(preg_replace('~[\\n]+~', '', $rowOut));
    }

    private function renderCartSummary(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->createView();
        $view->assign('orderSettings', $this->configuration[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $summaryOut = $view->render('/' . ucfirst($pdfType) . '/Order/Summary');

        return trim(preg_replace('~[\\n]+~', '', $summaryOut));
    }
}
