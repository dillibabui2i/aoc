<?php

namespace Tests\Feature\VBC\CdcVaccine;

trait CdcXmlFixtures
{
    protected const GROUPS_URL = 'https://www2.cdc.gov/vaccines/iis/iisstandards/XML.asp?rpt=vax2vg';
    protected const VACCINES_URL = 'https://www2a.cdc.gov/vaccines/iis/iisstandards/XML2.asp?rpt=cvx';

    /**
     * @param array<int, array{0: string, 1: string, 2: string}> $rows [cvx code, group name, group code]
     */
    protected function vaxToGroupXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><VGCodes>';
        foreach ($rows as [$cvx, $groupName, $groupCode]) {
            $xml .= '<CVXVGInfo>'
                . '<Name>ShortDescription</Name><Value>desc</Value>'
                . '<Name>CVXCode</Name><Value>' . str_pad($cvx, 10) . '</Value>'
                . '<Name>Status</Name><Value>Active</Value>'
                . '<Name>Vaccine Group Name</Name><Value>' . $groupName . '</Value>'
                . '<Name>CVX for Vaccine Group</Name><Value>' . $groupCode . '</Value>'
                . '</CVXVGInfo>';
        }

        return $xml . '</VGCodes>';
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> $rows
     *        [cvx code, short description, full name, status, last updated]
     */
    protected function cvxXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><CVXCodes>';
        foreach ($rows as [$cvx, $short, $full, $status, $updated]) {
            $xml .= '<CVXInfo><ShortDescription>' . $short . '</ShortDescription>'
                . '<FullVaccinename>' . $full . '</FullVaccinename>'
                . '<CVXCode>' . str_pad($cvx, 10) . '</CVXCode><Notes/>'
                . '<Status>' . $status . '</Status><LastUpdated>' . $updated . '</LastUpdated></CVXInfo>';
        }

        return $xml . '</CVXCodes>';
    }
}
