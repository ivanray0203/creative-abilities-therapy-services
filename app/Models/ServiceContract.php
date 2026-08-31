<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ServiceContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin's authorization to deliver one availed service: a pool of hours over
 * a fixed period.
 *
 * Sessions draw hours from the pool at booking and give them back when they
 * are cancelled or missed. When the pool empties, or the period ends, the
 * availed service stops being schedulable — whichever happens first.
 *
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property-read ClientService|null $clientService
 * @property-read User|null $therapist
 * @property-read User|null $issuedBy
 */
#[Fillable([
    'contract_number', 'client_service_id', 'therapist_id', 'issued_by_id',
    'allotted_hours', 'funding_code', 'period_start', 'period_end', 'status', 'notes',
])]
class ServiceContract extends Model
{
    /** @use HasFactory<ServiceContractFactory> */
    use HasFactory;

    /** Open for booking, as far as the cached status knows. */
    public const STATUS_ACTIVE = 'active';

    /** Every authorized hour has been drawn. */
    public const STATUS_EXHAUSTED = 'exhausted';

    /** The period has passed. Unused hours do not carry over. */
    public const STATUS_EXPIRED = 'expired';

    /** Withdrawn by an admin. The only status the booking gate itself reads. */
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The funding streams a contract's hours can be billed to, as the clinic's
     * hour-tracking sheet spells them. Free text would drift into six ways of
     * writing "BDS" and the report groups on this.
     *
     * @var array<int, string>
     */
    public const FUNDING_CODES = ['SS', 'BDS', 'BD', 'Counselling', 'split', 'BDS/split', 'Private'];

    /**
     * How an intake's or availed service's `funding_source` reads as a code on
     * the sheet. Lower-cased keys, because the two fields have collected
     * variants over time and the sheet itself is inconsistent about case.
     *
     * `Insurance` is deliberately absent: the sheet has never had a code for
     * it, and inventing one would put a value in front of admins that the
     * office does not use.
     *
     * @var array<string, string>
     */
    public const FUNDING_SOURCE_CODES = [
        'ss-fscd' => 'SS',
        'bds-fscd' => 'BDS',
        'bd-fscd' => 'BD',
        'counselling-fscd' => 'Counselling',
        'private' => 'Private',
    ];

    /**
     * The sheet's code for a `funding_source`, or null when it has none —
     * an unrecognised value, or insurance, which the sheet does not code.
     */
    public static function fundingCodeFor(?string $fundingSource): ?string
    {
        if (! is_string($fundingSource) || trim($fundingSource) === '') {
            return null;
        }

        return self::FUNDING_SOURCE_CODES[strtolower(trim($fundingSource))] ?? null;
    }

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'allotted_hours' => 'decimal:2',
        ];
    }

    /**
     * Contracts whose period covers `$date`, whatever is left on them.
     *
     * Deliberately reads the period rather than `status`: a contract lapses at
     * midnight but the sweep command runs later, and until it does, `status`
     * still says `active`. The cached status is for lists and badges.
     *
     * This is the contract a session *belongs to*. Whether it can afford the
     * session is a separate question, asked with the session's own hours
     * excluded when one is being edited — see ServiceContractLedger.
     *
     * @param  Builder<ServiceContract>  $query
     */
    #[Scope]
    protected function coveringOn(Builder $query, CarbonInterface $date): void
    {
        $query->where('status', '!=', self::STATUS_CANCELLED)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date);
    }

    /**
     * Contracts a *new* session dated `$date` could draw from: covering, and
     * with something left in the pool.
     *
     * What the pickers filter on. The balance is compared in SQL so a whole
     * caseload can be narrowed in one query.
     *
     * @param  Builder<ServiceContract>  $query
     */
    #[Scope]
    protected function openOn(Builder $query, CarbonInterface $date): void
    {
        $query->coveringOn($date)
            ->where('allotted_hours', '>', self::drawnHoursSubquery());
    }

    /**
     * Correlated sub-select totalling the hours already drawn from whichever
     * `service_contracts` row the surrounding query is looking at.
     *
     * Shared by the `openOn` scope and `usedHours()` so the SQL definition of
     * "used" and the PHP one cannot drift apart.
     */
    public static function drawnHoursSubquery(): QueryBuilder
    {
        return DB::table('client_service_schedule_session as ledger')
            ->join('schedule_sessions as drawn_session', 'drawn_session.id', '=', 'ledger.schedule_session_id')
            ->whereColumn('ledger.service_contract_id', 'service_contracts.id')
            // A cancelled or missed session hands its hours back, exactly as
            // it hands its slot back to the conflict check.
            ->whereNotIn('drawn_session.status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->selectRaw('COALESCE(SUM(ledger.hours), 0)');
    }

    /** @return BelongsTo<ClientService, $this> */
    public function clientService(): BelongsTo
    {
        return $this->belongsTo(ClientService::class);
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    /**
     * Sessions that have drawn from this contract, reached through the
     * ledger columns on the session/availed-service pivot.
     *
     * @return BelongsToMany<ScheduleSession, $this, SessionServiceAllocation>
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            ScheduleSession::class,
            'client_service_schedule_session',
            'service_contract_id',
            'schedule_session_id',
        )->using(SessionServiceAllocation::class)
            ->withPivot(['hours', 'client_service_id']);
    }

    /**
     * Hours drawn by sessions that still count — everything but cancelled and
     * no-show.
     *
     * `$ignoreSessionId` excludes a session's own existing draw, so editing a
     * two-hour booking does not fail the balance check against itself.
     */
    public function usedHours(?int $ignoreSessionId = null): float
    {
        return round((float) $this->sessions()
            ->whereNotIn('schedule_sessions.status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->when(
                $ignoreSessionId !== null,
                fn (Builder $query) => $query->where('schedule_sessions.id', '!=', $ignoreSessionId),
            )
            ->sum('client_service_schedule_session.hours'), 2);
    }

    public function remainingHours(?int $ignoreSessionId = null): float
    {
        return round((float) $this->allotted_hours - $this->usedHours($ignoreSessionId), 2);
    }

    public function coversDate(CarbonInterface $date): bool
    {
        return $this->period_start !== null
            && $this->period_end !== null
            && $date->betweenIncluded($this->period_start->startOfDay(), $this->period_end->endOfDay());
    }

    /**
     * The status the sweep command would give this contract right now.
     * Cancelled is an admin's decision and is never recomputed.
     */
    public function derivedStatus(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        if ($this->period_end !== null && $this->period_end->endOfDay()->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return $this->remainingHours() <= 0 ? self::STATUS_EXHAUSTED : self::STATUS_ACTIVE;
    }

    public function periodLabel(): string
    {
        return sprintf(
            '%s to %s',
            $this->period_start?->format('j M Y') ?? '?',
            $this->period_end?->format('j M Y') ?? '?',
        );
    }
}
