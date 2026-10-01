<?php

namespace Tests\Unit;

use App\Support\CssPurger;
use PHPUnit\Framework\TestCase;

/**
 * Vendor CSS is purged to the classes the site's content actually uses.
 */
class CssPurgerTest extends TestCase
{
    public function test_unused_class_rules_are_dropped_and_used_ones_kept(): void
    {
        $css = '.used{color:red}.unused{color:blue}.used.unused{color:green}';

        $this->assertSame('.used{color:red}', CssPurger::purge($css, ['used' => true]));
    }

    public function test_selector_lists_keep_only_used_selectors(): void
    {
        $css = '.a,.b,input{margin:0}';

        $this->assertSame('.a,input{margin:0}', CssPurger::purge($css, ['a' => true]));
    }

    public function test_classless_rules_and_non_media_at_rules_are_kept(): void
    {
        $css = 'input::placeholder{color:#9ca3af}[type=text]{border:0}@keyframes spin{to{transform:rotate(1turn)}}@font-face{font-family:x}';

        $this->assertSame($css, CssPurger::purge($css, []));
    }

    public function test_media_queries_are_purged_and_dropped_when_empty(): void
    {
        $css = '@media (min-width:768px){.md\:flex{display:flex}.md\:grid{display:grid}}@media print{.gone{display:none}}';

        $this->assertSame('@media (min-width:768px){.md\:flex{display:flex}}', CssPurger::purge($css, ['md:flex' => true]));
    }

    public function test_escaped_and_hex_escaped_class_names_are_matched(): void
    {
        $css = '.w-1\/2{width:50%}.\32xl\:block{display:block}.hover\:bg-gray-100:hover{color:red}';

        $this->assertSame($css, CssPurger::purge($css, ['w-1/2' => true, '2xl:block' => true, 'hover:bg-gray-100' => true]));
    }

    public function test_classes_inside_functional_pseudo_classes_are_not_required(): void
    {
        $css = '.dark\:bg-gray-700:is(.dark *){background:#374151}';

        $this->assertSame($css, CssPurger::purge($css, ['dark:bg-gray-700' => true]));
    }

    public function test_braces_inside_strings_do_not_break_parsing(): void
    {
        $css = '.a{content:"}"}.b{color:red}';

        $this->assertSame('.a{content:"}"}', CssPurger::purge($css, ['a' => true]));
    }
}
