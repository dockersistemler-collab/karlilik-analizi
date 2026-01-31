# Queue / Worker Notları

Bu proje pazaryeri entegrasyonları için **sync queue** ile çalıştırılmamalıdır.

## Neden?
`QUEUE_CONNECTION=sync` olduğunda queued olması gereken işler (entegrasyon senkronu / bildirimler / vb.) request içinde çalışır ve:
- Timeout
- Rate limit
- 500 hataları
kaçınılmaz olur.

## Önerilen ayar (Prod)
`.env`:
- `QUEUE_CONNECTION=redis`
- `REDIS_QUEUE_CONNECTION=default`
- `REDIS_QUEUE=default`

Worker:
```bash
php artisan queue:work redis --queue=default,integrations --sleep=1 --tries=3 --timeout=120
```

Not: Laravel Horizon kullanılacaksa ayrıca kurulum gerekir.

## Local / Basit kurulum
Redis yoksa `QUEUE_CONNECTION=database` kullanabilirsiniz.

Worker:
```bash
php artisan queue:work database --queue=default,integrations --sleep=1 --tries=3 --timeout=120
```

