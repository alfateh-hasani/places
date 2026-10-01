<?php

namespace Tests\Feature;

use App\Support\CssBundle;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Front-end stylesheets are concatenated in order into a fingerprinted bundle,
 * with relative url()s rewritten so assets still resolve from the bundle.
 */
class CssBundleTest extends TestCase
{
    private string $sourceDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDirectory = public_path('front/assets/__css-bundle-test');
        File::ensureDirectoryExists($this->sourceDirectory.'/fonts');
        File::put($this->sourceDirectory.'/first.css', '.a{background:url(../img/a.svg)}');
        File::put($this->sourceDirectory.'/fonts/second.css', '@font-face{src:url("x.woff2")}.b{background:url(data:image/png;base64,AA==)}');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceDirectory);
        File::delete(File::glob(public_path('front/assets/css/bundles/test-bundle-*.css')));

        parent::tearDown();
    }

    public function test_relative_urls_are_rewritten_and_absolute_ones_kept(): void
    {
        $css = CssBundle::rewriteUrls(
            '.a{background:url(../img/a.svg)} .b{background:url("data:image/png;base64,AA==")} .c{background:url(https://cdn.test/c.png)} .d{background:url(/root.png)}',
            'css/style.css',
        );

        $this->assertStringContainsString('url("'.asset('assets/img/a.svg').'")', $css);
        $this->assertStringContainsString('url("data:image/png;base64,AA==")', $css);
        $this->assertStringContainsString('url(https://cdn.test/c.png)', $css);
        $this->assertStringContainsString('url(/root.png)', $css);
    }

    public function test_files_are_bundled_in_order_into_one_stylesheet(): void
    {
        $html = (string) CssBundle::tags('test-bundle', ['__css-bundle-test/first.css', '__css-bundle-test/fonts/second.css']);

        $this->assertSame(1, substr_count($html, '<link'));
        $bundles = File::glob(public_path('front/assets/css/bundles/test-bundle-*.css'));
        $this->assertCount(1, $bundles);

        $css = File::get($bundles[0]);
        $this->assertLessThan(strpos($css, '.b{'), strpos($css, '.a{'));
        $this->assertStringContainsString(asset('assets/img/a.svg'), $css);
        $this->assertStringContainsString(asset('assets/__css-bundle-test/fonts/x.woff2'), $css);
    }

    public function test_editing_a_source_file_produces_a_new_bundle_and_removes_the_old_one(): void
    {
        $files = ['__css-bundle-test/first.css'];
        $before = (string) CssBundle::tags('test-bundle', $files);

        File::put($this->sourceDirectory.'/first.css', '.a{color:red}.changed{}');
        clearstatcache();
        $after = (string) CssBundle::tags('test-bundle', $files);

        $this->assertNotSame($before, $after);
        $this->assertCount(1, File::glob(public_path('front/assets/css/bundles/test-bundle-*.css')));
    }

    public function test_missing_files_are_skipped(): void
    {
        $this->assertSame('', (string) CssBundle::tags('test-bundle', ['__css-bundle-test/missing.css']));
    }
}
