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
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

readonly class PdfService implements DocumentRenderServiceInterface
{
    private array $configuration;

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
    }

    public function renderDocument(OrderItem $orderItem, string $pdfType): string
    {
        $pdfType .= 'Pdf';
        $pdfSettings = $this->configuration[$pdfType];
        $pdfDemand = PdfDemand::createFromSettings($pdfSettings);

        $pdf = TcpdfWrapper::createWithTypeAndSettings(
            $this->viewFactory,
            $pdfType,
            $this->configuration,
        );

        if (empty($pdfSettings['header'])) {
            $pdf->setPrintHeader(false);
        } else {
            if (!empty($pdfSettings['header']['margin'])) {
                $pdf->setHeaderMargin((int)$pdfSettings['header']['margin']);
                $pdf->SetMargins(PDF_MARGIN_LEFT, (float)$pdfSettings['header']['margin'], PDF_MARGIN_RIGHT);
            } else {
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
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

        $pdf->SetFont($font, $fontStyle, $fontSize);

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
                $pdf->renderView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        if (is_array($pdfSettings['body']['before']['html'] ?? null)) {
            foreach ($pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $pdf->renderView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $this->renderCart($pdf, $orderItem, $pdfType);

        if (is_array($pdfSettings['body']['after']['html'] ?? null)) {
            foreach ($pdfSettings['body']['after']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/After/';
                $assignToView = ['orderItem' => $orderItem];
                $pdf->renderView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        return $pdf->Output(dest: 'S');
    }

    private function renderMarker(TcpdfWrapper $pdf, PdfDemand $pdfDemand): void
    {
        if ($pdfDemand->getFoldMarksEnabled()) {
            $pdf->SetLineWidth(0.1);
            $pdf->Line(6.0, 105.0, 8.0, 105.0);
            $pdf->Line(6.0, 148.5, 10.0, 148.5);
            $pdf->Line(6.0, 210.0, 8.0, 210.0);
            $pdf->SetLineWidth(0.2);
        }

        if ($pdfDemand->getAddressFieldMarksEnabled()) {
            $pdf->SetLineWidth(0.1);

            $pdf->Line(20.0, 45.0, 21.0, 45.0);
            $pdf->Line(20.0, 45.0, 20.0, 46.0);
            $pdf->Line(105.0, 45.0, 104.0, 45.0);
            $pdf->Line(105.0, 45.0, 105.0, 46.0);

            $pdf->Line(20.0, 90.0, 21.0, 90.0);
            $pdf->Line(20.0, 90.0, 20.0, 89.0);

            $pdf->Line(105.0, 90.0, 104.0, 90.0);
            $pdf->Line(105.0, 90.0, 105.0, 89.0);

            $pdf->SetLineWidth(0.2);
        }
    }

    private function renderCart(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType): void
    {
        $config = $this->configuration[$pdfType]['body']['order'] ?? [];
        $config['height'] = 0;

        if (empty($config['spacingY']) && empty($config['positionY'])) {
            $config['spacingY'] = 5;
        }

        $headerOut = $this->renderCartHeader($pdf, $orderItem, $pdfType);
        $rowsOut = $this->renderCartRows($pdf, $orderItem, $pdfType);
        $footerOut = $this->renderCartFooter($pdf, $orderItem, $pdfType);

        if (empty($config['pageSubtotal'])) {
            $content = $this->buildCartTable($headerOut, $rowsOut, $footerOut);
            $pdf->writeHtmlCellWithConfig($content, $config);
            return;
        }

        $this->renderCartWithPageSubtotal($pdf, $orderItem, $pdfType, $config, $headerOut, $rowsOut, $footerOut);
    }

    /**
     * Splits the products into one table per page. Each table but the last
     * ends with the page footer (e.g. the subtotal), the last one with the
     * footer of the order (the summary).
     *
     * The number of products that fit on a page is determined by writing the
     * table on trial. The search starts with an estimate on the first two
     * pages and with the number of the previous page on all further pages,
     * and counts up or down from there. Products can differ in height, so the
     * number of the previous page is not guaranteed to fit.
     *
     * @param list<string> $rowsOut
     */
    private function renderCartWithPageSubtotal(
        TcpdfWrapper $pdf,
        OrderItem $orderItem,
        string $pdfType,
        array $config,
        string $headerOut,
        array $rowsOut,
        string $footerOut,
    ): void {
        $products = array_values($orderItem->getProducts()->toArray());
        $total = count($rowsOut);

        $continuationConfig = $config;
        unset($continuationConfig['spacingY'], $continuationConfig['positionY']);

        $start = 0;
        $previousCount = null;
        $isFreshPage = false;

        while (true) {
            $remaining = $total - $start;

            $finalTable = $this->buildCartTable($headerOut, array_slice($rowsOut, $start), $footerOut);
            if ($remaining === 0) {
                $pdf->writeHtmlCellWithConfig($finalTable, $config);
                return;
            }

            $pageTable = fn(int $count): string => $this->buildCartTable(
                $headerOut,
                array_slice($rowsOut, $start, $count),
                $this->renderCartPageFooter($pdf, $orderItem, $pdfType, array_slice($products, 0, $start + $count))
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
    private function buildCartTable(string $headerOut, array $rowsOut, string $footerOut): string
    {
        return '<table cellpadding="3">' . $headerOut . implode('', $rowsOut) . $footerOut . '</table>';
    }

    private function renderCartHeader(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType): string
    {
        $view = $pdf->createView('/' . ucfirst($pdfType) . '/Order/', 'Header');
        $view->assign('orderItem', $orderItem);
        $header = $view->render();

        return trim(preg_replace('~[\\n]+~', '', $header));
    }

    /**
     * @return list<string>
     */
    private function renderCartRows(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType): array
    {
        $view = $pdf->createView('/' . ucfirst($pdfType) . '/Order/', 'Product');
        $view->assign('orderItem', $orderItem);

        $rowsOut = [];

        foreach ($orderItem->getProducts() as $product) {
            $view->assign('product', $product);
            $rowOut = $view->render();

            $rowsOut[] = trim(preg_replace('~[\\n]+~', '', $rowOut));
        }

        return $rowsOut;
    }

    /**
     * Renders the footer of the product table on each page but the last one.
     *
     * @param list<Product> $products the products on this and the previous pages
     */
    private function renderCartPageFooter(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType, array $products): string
    {
        $subtotal = ['net' => 0.0, 'gross' => 0.0];
        foreach ($products as $product) {
            $subtotal['net'] += $product->getNet();
            $subtotal['gross'] += $product->getGross();
        }

        $view = $pdf->createView('/' . ucfirst($pdfType) . '/Order/', 'PageFooter');
        $view->assign('orderSettings', $this->configuration[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $view->assign('products', $products);
        $view->assign('subtotal', $subtotal);
        $pageFooter = $view->render();

        return trim(preg_replace('~[\\n]+~', '', $pageFooter));
    }

    private function renderCartFooter(TcpdfWrapper $pdf, OrderItem $orderItem, string $pdfType): string
    {
        $view = $pdf->createView('/' . ucfirst($pdfType) . '/Order/', 'Footer');
        $view->assign('orderSettings', $this->configuration[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $footer = $view->render();

        return trim(preg_replace('~[\\n]+~', '', $footer));
    }
}
