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

class TcpdfWrapper extends TCPDF
{
    private readonly array $pdfSettings;
    private array $headerParts = [];
    private array $footerParts = [];

    public function __construct(
        private readonly string $pdfType,
        array $settings,
    ) {
        $this->pdfSettings = $settings[$this->pdfType];

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

    public function addHeaderPart($content, array $config): void
    {
        $this->headerParts[] = ['content' => $content, 'config' => $config];
    }

    public function addFooterPart($content, array $config): void
    {
        $this->footerParts[] = ['content' => $content, 'config' => $config];
    }

    public function getCartPdfType(): string
    {
        return $this->pdfType;
    }

    public function Header(): void
    {
        if (empty($this->pdfSettings['header'] ?? [])) {
            return;
        }

        if (!empty($this->pdfSettings['fontSize'])) {
            $this->setFontSize($this->pdfSettings['fontSize']);
        }

        foreach ($this->headerParts as $headerPart) {
            $this->writeHtmlCellWithConfig($headerPart['content'], $headerPart['config']);
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

    public function Footer(): void
    {
        if (empty($this->pdfSettings['footer'] ?? [])) {
            return;
        }

        if (!empty($this->pdfSettings['fontSize'])) {
            $this->setFontSize($this->pdfSettings['fontSize']);
        }

        foreach ($this->footerParts as $footerPart) {
            $this->writeHtmlCellWithConfig($footerPart['content'], $footerPart['config']);
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
        if (isset($config['fontSize']) && is_numeric($config['fontSize'])) {
            $oldFontSize = $this->getFontSizePt();
            $this->setFontSize((float)$config['fontSize']);
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
            $this->setFontSize($oldFontSize);
        }
    }
}
