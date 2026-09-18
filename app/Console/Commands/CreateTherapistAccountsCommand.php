<?php

namespace App\Console\Commands;

use App\Mail\StaffInviteMail;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Creates therapist portal accounts and their team-member records for the
 * clinic's contractor directory, then emails each new therapist their login
 * email and temporary password.
 *
 * The directory is the static `DIRECTORY` list below (from the "Contractor
 * Directory" sheet), so the command needs no input file. An email that
 * already belongs to a user is skipped rather than reset, which makes the
 * command safe to re-run after adding new entries to the list.
 */
#[Signature('therapists:create
    {--dry-run : Show what would be created without writing or emailing}')]
#[Description('Create therapist accounts (user + team member) from the contractor directory and email each their temporary password')]
class CreateTherapistAccountsCommand extends Command
{
    /**
     * Sheet profession → the Career position the portal recognises. The
     * aide mapping matters most: `TeamMember::isAide()` keys off a position
     * ending in "aide" to swap Billing/Invoices for Hours/Timesheets.
     *
     * @var array<string, string>
     */
    public const PROFESSIONS = [
        'behavioural aide' => 'Behavioural & Developmental Aide',
        'behavioural & developmental aide' => 'Behavioural & Developmental Aide',
        'community & respite aide' => 'Community & Respite Aide',
        'behavioural consultant' => 'Behavioural Consultant/Therapist (BC)',
        'behavioural consultant/therapist (bc)' => 'Behavioural Consultant/Therapist (BC)',
        'occupational therapist' => 'Occupational Therapist (OT)',
        'occupational therapist (ot)' => 'Occupational Therapist (OT)',
        'physiotherapist' => 'Physiotherapist (PT)',
        'physiotherapist (pt)' => 'Physiotherapist (PT)',
        'psychologist' => 'Psychologist',
        'speech language pathologist' => 'Speech-Language Pathologist (SLP)',
        'speech-language pathologist' => 'Speech-Language Pathologist (SLP)',
        'speech-language pathologist (slp)' => 'Speech-Language Pathologist (SLP)',
    ];

    /**
     * The contractor directory. The work email is the login when present,
     * otherwise the personal email; the other becomes the secondary email.
     *
     * @var array<int, array{name: string, profession: string, address: string, city_province: string, postal_code: string, phone: string, personal_email: string, work_email: string, start_date: string, end_date: string}>
     */
    public const DIRECTORY = [
        [
            'name' => 'Tsedal Tewolde',
            'profession' => 'Behavioural Aide',
            'address' => '127 Templewood Rd NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 4B1',
            'phone' => '403 402 9953',
            'personal_email' => 'tsedaltewolde@gmail.com',
            'work_email' => '-',
            'start_date' => '2024-06-01',
            'end_date' => '2027-05-31',
        ],
        [
            'name' => 'Sarah Jane Racelis',
            'profession' => 'Behavioural Aide',
            'address' => '79 Macewan Meadow Crescent NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 3H7',
            'phone' => '403 918 4801',
            'personal_email' => 'jhean_a@yahoo.com',
            'work_email' => '-',
            'start_date' => '2024-06-17',
            'end_date' => '2026-06-16',
        ],
        [
            'name' => 'Michelle Marcinowski',
            'profession' => 'Behavioural Aide',
            'address' => '16 Heston St NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2K2C1',
            'phone' => '587 917 5461',
            'personal_email' => 'meeshchelle@outlook.com',
            'work_email' => '-',
            'start_date' => '2024-07-15',
            'end_date' => '2027-07-14',
        ],
        [
            'name' => 'Angela Yu',
            'profession' => 'Behavioural Aide',
            'address' => '252 Sherwood Place NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T4R 0Y3',
            'phone' => '403 617 2289',
            'personal_email' => 'yyu289@shaw.ca',
            'work_email' => '-',
            'start_date' => '2025-09-17',
            'end_date' => '2026-09-17',
        ],
        [
            'name' => 'Mila Brockmoeller',
            'profession' => 'Behavioural Aide',
            'address' => '820 Westmount Drive',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 1B1',
            'phone' => '403 465 4802',
            'personal_email' => 'mblbrooke@gmail.com',
            'work_email' => '-',
            'start_date' => '2025-11-26',
            'end_date' => '2026-11-26',
        ],
        [
            'name' => 'Jessamae Ocab',
            'profession' => 'Behavioural Aide',
            'address' => '120 Brightoncrest Point SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 5A7',
            'phone' => '825 747 3833',
            'personal_email' => 'jessamaeocab86@gmail.com',
            'work_email' => '-',
            'start_date' => '2025-01-01',
            'end_date' => '2027-01-01',
        ],
        [
            'name' => 'Wynona Arcartado',
            'profession' => 'Behavioural Aide',
            'address' => '141 Los Alamos Crescent NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 7E8',
            'phone' => '437 984 0425',
            'personal_email' => 'arcartadowynona@gmail.com',
            'work_email' => '-',
            'start_date' => '2026-02-14',
            'end_date' => '2027-02-14',
        ],
        [
            'name' => 'Tassia Audet',
            'profession' => 'Behavioural Aide',
            'address' => '86 Sienna Park Terrace SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3H 3L4',
            'phone' => '403 771 6744',
            'personal_email' => 'tassiaaudet@gmail.com',
            'work_email' => '',
            'start_date' => '2025-06-06',
            'end_date' => '2027-06-05',
        ],
        [
            'name' => 'Saraf Choudhury',
            'profession' => 'Behavioural Aide',
            'address' => '142 Arbour Lake Rise NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3G 0G9',
            'phone' => '403 369 7071',
            'personal_email' => 'sarafchdry@gmail.com',
            'work_email' => '',
            'start_date' => '2025-06-24',
            'end_date' => '2027-06-23',
        ],
        [
            'name' => 'Hailey Shaw',
            'profession' => 'Behavioural Aide',
            'address' => '216 East Lakeview Place',
            'city_province' => 'Chestermere AB',
            'postal_code' => 'T1X 0A2',
            'phone' => '587 581 1173',
            'personal_email' => 'hcj.shaw@gmail.com',
            'work_email' => '',
            'start_date' => '2025-07-04',
            'end_date' => '2027-07-04',
        ],
        [
            'name' => 'Autumn Day',
            'profession' => 'Behavioural Aide',
            'address' => '156 Copperstone Circle SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Z 0G8',
            'phone' => '403 702 0383',
            'personal_email' => 'dayautumn222@gmail.com',
            'work_email' => '',
            'start_date' => '2025-07-30',
            'end_date' => '2027-07-29',
        ],
        [
            'name' => 'Abby Sawchuk',
            'profession' => 'Behavioural Aide',
            'address' => '3017 27 Street SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3E 2G6',
            'phone' => '403 634 6180',
            'personal_email' => 'abby.sawchuk@gmail.com',
            'work_email' => '',
            'start_date' => '2025-09-04',
            'end_date' => '2026-09-04',
        ],
        [
            'name' => 'Elorie Mae Tagu-e',
            'profession' => 'Behavioural Aide',
            'address' => '103 Margate Close NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2A 3E5',
            'phone' => '403 830 8059',
            'personal_email' => 'elorietague@gmail.com',
            'work_email' => '',
            'start_date' => '2025-10-02',
            'end_date' => '2026-10-02',
        ],
        [
            'name' => 'Cristine Joy Palao',
            'profession' => 'Behavioural Aide',
            'address' => '168 Everridge Drive SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Y 5E5',
            'phone' => '368 886 2404',
            'personal_email' => 'palaocristine@gmail.com',
            'work_email' => '',
            'start_date' => '2025-10-02',
            'end_date' => '2026-10-02',
        ],
        [
            'name' => 'Sumandeep Kaur Thind',
            'profession' => 'Behavioural Aide',
            'address' => '169 Panatella Place NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3K 6C4',
            'phone' => '587 707 0246',
            'personal_email' => 'sumandeepthind@yahoo.ca',
            'work_email' => '',
            'start_date' => '2026-01-02',
            'end_date' => '2027-01-02',
        ],
        [
            'name' => 'Merry Chris Sultan',
            'profession' => 'Behavioural Aide',
            'address' => '362 Sora Way SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3S 0M3',
            'phone' => '403 702 7449',
            'personal_email' => 'chrisreris@gmail.com',
            'work_email' => '',
            'start_date' => '2026-02-02',
            'end_date' => '2027-02-01',
        ],
        [
            'name' => 'Diane Sordilla',
            'profession' => 'Behavioural Aide',
            'address' => '4223 Dover View Dr SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2B 1Z4',
            'phone' => '825 734 5548',
            'personal_email' => 'diane.jsordilla@gmail.com',
            'work_email' => '',
            'start_date' => '2026-03-10',
            'end_date' => '2027-03-09',
        ],
        [
            'name' => 'Annalyn Lerit',
            'profession' => 'Behavioural Aide',
            'address' => '256 Copperleaf Way SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Z 5G2',
            'phone' => '587 216 8113',
            'personal_email' => 'ann_2427@yahoo.com',
            'work_email' => '',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ],
        [
            'name' => 'Althea Villanueva',
            'profession' => 'Behavioural Aide',
            'address' => '11 Applemead Crt SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2A 7V5',
            'phone' => '403 837 7709',
            'personal_email' => 'villaalthea0526@gmail.com',
            'work_email' => '',
            'start_date' => '2026-05-15',
            'end_date' => '2027-05-14',
        ],
        [
            'name' => 'Ramish Raza',
            'profession' => 'Behavioural Aide',
            'address' => '353 Santan Bay NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3K 3N6',
            'phone' => '780 695 3506',
            'personal_email' => 'ramish.raza16@gmail.com',
            'work_email' => '',
            'start_date' => '2026-05-19',
            'end_date' => '2027-05-18',
        ],
        [
            'name' => 'Viola Sertolli',
            'profession' => 'Behavioural Aide',
            'address' => '245 Bridlerange Place SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Y 0K9',
            'phone' => '403 669-3274',
            'personal_email' => 'violasertolli@hotmail.com',
            'work_email' => '',
            'start_date' => '2026-06-15',
            'end_date' => '2027-06-14',
        ],
        [
            'name' => 'Tyra Mercure',
            'profession' => 'Behavioural Aide',
            'address' => '235 Centre St.',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 1L1',
            'phone' => '403 324 7550',
            'personal_email' => 'tyramercure21345@gmail.com',
            'work_email' => '',
            'start_date' => '2026-06-18',
            'end_date' => '2027-06-17',
        ],
        [
            'name' => 'Divya Raja',
            'profession' => 'Behavioural Aide',
            'address' => '157 Matinvalley Crescent NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J4L6',
            'phone' => '778 791 6023',
            'personal_email' => 'rajathiivya@gmail.com',
            'work_email' => '',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ],
        [
            'name' => 'Alyanna Del Rosario',
            'profession' => 'Behavioural Aide',
            'address' => '24 New Brighton Link SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Z 4J5',
            'phone' => '403 629 0362',
            'personal_email' => 'alyanna.delros@gmail.com',
            'work_email' => '',
            'start_date' => '2026-08-06',
            'end_date' => '2027-08-05',
        ],
        [
            'name' => 'John Patrick Cebedo',
            'profession' => 'Behavioural Aide',
            'address' => '157 Savanna Street NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J 0X3',
            'phone' => '403 354-4947',
            'personal_email' => 'johnpatrickcebedo041286@gmail.com',
            'work_email' => '',
            'start_date' => '2026-08-07',
            'end_date' => '2027-08-06',
        ],
        [
            'name' => 'Leizel Cebedo',
            'profession' => 'Behavioural Aide',
            'address' => '157 Savanna Street NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J 0X3',
            'phone' => '587 434-4079',
            'personal_email' => 'lgcebedo@gmail.com',
            'work_email' => '',
            'start_date' => '2026-08-07',
            'end_date' => '2027-08-06',
        ],
        [
            'name' => 'Rachel Cox',
            'profession' => 'Behavioural Aide',
            'address' => '5626 Pensacola Cres SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2A 2G6',
            'phone' => '587 707 6320',
            'personal_email' => 'rachellccox@gmail.com',
            'work_email' => '',
            'start_date' => '2026-08-07',
            'end_date' => '2027-08-06',
        ],
        [
            'name' => 'Amable Pardinas',
            'profession' => 'Behavioural Aide',
            'address' => '67 Whiteram Gate Northeast',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T1Y 5J5',
            'phone' => '403 829 3300',
            'personal_email' => 'lgal46@yahoo.com',
            'work_email' => '',
            'start_date' => '2026-09-03',
            'end_date' => '2027-09-02',
        ],
        [
            'name' => 'Bahadir Kanbul',
            'profession' => 'Behavioural Aide',
            'address' => 'Unit 406, 8235 19th Ave SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3H 6G3',
            'phone' => '778 581 8890',
            'personal_email' => 'kanbulba@gmail.com',
            'work_email' => '',
            'start_date' => '2026-09-05',
            'end_date' => '2027-09-04',
        ],
        [
            'name' => 'Elif Kanbul',
            'profession' => 'Behavioural Aide',
            'address' => 'Unit 406, 8235 19th Ave SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3H 6G3',
            'phone' => '236 795 7714',
            'personal_email' => 'elkanbul0542@gmail.com',
            'work_email' => '',
            'start_date' => '2026-09-08',
            'end_date' => '2027-09-07',
        ],
        [
            'name' => 'Frances Garrido',
            'profession' => 'Behavioural Consultant',
            'address' => '3102-10 Country Village Park NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 0W5',
            'phone' => '403 397 2087',
            'personal_email' => 'fad.garrido@gmail.com',
            'work_email' => 'frances.garrido@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-11-22',
            'end_date' => '2026-11-22',
        ],
        [
            'name' => 'Nichole Ramo',
            'profession' => 'Behavioural Consultant',
            'address' => '305-111 Tarawood Lane NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J 0C1',
            'phone' => '587 229 8291',
            'personal_email' => 'nicholeramo@gmail.com',
            'work_email' => 'nichole.ramo@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-08-08',
            'end_date' => '2027-08-07',
        ],
        [
            'name' => 'Angela Cua',
            'profession' => 'Behavioural Consultant',
            'address' => '613 Shawinigan Drive SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Y 2Y6',
            'phone' => '587 892-3520',
            'personal_email' => 'angelaicua@yahoo.com',
            'work_email' => 'angela.cua@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-08-29',
            'end_date' => '2027-08-28',
        ],
        [
            'name' => 'Gayatri Somani',
            'profession' => 'Behavioural Consultant',
            'address' => '502 4515 Varsity Dr NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3A 0Z8',
            'phone' => '403 615 3977',
            'personal_email' => 'gayatrisomani8@gmail.com',
            'work_email' => 'gayatri.somani@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-05-01',
            'end_date' => '2027-04-30',
        ],
        [
            'name' => 'Gordeen James',
            'profession' => 'Behavioural Consultant',
            'address' => '193 Savanna Dr NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J 2L1',
            'phone' => '587 438 9643',
            'personal_email' => 'gordeenjames@gmail.com',
            'work_email' => 'gordeen.james@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-03-01',
            'end_date' => '2027-02-28',
        ],
        [
            'name' => 'Deon Kruger',
            'profession' => 'Occupational Therapist',
            'address' => '454 Auburn Crest Way SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 1P9',
            'phone' => '403 393 0175',
            'personal_email' => '',
            'work_email' => 'deon.kruger@creativeabilitiestherapyservices.ca',
            'start_date' => '2024-07-15',
            'end_date' => '2027-07-14',
        ],
        [
            'name' => 'Ania Ivanovic',
            'profession' => 'Occupational Therapist',
            'address' => '175 Carr Crescent',
            'city_province' => 'Okotoks, Alberta',
            'postal_code' => 'T1S 1E3',
            'phone' => '587 778 1447',
            'personal_email' => 'ivanovic.ania@gmail.com',
            'work_email' => 'ania.ivanovic@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-10-02',
            'end_date' => '2026-10-02',
        ],
        [
            'name' => 'Prabhnoor Grewal',
            'profession' => 'Occupational Therapist',
            'address' => '88 Royal Birch Way NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3G 5X8',
            'phone' => '587 432 6400',
            'personal_email' => 'prabhsgrewal@gmail.com',
            'work_email' => 'prabh.grewal@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-11-09',
            'end_date' => '2026-11-09',
        ],
        [
            'name' => 'Kristine Arreola',
            'profession' => 'Occupational Therapist',
            'address' => '341 Saddlemont Blvd NE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3J 0M8',
            'phone' => '587 968 1208',
            'personal_email' => 'kristine.jeanelli@gmail.com',
            'work_email' => 'kristine.arreola@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-06-29',
            'end_date' => '2027-06-28',
        ],
        [
            'name' => 'Ezralie Ibarra',
            'profession' => 'Occupational Therapist',
            'address' => '180 Nolanlake View NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3R 0W3',
            'phone' => '403 828 3183',
            'personal_email' => 'ibarraezralie@gmail.com',
            'work_email' => 'ezralie.ibarra@creativeabilitiestherapyservices.ca',
            'start_date' => '2025-08-06',
            'end_date' => '2027-08-05',
        ],
        [
            'name' => 'Ariana Dickie',
            'profession' => 'Occupational Therapist',
            'address' => '108 Cranbrook Way SE',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3M 2C3',
            'phone' => '403 815 5169',
            'personal_email' => 'arianadickie@gmail.com',
            'work_email' => 'ariana.dickie@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-01-02',
            'end_date' => '2027-01-02',
        ],
        [
            'name' => 'Katherine Samiano',
            'profession' => 'Occupational Therapist',
            'address' => '287 Waterstone Cres SE',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B2G1',
            'phone' => '780 707 9666',
            'personal_email' => 'kssamiano.ot@gmail.com',
            'work_email' => 'katherine.samiano@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
        ],
        [
            'name' => 'Hiu Hung Lo',
            'profession' => 'Occupational Therapist',
            'address' => '402 35 Richard Court SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3E 7N9',
            'phone' => '403 401 5846',
            'personal_email' => 'connielo426@gmail.com',
            'work_email' => 'connie.lo@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
        ],
        [
            'name' => 'Mikayla MacDonald',
            'profession' => 'Physiotherapist',
            'address' => '105 Drake Landing Bend',
            'city_province' => 'Okotoks, Alberta',
            'postal_code' => 'T1S 5T8',
            'phone' => '403 852 9254',
            'personal_email' => 'macdonaldmpr@gmail.com',
            'work_email' => 'mikayla.macdonald@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-03-26',
            'end_date' => '2027-03-25',
        ],
        [
            'name' => 'Sharan Gautam',
            'profession' => 'Physiotherapist',
            'address' => '217 69 Springborough Crt SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3H 5V5',
            'phone' => '403 805 1474',
            'personal_email' => 'gsaisharan@yahoo.com',
            'work_email' => 'sharan.gautam@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-02-21',
            'end_date' => '2027-02-20',
        ],
        [
            'name' => 'John Marquina',
            'profession' => 'Psychologist',
            'address' => '2099 New Brighton Park SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 1B3',
            'phone' => '',
            'personal_email' => 'johncedric.marquina11@gmail.com',
            'work_email' => 'john.marquina@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-03-31',
            'end_date' => '2027-03-30',
        ],
        [
            'name' => 'Atoosa Daniali',
            'profession' => 'Psychologist',
            'address' => '501 2419 Erlton Rd SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2S 3B8',
            'phone' => '403 400 6407',
            'personal_email' => 'atoosa.daniali2012@gmail.com',
            'work_email' => 'atoosa.daniali@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ],
        [
            'name' => 'Prajakta Dhamankar',
            'profession' => 'Psychologist',
            'address' => '142 Somerglen Road SW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T2Y 3V4',
            'phone' => '587 834 1907',
            'personal_email' => 'tal.prajakta@gmail.com',
            'work_email' => '',
            'start_date' => '2026-09-04',
            'end_date' => '2027-09-03',
        ],
        [
            'name' => 'Zoha Azam',
            'profession' => 'Speech Language Pathologist',
            'address' => '252 Edgeland Rd NW',
            'city_province' => 'Calgary Alberta',
            'postal_code' => 'T3A 2Z1',
            'phone' => '825 760 1456',
            'personal_email' => 'zoha.azam24@gmail.com',
            'work_email' => 'zoha.azam@creativeabilitiestherapyservices.ca',
            'start_date' => '2026-01-26',
            'end_date' => '2027-01-26',
        ],
    ];

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->directory() as $index => $row) {
            $label = $row['name'] !== '' ? $row['name'] : "row {$index}";

            $email = $this->loginEmailFor($row);

            if ($email === null) {
                $this->warn("Skipped {$label}: no email address.");
                $failed++;

                continue;
            }

            if (User::query()->where('email', $email)->exists()) {
                $this->line("Skipped {$label}: {$email} already has an account.");
                $skipped++;

                continue;
            }

            $position = $this->positionFor($row['profession']);

            if ($position === null) {
                $this->warn("Skipped {$label}: unknown profession \"{$row['profession']}\".");
                $failed++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("Would create {$label} <{$email}> as {$position}.");
                $created++;

                continue;
            }

            $this->createTherapist($row, $email, $position);
            $this->info("Created {$label} <{$email}> as {$position}.");
            $created++;
        }

        $verb = $this->option('dry-run') ? 'Would create' : 'Created';
        $this->newLine();
        $this->info("{$verb} {$created}, skipped {$skipped} existing, {$failed} could not be processed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{name: string, profession: string, address: string, city_province: string, postal_code: string, phone: string, personal_email: string, work_email: string, start_date: string, end_date: string}  $row
     */
    private function createTherapist(array $row, string $email, string $position): void
    {
        [$firstName, $lastName] = $this->splitName($row['name']);
        [$city, $province] = $this->splitCityProvince($row['city_province']);
        $password = Str::random(10);
        $secondaryEmail = $this->secondaryEmailFor($row, $email);

        DB::transaction(function () use ($row, $email, $position, $firstName, $lastName, $city, $province, $password, $secondaryEmail): void {
            $user = User::query()->create([
                'email' => $email,
                'password' => $password,
                'role' => 'therapist',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $this->cleanPhone($row['phone']),
                'is_active' => true,
            ]);

            TeamMember::query()->create([
                'user_id' => $user->id,
                'position' => $position,
                'title' => $position,
                'department' => 'clinical_services',
                'employment_status' => 'active',
                'hire_date' => $this->parseDate($row['start_date']),
                'phone' => $this->cleanPhone($row['phone']),
                'secondary_email' => $secondaryEmail,
                'street_address' => $row['address'] !== '' ? $row['address'] : null,
                'city' => $city,
                'province' => $province,
                'zip_code' => $row['postal_code'] !== '' ? $row['postal_code'] : null,
                'can_manage_clients' => true,
                'additional_notes' => $this->contractNote($row),
            ]);

            AuditLogger::log('Created team member', 'Users', "Created therapist account {$user->email} via therapists:create");
        });

        Mail::to($email)->send(new StaffInviteMail($firstName, $email, $password));
    }

    /**
     * The rows to process; a seam so tests can run against their own list.
     *
     * @return array<int, array{name: string, profession: string, address: string, city_province: string, postal_code: string, phone: string, personal_email: string, work_email: string, start_date: string, end_date: string}>
     */
    protected function directory(): array
    {
        return self::DIRECTORY;
    }

    /**
     * The work email is the login when the sheet has one; personal otherwise.
     *
     * @param  array{personal_email: string, work_email: string}  $row
     */
    private function loginEmailFor(array $row): ?string
    {
        foreach ([$row['work_email'], $row['personal_email']] as $candidate) {
            $email = $this->cleanEmail($candidate);

            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    /**
     * @param  array{personal_email: string, work_email: string}  $row
     */
    private function secondaryEmailFor(array $row, string $loginEmail): ?string
    {
        foreach ([$row['personal_email'], $row['work_email']] as $candidate) {
            $email = $this->cleanEmail($candidate);

            if ($email !== null && $email !== $loginEmail) {
                return $email;
            }
        }

        return null;
    }

    private function cleanEmail(string $value): ?string
    {
        $email = Str::lower(trim($value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    private function cleanPhone(string $value): ?string
    {
        $phone = trim($value);

        return $phone !== '' && $phone !== '-' ? $phone : null;
    }

    private function positionFor(string $profession): ?string
    {
        $key = Str::lower(trim($profession));

        return self::PROFESSIONS[$key] ?? null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) <= 1) {
            return [$parts[0] ?? '', ''];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), $lastName];
    }

    /**
     * The sheet writes "Calgary, Alberta", "Calgary Alberta" and
     * "Chestermere AB" interchangeably.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function splitCityProvince(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [null, null];
        }

        if (str_contains($value, ',')) {
            [$city, $province] = array_map('trim', explode(',', $value, 2));
        } else {
            $parts = preg_split('/\s+/', $value) ?: [];
            $province = count($parts) > 1 ? array_pop($parts) : null;
            $city = implode(' ', $parts);
        }

        if ($province !== null && Str::upper($province) === 'AB') {
            $province = 'Alberta';
        }

        return [$city !== '' ? $city : null, $province !== '' ? $province : null];
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '' || $value === '-') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The team-member row has no contract end column, so the term is kept
     * in the notes where an admin will see it on the profile.
     *
     * @param  array{start_date: string, end_date: string}  $row
     */
    private function contractNote(array $row): ?string
    {
        $start = $this->parseDate($row['start_date']);
        $end = $this->parseDate($row['end_date']);

        if ($start === null && $end === null) {
            return null;
        }

        return sprintf(
            'Contract: %s to %s',
            $start?->toDateString() ?? 'n/a',
            $end?->toDateString() ?? 'n/a',
        );
    }
}
