<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

    use \App\Services\Metaphysics\FlyingStarService;

    /**
     * Test Standard Chart Calculation (Period 9, Facing S1 Bing)
     * S1 (Bing) is Yang Fire. 
     * Base Star for P9 is 9.
     * Facing Star 9 in South (Li) -> 9 is odd (Yang) -> Flies Forward.
     * Sitting Star 4 in North (Kan) -> 4 is even (Yin) -> Flies Backward? No, checking specific rules.
     */
class FlyingStarsTest extends TestCase
{
    public function test_standard_chart_period_9_south_1(): void
    {
        $service = new FlyingStarService();
        
        // P9, Facing 165° (S1 - Bing)
        // Center: Base 9.
        // Facing Palace (South) has Base Star 4.
        // Sitting Palace (North) has Base Star 5.
        
        $chart = $service->calculateChart(9, 165.0);
        
        $this->assertEquals(9, $chart['base'][5]); // Center Base is 9
        $this->assertEquals('S1 (Bing)', $chart['facing_mountain']);
        $this->assertEquals('N1 (Ren)', $chart['sitting_mountain']);
        
        // Flight Direction Test
        
        // Water Star (Facing): Base Star 4.
        // 4 belongs to Xun Palace (SE).
        // Original Facing is S1 (Bing) -> Sub-Index 1.
        // Look at SE1 (Chen) -> Yin (-).
        // So Water Star 4 should fly Backward (-).
        $this->assertEquals('-', $chart['water_flight_direction']);
        
        // Mountain Star (Sitting): Base Star 5.
        // 5 adopts the polarity of the Sitting Palace (North).
        // Sitting is N1 (Ren) -> Sub-Index 1.
        // N1 is Yang (+).
        // So Mountain Star 5 should fly Forward (+).
        $this->assertEquals('+', $chart['mountain_flight_direction']);
    }

    /**
     * Test Replacement Star Requirement
     * 3 degrees from boundary.
     * NE1 (Chou) is 22.5 to 37.5.
     * Boundary at 37.5.
     * 35.0 is 2.5 degrees away -> Should NOT strictly need replacement (needs < 3.0?).
     * Wait, logic says <= 3.0.
     * 37.5 - 35.0 = 2.5 -> True.
     */
    public function test_needs_replacement_chart(): void
    {
        $service = new FlyingStarService();

        // 37.5 is boundary between NE1 and NE2.
        // 35.0 is close.
        $this->assertTrue($service->needsReplacementChart(35.0));
        
        // 30.0 is middle of NE1 (22.5 - 37.5). 
        // Dist to 22.5 is 7.5. Dist to 37.5 is 7.5.
        // Should be false.
        $this->assertFalse($service->needsReplacementChart(30.0));
    }

    /**
     * Test Replacement Chart Logic (Period 8, NE1 Chou)
     * Base 8.
     * Facing NE1 (Chou). Mountain 8. Water 8.
     * Replacement for 8 at sub-index 1 (Chou) is 8?
     * Let's check map: 8 => [8, 8, 9]. So 8 stays 8.
     * 
     * Let's try one that DOES change.
     * Period 8, SW1 (Wei).
     * Sitting NE1 (Chou).
     * 
     * Base 8.
     * Sitting Star (NE) -> 2.
     * Facing Star (SW) -> 5.
     * 
     * Replacement for 2 at NE1?
     * NE1 is Sub-Index 1.
     * Map for 2: [2, 2, 1]. So 2 stays 2.
     * 
     * Replacement for 5 at SW1?
     * SW1 is Sub-Index 1.
     * Star 5 at SW Palace (Kun).
     * Kun Map: [2, 2, 1].
     * So 5 becomes 2.
     */
    public function test_replacement_chart_calculation(): void
    {
        $service = new FlyingStarService();
        
        // Period 8, Facing SW1 (210°)
        // Force Replacement = true
        $chart = $service->calculateChart(8, 210.0, null, true);
        
        // Center Stars
        // We expect Water Star to be replaced from 5 to 2.
        // Standard Water Star would be 5 (Base at Facing SW).
        
        // Verify we got the replacement
        // Note: calculateChart returns the full flown maps, we need to inspect the center (Gua 5 usually, but 'base' is keyed by Gua)
        // Wait, the return 'mountain' and 'water' arrays are the FLOWN stars.
        // The center star is at index 5 of the resulting array? 
        // No, 'base', 'mountain', 'water' arrays are keyed by GUA POSITION (1-9).
        // So $chart['water'][5] is the Water Star in the Center.
        
        $this->assertEquals(2, $chart['water'][5]); 
        
        // And check flight direction for the new star (2).
        // 2 is Kun. SW1 of Kun is Wei (-).
        // So it should fly backward (-).
        $this->assertEquals('-', $chart['water_flight_direction']);
    }

    /**
     * Test Star 5 Flight Logic
     * If Star 5 is the one flying (without replacement), it adopts the polarity of the original palace.
     * E.g. Period 5. Center 5.
     * Facing S1 (Bing).
     * Sitting N1 (Ren).
     * 
     * P5 Center.
     * Facing Star (S) is 1.
     * Sitting Star (N) is 9.
     * 
     * Let's find a case where 5 is the star.
     * Period 2. center 2.
     * NE (8) has Base 8.
     * SW (2) has Base 5.
     * 
     * Facing SW2 (Kun).
     * Facing Star is Base 5.
     * No replacement.
     * Star 5 comes from SW Palace (Kun).
     * SW2 of Kun is Kun (+).
     * So 5 matches Kun (+). Flies forward.
     */
    public function test_star_5_flight_direction(): void
    {
        $service = new FlyingStarService();
        
        // Updated Use Case:
        // Period 2.
        // Needs a house where Water Star (Facing Star) is 5.
        // In Period 2, Star 5 is in NE (Gua 8).
        // So we need a house Facing NE.
        // Let's take NE1 (Chou) as Facing.
        
        // P2, Facing NE1 (30°).
        $chart = $service->calculateChart(2, 30.0);
        
        // Water Star in Center should be 5.
        $this->assertEquals(5, $chart['water'][5]);
        
        // Direction test:
        // Star 5 comes from NE Palace.
        // Sub-mountain is NE1 (Chou).
        // NE1 is Yin (-).
        // So Star 5 should fly Backward (-).
        $this->assertEquals('-', $chart['water_flight_direction']);
    }
}
