<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Assigns every client in the clinic's caseload directory to the therapists
 * who work with them, the same way the admin client page does: each one
 * joins the client's care team, and the first becomes the primary therapist
 * when the client has none yet.
 *
 * The caseload is the static `CASELOAD` list below (from the "Caseload
 * Directory" sheet), so the command needs no input file. It only covers the
 * service-provider columns: the "PT" placeholder in the Physiotherapist
 * column, Payroll and Clinical Coordinator are left out. Each client's
 * therapists are listed clinical roles first, aides last, so the primary is
 * a clinician wherever the client has one.
 *
 * Assignments are only ever added, never removed, and an existing primary
 * therapist is never replaced, so the command is safe to re-run.
 */
#[Signature('clients:assign-therapists
    {--dry-run : Show what would be assigned without changing anything}')]
#[Description('Assign each client in the caseload directory to their therapists (care team + primary therapist)')]
class AssignClientTherapistsCommand extends Command
{
    /**
     * Caseload names that are spelled differently on the client record —
     * a typo on one sheet or the other, or a different form of the name.
     *
     * @var array<string, string>
     */
    public const CLIENT_ALIASES = [
        'Olivia Christie' => 'Olivia Chrsitie',
        'Richtan Hyunh' => 'Richtan Huynh',
        'Maria Lisa Nsimba' => 'Marie Elisabeth Nsimba',
    ];

    /**
     * Caseload names for therapists whose account carries another name.
     *
     * @var array<string, string>
     */
    public const THERAPIST_ALIASES = [
        'Connie Lo' => 'Hiu Hung Lo',
    ];

    /**
     * Client name => [therapist name => role(s) on the caseload sheet].
     *
     * @var array<string, array<string, string>>
     */
    public const CASELOAD = [
        'Abel Mwangi' => [
            'Deon Kruger' => 'Occupational Therapist',
        ],
        'Aahil Zamani' => [
            'John Marquina' => 'Psychologist',
        ],
        'Abubakarr Jalloh' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
        'Adab Nota' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Adrian McKenny' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Sarah Jane Racelis' => 'Behavioural Aide',
            'Althea Villanueva' => 'Behavioural Aide',
        ],
        'Adrija Dey' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Adonias Tesfaye' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Ariana Dickie' => 'Occupational Therapist',
        ],
        'Ahmed Mohamed' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Saraf Choudhury' => 'Behavioural Aide',
        ],
        'Alex Chengwa' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'Ezralie Ibarra' => 'Occupational Therapist',
        ],
        'Alexandra Lucia Co' => [
            'John Marquina' => 'Psychologist',
        ],
        'Allen James Sabalbaro' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Mila Brockmoeller' => 'Behavioural Aide',
        ],
        'Alsadiq Yassin' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Alon Jedrek Roces Racelis' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Amber Mann' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Amen Meles' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Hailey Shaw' => 'Behavioural Aide',
        ],
        'Anamta Mohtashim' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Anantvir Singh Saini' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Kamal Srinivasan' => 'Behavioural Aide',
        ],
        'Anela Salas' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
        ],
        'Anson An' => [
            'Kristine Arreola' => 'Occupational Therapist',
        ],
        'Atta Ali Naqvi' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Archer Johansen' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Prabhnoor Grewal' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
            'Amable Pardinas' => 'Behavioural Aide',
        ],
        'Aryan Rijal' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Cristine Joy Palao' => 'Behavioural Aide',
        ],
        'Aurora Harkonen' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Austin Shank' => [
            'Deon Kruger' => 'Occupational Therapist',
        ],
        'Avyukt Sivakumar' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Avitaj Singh Gill' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Ayan Kumar' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Beau Vandenhurk' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Caleb Vargas' => [
            'Ariana Dickie' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Catalina Rivas-Oswald' => [
            'Katherine Samiano' => 'Occupational Therapist',
        ],
        'Chidubem Odinukwe' => [
            'Ariana Dickie' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Christian Tilahun' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Dahlia Manalo' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Daksh Bansal' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Atoosa Daniali' => 'Psychologist',
        ],
        'Dhakshan Kumar' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Connie Lo' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
            'Bahadir Kanbul' => 'Behavioural Aide',
        ],
        'Daniel Ojodun' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Daniyal Bhatti' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Dave Salas' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
        ],
        'David Stacey' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Dev Gill' => [
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Doris Leanda Baker' => [
            'Atoosa Daniali' => 'Psychologist',
        ],
        'Elias Weyessa' => [
            'Kristine Arreola' => 'Occupational Therapist',
        ],
        'Elijah Bibanco' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Ezra Nigusse' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Divya Raja' => 'Behavioural Aide',
        ],
        'Faris Mahmoud Jarrar' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'France Kellyn Balandino' => [
            'Kristine Arreola' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'John Patrick Cebedo' => 'Behavioural Aide',
        ],
        'Gabriela Adam' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Gabriel Owen Corniciuc' => [
            'Katherine Samiano' => 'Occupational Therapist',
        ],
        'Grace Odediran' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Gurdit Singh' => [
            'Deon Kruger' => 'Occupational Therapist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Gurfateh Singh' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Angela Yu' => 'Behavioural Aide',
        ],
        'Gursanjh Singh Dhingra' => [
            'John Marquina' => 'Psychologist',
        ],
        'Gurvar Kang' => [
            'John Marquina' => 'Psychologist',
        ],
        'Gwyneth Raine Rosario' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Althea Villanueva' => 'Behavioural Aide',
        ],
        'Hadi Amin' => [
            'Kristine Arreola' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Michelle Marcinowski' => 'Behavioural Aide',
        ],
        'Halima Arike Zakariyau' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Hasan Nurdin' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Annalyn Lerit' => 'Behavioural Aide',
        ],
        'Hassan Ali' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Ramish Raza' => 'Behavioural Aide',
        ],
        'Hazeem Ahmed' => [
            'Atoosa Daniali' => 'Psychologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Hunter Davis Johansen' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Prabhnoor Grewal' => 'Occupational Therapist',
            'Amable Pardinas' => 'Behavioural Aide',
        ],
        'Ian Andrei Juco' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Ian Olorunfemi' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Deon Kruger' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Ibrahim Ahmad' => [
            'Ariana Dickie' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Viola Sertolli' => 'Behavioural Aide',
        ],
        'Ibukun Sokoya' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Ikeoluwa Abubakre' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Angela Yu' => 'Behavioural Aide',
        ],
        'Iman Hassan' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Mikayla MacDonald' => 'Physiotherapist',
        ],
        'Isabella Moonen' => [
            'Frances Garrido' => 'Behavioural Consultant',
        ],
        'Isabelle Harkonen' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Jan Jeffrey Manzon' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'John Marquina' => 'Psychologist',
        ],
        'Jaisnoor Sandhu' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
        'Jayden Fray' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Jayden Racelis' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'Ezralie Ibarra' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
        ],
        'Jessie Meng' => [
            'Ariana Dickie' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Jhon Corregidor' => [
            'Kristine Arreola' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Wynona Arcartado' => 'Behavioural Aide',
        ],
        'Jonathan Nguyen Phan' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Kristine Arreola' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Elorie Mae Tagu-e' => 'Behavioural Aide',
        ],
        'Jomer Torregoza' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'Ariana Dickie' => 'Occupational Therapist',
            'Atoosa Daniali' => 'Psychologist',
            'Wynona Arcartado' => 'Behavioural Aide',
        ],
        'Joshua Otolorin' => [
            'Ariana Dickie' => 'Occupational Therapist',
        ],
        'Kai Davies' => [
            'Ania Ivanovic' => 'Occupational Therapist',
        ],
        'Kain Gregory' => [
            'Frances Garrido' => 'Behavioural Consultant',
        ],
        'Kalin Sanchez' => [
            'Ariana Dickie' => 'Occupational Therapist',
        ],
        'Kaden Leung' => [
            'John Marquina' => 'Psychologist',
        ],
        'Keira Leaming-Rohl' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
        'Kenshin Sayoto' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Bryan Lerit' => 'Behavioural Aide',
        ],
        'Khai Thuy Nguyen' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Kobi Emeka-Okafor' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Lael Bucana' => [
            'Gayatri Somani' => 'Behavioural Consultant',
        ],
        'Laith Alrawi' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Leonard McKenny' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Sarah Jane Racelis' => 'Behavioural Aide',
            'Bryan Lerit' => 'Behavioural Aide',
        ],
        'Lian Palafox' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Luke Gavin Austria' => [
            'Connie Lo' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Elif Kanbul' => 'Behavioural Aide',
        ],
        'Lyanna Zhu' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Malakai Foucault' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Wynona Arcartado' => 'Behavioural Aide',
        ],
        'Mantaj Singh Bal' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Leizel Cebedo' => 'Behavioural Aide',
        ],
        'Maria Lisa Nsimba' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Maryam Abbas' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Mason Adhikari' => [
            'Prabhnoor Grewal' => 'Occupational Therapist',
            'Saraf Choudhury' => 'Behavioural Aide',
        ],
        'Matias Eduardo Leardi' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Marwan Oga' => [
            'Katherine Samiano' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Maruf Oga' => [
            'Katherine Samiano' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Michael Jidenna Oduah' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Prabhnoor Grewal' => 'Occupational Therapist',
        ],
        'Michael Waterman' => [
            'John Marquina' => 'Psychologist',
        ],
        'Mohamed Jalloh' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
        'Muhammad Irfan' => [
            'John Marquina' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Musab Bin Ahmad' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Nasar Ali' => [
            'Kristine Arreola' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Nickolas Dunning' => [
            'Katherine Samiano' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Norah Fatimah Hussain' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
        ],
        'Noah Ivan Ambrosio' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Ogheneochuko Ojaruwedia' => [
            'Ezralie Ibarra' => 'Occupational Therapist',
        ],
        'Olivia Christie' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Olivia Stewart' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Atoosa Daniali' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Oluwapinrekanmi Oguntoyinbo' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Oluwafisayo Akinbulijo' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Tassia Audet' => 'Behavioural Aide',
            'Tyra Mercure' => 'Behavioural Aide',
        ],
        'Omachonu Emmanuel' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Patrick Touch' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'Hailey Shaw' => 'Behavioural Aide',
        ],
        'Pharez Werku' => [
            'Prabhnoor Grewal' => 'Occupational Therapist',
        ],
        'Prabhjot Singh Chohan' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Ralph Ramones' => [
            'Nichole Ramo' => 'Behavioural Consultant',
        ],
        'Rayyan Syed-Ayaz' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Rayan Memon' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
        ],
        'Reed Gerlinsky' => [
            'Kristine Arreola' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
        ],
        'Richtan Hyunh' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Rim Daroui' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Jessamae Ocab' => 'Behavioural Aide',
        ],
        'Rylan Bolen' => [
            'Mikayla MacDonald' => 'Physiotherapist',
        ],
        'Safreen Islam' => [
            'Nichole Ramo' => 'Behavioural Consultant',
            'John Marquina' => 'Psychologist',
        ],
        'Samuel Hindolo Vandy' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
        'Sinaan Bakhtiar' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Srihan Vasireddy' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Cristine Joy Palao' => 'Behavioural Aide',
        ],
        'Syed Mustafa' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Taymoor Alikhan' => [
            'Atoosa Daniali' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Tristine Genesis Inopia' => [
            'Frances Garrido' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Cristine Joy Palao' => 'Behavioural Aide',
        ],
        'Valentina Galhardo' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Autumn Day' => 'Behavioural Aide',
        ],
        'Vince Manggad' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'West Shire' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
        ],
        'Xavier Macariola' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Ariana Dickie' => 'Occupational Therapist',
            'John Marquina' => 'Psychologist',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Sharan Gautam' => 'Physiotherapist',
            'Bryan Lerit' => 'Behavioural Aide',
            'Alyanna Del Rosario' => 'Behavioural Aide',
        ],
        'Xiang Han Chen' => [
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Mikayla MacDonald' => 'Physiotherapist',
            'Tassia Audet' => 'Behavioural Aide',
        ],
        'Yash Raj' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Ramish Raza' => 'Behavioural Aide',
        ],
        'Yuvsanj Gill' => [
            'John Marquina' => 'Psychologist',
        ],
        'Zavi LR Carty' => [
            'Gayatri Somani' => 'Behavioural Consultant',
            'Mary Ann Lerit' => 'Occupational Therapist',
            'Alyanna Del Rosario' => 'Behavioural Aide',
        ],
        'Zack Lorenzo Solomon' => [
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Althea Villanueva' => 'Behavioural Aide',
        ],
        'Zander Pham' => [
            'Ariana Dickie' => 'Occupational Therapist',
        ],
        'Zane Rodriguez' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Annalyn Lerit' => 'Behavioural Aide',
        ],
        'Zeanna Rodriguez' => [
            'Gordeen James' => 'Behavioural Consultant',
            'Zoha Azam' => 'Speech-Language Pathologist',
            'Althea Villanueva' => 'Behavioural Aide',
        ],
        'Zimuzo Asher Egenti' => [
            'Ariana Dickie' => 'Occupational Therapist',
        ],
    ];

    private int $assigned = 0;

    private int $alreadyAssigned = 0;

    private int $primariesSet = 0;

    /**
     * Clients given a primary therapist during this run.
     *
     * @var array<int, true>
     */
    private array $primaryGiven = [];

    /** @var array<string, true> */
    private array $missingClients = [];

    /** @var array<string, true> */
    private array $missingTherapists = [];

    /** @var array<string, true> */
    private array $ambiguousClients = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->resetTallies();
        $clients = $this->clientsByName();
        $therapists = $this->therapistsByName();

        foreach ($this->caseload() as $clientName => $caseload) {
            $client = $this->findClient($clients, $clientName);

            if ($client === null) {
                continue;
            }

            foreach ($caseload as $therapistName => $role) {
                $therapist = $therapists->get($this->normalize(self::THERAPIST_ALIASES[$therapistName] ?? $therapistName));

                if ($therapist === null) {
                    $this->missingTherapists[$therapistName] = true;
                    $this->line("Skipped {$therapistName} ({$role}) for {$clientName}: no therapist account.");

                    continue;
                }

                $this->assign($client, $therapist, $role, $dryRun);
            }
        }

        $this->summarize($dryRun);

        return self::SUCCESS;
    }

    /**
     * The caseload to assign. A method rather than a direct constant read so
     * tests can substitute a small, controlled list.
     *
     * @return array<string, array<string, string>>
     */
    protected function caseload(): array
    {
        return self::CASELOAD;
    }

    private function assign(Client $client, User $therapist, string $role, bool $dryRun): void
    {
        $label = "{$therapist->full_name} ({$role}) to {$client->displayName()}";
        $onCareTeam = $client->careTeam->contains('id', $therapist->id);
        $becomesPrimary = $client->primary_therapist_id === null && ! isset($this->primaryGiven[$client->id]);

        if ($onCareTeam && ! $becomesPrimary) {
            $this->alreadyAssigned++;

            return;
        }

        if (! $dryRun) {
            DB::transaction(function () use ($client, $therapist, $becomesPrimary): void {
                if ($becomesPrimary) {
                    $client->assignTherapist($therapist);
                } else {
                    $client->careTeam()->syncWithoutDetaching([$therapist->id]);
                }

                $this->addToTeamMemberCaseload($therapist, $client);
            });

            AuditLogger::log('Assigned therapist', 'Clients', "Assigned {$therapist->full_name} to client #{$client->id} via clients:assign-therapists");
        }

        // Remembered here rather than read back off the client, so a dry run
        // makes the same decisions for the rest of the row as a real run.
        if ($becomesPrimary) {
            $this->primaryGiven[$client->id] = true;
            $this->primariesSet++;
        }

        $client->careTeam->push($therapist);
        $this->assigned++;

        $verb = $dryRun ? 'Would assign' : 'Assigned';
        $this->info("{$verb} {$label}".($becomesPrimary ? ' as primary therapist.' : '.'));
    }

    /**
     * The therapist's own client list, which the service approval flow
     * (IntakeController::therapistApprove) also keeps.
     */
    private function addToTeamMemberCaseload(User $therapist, Client $client): void
    {
        $teamMember = TeamMember::query()->where('user_id', $therapist->id)->first();

        if ($teamMember === null) {
            return;
        }

        $caseload = $teamMember->client ?? [];

        if (! in_array($client->id, $caseload, true)) {
            $caseload[] = $client->id;
            $teamMember->update(['client' => $caseload]);
        }
    }

    /**
     * Finds the client by their full name, falling back to first + last name
     * because the client directory often carries a middle name the caseload
     * sheet leaves out ("Archer Dawson Johansen" vs "Archer Johansen"). The
     * fallback only counts when exactly one client fits.
     *
     * @param  Collection<int, Client>  $clients
     */
    private function findClient(Collection $clients, string $caseloadName): ?Client
    {
        $name = $this->normalize(self::CLIENT_ALIASES[$caseloadName] ?? $caseloadName);

        $matches = $clients->filter(fn (Client $client): bool => $this->normalize($client->displayName()) === $name);

        if ($matches->isEmpty()) {
            $matches = $clients->filter(fn (Client $client): bool => $this->firstAndLast($client->displayName()) === $this->firstAndLast($name));
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->isEmpty()) {
            $this->missingClients[$caseloadName] = true;
            $this->warn("Skipped {$caseloadName}: no client with that name.");
        } else {
            $this->ambiguousClients[$caseloadName] = true;
            $this->warn("Skipped {$caseloadName}: {$matches->count()} clients share that name.");
        }

        return null;
    }

    /** @return Collection<int, Client> */
    private function clientsByName(): Collection
    {
        return Client::query()->with(['originalIntake', 'careTeam'])->get();
    }

    /** @return Collection<string, User> */
    private function therapistsByName(): Collection
    {
        return User::query()
            ->role('therapist')
            ->get()
            ->keyBy(fn (User $user): string => $this->normalize("{$user->first_name} {$user->last_name}"));
    }

    private function normalize(string $name): string
    {
        return Str::of($name)->squish()->lower()->toString();
    }

    private function firstAndLast(string $name): string
    {
        $parts = explode(' ', $this->normalize($name));

        return count($parts) > 1 ? $parts[0].' '.end($parts) : $parts[0];
    }

    /** Each run reports on itself, even when the same instance runs twice. */
    private function resetTallies(): void
    {
        $this->assigned = 0;
        $this->alreadyAssigned = 0;
        $this->primariesSet = 0;
        $this->primaryGiven = [];
        $this->missingClients = [];
        $this->missingTherapists = [];
        $this->ambiguousClients = [];
    }

    private function summarize(bool $dryRun): void
    {
        $verb = $dryRun ? 'Would assign' : 'Assigned';

        $this->newLine();
        $this->info("{$verb} {$this->assigned} therapist(s), {$this->primariesSet} as primary; {$this->alreadyAssigned} already assigned.");

        if ($this->missingClients !== []) {
            $this->warn('Clients not found ('.count($this->missingClients).'): '.implode(', ', array_keys($this->missingClients)));
        }

        if ($this->ambiguousClients !== []) {
            $this->warn('Clients with more than one match ('.count($this->ambiguousClients).'): '.implode(', ', array_keys($this->ambiguousClients)));
        }

        if ($this->missingTherapists !== []) {
            $this->warn('Therapists without an account ('.count($this->missingTherapists).'): '.implode(', ', array_keys($this->missingTherapists)));
        }
    }
}
