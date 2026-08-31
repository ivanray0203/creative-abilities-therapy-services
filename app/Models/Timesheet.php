<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TimesheetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An aide's FSCD time sheet for one child over one period.
 *
 * The aide's counterpart to an Invoice: generated from loose entries, signed
 * by both sides, and filed on Drive. It records hours only — there are no
 * rates or amounts anywhere on the form.
 *
 * @property array<int, array<string, mixed>>|null $rows Frozen snapshot of the claimed entries, shaped like {date, hourly_respite, community_support, bda_direct, bda_indirect}.
 * @property array<int, array<string, mixed>>|null $timeline
 * @property CarbonImmutable|null $period_start
 * @property CarbonImmutable|null $period_end
 * @property CarbonImmutable|null $aide_signed_at
 * @property CarbonImmutable|null $parent_signed_at
 * @property-read Client|null $client
 * @property-read User|null $therapist
 */
#[Fillable([
    'timesheet_number', 'therapist_id', 'client_id', 'issued_by_id',
    'period_start', 'period_end', 'rows',
    'total_hourly_respite', 'total_community_support', 'total_bda_direct',
    'total_bda_indirect', 'total_hours',
    'aide_signature', 'parent_signature', 'aide_signed_at', 'parent_signed_at',
    'status', 'not_signed_timesheet', 'signed_timesheet', 'timeline',
])]
class Timesheet extends Model
{
    /** @use HasFactory<TimesheetFactory> */
    use HasFactory;

    /** Generated and signed by the aide, waiting on the parent. */
    public const STATUS_AWAITING_CLIENT = 'awaiting_client';

    /** Signed by both sides — the copy the admin reads. */
    public const STATUS_SIGNED = 'signed';

    /**
     * The four hour columns of the printed form, keyed by the entry column
     * they total. Kept here so the generator, the PDF and the on-screen
     * table all print the same headings in the same order.
     *
     * @var array<string, string>
     */
    public const COLUMNS = [
        'hourly_respite' => 'Hourly Respite',
        'community_support' => 'Community Support Aide',
        'bda_direct' => 'Direct Hours',
        'bda_indirect' => 'Indirect Hours',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rows' => 'array',
            'timeline' => 'array',
            'total_hourly_respite' => 'decimal:2',
            'total_community_support' => 'decimal:2',
            'total_bda_direct' => 'decimal:2',
            'total_bda_indirect' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'aide_signed_at' => 'datetime',
            'parent_signed_at' => 'datetime',
        ];
    }

    /**
     * Forms both sides have signed — what the admin is here to read.
     *
     * @param  Builder<Timesheet>  $query
     */
    #[Scope]
    protected function signed(Builder $query): void
    {
        $query->where('status', self::STATUS_SIGNED);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    /** @return HasMany<TimesheetEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }
}
