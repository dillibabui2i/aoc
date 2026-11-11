<?php

namespace App\VBC\Services\CdcVaccine;

use SimpleXMLElement;

class CdcXmlReader
{
    /**
     * @throws CdcSyncException on invalid XML or a wrong root element
     */
    public function read(string $xml, string $rootName): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $root = simplexml_load_string($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($root === false || $root->getName() !== $rootName) {
            throw new CdcSyncException("Invalid CDC XML: expected root element <{$rootName}>");
        }

        return $root;
    }
}
