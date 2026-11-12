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
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;

class TcpdfWrapper extends TCPDF
{
    private readonly array $pdfSettings;
    private readonly array $viewSettings;

    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
        private readonly string $pdfType,
        array $settings,
    ) {
        $this->pdfSettings = $settings[$this->pdfType];
        $this->viewSettings = $settings['view'];

        parent::__construct();
    }

    public static function createWithTypeAndSettings(
        ViewFactoryInterface $viewFactory,
        string $pdfType,
        array $pdfSettings,
    ): self {
        return new self(
            $viewFactory,
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
                $this->renderView(
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
                $this->renderView(
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

    public function renderView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = []
    ): void {
        $view = $this->createView($templatePath, ucfirst($type));

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
        $width = (float)($config['width'] ?? 0.0);
        $height = (float)($config['height'] ?? 0.0);

        $positionX = null;
        if (isset($config['positionX']) && is_numeric($config['positionX'])) {
            $positionX = (float)$config['positionX'];
        }
        $positionY = null;
        if (isset($config['positionY']) && is_numeric($config['positionY'])) {
            $positionY = (float)$config['positionY'];
        }

        $align = 'L';
        if (isset($config['align']) && in_array($config['align'], ['L', 'C', 'R', ''])) {
            $align = $config['align'];
        }

        $oldFontSize = null;
        if (!empty($config['fontSize']) && is_numeric($config['fontSize'])) {
            $oldFontSize = $this->getFontSizePt();
            $this->SetFontSize((float)$config['fontSize']);
        }

        if (isset($config['spacingY']) && is_numeric($config['spacingY'])) {
            $this->setY($this->getY() + (float)$config['spacingY']);
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

        if (is_null($oldFontSize) === false) {
            $this->SetFontSize($oldFontSize);
        }
    }

    public function createView(
        string $templatePath,
        string $templateFileName = 'Default',
        string $format = 'html'
    ): ViewInterface {
        $templatePathAndFileName = $templatePath . $templateFileName . '.' . $format;

        $layoutRootPaths = null;
        $partialRootPaths = null;
        $completeTemplatePathAndFileName = null;

        if (!empty($this->viewSettings)) {
            $layoutRootPaths = $this->viewSettings['layoutRootPaths'];
            $partialRootPaths = $this->viewSettings['partialRootPaths'];

            if ($this->viewSettings['templateRootPaths']) {
                foreach ($this->viewSettings['templateRootPaths'] as $pathNameValue) {
                    $templateRootPath = GeneralUtility::getFileAbsFileName(
                        $pathNameValue
                    );

                    $completePath = $templateRootPath . $templatePathAndFileName;

                    if (file_exists($completePath)) {
                        $completeTemplatePathAndFileName = $completePath;
                    }
                }
            }
        }

        $viewFactoryData = new ViewFactoryData(
            partialRootPaths: $partialRootPaths,
            layoutRootPaths: $layoutRootPaths,
            templatePathAndFilename: $completeTemplatePathAndFileName,
            format: $format,
        );

        return $this->viewFactory->create($viewFactoryData);
    }
}
