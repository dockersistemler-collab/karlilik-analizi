<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Http;

class TrendyolService
{
    protected $apiKey;
    protected $apiSecret;
    protected $supplierId;
    protected $baseUrl;

    // Kurucu metod: Servis çağrıldığında şifreleri alır
    public function __construct($apiKey, $apiSecret, $supplierId, $isTest = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->supplierId = $supplierId;
        
        // Trendyol API adresi (Test veya Canlı) ?? $this->baseUrl = $isTest 
            ? 'https://stageapi.trendyol.com/stage/sapigw/suppliers' 
            : 'https://api.trendyol.com/sapigw/suppliers';
    }

    /**
     * BAĞLANTI KONTROLÜ
     * Bu fonksiyon sadece Trendyol kapısını çalar.
     * İçeri giremese bile kapının orada olduğunu doğrular.
     */
    public function checkConnection()
    {
        // GELİŞTİRME MODU:
        // Eğer API anahtarı olarak 'test' yazarsak, bağlantı kurulmuş gibi davran.
        if ($this->apiKey === 'test') {
            return [
                'success' => true,
                'message' => 'TEST MODU: Bağlantı başarılı simüle edildi.'
            ];
        }

        // GERÇEK BAĞLANTI (Bu kısım gerçek anahtar girildiğinde çalışacak)
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json'
                ])
                ->timeout(5) // 5 saniyeden fazla bekleme
                ->get("{$this->baseUrl}/{$this->supplierId}/addresses");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Bağlantı Başarılı!'];
            }
            
            return [
                'success' => false, 
                'message' => 'Hata: ' . $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Sunucu Hatası: ' . $e->getMessage()
            ];
        }
    }
}