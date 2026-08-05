<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Same 5-color teal palette the reference used for its (hardcoded)
     * "Sessions by Service" pie chart, now cycled/reused across however
     * many services actually have sessions this month.
     *
     * @var array<int, string>
     */
    private const CHART_COLORS = ['#0D9488', '#14B8A6', '#2DD4BF', '#5EEAD4', '#99F6E4'];

    /**
     * Ported from cats-frontend's src/pages/admin/Dashboard.tsx, with the
     * hardcoded stats/chart values replaced by real queries. There is no
     * "expenses" entity anywhere in this app (the reference's expense bars
     * were pure mock data with nothing to back them), so that series is
     * dropped rather than inventing numbers for it.
     */
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => $this->stats(),
            'revenueByMonth' => $this->revenueByMonth(),
            'sessionsByService' => $this->sessionsByService(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $pendingIntakes = Intake::query()->where('status', 'pending')->count();
        $pendingIntakesTrend = $this->weekOverWeekTrend(Intake::query());

        $activeClients = Client::query()->where('status', 'active')->count();
        $activeClientsTrend = $this->monthOverMonthTrend(Client::query()->where('status', 'active'));

        $unpaidStatuses = ['sent', 'unpaid', 'draft', 'overdue'];
        $unpaidInvoices = Invoice::query()->whereIn('status', $unpaidStatuses);
        $unpaidTotal = (clone $unpaidInvoices)->sum('amount_due');
        $unpaidCount = (clone $unpaidInvoices)->count();

        $todaySessions = ScheduleSession::query()->whereDate('scheduled_start', Carbon::today());
        $todayCompleted = (clone $todaySessions)->where('status', 'completed')->count();
        $todayTotal = (clone $todaySessions)->count();

        return [
            'pending_intakes' => $pendingIntakes,
            'pending_intakes_trend' => $pendingIntakesTrend,
            'active_clients' => $activeClients,
            'active_clients_trend' => $activeClientsTrend,
            'unpaid_invoices_total' => (float) $unpaidTotal,
            'unpaid_invoices_count' => $unpaidCount,
            'today_sessions_total' => $todayTotal,
            'today_sessions_completed' => $todayCompleted,
            'today_sessions_upcoming' => $todayTotal - $todayCompleted,
        ];
    }

    /**
     * New-record count this week vs. the prior week, as a signed delta and
     * percentage — used for the "Pending Intakes" trend line.
     *
     * @return array{delta: int, percent: int}
     */
    private function weekOverWeekTrend(Builder $query): array
    {
        $thisWeek = (clone $query)->where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $lastWeek = (clone $query)->whereBetween('created_at', [
            Carbon::now()->subDays(14), Carbon::now()->subDays(7),
        ])->count();

        $delta = $thisWeek - $lastWeek;
        $percent = $lastWeek > 0 ? (int) round(($delta / $lastWeek) * 100) : ($thisWeek > 0 ? 100 : 0);

        return ['delta' => $delta, 'percent' => $percent];
    }

    /**
     * Same idea as weekOverWeekTrend() but month-over-month — used for the
     * "Active Clients" trend line.
     *
     * @return array{delta: int, percent: int}
     */
    private function monthOverMonthTrend(Builder $query): array
    {
        $thisMonth = (clone $query)->where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $lastMonth = (clone $query)->whereBetween('created_at', [
            Carbon::now()->subMonthNoOverflow()->startOfMonth(),
            Carbon::now()->startOfMonth(),
        ])->count();

        $delta = $thisMonth - $lastMonth;
        $percent = $lastMonth > 0 ? (int) round(($delta / $lastMonth) * 100) : ($thisMonth > 0 ? 100 : 0);

        return ['delta' => $delta, 'percent' => $percent];
    }

    /**
     * Paid-invoice revenue for each of the last 7 months (including the
     * current one).
     *
     * @return array<int, array{month: string, revenue: float}>
     */
    private function revenueByMonth(): array
    {
        $months = collect(range(6, 0))->map(fn (int $i) => Carbon::now()->subMonthsNoOverflow($i)->startOfMonth());

        return $months->map(function (Carbon $month) {
            $revenue = Invoice::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('total');

            return [
                'month' => $month->format('M'),
                'revenue' => (float) $revenue,
            ];
        })->all();
    }

    /**
     * This month's completed-session distribution by service, top 5 by
     * count with any remainder grouped into "Other".
     *
     * @return array<int, array{label: string, percent: int, sessions: int, color: string}>
     */
    private function sessionsByService(): array
    {
        $sessions = ScheduleSession::query()
            ->whereBetween('scheduled_start', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('service')
            ->get();

        $total = $sessions->count();

        if ($total === 0) {
            return [];
        }

        $counts = $sessions
            ->groupBy(fn (ScheduleSession $session) => $session->service?->name ?? $session->service_name ?? 'Other')
            ->map->count()
            ->sortDesc();

        $top = $counts->take(5);
        $otherCount = $counts->slice(5)->sum();

        if ($otherCount > 0) {
            $top->put('Other', $top->get('Other', 0) + $otherCount);
        }

        $index = 0;

        return $top->map(function (int $count, string $label) use ($total, &$index) {
            $slice = [
                'label' => $label,
                'percent' => (int) round(($count / $total) * 100),
                'sessions' => $count,
                'color' => self::CHART_COLORS[$index % count(self::CHART_COLORS)],
            ];

            $index++;

            return $slice;
        })->values()->all();
    }
}
