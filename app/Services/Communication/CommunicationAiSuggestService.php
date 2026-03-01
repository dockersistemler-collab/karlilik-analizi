<?php

namespace App\Services\Communication;

use App\Models\CommunicationTemplate;
use App\Models\CommunicationThread;

class CommunicationAiSuggestService
{
    /** @var array<string, array<int, string>> */
    private const KEYWORDS = [
        'shipping' => ['kargo', 'teslim', 'ne zaman gelir', 'kargoya', 'tracking', 'takip', 'gonderim'],
        'return' => ['iade', 'degisim', 'iptal', 'cayma', 'geri gonder', 'kargo ucreti'],
        'warranty' => ['garanti', 'servis', 'arizali', 'bozuk', 'defo', 'kirik', 'calismiyor'],
        'product' => ['olcu', 'beden', 'uyumlu mu', 'ozellik', 'renk', 'model', 'orijinal mi', 'fatura', 'kalip', 'kalibi', 'dar mi', 'bol mu'],
    ];

    public function suggest(CommunicationThread $thread): array
    {
        $thread->loadMissing(['marketplace', 'marketplaceStore', 'messages']);

        $lastInbound = $thread->messages
            ->where('direction', 'inbound')
            ->sortByDesc('id')
            ->first();

        $content = mb_strtolower(trim(implode(' ', array_filter([
            (string) ($thread->subject ?? ''),
            (string) ($lastInbound?->body ?? ''),
        ]))), 'UTF-8');
        $contentNormalized = $this->normalize($content);

        $reasons = [];
        $category = $this->detectCategory($thread, $contentNormalized, $reasons);

        $ownerId = (int) ($thread->marketplaceStore?->user_id ?? 0);
        $historyMatch = $this->findBestHistoryMatch($thread, $ownerId, $contentNormalized);
        $templateMatch = $this->findBestTemplateMatch($thread, $ownerId, $category, $contentNormalized);

        $source = 'dynamic';
        $templateId = null;
        $confidence = 60;

        if ($historyMatch && $historyMatch['score'] >= 0.22) {
            $source = 'history';
            $suggested = $this->personalizeText((string) $historyMatch['text'], $thread);
            $confidence = min(96, max(65, (int) round(55 + ($historyMatch['score'] * 100))));
            $reasons[] = 'source:history';
            $reasons[] = 'history_thread:' . (string) $historyMatch['thread_id'];
        } elseif ($templateMatch && $templateMatch['score'] >= 0.15) {
            $source = 'template';
            $template = $templateMatch['template'];
            $suggested = $this->fillTemplateVariables((string) $template->body, $thread);
            $templateId = (int) $template->id;
            $confidence = min(92, max(62, (int) round(52 + ($templateMatch['score'] * 100))));
            $reasons[] = 'source:template';
            $reasons[] = 'template_id:' . (string) $templateId;
        } else {
            $suggested = $this->buildDynamicSuggestion($thread, $category);
            $confidence = 58;
            $reasons[] = 'source:dynamic';
        }

        return [
            'suggested_text' => $suggested,
            'category' => $category,
            'template_id' => $templateId,
            'confidence' => $confidence,
            'reason' => $reasons,
            'source' => $source,
        ];
    }

    private function detectCategory(CommunicationThread $thread, string $contentNormalized, array &$reasons): string
    {
        $reasons = [];
        $matchCounts = [
            'shipping' => 0,
            'return' => 0,
            'warranty' => 0,
            'product' => 0,
            'general' => 0,
        ];

        foreach (self::KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                $keywordNormalized = $this->normalize((string) mb_strtolower($keyword, 'UTF-8'));
                if ($contentNormalized !== '' && str_contains($contentNormalized, $keywordNormalized)) {
                    $matchCounts[$category]++;
                    $reasons[] = 'keyword:' . $keyword;
                }
            }
        }

        $category = 'general';
        $maxHits = 0;
        foreach (['shipping', 'return', 'warranty', 'product'] as $candidate) {
            $hits = (int) ($matchCounts[$candidate] ?? 0);
            if ($hits > $maxHits) {
                $maxHits = $hits;
                $category = $candidate;
            }
        }

        if ($category === 'general' && $thread->channel === 'return') {
            $category = 'return';
            $reasons[] = 'channel:return';
        }

        return $category;
    }

    private function findBestHistoryMatch(CommunicationThread $thread, int $ownerId, string $currentInbound): ?array
    {
        if ($ownerId <= 0 || $currentInbound === '') {
            return null;
        }

        $candidates = CommunicationThread::query()
            ->where('id', '!=', $thread->id)
            ->where('channel', $thread->channel)
            ->whereIn('status', ['answered', 'closed'])
            ->whereHas('marketplaceStore', function ($q) use ($ownerId): void {
                $q->where('user_id', $ownerId);
            })
            ->with(['messages' => function ($q): void {
                $q->orderBy('id');
            }])
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get();

        $best = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $inbound = $candidate->messages->where('direction', 'inbound')->last();
            $outbound = $candidate->messages->where('direction', 'outbound')->last();

            if (!$inbound || !$outbound) {
                continue;
            }

            $candidateInbound = $this->normalize((string) $inbound->body);
            if ($candidateInbound === '') {
                continue;
            }

            $score = $this->similarityScore($currentInbound, $candidateInbound);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'thread_id' => (int) $candidate->id,
                    'score' => $score,
                    'text' => (string) $outbound->body,
                ];
            }
        }

        return $best;
    }

    private function findBestTemplateMatch(CommunicationThread $thread, int $ownerId, string $category, string $content): ?array
    {
        if ($content === '') {
            return null;
        }

        $templates = CommunicationTemplate::query()
            ->where('is_active', true)
            ->whereIn('category', [$category, 'general'])
            ->where(function ($q) use ($ownerId): void {
                if ($ownerId > 0) {
                    $q->where('user_id', $ownerId)->orWhereNull('user_id');
                    return;
                }
                $q->whereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$ownerId])
            ->orderBy('id')
            ->get();

        $bestTemplate = null;
        $bestScore = 0.0;

        foreach ($templates as $template) {
            $haystack = $this->normalize(((string) $template->title) . ' ' . ((string) $template->body));
            $score = $this->similarityScore($content, $haystack);
            if ((string) $template->category === $category) {
                $score += 0.08;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTemplate = $template;
            }
        }

        if (!$bestTemplate) {
            return null;
        }

        return [
            'template' => $bestTemplate,
            'score' => $bestScore,
        ];
    }

    private function similarityScore(string $a, string $b): float
    {
        $aTokens = $this->tokenize($a);
        $bTokens = $this->tokenize($b);

        if (empty($aTokens) || empty($bTokens)) {
            return 0.0;
        }

        $intersect = array_intersect($aTokens, $bTokens);
        $union = array_unique(array_merge($aTokens, $bTokens));

        return count($union) > 0 ? (count($intersect) / count($union)) : 0.0;
    }

    /** @return array<int, string> */
    private function tokenize(string $text): array
    {
        $normalized = $this->normalize($text);
        $parts = preg_split('/[^a-z0-9]+/i', $normalized) ?: [];

        $tokens = array_values(array_filter($parts, static function ($part): bool {
            return strlen((string) $part) >= 2;
        }));

        return array_values(array_unique($tokens));
    }

    private function personalizeText(string $text, CommunicationThread $thread): string
    {
        $customerName = trim((string) ($thread->customer_name ?: 'Merhaba'));

        if (!str_contains($text, $customerName) && str_starts_with($this->normalize($text), 'merhaba')) {
            return preg_replace('/^Merhaba[,]?\s*/i', $customerName . ', ', $text) ?? $text;
        }

        return $text;
    }

    private function buildDynamicSuggestion(CommunicationThread $thread, string $category): string
    {
        $customerName = trim((string) ($thread->customer_name ?: 'Merhaba'));
        $productName = trim((string) ($thread->product_name ?: 'urun'));
        $orderId = trim((string) ($thread->external_order_id ?: ''));
        $storeName = trim((string) ($thread->marketplaceStore?->store_name ?: 'magazamiz'));

        $orderPart = $orderId !== '' ? " Siparis numaraniz: {$orderId}." : '';

        return match ($category) {
            'shipping' => "{$customerName}, iletiniz icin tesekkur ederiz. {$productName} urununuzun kargo surecini kontrol ediyoruz. En kisa surede net teslimat bilgisini sizinle paylasacagiz.{$orderPart}",
            'return' => "{$customerName}, iade/degisim talebinizi aldik. {$productName} icin sureci baslattik. Onay ve takip adimlarini kisa sure icinde size iletecegiz.{$orderPart}",
            'warranty' => "{$customerName}, yasadiginiz sorun icin uzgunuz. {$productName} urunuyle ilgili garanti/servis incelemesini baslatiyoruz. Sonuc ve yonlendirmeyi en kisa surede size iletecegiz.{$orderPart}",
            'product' => "{$customerName}, sorunuz icin tesekkur ederiz. {$productName} urununde kalip/beden bilgisi modele gore degisebiliyor. En dogru secim icin urun olcu tablosunu kontrol etmenizi oneririz. Boy-kilo veya olculerinizi paylasirsaniz size net beden onerebiliriz.",
            default => "{$customerName}, mesajinizi aldik. {$storeName} olarak konuyu inceliyoruz ve en kisa surede net bir yanitla size donus saglayacagiz.{$orderPart}",
        };
    }

    private function fillTemplateVariables(string $body, CommunicationThread $thread): string
    {
        $meta = is_array($thread->meta) ? $thread->meta : [];
        $replacements = [
            '{CUSTOMER_NAME}' => (string) ($thread->customer_name ?: 'Merhaba'),
            '{PRODUCT_NAME}' => (string) ($thread->product_name ?: 'urun'),
            '{ORDER_ID}' => (string) ($thread->external_order_id ?: ''),
            '{STORE_NAME}' => (string) ($thread->marketplaceStore?->store_name ?: ''),
            '{TRACKING_URL}' => (string) (data_get($meta, 'tracking_url', '')),
            '{ETA_DATE}' => (string) (data_get($meta, 'eta_date', '')),
        ];

        return strtr($body, $replacements);
    }

    private function normalize(string $text): string
    {
        $replace = [
            '?' => 'i', '?' => 'i',
            '?' => 'g', '?' => 'g',
            '?' => 's', '?' => 's',
            '?' => 'o', '?' => 'o',
            '?' => 'u', '?' => 'u',
            '?' => 'c', '?' => 'c',
        ];

        $text = strtr($text, $replace);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim(mb_strtolower($text, 'UTF-8'));
    }
}
