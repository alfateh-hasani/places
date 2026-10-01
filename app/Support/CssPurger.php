<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Minimal PurgeCSS for precompiled vendor stylesheets (Flowbite): drops selectors
 * whose class names never appear in the given content, keeps everything else
 * (element/attribute selectors, @font-face, @keyframes, …) untouched.
 */
class CssPurger
{
    /**
     * Collect candidate class tokens from source files, the same broad way
     * Tailwind's extractor does (over-inclusion only keeps extra rules).
     *
     * @param  list<string>  $paths
     * @return array<string, true>
     */
    public static function tokensFromFiles(array $paths): array
    {
        $tokens = [];

        foreach ($paths as $path) {
            foreach (preg_split('/[\s"\'`<>=;{}]+/', (string) File::get($path)) ?: [] as $token) {
                if ($token === '') {
                    continue;
                }

                $tokens[$token] = true;

                foreach (preg_split('/[,()]/', $token) ?: [] as $part) {
                    if ($part !== '') {
                        $tokens[$part] = true;
                    }
                }
            }
        }

        return $tokens;
    }

    /**
     * @param  array<string, true>  $tokens
     */
    public static function purge(string $css, array $tokens): string
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        $output = '';
        $length = strlen($css);
        $position = 0;

        while ($position < $length) {
            $open = strpos($css, '{', $position);

            if ($open === false) {
                break;
            }

            $prelude = trim(substr($css, $position, $open - $position));
            $close = self::matchingBrace($css, $open);
            $body = substr($css, $open + 1, $close - $open - 1);
            $position = $close + 1;

            if (str_starts_with($prelude, '@media') || str_starts_with($prelude, '@supports')) {
                $inner = self::purge($body, $tokens);

                if ($inner !== '') {
                    $output .= $prelude.'{'.$inner.'}';
                }
            } elseif (str_starts_with($prelude, '@')) {
                $output .= $prelude.'{'.$body.'}';
            } else {
                $kept = array_filter(
                    self::splitSelectors($prelude),
                    fn (string $selector): bool => self::isUsed($selector, $tokens),
                );

                if ($kept !== []) {
                    $output .= implode(',', $kept).'{'.$body.'}';
                }
            }
        }

        return $output;
    }

    private static function matchingBrace(string $css, int $open): int
    {
        $depth = 0;
        $length = strlen($css);
        $quote = null;

        for ($index = $open; $index < $length; $index++) {
            $character = $css[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '{') {
                $depth++;
            } elseif ($character === '}' && --$depth === 0) {
                return $index;
            }
        }

        return $length - 1;
    }

    /**
     * Split a selector list on top-level commas (not those inside :is(...) etc.).
     *
     * @return list<string>
     */
    private static function splitSelectors(string $selectorList): array
    {
        $selectors = [];
        $depth = 0;
        $current = '';
        $length = strlen($selectorList);

        for ($index = 0; $index < $length; $index++) {
            $character = $selectorList[$index];

            if ($character === '\\') {
                $current .= $character.($selectorList[$index + 1] ?? '');
                $index++;

                continue;
            }

            if ($character === '(' || $character === '[') {
                $depth++;
            } elseif ($character === ')' || $character === ']') {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $selectors[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $character;
        }

        $selectors[] = trim($current);

        return array_values(array_filter($selectors, fn (string $selector): bool => $selector !== ''));
    }

    /**
     * A selector is kept when every class it requires (outside of functional
     * pseudo-classes like :is()/:not()) appears in the content.
     *
     * @param  array<string, true>  $tokens
     */
    private static function isUsed(string $selector, array $tokens): bool
    {
        $withoutPseudoArguments = (string) preg_replace('/:(?:is|where|not|has)\((?:[^()]|\([^()]*\))*\)/', '', $selector);
        preg_match_all('/\.((?:\\\\[0-9a-fA-F]{1,6} ?|\\\\.|[A-Za-z0-9_-])+)/', $withoutPseudoArguments, $matches);

        foreach ($matches[1] as $escapedClass) {
            $class = (string) preg_replace_callback(
                '/\\\\([0-9a-fA-F]{1,6}) ?|\\\\(.)/',
                fn (array $escape): string => ($escape[2] ?? '') !== '' ? $escape[2] : mb_chr((int) hexdec($escape[1])),
                $escapedClass,
            );

            if (! isset($tokens[$class])) {
                return false;
            }
        }

        return true;
    }
}
