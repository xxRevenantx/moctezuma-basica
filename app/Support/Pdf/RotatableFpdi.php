<?php

namespace App\Support\Pdf;

use setasign\Fpdi\Fpdi;

class RotatableFpdi extends Fpdi
{
    protected float $rotationAngle = 0.0;

    public function rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x < 0) {
            $x = $this->GetX();
        }

        if ($y < 0) {
            $y = $this->GetY();
        }

        if ($this->rotationAngle !== 0.0) {
            $this->_out('Q');
        }

        $this->rotationAngle = $angle;

        if ($angle !== 0.0) {
            $angle *= M_PI / 180;
            $cos = cos($angle);
            $sin = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;

            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                $cos,
                $sin,
                -$sin,
                $cos,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }

    public function placeTemplateRotated(int|string $templateId, array $size, int $rotation): void
    {
        $rotation = (($rotation % 360) + 360) % 360;
        $sourceWidth = (float) $size['width'];
        $sourceHeight = (float) $size['height'];
        $pageWidth = $this->GetPageWidth();
        $pageHeight = $this->GetPageHeight();

        if ($rotation === 0) {
            $this->useTemplate($templateId, 0, 0, $sourceWidth, $sourceHeight, true);

            return;
        }

        $this->rotate($rotation, $pageWidth / 2, $pageHeight / 2);
        $x = ($pageWidth - $sourceWidth) / 2;
        $y = ($pageHeight - $sourceHeight) / 2;
        $this->useTemplate($templateId, $x, $y, $sourceWidth, $sourceHeight, true);
        $this->rotate(0);
    }

    protected function _endpage(): void
    {
        if ($this->rotationAngle !== 0.0) {
            $this->rotationAngle = 0.0;
            $this->_out('Q');
        }

        parent::_endpage();
    }
}
