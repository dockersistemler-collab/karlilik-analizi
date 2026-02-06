<?php

namespace App\Http\Controllers\SuperAdmin\Plans;

use App\Http\Controllers\Controller;
use App\Models\MailRuleAssignment;
use App\Models\MailTemplate;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanMailRulesController extends Controller
{
    public function edit(Request $request, Plan $plan): View
    {
        $key = trim((string) $request->get('key', ''));
        $category = trim((string) $request->get('category', ''));

        $templatesQuery = MailTemplate::query()->orderBy('key');
        if ($key !== '') {
            $templatesQuery->where('key', 'like', '%'.$key.'%');
        }
        if ($category !== '') {
            $templatesQuery->where('category', $category);
        }
$templates = $templatesQuery->get();

        $rules = MailRuleAssignment::query()
            ->where('scope_type', 'plan')
            ->where('scope_id', $plan->id)
            ->get()
            ->keyBy('key');

        $allowedMap = [];
        foreach ($templates as $template) {
            $rule = $rules->get($template->key);
            $allowedMap[$template->key] = $rule ? (bool) $rule->allowed : true;
        }

        return view('super-admin.plans.mail_rules.edit', compact('plan', 'templates', 'allowedMap', 'key', 'category'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $allowedKeys = $request->input('allowed', []);
        if (!is_array($allowedKeys)) {
            $allowedKeys = [];
        }
$allowedKeys = array_values(array_filter($allowedKeys, fn ($v) => is_string($v) && $v !== ''));

        $templates = MailTemplate::query()->get(['key']);
        $now = now();
        $rows = [];
        foreach ($templates as $template) {
            $rows[] = [
                'scope_type' => 'plan',
                'scope_id' => $plan->id,
                'key' => $template->key,
                'allowed' => in_array($template->key, $allowedKeys, true),
                'daily_limit' => null,
                'monthly_limit' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            MailRuleAssignment::query()->upsert(
                $rows,
                ['scope_type', 'scope_id', 'key'],
                ['allowed', 'daily_limit', 'monthly_limit', 'updated_at']
            );
        }

        return redirect()
            ->route('super-admin.plans.mail-rules.edit', $plan)
            ->with('success', 'Mail yetkileri guncellendi.');
    }
}
