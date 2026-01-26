<?php

namespace App\Services\Marketplace;

interface MarketplaceInterface
{
    // Ürün işlemleri
    public function createProduct(array $productData): array;
    public function updateProduct(string $marketplaceProductId, array $productData): array;
    public function deleteProduct(string $marketplaceProductId): bool;
    public function getProduct(string $marketplaceProductId): array;
    
    // Stok işlemleri
    public function updateStock(string $marketplaceProductId, int $quantity): bool;
    public function getStock(string $marketplaceProductId): int;
    
    // Fiyat işlemleri
    public function updatePrice(string $marketplaceProductId, float $price): bool;
    
    // Sipariş işlemleri
    public function getOrders(array $filters = []): array;
    public function getOrder(string $marketplaceOrderId): array;
    public function updateOrderStatus(string $marketplaceOrderId, string $status): bool;
    public function shipOrder(string $marketplaceOrderId, array $shippingData): bool;
    
    // Bağlantı testi
    public function testConnection(): bool;
}