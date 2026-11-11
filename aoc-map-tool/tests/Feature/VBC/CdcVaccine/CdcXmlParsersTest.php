<?php

namespace Tests\Feature\VBC\CdcVaccine;

use App\VBC\Services\CdcVaccine\CdcSyncException;
use App\VBC\Services\CdcVaccine\CdcVaccineXmlParser;
use App\VBC\Services\CdcVaccine\CdcVaxToGroupXmlParser;
use Tests\TestCase;

class CdcXmlParsersTest extends TestCase
{
    use CdcXmlFixtures;

    public function test_group_parser_trims_casts_and_deduplicates_groups(): void
    {
        $result = app(CdcVaxToGroupXmlParser::class)->parse($this->vaxToGroupXml([
            ['01', ' DTAP ', '107'],
            ['02', 'POLIO', '89'],
            ['20', ' DTAP ', '107'],
        ]));

        $this->assertSame([107, 89], array_keys($result->groups));
        $this->assertSame('DTAP', $result->groups[107]->cdcName);
        $this->assertSame([1 => 107, 2 => 89, 20 => 107], $result->vaccineGroupCodes);
    }

    public function test_group_parser_first_group_wins_for_multi_group_vaccine(): void
    {
        $result = app(CdcVaxToGroupXmlParser::class)->parse($this->vaxToGroupXml([
            ['10', 'POLIO', '89'],
            ['10', 'OTHER', '55'],
        ]));

        $this->assertSame([10 => 89], $result->vaccineGroupCodes);
        $this->assertSame([89, 55], array_keys($result->groups));
    }

    public function test_vaccine_parser_trims_and_converts_date(): void
    {
        $result = app(CdcVaccineXmlParser::class)->parse($this->cvxXml([
            ['143', ' Adeno 4 and 7 ', ' Adenovirus, type 4 ', 'Active', '3/20/2011'],
            ['54', 'adeno 4', 'adenovirus 4', 'Inactive', 'not-a-date'],
            ['55', 'x', 'y', 'Active', ''],
        ]));

        $this->assertSame([143, 54, 55], array_keys($result));
        $this->assertSame('Adeno 4 and 7', $result[143]->shortDescription);
        $this->assertSame('Adenovirus, type 4', $result[143]->fullName);
        $this->assertSame('2011-03-20', $result[143]->lastUpdated);
        $this->assertNull($result[54]->lastUpdated);
        $this->assertNull($result[55]->lastUpdated);
    }

    public function test_invalid_empty_or_wrong_root_xml_throws(): void
    {
        $parser = app(CdcVaxToGroupXmlParser::class);
        foreach (['<VGCodes><broken>', '<VGCodes></VGCodes>', '<Other><CVXVGInfo/></Other>'] as $xml) {
            try {
                $parser->parse($xml);
                $this->fail('Expected CdcSyncException for: ' . $xml);
            } catch (CdcSyncException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->expectException(CdcSyncException::class);
        app(CdcVaccineXmlParser::class)->parse('<CVXCodes></CVXCodes>');
    }
}
