<?php

namespace Tests\Unit\Marketplaces;

use App\Domains\Marketplaces\Connectors\Amazon\AmazonAuthHeaderBuilder;
use App\Domains\Marketplaces\Connectors\Hepsiburada\HepsiburadaAuthHeaderBuilder;
use App\Domains\Marketplaces\Connectors\N11\N11AuthHeaderBuilder;
use App\Domains\Marketplaces\Connectors\Trendyol\TrendyolAuthHeaderBuilder;
use RuntimeException;
use Tests\TestCase;

class AuthHeaderBuilderTest extends TestCase
{
    public function test_trendyol_builder_requires_api_credentials_and_sets_basic_auth(): void
    {
        $headers = (new TrendyolAuthHeaderBuilder())->headers([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'seller_id' => '123',
            'store_front_code' => 'sf',
        ]);

        $this->assertSame('Basic ' . base64_encode('key:secret'), $headers['Authorization']);
        $this->assertSame('sf', $headers['storeFrontCode']);
    }

    public function test_hepsiburada_builder_supports_bearer_and_basic_fallback(): void
    {
        $bearer = (new HepsiburadaAuthHeaderBuilder())->headers([
            'access_token' => 'hb-token',
        ]);
        $this->assertSame('Bearer hb-token', $bearer['Authorization']);

        $basic = (new HepsiburadaAuthHeaderBuilder())->headers([
            'merchant_id' => 'merchant',
            'merchant_password' => 'secret',
        ]);
        $this->assertSame('Basic ' . base64_encode('merchant:secret'), $basic['Authorization']);
    }

    public function test_n11_builder_supports_bearer_and_basic_fallback(): void
    {
        $bearer = (new N11AuthHeaderBuilder())->headers([
            'access_token' => 'n11-token',
        ]);
        $this->assertSame('Bearer n11-token', $bearer['Authorization']);

        $basic = (new N11AuthHeaderBuilder())->headers([
            'app_key' => 'app-key',
            'app_secret' => 'app-secret',
        ]);
        $this->assertSame('Basic ' . base64_encode('app-key:app-secret'), $basic['Authorization']);
        $this->assertSame('app-key', $basic['appkey']);
    }

    public function test_amazon_builder_requires_access_token(): void
    {
        $headers = (new AmazonAuthHeaderBuilder())->headers([
            'access_token' => 'amz-token',
        ]);
        $this->assertSame('Bearer amz-token', $headers['Authorization']);
        $this->assertSame('amz-token', $headers['x-amz-access-token']);

        $this->expectException(RuntimeException::class);
        (new AmazonAuthHeaderBuilder())->headers([]);
    }
}

