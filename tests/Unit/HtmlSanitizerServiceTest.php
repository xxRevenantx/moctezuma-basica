<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizerService;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerServiceTest extends TestCase
{
    public function test_elimina_scripts_eventos_y_urls_javascript(): void
    {
        $html = <<<'HTML'
<p onclick="alert(1)">Texto<script>alert(2)</script><a href="javascript:alert(3)" target="_blank">enlace</a></p>
HTML;

        $resultado = (new HtmlSanitizerService())->sanitize($html);

        $this->assertStringContainsString('Texto', $resultado);
        $this->assertStringContainsString('enlace', $resultado);
        $this->assertStringNotContainsStringIgnoringCase('<script', $resultado);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $resultado);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $resultado);
    }

    public function test_conserva_formato_editor_permitido(): void
    {
        $html = '<p style="text-align: center; position: fixed"><strong>Contenido</strong></p>';
        $resultado = (new HtmlSanitizerService())->sanitize($html);

        $this->assertStringContainsString('<p', $resultado);
        $this->assertStringContainsString('<strong>Contenido</strong>', $resultado);
        $this->assertStringNotContainsString('position', $resultado);
    }
}
