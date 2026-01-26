<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMarketplaceService implements MarketplaceInterface
{
    protected MarketplaceCredential $credentials;
    protected string $baseUrl;
    
    public function __construct(MarketplaceCredential $credentials)
    {
        $this->credentials = $credentials;
        $this->baseUrl = $credentials->marketplace->api_url;
    }
    
    /**
     * HTTP isteği gönder
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $response = Http::withHeaders(array_merge($this->getDefaultHeaders(), $headers))
                ->$method($url, $data);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            Log::error("Marketplace API Error", [
                'marketplace' => $this->credentials->marketplace->code,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status(),
            ];
            
        } catch (\Exception $e) {
            Log::error("Marketplace Request Exception", [
                'marketplace' => $this->credentials->marketplace->code,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Varsayılan header'ları döndür
     */
    abstract protected function getDefaultHeaders(): array;
    
    /**
     * Rate limiting kontrolü
     */
    protected function checkRateLimit(): bool
    {
        // Her pazaryeri kendi rate limit mantığını implement edecek
        return true;
    }
}