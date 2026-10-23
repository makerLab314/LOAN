<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

/**
 * QR-Codes und Code-128-Barcodes als SVG.
 */
class Codes
{
    public function qrSvg(string $content, int $size = 200): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd));

        return $this->stripXmlHeader($writer->writeString($content));
    }

    public function barcodeSvg(string $content): string
    {
        $barcode = (new TypeCode128)->getBarcode($content);
        $renderer = new SvgRenderer;
        $renderer->setSvgType(SvgRenderer::TYPE_SVG_INLINE);

        return $this->stripXmlHeader($renderer->render($barcode, $barcode->getWidth() * 2, 40));
    }

    private function stripXmlHeader(string $svg): string
    {
        return trim((string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}
