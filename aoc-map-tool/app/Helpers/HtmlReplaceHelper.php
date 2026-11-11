<?php

declare(strict_types=1);

namespace App\Helpers;

class HtmlReplaceHelper
{
    /**
     * Replaces the table in $targetHtml with the table from $sourceHtml.
     *
     * @param string $sourceHtml
     * @param string $targetHtml
     * @return string
     */
    public function replaceTable(string $sourceHtml, string $targetHtml): string
    {
        $pattern = '/<table[^>]*>.*?<\/table>/is';

        if (preg_match($pattern, $sourceHtml, $matches)) {
            $sourceTable = $matches[0];
            return preg_replace($pattern, $sourceTable, $targetHtml);
        }

        return $targetHtml;
    }
}
