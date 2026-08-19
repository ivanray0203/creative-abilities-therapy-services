<?php

namespace Database\Factories;

use App\Models\InvoiceService;
use Database\Seeders\InvoiceServiceSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds rate lines from the real rate sheet rather than invented ones, so a
 * test billing an "OT - Home Visit" bills it at the rate the clinic charges.
 *
 * Rows are handed out in sheet order. Past the end of the sheet the walk
 * starts over, with the pass number appended to the name and code so the
 * unique constraints still hold.
 *
 * @extends Factory<InvoiceService>
 */
class InvoiceServiceFactory extends Factory
{
    private static int $nextRow = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = self::$nextRow++;
        $pass = intdiv($row, count(InvoiceServiceSeeder::RATE_CARD));
        [$name, $code, $discipline, $rateFscd, $ratePrivate] = InvoiceServiceSeeder::RATE_CARD[$row % count(InvoiceServiceSeeder::RATE_CARD)];

        return [
            'name' => $pass === 0 ? $name : $name.' ('.($pass + 1).')',
            'code' => $pass === 0 ? $code : $code.'-'.($pass + 1),
            'discipline' => $discipline,
            'rate_fscd' => $rateFscd,
            'rate_private' => $ratePrivate,
            'is_active' => true,
            'sort_order' => ($row % count(InvoiceServiceSeeder::RATE_CARD)) + 1,
        ];
    }

    /**
     * A specific line from the rate sheet, by its code.
     */
    public function code(string $code): static
    {
        $row = collect(InvoiceServiceSeeder::RATE_CARD)->firstOrFail(fn (array $line): bool => $line[1] === $code);

        return $this->state(fn (): array => [
            'name' => $row[0],
            'code' => $row[1],
            'discipline' => $row[2],
            'rate_fscd' => $row[3],
            'rate_private' => $row[4],
        ]);
    }
}
