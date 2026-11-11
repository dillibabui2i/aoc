<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\HtmlReplaceHelper;
use Tests\UnitTestCase;

class HtmlReplaceHelperTest extends UnitTestCase
{
    private HtmlReplaceHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new HtmlReplaceHelper();
    }

    public function testReplaceTable(): void
    {
        $sourceHtml = '<div><table border="1"><tr><td>Source</td></tr></table></div>';
        $targetHtml = '<body><p>Hello</p><table class="old"><tr><td>Target</td></tr></table></body>';

        $expected = '<body><p>Hello</p><table border="1"><tr><td>Source</td></tr></table></body>';

        $result = $this->helper->replaceTable($sourceHtml, $targetHtml);

        $this->assertEquals($expected, $result);
    }

    public function testReplaceTableNoSourceTable(): void
    {
        $sourceHtml = '<div>No table here</div>';
        $targetHtml = '<body><table><tr><td>Target</td></tr></table></body>';

        $result = $this->helper->replaceTable($sourceHtml, $targetHtml);

        $this->assertEquals($targetHtml, $result);
    }

    public function testReplaceTableNoTargetTable(): void
    {
        $sourceHtml = '<div><table><tr><td>Source</td></tr></table></div>';
        $targetHtml = '<body>No table here</body>';

        $result = $this->helper->replaceTable($sourceHtml, $targetHtml);

        // preg_replace returns targetHtml unchanged if no match
        $this->assertEquals($targetHtml, $result);
    }

    public function testReplaceTableMultiLine(): void
    {
        $sourceHtml = "<div>\n<table border=\"1\">\n<tr><td>Source</td></tr>\n</table>\n</div>";
        $targetHtml = "<body>\n<table class=\"old\">\n<tr><td>Target</td></tr>\n</table>\n</body>";

        $expected = "<body>\n<table border=\"1\">\n<tr><td>Source</td></tr>\n</table>\n</body>";

        $result = $this->helper->replaceTable($sourceHtml, $targetHtml);

        $this->assertEquals($expected, $result);
    }
}
