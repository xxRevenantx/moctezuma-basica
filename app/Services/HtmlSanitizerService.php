<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Throwable;

class HtmlSanitizerService
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'span',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'hr', 'div', 'a',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form',
        'input', 'button', 'textarea', 'select', 'option', 'meta', 'link', 'base',
    ];

    private const GLOBAL_ATTRIBUTES = ['class', 'style', 'title'];

    private const TAG_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'ol' => ['start', 'type'],
    ];

    private const ALLOWED_STYLE_PROPERTIES = [
        'text-align', 'font-weight', 'font-style', 'text-decoration', 'font-size',
        'line-height', 'color', 'background-color', 'margin', 'margin-left',
        'margin-right', 'margin-top', 'margin-bottom', 'padding', 'padding-left',
        'padding-right', 'padding-top', 'padding-bottom', 'border', 'border-width',
        'border-style', 'border-color', 'border-collapse', 'width', 'max-width',
        'vertical-align', 'list-style-type',
    ];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return $this->fallback($html);
        }

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $previous = libxml_use_internal_errors(true);
            $dom->loadHTML(
                '<?xml encoding="utf-8" ?><div id="sanitizer-root">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $root = $dom->getElementById('sanitizer-root');
            if (! $root) {
                return $this->fallback($html);
            }

            $this->cleanChildren($root);

            $result = '';
            foreach (iterator_to_array($root->childNodes) as $child) {
                $result .= $dom->saveHTML($child);
            }

            return trim($result);
        } catch (Throwable) {
            return $this->fallback($html);
        }
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                if ($node->nodeType === XML_COMMENT_NODE) {
                    $parent->removeChild($node);
                }
                continue;
            }

            $tag = mb_strtolower($node->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);
                continue;
            }

            $this->cleanChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $this->cleanAttributes($node, $tag);
        }
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::GLOBAL_ATTRIBUTES, self::TAG_ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = mb_strtolower($attribute->name);
            $value = trim($attribute->value);

            if (str_starts_with($name, 'on') || ! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if ($name === 'style') {
                $style = $this->sanitizeStyle($value);
                if ($style === '') {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', $style);
                }
            }

            if ($name === 'href' && ! $this->safeUrl($value)) {
                $element->removeAttribute('href');
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function sanitizeStyle(string $style): string
    {
        if (preg_match('/url\s*\(|expression\s*\(|javascript:|data:/i', $style)) {
            return '';
        }

        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, null);
            $property = mb_strtolower(trim((string) $property));
            $value = trim((string) $value);

            if ($property === '' || $value === '' || ! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            if (preg_match('/url\s*\(|expression\s*\(|javascript:|data:/i', $value)) {
                continue;
            }

            $safe[] = $property.': '.$value;
        }

        return implode('; ', $safe);
    }

    private function safeUrl(string $url): bool
    {
        if ($url === '' || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }

    private function fallback(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|math|form|input|button|textarea|select|option|meta|link|base)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(input|meta|link|base)[^>]*?/?>#is', '', $html) ?? '';

        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $html = strip_tags($html, $allowed);

        // Sin DOM no es posible validar atributos con precisión. Se eliminan
        // todos los atributos y se conservan únicamente las etiquetas seguras.
        $html = preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html) ?? '';

        return trim($html);
    }
}
