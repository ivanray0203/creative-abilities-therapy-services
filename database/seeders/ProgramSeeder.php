<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Sample programmes for the public Programs page.
 *
 * Placeholders standing in for the clinic's real group offerings — the copy
 * is written to be plausible rather than final, so it can be replaced from
 * the database without touching code.
 */
class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->programs() as $program) {
            Program::query()->updateOrCreate(['slug' => $program['slug']], $program);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function programs(): array
    {
        return [
            [
                'name' => 'Social Skills Group',
                'slug' => 'social-skills-group',
                'category' => 'Social Skills',
                'summary' => 'A small weekly group where children practise conversation, turn-taking, and friendship skills with peers.',
                'description' => "Our Social Skills Group gives children a structured, playful setting to build the everyday skills that make friendships easier — starting a conversation, reading body language, taking turns, and handling disagreements.\n\nGroups are kept small and are led by an Occupational Therapist alongside a Speech-Language Pathologist, so each child gets attention while still learning from their peers. Sessions blend games, role-play, and guided free play, and every family receives a short weekly summary with ideas to carry the skills home.",
                'age_range' => '6-9 years',
                'schedule' => 'Saturdays, 10:00 AM - 11:30 AM',
                'location' => 'Main Clinic - Group Room',
                'highlights' => [
                    'Led by an OT and an SLP together',
                    'Maximum of 8 children per group',
                    'Weekly take-home summary for parents',
                    'Runs for 8 weeks',
                ],
                'capacity' => 8,
                'price' => 320.00,
                'starts_on' => now()->addWeeks(5)->toDateString(),
                'ends_on' => now()->addWeeks(13)->toDateString(),
                'registration_closes_on' => now()->addWeeks(4)->toDateString(),
                'is_active' => true,
            ],
            [
                'name' => 'Summer Sensory Camp',
                'slug' => 'summer-sensory-camp',
                'category' => 'Camp',
                'summary' => 'A week-long day camp built around sensory play, movement, and regulation strategies.',
                'description' => "Summer Sensory Camp is a full week of movement, messy play, and outdoor exploration designed for children who benefit from a sensory-rich environment.\n\nEach day pairs an active session — obstacle courses, water play, climbing — with a quieter regulation block where children practise calming strategies they can use at home and at school. Staffing is kept at a high ratio so every child is supported, and the week closes with a short showcase for families.",
                'age_range' => '5-10 years',
                'schedule' => 'Monday to Friday, 9:00 AM - 3:00 PM',
                'location' => 'Community Hall and adjoining park',
                'highlights' => [
                    'One staff member for every three children',
                    'Daily sensory and regulation blocks',
                    'Snacks provided; bring your own lunch',
                    'Family showcase on the Friday',
                ],
                'capacity' => 16,
                'price' => 475.00,
                'starts_on' => now()->addMonths(2)->toDateString(),
                'ends_on' => now()->addMonths(2)->addDays(4)->toDateString(),
                'registration_closes_on' => now()->addWeeks(7)->toDateString(),
                'is_active' => true,
            ],
            [
                'name' => 'Early Words Playgroup',
                'slug' => 'early-words-playgroup',
                'category' => 'Early Years',
                'summary' => 'A parent-and-toddler group for building first words through play, led by a Speech-Language Pathologist.',
                'description' => "Early Words Playgroup is for toddlers who are slow to start talking, and for the parents who want practical strategies rather than a waiting list.\n\nEach session opens with free play while the Speech-Language Pathologist models language techniques in the moment, then moves into songs, books, and a short parent huddle to talk through what worked. Parents stay for the whole session — the aim is for the strategies to leave with you.",
                'age_range' => '18 months - 3 years',
                'schedule' => 'Wednesdays, 9:30 AM - 10:30 AM',
                'location' => 'Main Clinic - Early Years Room',
                'highlights' => [
                    'A parent or caregiver attends with the child',
                    'Led by a Speech-Language Pathologist',
                    'Maximum of 6 families',
                    'Runs for 6 weeks',
                ],
                'capacity' => 6,
                'price' => 180.00,
                'starts_on' => now()->addWeeks(3)->toDateString(),
                'ends_on' => now()->addWeeks(9)->toDateString(),
                'registration_closes_on' => now()->addWeeks(2)->toDateString(),
                'is_active' => true,
            ],
            [
                'name' => 'Parent Regulation Workshop',
                'slug' => 'parent-regulation-workshop',
                'category' => 'Parent Workshop',
                'summary' => 'A single evening session for parents on understanding meltdowns and building regulation routines at home.',
                'description' => "This evening workshop is for parents and caregivers who want to understand what sits underneath a meltdown, and what actually helps in the moment.\n\nWe cover the difference between a tantrum and a sensory overload, how to read early warning signs, and how to build a regulation routine that fits a real household rather than an ideal one. The session is practical and discussion-led, and you will leave with a written plan for your own child. No childcare is provided.",
                'age_range' => 'Parents and caregivers',
                'schedule' => 'One evening, 6:30 PM - 8:30 PM',
                'location' => 'Main Clinic - Meeting Room',
                'highlights' => [
                    'Single two-hour session',
                    'Written take-home plan for your child',
                    'Discussion-led, questions welcome',
                    'Adults only - no childcare provided',
                ],
                'capacity' => 24,
                'price' => 45.00,
                'starts_on' => now()->addWeeks(6)->toDateString(),
                'ends_on' => now()->addWeeks(6)->toDateString(),
                'registration_closes_on' => now()->addWeeks(5)->toDateString(),
                'is_active' => true,
            ],
        ];
    }
}
