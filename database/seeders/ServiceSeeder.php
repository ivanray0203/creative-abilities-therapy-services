<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceOffering;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the public Services page content, ported from
 * resources/js/lib/content/services-fallback.ts (tempServices) so the
 * database-backed page renders the same 5 services as the reference site.
 *
 * Also seeds the `service_offerings` catalog (billing/scheduling services,
 * e.g. used by ClientService/ScheduleSession and matched against therapist
 * specializations) from the intake form's "Services Needed" list
 * (resources/js/lib/content/intake-taxonomy.ts IntakeServicesNeeded) — a
 * separate model from `Service` above.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Occupational Therapy',
                'code' => 'OTS-101',
                'short_description' => '',
                'description' => 'Our Occupational Therapists (OTs) support your child in building functional skills and greater independence in daily life. Using a personalized approach, sensory strategies, and fun hands-on activities, we partner with families to create an environment where children can learn, grow, and thrive.',
                'duration_minutes' => 60,
                'base_price' => 120,
                'is_active' => true,
                'benefits' => ['Improved daily functioning', 'Enhanced independence', 'Better quality of life'],
                'offerings' => ['In-person sessions', 'Telehealth sessions', 'Home visits'],
                'approaches' => ['Personalized activity-based therapy', 'Adaptive techniques', 'Goal-oriented exercises'],
                'outcomes' => ['Increased independence', 'Enhanced motor skills', 'Improved self-confidence'],
                'ages' => '4-18 years',
                'signs_to_look_for' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'conditions' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'frequency' => '1-2 sessions/months',
                'location' => 'Home/Community',
                'tags' => ['Sensory Processing', 'Fine Motor Skills', 'Self-Care', 'Daily Living'],
                'main_tag' => 'ACOT Registered',
                'duration' => '60-90 min',
                'description_highlight' => null,
                'photo' => '/images/1920x1080/photo_teching_1920x1080.jpg',
                'area_of_focus' => [
                    [
                        'title' => 'Sensory Processing',
                        'desc' => 'Helping children understand, organize, and respond to sensory information in their environment so they feel regulated and ready to participate.',
                    ],
                    [
                        'title' => 'Fine Motor Skills',
                        'desc' => 'Building hand strength, coordination, and control for activities like writing, cutting, dressing, feeding, and play.',
                    ],
                    [
                        'title' => 'Adaptive Skills',
                        'desc' => 'Helping children develop independence in daily activities such as dressing, play, writing, cutting, sensory regulation, toilet training, and feeding. We support children in learning routines and self-help skills that build confidence and independence in everyday tasks.',
                    ],
                ],
            ],
            [
                'name' => 'Speech-Language Therapy',
                'code' => 'SLTS-202',
                'short_description' => 'Restores mobility and function through movement and exercises.',
                'description' => 'Our Speech Language Pathologists (SLPs) provide individualized therapy to help children build strong communication and oral motor skills. We support children with speech sounds, understanding and using language, social communication, and feeding or swallowing concerns related to oral motor skills.',
                'duration_minutes' => 45,
                'base_price' => 100,
                'is_active' => true,
                'benefits' => ['Reduced pain', 'Improved mobility', 'Faster recovery'],
                'offerings' => ['One-on-one sessions', 'Group therapy', 'Telehealth consultations'],
                'approaches' => ['Manual therapy', 'Strength training', 'Stretching and mobility exercises'],
                'outcomes' => ['Pain reduction', 'Enhanced mobility', 'Improved posture and balance'],
                'ages' => '4-18 years',
                'signs_to_look_for' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'conditions' => 'Speech delays, language disorders, articulation challenges, stuttering, autism spectrum, hearing impairments',
                'frequency' => '1-2 sessions/months',
                'location' => 'Home/Community/Online',
                'tags' => ['Articulation', 'Language Development', 'Social Communication', 'Swallowing'],
                'main_tag' => 'ACSLPA Registered',
                'duration' => '60-90min',
                'description_highlight' => null,
                'photo' => '/images/1920x1080/photo_baby_love_1920x1080.jpg.jpg',
                'area_of_focus' => [
                    [
                        'title' => 'Speech and Oral Motor Skills',
                        'desc' => 'Improving articulation, speech clarity, and oral motor strength needed for speech and feeding.',
                    ],
                    [
                        'title' => 'Language Development',
                        'desc' => 'Supporting expressive and receptive language, including vocabulary, grammar, and understanding instructions.',
                    ],
                    [
                        'title' => 'Social Communication',
                        'desc' => 'Teaching children how to interact with others, engage in conversations, take turns, and understand social cues.',
                    ],
                ],
            ],
            [
                'name' => 'Physiotherapy',
                'code' => 'P-303',
                'short_description' => 'Improves communication, speech, and swallowing functions.',
                'description' => 'Our Physiotherapists (PTs) work with children to improve movement, balance, and coordination. With personalized exercises and interventions, we help children gain physical strength and functional mobility. Our physiotherapy services also support gross motor development, strength, balance, coordination, and mobility, including walking, running, and wheelchair use.',
                'duration_minutes' => 30,
                'base_price' => 90,
                'is_active' => true,
                'benefits' => ['Better communication skills', 'Enhanced social interaction', 'Improved swallowing function'],
                'offerings' => ['In-person sessions', 'Online therapy', 'Parent coaching'],
                'approaches' => ['Articulation exercises', 'Language therapy', 'Swallowing therapy'],
                'outcomes' => ['Clearer speech', 'Improved language comprehension', 'Better feeding and swallowing'],
                'ages' => '4-18 years',
                'signs_to_look_for' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'conditions' => 'Cerebral palsy, muscular dystrophy, developmental coordination disorder, sports injuries, postural issues',
                'frequency' => '1-2 sessions/month',
                'location' => 'Home/Community',
                'tags' => ['Gross Motor Skills', 'Balance & Coordination', 'Strength Building', 'Mobility'],
                'main_tag' => 'CPTA Registered',
                'duration' => '60-90 min',
                'description_highlight' => null,
                'photo' => '/images/1920x1080/photo_nyl_1920x1080.jpg.jpg',
                'area_of_focus' => [
                    [
                        'title' => 'Motor Coordination',
                        'desc' => 'Supporting children to develop gross motor skills, such as walking, running, and jumping.',
                    ],
                    [
                        'title' => 'Strength and Endurance',
                        'desc' => 'Creating individualized plans to improve physical strength and stamina.',
                    ],
                    [
                        'title' => 'Posture and Balance',
                        'desc' => 'Working on strategies to improve posture and overall body mechanics.',
                    ],
                ],
            ],
            [
                'name' => 'Behavioural Consulting, Counselling, and Psychological Services',
                'code' => 'BCCPS-303',
                'short_description' => 'Improves communication, speech, and swallowing functions.',
                'description' => 'Our team of Behavioural Consultants, Psychologists, and Counsellors provides compassionate and individualized support for children and families experiencing emotional and behavioural challenges. We offer assessments and strategies for emotional regulation, behavior management, learning, and mental health needs. We also support children and families experiencing anxiety, stress, grief, adjustment concerns, or challenges with emotional well-being.',
                'duration_minutes' => 30,
                'base_price' => 90,
                'is_active' => true,
                'benefits' => ['Better communication skills', 'Enhanced social interaction', 'Improved swallowing function'],
                'offerings' => ['In-person sessions', 'Online therapy', 'Parent coaching'],
                'approaches' => ['Articulation exercises', 'Language therapy', 'Swallowing therapy'],
                'outcomes' => ['Clearer speech', 'Improved language comprehension', 'Better feeding and swallowing'],
                'ages' => '4-18 years (and families)',
                'signs_to_look_for' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'conditions' => 'ADHD, anxiety, depression, autism spectrum, ODD, trauma, behavioural challenges',
                'frequency' => '1-2 sessions/month',
                'location' => 'Home/Community/Online',
                'tags' => ['Emotional Regulation', 'ADHD Support', 'Anxiety & Depression', 'Behaviour Management'],
                'main_tag' => 'CAP Registered',
                'duration' => '60-90 min',
                'description_highlight' => null,
                'photo' => '/images/1920x1080/photo_bnw_1920x1080.jpg',
                'area_of_focus' => [
                    [
                        'title' => 'Behavioural Management',
                        'desc' => 'We support children in developing positive behaviour strategies, improving self-control, and strengthening social interaction skills.',
                    ],
                    [
                        'title' => 'Emotional Regulation',
                        'desc' => 'We help children identify, understand, and manage their emotions in healthy and constructive ways.',
                    ],
                    [
                        'title' => 'Individual and Family Counselling',
                        'desc' => 'We offer one-on-one counselling to support children’s emotional growth and provide family counselling to strengthen communication, connection, and overall family well-being.',
                    ],
                ],
            ],
            [
                'name' => 'Behavioural Developmental Aide Services',
                'code' => 'BDAS-303',
                'short_description' => 'Improves communication, speech, and swallowing functions.',
                'description' => 'Our Behavioural Developmental Aides provide a nurturing and inclusive environment that supports the development of essential social, play, and daily living skills. They work closely with children in supportive settings that allow them to explore, interact, and learn through guided play and structured activities.',
                'duration_minutes' => 30,
                'base_price' => 90,
                'is_active' => true,
                'benefits' => ['Better communication skills', 'Enhanced social interaction', 'Improved swallowing function'],
                'offerings' => ['In-person sessions', 'Online therapy', 'Parent coaching'],
                'approaches' => ['Articulation exercises', 'Language therapy', 'Swallowing therapy'],
                'outcomes' => ['Clearer speech', 'Improved language comprehension', 'Better feeding and swallowing'],
                'ages' => '4-18 years',
                'signs_to_look_for' => 'Sensory processing disorders, developmental delays, autism spectrum, ADHD, fine motor challenges',
                'conditions' => 'All developmental abilities, autism spectrum, social skill challenges, play skill delays',
                'frequency' => '3-5 sessions/week',
                'location' => 'Home/Community',
                'tags' => ['Play-Based Learning', 'Social Skills', 'Developmental Milestones', 'Inclusive Environment'],
                'main_tag' => 'Nurturing Growth',
                'duration' => '60-180 min',
                'description_highlight' => null,
                'photo' => '/images/1920x1080/photo_baby_1920x1080.jpg',
                'area_of_focus' => [
                    [
                        'title' => 'Social Skills Development',
                        'desc' => 'Helping children interact appropriately with peers and adults.',
                    ],
                    [
                        'title' => 'Play Facilitation',
                        'desc' => 'Encouraging creativity, engagement, and exploration through both structured and unstructured play.',
                    ],
                    [
                        'title' => 'Developmental Milestones',
                        'desc' => 'Supporting children in building skills and achieving developmental goals through individualized interventions.',
                    ],
                    [
                        'title' => 'Goal-Based Support',
                        'desc' => "Working directly with your child under a professional's guidance to practice goals such as communication, social skills, routines, and behavior strategies.",
                    ],
                ],
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['code' => $service['code']], $service);
        }

        $offeringNames = [
            'Occupational Therapy',
            'Speech and Language Therapy',
            'Psychological Support',
            'Behavioural Aide Services',
            'Respite Aide Services',
            'Physiotherapy',
            'Behavioural Consulting',
            'Counselling',
            'Community Aide Services',
        ];

        foreach ($offeringNames as $name) {
            ServiceOffering::query()->firstOrCreate(
                ['name' => $name],
                ['code' => Str::slug($name), 'is_active' => true],
            );
        }
    }
}
