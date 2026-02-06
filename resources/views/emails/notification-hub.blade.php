<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h1 style="font-size: 18px; margin: 0 0 12px;">{{ $notification->title }}</h1>
        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">{!! nl2br(e($notification->body)) !!}</p>
        @if($notification->action_url)
            <p style="margin: 0 0 16px;">
                <a href="{{ url($notification->action_url) }}" style="display: inline-block; background: #ff4439; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 600;">Detaya Git</a>
            </p>
        @endif
        <p style="font-size: 12px; color: #64748b; margin: 0;">Bu otomatik bir bildirimdir.</p>
    </div>
</body>
</html>