<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Serves a group of front-end stylesheets as one render-blocking request instead
 * of one per file, without a build step.
 *
 * Files are concatenated in the given order (so the cascade is unchanged) into
 * public/front/assets/css/bundles/{name}-{hash}.css. The hash covers each file's
 * path, mtime and size, so editing any source file (e.g. custom.css) produces a
 * new bundle on the next request. Relative url()s are rewritten to absolute
 * URLs so fonts/images keep resolving from the bundle's location.
 *
 * If the bundle cannot be written, the individual <link> tags are emitted.
 */
class CssBundle
{
    private const BUNDLE_DIR = 'front/assets/css/bundles';

    /**
     * @param  list<string>  $files  Paths relative to public/front/assets, e.g. "css/style.css".
     */
    public static function tags(string $name, array $files): HtmlString
    {
        $files = array_values(array_filter($files, fn (string $file): bool => is_file(self::sourcePath($file))));

        if ($files === []) {
            return new HtmlString('');
        }

        $url = self::bundleUrl($name, $files);

        if ($url === null) {
            return new HtmlString(implode("\n    ", array_map(
                fn (string $file): string => self::linkTag(asset('assets/'.$file).'?v='.filemtime(self::sourcePath($file))),
                $files,
            )));
        }

        return new HtmlString(self::linkTag($url));
    }

    /**
     * @param  list<string>  $files
     */
    private static function bundleUrl(string $name, array $files): ?string
    {
        $fingerprint = implode('|', array_map(
            fn (string $file): string => $file.':'.filemtime(self::sourcePath($file)).':'.filesize(self::sourcePath($file)),
            $files,
        ));
        $filename = $name.'-'.substr(md5($fingerprint), 0, 12).'.css';
        $target = public_path(self::BUNDLE_DIR.'/'.$filename);

        if (! is_file($target) && ! self::build($name, $files, $target)) {
            return null;
        }

        return asset('assets/css/bundles/'.$filename);
    }

    /**
     * @param  list<string>  $files
     */
    private static function build(string $name, array $files, string $target): bool
    {
        try {
            $directory = dirname($target);

            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                return false;
            }

            $css = '';

            foreach ($files as $file) {
                $css .= '/* '.$file.' */'."\n".self::rewriteUrls((string) file_get_contents(self::sourcePath($file)), $file)."\n";
            }

            $temporary = $target.'.'.bin2hex(random_bytes(4)).'.tmp';

            if (file_put_contents($temporary, $css) === false || ! rename($temporary, $target)) {
                @unlink($temporary);

                return false;
            }

            foreach (glob($directory.'/'.$name.'-*.css') ?: [] as $stale) {
                if ($stale !== $target) {
                    @unlink($stale);
                }
            }

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Rewrite relative url(...) references against the source file's own URL.
     */
    public static function rewriteUrls(string $css, string $file): string
    {
        $baseDirectory = dirname($file);

        return (string) preg_replace_callback(
            '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
            function (array $match) use ($baseDirectory): string {
                $reference = trim($match[2]);

                if (preg_match('#^(data:|https?:|//|/|\#)#i', $reference)) {
                    return $match[0];
                }

                return 'url("'.asset('assets/'.self::normalizePath($baseDirectory.'/'.$reference)).'")';
            },
            $css,
        );
    }

    private static function normalizePath(string $path): string
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    private static function sourcePath(string $file): string
    {
        return public_path('front/assets/'.$file);
    }

    private static function linkTag(string $href): string
    {
        return '<link href="'.e($href).'" rel="stylesheet" />';
    }
}
