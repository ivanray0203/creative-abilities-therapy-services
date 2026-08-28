<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TimesheetEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The hours an aide gave one child on one day, split across the four
 * columns the FSCD time sheet prints.
 *
 * Entries stay loose until the aide generates a timesheet, which claims
 * them by filling `timesheet_id` — the same arrangement billing items and
 * invoices have.
 *
 * @property CarbonImmutable|null $entry_date
 * @property-read Client|null $client
 * @property-read User|null $therapist
 * @property-read Timesheet|null $timesheet
 */
#[Fillable([
    'therapist_id', 'client_id', 'entry_date', 'hourly_respite_hours',
    'community_support_hours', 'bda_direct_hours', 'bda_indirect_hours',
    'notes', 'timesheet_id',
])]
class TimesheetEntry extends Model
{
    /** @use HasFactory<TimesheetEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hourly_respite_hours' => 'decimal:2',
            'community_support_hours' => 'decimal:2',
            'bda_direct_hours' => 'decimal:2',
            'bda_indirect_hours' => 'decimal:2',
        ];
    }

    /**
     * Entries no timesheet has claimed yet — what a generate run picks up.
     *
     * @param  Builder<TimesheetEntry>  $query
     */
    #[Scope]
    protected function unclaimed(Builder $query): void
    {
        $query->whereNull('timesheet_id');
    }

    /** Every column added together, as the form's right-hand total reads. */
    public function totalHours(): float
    {
        return (float) $this->hourly_respite_hours
            + (float) $this->community_support_hours
            + (float) $this->bda_direct_hours
            + (float) $this->bda_indirect_hours;
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

    /** @return BelongsTo<Timesheet, $this> */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }
}
