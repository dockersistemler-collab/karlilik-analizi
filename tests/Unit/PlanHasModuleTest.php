<?php

namespace Tests\Unit;

use App\Models\Plan;
use Tests\TestCase;

class PlanHasModuleTest extends TestCase
{
    public function test_enabled_modules_default_is_empty_array(): void
    {
        $plan = new Plan();

        $this->assertSame([], $plan->enabledModules());
        $this->assertFalse($plan->hasModule('anything'));
    }

    public function test_enabled_modules_empty_modules_means_no_access(): void
    {
        $plan = new Plan([
            'features' => [
                'modules' => [],
            ],
        ]);

        $this->assertSame([], $plan->enabledModules());
        $this->assertFalse($plan->hasModule('feature.api_access'));
    }

    public function test_wildcard_enables_all_modules(): void
    {
        $plan = new Plan([
            'features' => [
                'modules' => ['*'],
            ],
        ]);

        $this->assertSame(['*'], $plan->enabledModules());
        $this->assertTrue($plan->hasModule('feature.api_access'));
        $this->assertTrue($plan->hasModule('integration.trendyol'));
        $this->assertTrue($plan->hasModule('reports.orders'));
    }

    public function test_exact_module_match_is_allowed(): void
    {
        $plan = new Plan([
            'features' => [
                'modules' => ['integration.trendyol', 'feature.einvoice'],
            ],
        ]);

        $this->assertTrue($plan->hasModule('integration.trendyol'));
        $this->assertTrue($plan->hasModule('feature.einvoice'));
        $this->assertFalse($plan->hasModule('integration.hepsiburada'));
    }

    public function test_prefix_matching_is_preserved(): void
    {
        $plan = new Plan([
            'features' => [
                'modules' => ['reports'],
            ],
        ]);

        $this->assertTrue($plan->hasModule('reports.orders'));
        $this->assertTrue($plan->hasModule('reports'));
        $this->assertFalse($plan->hasModule('feature.api_access'));
    }
}

