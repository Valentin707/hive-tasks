<?php

declare(strict_types=1);

namespace App\Livewire;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class RichTextSanitizer
{
    private const array ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'code',
        'em',
        'h1',
        'h2',
        'h3',
        'li',
        'ol',
        'p',
        'pre',
        's',
        'strong',
        'u',
        'ul',
    ];

    private const array BLOCKED_TAGS = [
        'embed',
        'iframe',
        'math',
        'object',
        'script',
        'style',
        'svg',
    ];

    public static function sanitize(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousInternalErrors = libxml_use_internal_errors(true);

        try {
            $parsed = $document->loadHTML(
                '<?xml encoding="UTF-8" ?><div id="rich-text-root">'.$content.'</div>',
                LIBXML_HTML_NODEFDTD | LIBXML_NONET,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousInternalErrors);
        }

        $root = $document->getElementsByTagName('div')->item(0);

        if (! $parsed || ! ($root instanceof DOMElement)) {
            return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }

        return self::sanitizeNodeChildren($root);
    }

    private static function sanitizeNodeChildren(DOMNode $parent): string
    {
        $sanitized = '';

        foreach ($parent->childNodes as $node) {
            if ($node instanceof DOMText) {
                $sanitized .= htmlspecialchars($node->nodeValue ?? '', ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

                continue;
            }

            if (! ($node instanceof DOMElement)) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::BLOCKED_TAGS, true)) {
                continue;
            }

            $children = self::sanitizeNodeChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $sanitized .= $children;

                continue;
            }

            if ($tag === 'br') {
                $sanitized .= '<br>';

                continue;
            }

            if ($tag === 'a') {
                $sanitized .= self::sanitizeAnchor($node, $children);

                continue;
            }

            $sanitized .= '<'.$tag.'>'.$children.'</'.$tag.'>';
        }

        return $sanitized;
    }

    private static function sanitizeAnchor(DOMElement $anchor, string $children): string
    {
        $href = trim($anchor->getAttribute('href'));
        $scheme = parse_url($href, PHP_URL_SCHEME);

        if (
            $href === ''
            || preg_match('/[\x00-\x20]/', $href) === 1
            || (is_string($scheme) && ! in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true))
        ) {
            return $children;
        }

        $safeHref = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return '<a href="'.$safeHref.'" target="_blank" rel="noopener noreferrer">'.$children.'</a>';
    }
}
