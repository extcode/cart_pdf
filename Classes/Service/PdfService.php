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
        private ViewFactoryInterface $viewFactory,
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

    public function getStandaloneView(string $format = 'html'): ViewInterface
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

    public function renderStandaloneView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = [],
    ): string {
        $view = $this->getStandaloneView();

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

            $content = $this->renderStandaloneView(
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

            $content = $this->renderStandaloneView(
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
                $content = $this->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfig($content, $partConfig);
            }
        }

        if (is_array($pdfSettings['body']['before']['html'] ?? null)) {
            foreach ($pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $content = $this->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfig($content, $partConfig);
            }
        }

        $this->renderCart($pdf, $orderItem, $pdfType);

        if (is_array($pdfSettings['body']['after']['html'] ?? null)) {
            foreach ($pdfSettings['body']['after']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/After/';
                $assignToView = ['orderItem' => $orderItem];
                $content = $this->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                $pdf->writeHtmlCellWithConfig($content, $partConfig);
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
        $bodyOut = $this->renderCartBody($orderItem, $pdfType);
        $footerOut = $this->renderCartFooter($orderItem, $pdfType);

        $content = '<table cellpadding="3">' . $headerOut . $bodyOut . $footerOut . '</table>';

        $pdf->writeHtmlCellWithConfig($content, $config);
    }

    private function renderCartHeader(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->getStandaloneView();
        $view->assign('orderItem', $orderItem);
        $header = $view->render('/' . ucfirst($pdfType) . '/Order/Header');

        return trim(preg_replace('~[\\n]+~', '', $header));
    }

    private function renderCartBody(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->getStandaloneView();
        $view->assign('orderItem', $orderItem);

        $bodyOut = '';

        foreach ($orderItem->getProducts() as $product) {
            $view->assign('product', $product);
            $product = $view->render('/' . ucfirst($pdfType) . '/Order/Product');

            $bodyOut .= trim(preg_replace('~[\\n]+~', '', $product));
        }

        return $bodyOut;
    }

    private function renderCartFooter(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->getStandaloneView();
        $view->assign('orderSettings', $this->configuration[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $footer = $view->render('/' . ucfirst($pdfType) . '/Order/Footer');

        return trim(preg_replace('~[\\n]+~', '', $footer));
    }
}
