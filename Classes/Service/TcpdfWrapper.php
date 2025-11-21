<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TCPDF;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class TcpdfWrapper extends TCPDF
{
    private readonly array $pdfSettings;
    private readonly array $viewSettings;

    public function __construct(
        private readonly string $pdfType,
        array $settings,
    ) {
        $this->pdfSettings = $settings[$this->pdfType];
        $this->viewSettings = $settings['view'];

        parent::__construct();
    }

    public static function createWithTypeAndSettings(
        string $pdfType,
        array $pdfSettings,
    ): self {
        return new self(
            $pdfType,
            $pdfSettings,
        );
    }

    public function getCartPdfType(): string
    {
        return $this->pdfType;
    }

    public function header(): void
    {
        if (empty($this->pdfSettings['header'] ?? [])) {
            return;
        }

        if (!empty($this->pdfSettings['fontSize'])) {
            $this->SetFontSize($this->pdfSettings['fontSize']);
        }

        if (!empty($this->pdfSettings['header']['html'])) {
            foreach ($this->pdfSettings['header']['html'] as $partName => $partConfig) {
                $this->renderStandaloneView(
                    $this->pdfSettings['header']['html'][$partName]['templatePath'],
                    $partName,
                    $partConfig
                );
            }
        }

        if (!empty($this->pdfSettings['header']['line'])) {
            foreach ($this->pdfSettings['header']['line'] as $partConfig) {
                $this->Line(
                    $partConfig['x1'],
                    $partConfig['y1'],
                    $partConfig['x2'],
                    $partConfig['y2'],
                    $partConfig['style']
                );
            }
        }
    }

    public function footer(): void
    {
        if (empty($this->pdfSettings['footer'] ?? [])) {
            return;
        }

        if (!empty($this->pdfSettings['fontSize'])) {
            $this->SetFontSize($this->pdfSettings['fontSize']);
        }

        if (!empty($this->pdfSettings['footer']['html'])) {
            foreach ($this->pdfSettings['footer']['html'] as $partName => $partConfig) {
                $this->renderStandaloneView(
                    $this->pdfSettings['footer']['html'][$partName]['templatePath'],
                    $partName,
                    $partConfig
                );
            }
        }

        if (!empty($this->pdfSettings['footer']['line'])) {
            foreach ($this->pdfSettings['footer']['line'] as $partConfig) {
                $this->Line(
                    $partConfig['x1'],
                    $partConfig['y1'],
                    $partConfig['x2'],
                    $partConfig['y2'],
                    $partConfig['style']
                );
            }
        }
    }

    public function renderStandaloneView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = []
    ): void {
        $view = $this->getStandaloneView($templatePath, ucfirst($type));

        if (!empty($config['file'])) {
            $file = GeneralUtility::getFileAbsFileName($config['file']);
            $view->assign('file', $file);
            $view->assign('file_base64', base64_encode(file_get_contents($file)));
            $view->assign('file_mimetype', mime_content_type($file));

            if (!empty($config['width'])) {
                $view->assign('width', $config['width']);
            }

            if (!empty($config['height'])) {
                $view->assign('heigth', $config['heigth']);
            }
        }

        if ($type === 'page') {
            $view->assign('numPage', $this->getAliasNumPage());
            $view->assign('numPages', $this->getAliasNbPages());
        }

        $view->assignMultiple($assignToView);

        $content = (string)$view->render();
        $content = trim(preg_replace('~[\\n]+~', '', $content));

        $this->writeHtmlCellWithConfig($content, $config);
    }

    public function writeHtmlCellWithConfig(string $content, array $config): void
    {
        $width = $config['width'];
        $height = 0;

        if (!empty($config['height'])) {
            $height = $config['height'];
        }
        $positionX = $config['positionX'];
        $positionY = $config['positionY'];
        $align = 'L';

        if (!empty($config['align'])) {
            $align = $config['align'];
        }

        $oldFontSize = $this->getFontSizePt();

        if (!empty($config['fontSize'])) {
            $this->SetFontSize($config['fontSize']);
        }

        if (!empty($config['spacingY'])) {
            $this->setY($this->getY() + $config['spacingY']);
        }

        $this->writeHTMLCell(
            $width,
            $height,
            $positionX,
            $positionY,
            $content,
            false,
            2,
            false,
            true,
            $align,
            true
        );

        if (!empty($config['fontSize'])) {
            $this->SetFontSize($oldFontSize);
        }
    }

    public function getStandaloneView(
        string $templatePath,
        string $templateFileName = 'Default',
        string $format = 'html'
    ): StandaloneView {
        $templatePathAndFileName = $templatePath . $templateFileName . '.' . $format;

        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setFormat($format);

        if (!empty($this->viewSettings)) {
            $view->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($this->viewSettings['layoutRootPaths']);
            $view->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($this->viewSettings['partialRootPaths']);

            if ($this->viewSettings['templateRootPaths']) {
                foreach ($this->viewSettings['templateRootPaths'] as $pathNameKey => $pathNameValue) {
                    $templateRootPath = GeneralUtility::getFileAbsFileName(
                        $pathNameValue
                    );

                    $completePath = $templateRootPath . $templatePathAndFileName;

                    if (file_exists($completePath)) {
                        $view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($completePath);
                    }
                }
            }
        }

        return $view;
    }
}
