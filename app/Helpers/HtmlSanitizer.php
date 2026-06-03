<?php

namespace App\Helpers;

class HtmlSanitizer
{
    private static array $allowedTags = [
        'p', 'br', 'b', 'i', 'u', 'em', 'strong', 'a', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img', 'figure',
        'figcaption', 'div', 'span', 'hr', 'sub', 'sup', 'mark',
    ];

    private static array $allowedAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        '*' => ['class', 'id', 'style'],
    ];

    private static array $dangerousPatterns = [
        '/on\w+\s*=/i',           // onclick, onerror, onload, etc.
        '/javascript\s*:/i',      // javascript: URLs
        '/vbscript\s*:/i',        // vbscript: URLs
        '/data\s*:[^image]/i',    // data: URLs (except data:image)
        '/expression\s*\(/i',     // CSS expression()
        '/@import/i',             // CSS @import
        '/behavior\s*:/i',        // CSS behavior
        '/-moz-binding/i',        // Firefox XBL binding
    ];

    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Strip dangerous patterns first
        foreach (self::$dangerousPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Build allowed tags string for strip_tags
        $tagString = implode('', array_map(fn($t) => "<{$t}>", self::$allowedTags));
        $html = strip_tags($html, $tagString);

        // Remove dangerous attributes using DOMDocument
        if (empty(trim($html))) {
            return '';
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        foreach ($xpath->query('//*') as $node) {
            $tag = strtolower($node->nodeName);
            $allowedForTag = array_merge(
                self::$allowedAttributes['*'] ?? [],
                self::$allowedAttributes[$tag] ?? []
            );

            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                $attrName = strtolower($attr->name);
                $attrValue = $attr->value;

                // Remove disallowed attributes
                if (!in_array($attrName, $allowedForTag)) {
                    $attrsToRemove[] = $attr->name;
                    continue;
                }

                // Sanitize href/src — only allow http, https, mailto, tel
                if (in_array($attrName, ['href', 'src'])) {
                    $scheme = parse_url($attrValue, PHP_URL_SCHEME);
                    if ($scheme && !in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'])) {
                        $attrsToRemove[] = $attr->name;
                    }
                }

                // Check attribute values for dangerous patterns
                foreach (self::$dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $attrValue)) {
                        $attrsToRemove[] = $attr->name;
                        break;
                    }
                }
            }

            foreach ($attrsToRemove as $name) {
                $node->removeAttribute($name);
            }

            // Force rel="noopener noreferrer" and target="_blank" on external links
            if ($tag === 'a' && $node->hasAttribute('href')) {
                $href = $node->getAttribute('href');
                if (str_starts_with($href, 'http')) {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        // Extract the inner HTML of our wrapper div
        $wrapper = $doc->getElementsByTagName('div')->item(0);
        $result = '';
        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $result .= $doc->saveHTML($child);
            }
        }

        return $result;
    }
}
