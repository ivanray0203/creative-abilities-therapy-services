<?php

namespace App\Models;

use Database\Factories\InvoiceServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A billable line on the invoice rate card, with its FSCD and private rate.
 *
 * A rate is null when the sheet leaves that column blank — travel, mileage
 * and the named private sessions are billed under one funding stream only.
 *
 * @property string|null $rate_fscd
 * @property string|null $rate_private
 */
#[Fillable(['name', 'code', 'discipline', 'rate_fscd', 'rate_private', 'is_active', 'sort_order'])]
class InvoiceService extends Model
{
    /** @use HasFactory<InvoiceServiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rate_fscd' => 'decimal:2',
            'rate_private' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<InvoiceService>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Intake funding sources billed against the FSCD rate column. Anything
     * else — Insurance, private — bills the private/insurance column.
     *
     * @var array<int, string>
     */
    public const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

    /**
     * Which rate column a client's funding source bills against.
     */
    public static function fundingStream(?string $fundingSource): string
    {
        return in_array($fundingSource, self::FSCD_FUNDING_SOURCES, true) ? 'fscd' : 'private';
    }

    /**
     * The rate that applies to a client, by how their care is funded.
     */
    public function rateFor(string $fundingSource): ?string
    {
        return $fundingSource === 'private' ? $this->rate_private : $this->rate_fscd;
    }
}
