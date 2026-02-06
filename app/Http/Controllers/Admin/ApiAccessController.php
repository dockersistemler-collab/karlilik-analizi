<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiAuditLog;
use App\Models\Module;
use App\Services\Entitlements\EntitlementService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ApiAccessController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $apiModule = Module::query()->where('code', 'feature.einvoice_api')->first();
        $hasAccess = $entitlements->hasModule($user, 'feature.einvoice_api');
        $tokens = $hasAccess ? $user->tokens()->latest()->get() : collect();

        $abilities = [
            'einvoices:read' => 'E-Fatura Okuma',
            'einvoices:status' => 'Provider Status Güncelleme',
        ];

        return view('admin.settings.api', compact('tokens', 'abilities', 'hasAccess', 'apiModule'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        if (!app(EntitlementService::class)->hasModule($user, 'feature.einvoice_api')) {
            return redirect()
                ->route('portal.settings.api')
                ->with('info', 'API erişimi için E-Fatura API Erişimi modülünü satın almalısınız.');
        }
$validated = $request->validate(['name' => 'required|string|max:100',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string|in:einvoices:read,einvoices:status',
            'expires_in_days' => 'nullable|integer|in:30,90,180,365',
            'ip_allowlist' => 'nullable|string|max:4000',
        ]);

        $abilities = array_values(array_unique(array_values($validated['abilities'] ?? [])));
        if (empty($abilities)) {
            $abilities = ['einvoices:read'];
        }
$expiresInDays = (int) ($validated['expires_in_days'] ?? 90);
        if (!in_array($expiresInDays, [30, 90, 180, 365], true)) {
            $expiresInDays = 90;
        }
$allowlist = $this->parseIpAllowlist((string) ($validated['ip_allowlist'] ?? ''));

        $token = $user->createToken($validated['name'], $abilities, now()->addDays($expiresInDays));
        $token->accessToken->ip_allowlist_json = empty($allowlist) ? null : $allowlist;
        $token->accessToken->save();

        return back()->with('created_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        if (!app(EntitlementService::class)->hasModule($user, 'feature.einvoice_api')) {
            return redirect()
                ->route('portal.settings.api')
                ->with('info', 'API erişimi için E-Fatura API Erişimi modülünü satın almalısınız.');
        }
$token = $user->tokens()->whereKey($tokenId)->first();
        if (!$token) {
            abort(404);
        }
$token->delete();

        return back()->with('success', 'Token iptal edildi.');
    }

    public function logs(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $validated = $request->validate(['status_code' => 'nullable|integer|min:100|max:599',
            'token_id' => 'nullable|integer|min:1',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = ApiAuditLog::query()
            ->where('api_audit_logs.user_id', $user->id);

        $defaultFrom = Carbon::now()->subDays(30)->startOfDay();
        $from = !empty($validated['from']) ? Carbon::parse((string) $validated['from'])->startOfDay() : $defaultFrom;
        $to = !empty($validated['to']) ? Carbon::parse((string) $validated['to'])->endOfDay() : null;

        $query->where('api_audit_logs.created_at', '>=', $from);
        if ($to) {
            $query->where('api_audit_logs.created_at', '<=', $to);
        }

        if (!empty($validated['status_code'])) {
            $query->where('api_audit_logs.status_code', (int) $validated['status_code']);
        }

        if (!empty($validated['token_id'])) {
            $query->where('api_audit_logs.token_id', (int) $validated['token_id']);
        }
$logs = $query
            ->leftJoin('personal_access_tokens as pat', 'api_audit_logs.token_id', '=', 'pat.id')
            ->orderByDesc('api_audit_logs.id')
            ->select([
                'api_audit_logs.*',
                DB::raw('pat.name as token_name'),
            ])
            ->paginate(50)
            ->withQueryString();

        $tokens = $user->tokens()->latest()->get(['id', 'name']);

        return view('admin.settings.api-logs', [
            'logs' => $logs,
            'tokens' => $tokens,
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function parseIpAllowlist(string $input): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $input) ?: [];
        $lines = array_values(array_filter(array_map(fn ($l) => trim((string) $l), $lines), fn ($l) => $l !== ''));

        if (count($lines) > 20) {
            throw ValidationException::withMessages([
                'ip_allowlist' => ['Maksimum 20 kayıt girebilirsiniz.'],
            ]);
        }
$result = [];
        foreach ($lines as $line) {
            if (str_contains($line, '/')) {
                [$ip, $prefix] = array_pad(explode('/', $line, 2), 2, null);
                $ip = trim((string) $ip);
                $prefix = trim((string) $prefix);

                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    throw ValidationException::withMessages([
                        'ip_allowlist' => ["Geçersiz IP/CIDR: {$line}"],
                    ]);
                }
                if ($prefix === '' || !ctype_digit($prefix)) {
                    throw ValidationException::withMessages([
                        'ip_allowlist' => ["Geçersiz CIDR prefix: {$line}"],
                    ]);
                }
$prefixInt = (int) $prefix;
                $max = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128;
                if ($prefixInt < 0 || $prefixInt > $max) {
                    throw ValidationException::withMessages([
                        'ip_allowlist' => ["Geçersiz CIDR prefix aralığı: {$line}"],
                    ]);
                }
$result[] = "{$ip}/{$prefixInt}";
                continue;
            }

            if (filter_var($line, FILTER_VALIDATE_IP)) {
                $result[] = $line;
                continue;
            }

            throw ValidationException::withMessages([
                'ip_allowlist' => ["Geçersiz IP: {$line}"],
            ]);
        }

        return array_values(array_unique($result));
    }
}


