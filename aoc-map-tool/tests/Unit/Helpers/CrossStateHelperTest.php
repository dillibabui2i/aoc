<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\CrossStateHelper;
use Tests\UnitTestCase;

class CrossStateHelperTest extends UnitTestCase
{
    private CrossStateHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new CrossStateHelper();
    }

    /**
     * @dataProvider crossStateDataProvider
     */
    public function testIsCrossState(string $state1, string $state2, bool $expected): void
    {
        $this->assertEquals($expected, $this->helper->isCrossState($state1, $state2));
    }

    public function crossStateDataProvider(): array
    {
        return [
            'same state' => ['NY', 'NY', false],
            'different states' => ['NY', 'NJ', true],
            'empty strings' => ['', '', false],
            'one empty string' => ['NY', '', true],
        ];
    }

    public function testGetIpeaCrossStateNotes(): void
    {
        $ipeaState = 'New York';
        $clientState = 'New Jersey';

        $expectedNote = "Cross-state note added: This nurse is scheduled for an IPEA
        in New York for a client in New Jersey. The IPEA email will include branch/location
        details for New York and all other resources will reflect New Jersey.";

        $result = $this->helper->getIpeaCrossStateNotes($ipeaState, $clientState);

        // Normalize whitespace for comparison to avoid brittle tests due to indentation/newlines
        $this->assertEquals(
            preg_replace('/\s+/', ' ', trim($expectedNote)),
            preg_replace('/\s+/', ' ', trim($result))
        );
    }
}
