<?php

namespace App\Console\Commands;

use App\Models\Intake;
use App\Services\AuditLogger;
use App\Services\IntakeApprovalService;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Creates an approved intake and a Client record for every child in the
 * clinic's client directory, promoting each through the same path the admin
 * "Approve" action uses. A parent whose email is new gets a portal account
 * and the welcome email carrying their login email and temporary password;
 * a parent already on file (a second child, or an earlier run) keeps their
 * existing login and gets no second email.
 *
 * The directory is the static `DIRECTORY` list below (from the "CLIENT
 * INFO" sheet), so the command needs no input file. A child whose intake
 * already exists under the same parent email is skipped, which makes the
 * command safe to re-run after adding entries.
 */
#[Signature('clients:create
    {--dry-run : Show what would be created without writing or emailing}')]
#[Description('Create intakes and client accounts from the client directory and email each new parent their temporary password')]
class CreateClientAccountsCommand extends Command
{
    /**
     * The client directory. `fscd_file` is the FSCD file number, or the
     * word "Private" for privately funded families.
     *
     * @var array<int, array{parent_email: string, child_name: string, parent_name: string, address: string, city_province: string, postal_code: string, fscd_file: string}>
     */
    public const DIRECTORY = [
        [
            'parent_email' => 'a_affan@outlook.com',
            'child_name' => 'Maryam Abbas',
            'parent_name' => 'Asmaa Affan',
            'address' => '4719 Rundlewood Road NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 2N7',
            'fscd_file' => '52805',
        ],
        [
            'parent_email' => 'mobola.abubakre@gmail.com',
            'child_name' => 'Ikeoluwa Abubakre',
            'parent_name' => 'Omobolaji Abubakre',
            'address' => '32 Edith Mews NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 1Y8',
            'fscd_file' => '62254',
        ],
        [
            'parent_email' => 'adam.marcel@gmail.com',
            'child_name' => 'Gabriela Adam',
            'parent_name' => 'Marcel Adam',
            'address' => '41 Cranbrook Place SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 1S4',
            'fscd_file' => '57978',
        ],
        [
            'parent_email' => 'samitapoudel@gmail.com',
            'child_name' => 'Mason Adhikari',
            'parent_name' => 'Samita Sharma Poudel',
            'address' => '96 Sage Bank Crescent NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 0J2',
            'fscd_file' => '56702',
        ],
        [
            'parent_email' => 'kaleembook1@gmail.com',
            'child_name' => 'Hazeem Ahmed',
            'parent_name' => 'Kaleem Ijaz',
            'address' => '304 Chelsea Pier Manor',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 3H6',
            'fscd_file' => '61342',
        ],
        [
            'parent_email' => 'farrahnaz2000@hotmail.com',
            'child_name' => 'Ibrahim Ahmad',
            'parent_name' => 'Farah Ahmad',
            'address' => '165 Varsity Manor SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Y 0S6',
            'fscd_file' => '40743',
        ],
        [
            'parent_email' => 'asma_kifa@yahoo.com',
            'child_name' => 'Musab Bin Ahmad',
            'parent_name' => 'Asma Ahmed',
            'address' => '500 Nolan Hill Rise NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 2E3',
            'fscd_file' => '74973',
        ],
        [
            'parent_email' => 'akinbulijoronke@gmail.com',
            'child_name' => 'Oluwafisayo Akinbulijo',
            'parent_name' => 'Ronke Akinbulijo',
            'address' => '6 Merganser Drive West',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X2Y2',
            'fscd_file' => '61743',
        ],
        [
            'parent_email' => 'razavifatema@yahoo.com',
            'child_name' => 'Hassan Ali',
            'parent_name' => 'Seyyedeh Razavi',
            'address' => '17 Berwick Rise NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 1C9',
            'fscd_file' => '55298',
        ],
        [
            'parent_email' => 'nowreen04@hotmail.com',
            'child_name' => 'Nasar Ali',
            'parent_name' => 'Nowreen Ali',
            'address' => '530 12th Ave SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2R 0B1',
            'fscd_file' => '65336',
        ],
        [
            'parent_email' => 'mishib99@gmail.com',
            'child_name' => 'Taymoor Alikhan',
            'parent_name' => 'Mehrin Shahid',
            'address' => '80 Carrington Way NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 0Z1',
            'fscd_file' => '56834',
        ],
        [
            'parent_email' => 'sjismaeel@gmail.com',
            'child_name' => 'Laith Alrawi',
            'parent_name' => 'Suzan Ismaeel',
            'address' => '11 Nolancrest Manor NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 0V7',
            'fscd_file' => '63599',
        ],
        [
            'parent_email' => 'ambrosiojen@gmail.com',
            'child_name' => 'Noah Ivan Ambrosio',
            'parent_name' => 'Jennifer Ambrosio',
            'address' => '173 Ranch Rise',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 0G3',
            'fscd_file' => '65988',
        ],
        [
            'parent_email' => 'salwa88za@hotmail.com',
            'child_name' => 'Hadi Amin',
            'parent_name' => 'Salwa Zarzour',
            'address' => '52 Macewan Ridge View NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 3W2',
            'fscd_file' => '38697',
        ],
        [
            'parent_email' => 'graceliann@gmail.com',
            'child_name' => 'Anson An',
            'parent_name' => 'Chen Nan Guo',
            'address' => '232 Sandarac Place NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 2Y8',
            'fscd_file' => '59860',
        ],
        [
            'parent_email' => 'gracevher@yahoo.com',
            'child_name' => 'Luke Gavin Austria',
            'parent_name' => 'Grace Austria',
            'address' => '1119 39 St SW Apartment-A',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3C 1V3',
            'fscd_file' => '53855',
        ],
        [
            'parent_email' => 'kirankhanh@gmail.com',
            'child_name' => 'Rayyan Syed-Ayaz',
            'parent_name' => 'Kiran Khan',
            'address' => '377 Belmont Park SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 5S9',
            'fscd_file' => '51198',
        ],
        [
            'parent_email' => 'roya.rafizada@gmail.com',
            'child_name' => 'Sinaan Bakhtiar',
            'parent_name' => 'Roya Bakhtiar',
            'address' => '41 Cityside Rise NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 1H7',
            'fscd_file' => '56906',
        ],
        [
            'parent_email' => 'meetriar5@gmail.com',
            'child_name' => 'Mantaj Singh Bal',
            'parent_name' => 'Harmeet Pal Kaur',
            'address' => '65 Cityspring Terrace NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 1Z5',
            'fscd_file' => '68482',
        ],
        [
            'parent_email' => 'lynxus86@yahoo.com',
            'child_name' => 'France Kellyn Balandino',
            'parent_name' => 'Rhodalyn Balandino',
            'address' => '14 Whiteram Place NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 5J8',
            'fscd_file' => '',
        ],
        [
            'parent_email' => 'mohit_j16@yahoo.ca',
            'child_name' => 'Daksh Bansal',
            'parent_name' => 'Shaveta Bansal',
            'address' => '11133 Cityscape Dr. NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N1A8',
            'fscd_file' => '62366',
        ],
        [
            'parent_email' => 'teliesharennalls@hotmail.com',
            'child_name' => 'Doris Leanda Baker',
            'parent_name' => 'Teliesha Sinead Rennalls',
            'address' => '5920 22 Ave NE, 435',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 4P4',
            'fscd_file' => '61205',
        ],
        [
            'parent_email' => 'rahilabhatti@gmail.com',
            'child_name' => 'Daniyal Bhatti',
            'parent_name' => 'Rahila Bhatti',
            'address' => '435 Skyview shores manor NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N0H4',
            'fscd_file' => '46903',
        ],
        [
            'parent_email' => 'katrina.bibanco@gmail.com',
            'child_name' => 'Elijah Bibanco',
            'parent_name' => 'Katrina Bibanco',
            'address' => '118 Arbour Lake Rise NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3G 0G9',
            'fscd_file' => '48532',
        ],
        [
            'parent_email' => '130bolen@gmail.com',
            'child_name' => 'Rylan Bolen',
            'parent_name' => 'Jaime Bolen',
            'address' => '130 Drake Landing Terrace',
            'city_province' => 'Okotoks, Alberta',
            'postal_code' => 'T1S 0H1',
            'fscd_file' => '56811',
        ],
        [
            'parent_email' => 'shayshayb12@yahoo.ca',
            'child_name' => 'Zavi LR Carty',
            'parent_name' => 'Shay Carty',
            'address' => '3 riverstone cres SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2C 4A4',
            'fscd_file' => '75490',
        ],
        [
            'parent_email' => 'xinli3678@gmail.com',
            'child_name' => 'Xiang Han Chen',
            'parent_name' => 'Xin Li',
            'address' => '112 Sandpiper Landing',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 1Y8',
            'fscd_file' => '62939',
        ],
        [
            'parent_email' => 'kaele.tunyi@gmail.com',
            'child_name' => 'Alex Chengwa',
            'parent_name' => 'Kaele Tunyi Anembom',
            'address' => '32 Nolancliff Place NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 0T4',
            'fscd_file' => '56720',
        ],
        [
            'parent_email' => 'sainimeenu@yahoo.com',
            'child_name' => 'Prabhjot Singh Chohan',
            'parent_name' => 'Kulwinder Kaur Saini',
            'address' => '192 Cornerbrook Rd NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 2L2',
            'fscd_file' => '68089',
        ],
        [
            'parent_email' => 'christiein.james@gmail.com',
            'child_name' => 'Olivia Chrsitie',
            'parent_name' => 'James Christie',
            'address' => '248 Radley Place SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 5X6',
            'fscd_file' => '58879',
        ],
        [
            'parent_email' => 'alstco1118@gmail.com',
            'child_name' => 'Maria Reylene Tiburcio',
            'parent_name' => 'Maria Reylene Tiburcio',
            'address' => '247 Hidden Ranch Cir NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3A 5R2',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'alstco1118@gmail.com',
            'child_name' => 'Alexandra Lucia Co',
            'parent_name' => 'Maria Reylene Tiburcio',
            'address' => '248 Hidden Ranch Cir NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3A 5R2',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'alstco1118@gmail.com',
            'child_name' => 'Alexander Excel Co',
            'parent_name' => 'Maria Reylene Tiburcio',
            'address' => '249 Hidden Ranch Cir NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3A 5R2',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'sdetrempe@gmail.com',
            'child_name' => 'Gabriel Owen Corniciuc',
            'parent_name' => 'Stephanie Corniciuc',
            'address' => '610 Canals Crossing SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B4L4',
            'fscd_file' => '68645',
        ],
        [
            'parent_email' => 'johanneuy@yahoo.com',
            'child_name' => 'Jhon Corregidor',
            'parent_name' => 'Joan Nacion',
            'address' => '8 Radcliffe Bay SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 6B3',
            'fscd_file' => '40923',
        ],
        [
            'parent_email' => 'jackiedeleon12@yahoo.ca',
            'child_name' => 'Hugo Ken Coronel',
            'parent_name' => 'Jackie Coronel',
            'address' => '617 32 Ave NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2E 8Y7',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'youssefdroui07@gmail.com',
            'child_name' => 'Rim Daroui',
            'parent_name' => 'Youssef Daroui',
            'address' => '12 New Brighton Grove SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 0W3',
            'fscd_file' => '41090',
        ],
        [
            'parent_email' => 'audrey.peters26@gmail.com',
            'child_name' => 'Kai Davies',
            'parent_name' => 'Audrey Peters',
            'address' => '365059 60th St East',
            'city_province' => 'Foothills County, AB',
            'postal_code' => 'T1S 5W8',
            'fscd_file' => '31893',
        ],
        [
            'parent_email' => 'pritamjdey8080@gmail.com',
            'child_name' => 'Adrija Dey',
            'parent_name' => 'Pritam Dey',
            'address' => '226 New Brighton Circle SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 4B4',
            'fscd_file' => '44559',
        ],
        [
            'parent_email' => 'aartidhingra02@gmail.com',
            'child_name' => 'Gursanjh Singh Dhingra',
            'parent_name' => 'Aarti Bajaj Dhingra',
            'address' => '569 Liuxstone Landing SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 0C7',
            'fscd_file' => '63247',
        ],
        [
            'parent_email' => 'mrsmichelle.dunning@hotmail.com',
            'child_name' => 'Nickolas Teddy Jay Dunning',
            'parent_name' => 'Michelle Amanda Dunning',
            'address' => '139 Cooperstown Place',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 3T7',
            'fscd_file' => '57715',
        ],
        [
            'parent_email' => 'ogocciokafor@gmail.com',
            'child_name' => 'Kobi Emeka-Okafor',
            'parent_name' => 'Ogo Okafor',
            'address' => '85 Mallard Grove SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3S 0E2',
            'fscd_file' => '62684',
        ],
        [
            'parent_email' => 'elohor42@gmail.com',
            'child_name' => 'Zimuzo Asher Egenti',
            'parent_name' => 'Elohor Lois Egenti',
            'address' => '22 Lucas Terrace NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1P9',
            'fscd_file' => '66350',
        ],
        [
            'parent_email' => 'ojokatina@yahoo.com',
            'child_name' => 'Omachonu Emmanuel',
            'parent_name' => 'Tina Ojomah',
            'address' => '13 Sherwood Parade',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 1R1',
            'fscd_file' => '63820',
        ],
        [
            'parent_email' => 'ashleighfuellbrandt@hotmail.com',
            'child_name' => 'Malakai Foucault',
            'parent_name' => 'Ashleigh Fuellbrandt',
            'address' => '3418 64 Street NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 4L5',
            'fscd_file' => '60772',
        ],
        [
            'parent_email' => 'kerri_punkert@hotmail.com',
            'child_name' => 'Jayden Fray',
            'parent_name' => 'Kerri-Lee Fray',
            'address' => '112 Everridge Drive SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Y 4R4',
            'fscd_file' => '35172',
        ],
        [
            'parent_email' => 'gre.galhardo@icloud.com',
            'child_name' => 'Valentina Galhardo',
            'parent_name' => 'Greice Galhardo',
            'address' => '8108 Masters Boulevard',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 2L5',
            'fscd_file' => '62085',
        ],
        [
            'parent_email' => 'cgerlinsky@hotmail.com',
            'child_name' => 'Reed Gerlinsky',
            'parent_name' => 'Courtney Gerlinsky',
            'address' => '38-6020 Temple drive NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 4R5',
            'fscd_file' => '51507',
        ],
        [
            'parent_email' => 'soni2011@live.ca',
            'child_name' => 'Dev Gill',
            'parent_name' => 'Sukhpreet Kaur',
            'address' => '8 Red Sky Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 1K4',
            'fscd_file' => '58956',
        ],
        [
            'parent_email' => 'ranpreetgill1988@gmail.com',
            'child_name' => 'Avitaj Singh Gill',
            'parent_name' => 'Ranpreet Singh Gill',
            'address' => '273 Saddlecrest Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 5N3',
            'fscd_file' => '54837',
        ],
        [
            'parent_email' => 'himpreetbrar@hotmail.com',
            'child_name' => 'Yuvsanj Gill',
            'parent_name' => 'Himpreet Gill',
            'address' => '1607 Woodside Blvd NW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 2K1',
            'fscd_file' => '58399',
        ],
        [
            'parent_email' => 'amanda.gregory2124@gmail.com',
            'child_name' => 'Kain Gregory',
            'parent_name' => 'Amanda Gregory',
            'address' => '605 Marina Drive',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 0N9',
            'fscd_file' => '57966',
        ],
        [
            'parent_email' => 'farran.harkonen@gmail.com',
            'child_name' => 'Aurora Harkonen',
            'parent_name' => 'Farran Harkonen',
            'address' => '25 Mountain Circle SE',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4A 1X8',
            'fscd_file' => '54009',
        ],
        [
            'parent_email' => 'farran.harkonen@gmail.com',
            'child_name' => 'Isabelle Harkonen',
            'parent_name' => 'Farran Harkonen',
            'address' => '26 Mountain Circle SE',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4A 1X9',
            'fscd_file' => '70229',
        ],
        [
            'parent_email' => 'ubahmohamud@gmail.com',
            'child_name' => 'Iman Hassan',
            'parent_name' => 'Ubah Mohamud',
            'address' => '20 Coventry Green NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 4M3',
            'fscd_file' => '28402',
        ],
        [
            'parent_email' => 'gsawaich@gmail.com',
            'child_name' => 'Norah Fatimah Hussain',
            'parent_name' => 'Gurmeet  Sawaich',
            'address' => '2103 Country Hills Circle NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 4Z3',
            'fscd_file' => '69667',
        ],
        [
            'parent_email' => 'lazy_pig8384@yahoo.com',
            'child_name' => 'Richtan Huynh',
            'parent_name' => 'Ngoc Hanh Huynh',
            'address' => '31 Radcliffe Close SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 6B2',
            'fscd_file' => '68178',
        ],
        [
            'parent_email' => 'shamimirfan872@gmail.com',
            'child_name' => 'Muhammad Abdullah Irfan',
            'parent_name' => 'Shamim Irfan',
            'address' => '2443 48 St. SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2B 1M5',
            'fscd_file' => '67703',
        ],
        [
            'parent_email' => 'mariacecilia.inopia@yahoo.com',
            'child_name' => 'Tristine Genesis Inopia',
            'parent_name' => 'Maria Cecilia Inopia',
            'address' => '10203 7 Street SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 0G2',
            'fscd_file' => '48937',
        ],
        [
            'parent_email' => 'shafaq699@gmail.com',
            'child_name' => 'Amaya Islam',
            'parent_name' => 'Shafaq Sattar',
            'address' => '43 corner glen common NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 2L4',
            'fscd_file' => '62557',
        ],
        [
            'parent_email' => 'hello.nilufar@gmail.com',
            'child_name' => 'Safreen Islam',
            'parent_name' => 'Nilufar Yeasmin',
            'address' => '432 Abadan PI NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 6W3',
            'fscd_file' => '65180',
        ],
        [
            'parent_email' => 'vishwa.g@gmail.com',
            'child_name' => 'Pranshi Iyer',
            'parent_name' => 'Vishwa Gopalkrishna',
            'address' => '288 Tremblant Way SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3H4G9',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'mseraybah@yahoo.com',
            'child_name' => 'Abubakarr Jalloh',
            'parent_name' => 'Mariama Jalloh',
            'address' => '113 Pinehill Place NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 2L8',
            'fscd_file' => '51333',
        ],
        [
            'parent_email' => 'mseraybah@yahoo.com',
            'child_name' => 'Mohamed Jalloh',
            'parent_name' => 'Mariama Jalloh',
            'address' => '113 Pinehill Place NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 2L8',
            'fscd_file' => '51332',
        ],
        [
            'parent_email' => 'j_ma6@yahoo.ca',
            'child_name' => 'Faris Mahmoud Jarrar',
            'parent_name' => 'Mahmoud Jarrar',
            'address' => '123 908 Ranchlands Blvd NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3G 1X9',
            'fscd_file' => '57262',
        ],
        [
            'parent_email' => 'eventhorizon.837@gmail.com',
            'child_name' => 'Archer Dawson Johansen',
            'parent_name' => 'Tara Johansen',
            'address' => '14957 1st Street NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1N2',
            'fscd_file' => '68489',
        ],
        [
            'parent_email' => 'eventhorizon.837@gmail.com',
            'child_name' => 'Hunter Davis Johansen',
            'parent_name' => 'Tara Johansen',
            'address' => '14957 1st Street NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1N2',
            'fscd_file' => '68487',
        ],
        [
            'parent_email' => 'imeldateopaco@gmail.com',
            'child_name' => 'Ian Andrei Juco',
            'parent_name' => 'Imelda Juco',
            'address' => '125 Strathmore Lake Common',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 1Y7',
            'fscd_file' => '67481',
        ],
        [
            'parent_email' => 'gurmandeep2127@gmail.com',
            'child_name' => 'Gurvar Kang',
            'parent_name' => 'Gurneet Kaur',
            'address' => '392 Nolanhill Blvd NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3R 0P9',
            'fscd_file' => '58693',
        ],
        [
            'parent_email' => 'justina.legas@gmail.com',
            'child_name' => 'Rojus Legas Kenstavicius',
            'parent_name' => 'Justina Legas',
            'address' => '94-15 Bermondsey Way NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 1Y9',
            'fscd_file' => '75654',
        ],
        [
            'parent_email' => 'sscsidrah@hotmail.com',
            'child_name' => 'Sufyan Khan',
            'parent_name' => 'Sidrah Najeeb',
            'address' => '88 Somercrest Close SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Y 3H8',
            'fscd_file' => '61180',
        ],
        [
            'parent_email' => 'shafina786b@gmail.com',
            'child_name' => 'Zeehan Khanmohamed',
            'parent_name' => 'Shafina Bhimji',
            'address' => '43 Templehill Drive NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 4C4',
            'fscd_file' => '38950',
        ],
        [
            'parent_email' => 'nengmin.misao@gmail.com',
            'child_name' => 'Ayan Kumar',
            'parent_name' => 'Nengminchong Misao',
            'address' => '908 Prairie Springs Dr SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 0E4',
            'fscd_file' => '59917',
        ],
        [
            'parent_email' => 'sgankum@gmail.com',
            'child_name' => 'Dhakshan Ganesh Kumar',
            'parent_name' => 'Ganesh kumar Subramanian',
            'address' => '401-1611 23 Ave SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2T 0V1',
            'fscd_file' => '68778',
        ],
        [
            'parent_email' => 'tyrat33@yahoo.com',
            'child_name' => 'Keira Leaming-Rohl',
            'parent_name' => 'Tyra Thompson',
            'address' => '1307 2631 38 Street NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 3Z8',
            'fscd_file' => '45913',
        ],
        [
            'parent_email' => 'maylen_leardi@hotmail.com',
            'child_name' => 'Matias Eduardo Leardi',
            'parent_name' => 'Maylen Leardi Bayester',
            'address' => '64 Aberfoyle Close NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 6S6',
            'fscd_file' => '68280',
        ],
        [
            'parent_email' => 'ann_2427@yahoo.com',
            'child_name' => 'Louise Lerit',
            'parent_name' => 'Annalyn Lerit',
            'address' => '256 Copperleaf Way SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 5G2',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'hr.vanessatai@gmail.com',
            'child_name' => 'Kaden Leung',
            'parent_name' => 'Vanessa Tai',
            'address' => '1055 Evanston Dr NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 0K4',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'hr.vanessatai@gmail.com',
            'child_name' => 'Gianna Leung',
            'parent_name' => 'Vanessa Tai',
            'address' => '1056 Evanston Dr NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 0K4',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'hr.vanessatai@gmail.com',
            'child_name' => 'Sheldon Leung',
            'parent_name' => 'Vanessa Tai',
            'address' => '1057 Evanston Dr NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 0K4',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'hr.vanessatai@gmail.com',
            'child_name' => 'Vanessa Tai',
            'parent_name' => 'Vanessa Tai',
            'address' => '1058 Evanston Dr NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 0K4',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'bugayongwilma@yahoo.ca',
            'child_name' => 'Xavier Macariola',
            'parent_name' => 'Wilma Macariola',
            'address' => '1604 Auburn Bay Square',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 0E9',
            'fscd_file' => '69629',
        ],
        [
            'parent_email' => 'aizele_orense@yahoo.com.ph',
            'child_name' => 'Dahlia Alessi Manalo',
            'parent_name' => 'Aizele Dell Orense',
            'address' => '92 Ambleton Street NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1W5',
            'fscd_file' => '64786',
        ],
        [
            'parent_email' => 'gracegutoman@gmail.com',
            'child_name' => 'Vince Manggad',
            'parent_name' => 'Grace Manggad',
            'address' => '98 Falmere Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 2Y6',
            'fscd_file' => '65684',
        ],
        [
            'parent_email' => 'raimann111@gmail.com',
            'child_name' => 'Amber Mann',
            'parent_name' => 'Rai Mann',
            'address' => '53 Hanson Lane NE',
            'city_province' => 'Landon Alberta',
            'postal_code' => 'T0J 1X1',
            'fscd_file' => '55306',
        ],
        [
            'parent_email' => 'janepalatan24941@yahoo.com',
            'child_name' => 'Jan Jeffrey Manzon',
            'parent_name' => 'Jane Palatan',
            'address' => '141 Ranch Rise',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 0G3',
            'fscd_file' => '63018',
        ],
        [
            'parent_email' => 'aileenjavier702@yahoo.com',
            'child_name' => 'Adrian McKenny',
            'parent_name' => 'Aileen Javier',
            'address' => '10831 Sacramento Drive SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 0J3',
            'fscd_file' => '51547',
        ],
        [
            'parent_email' => 'aileenjavier702@yahoo.com',
            'child_name' => 'Leonard McKenny',
            'parent_name' => 'Aileen Javier',
            'address' => '10831 Sacramento Drive SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 0J3',
            'fscd_file' => '23991',
        ],
        [
            'parent_email' => 'xiedan_98@hotmail.com',
            'child_name' => 'Jessie Meng',
            'parent_name' => 'Dan Xie',
            'address' => '172 Chaparral Circle SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 3M2',
            'fscd_file' => '67687',
        ],
        [
            'parent_email' => 'yonexmele22@gmail.com',
            'child_name' => 'Amen Meles',
            'parent_name' => 'Yonas Worke',
            'address' => '3204 48 St NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 1H5',
            'fscd_file' => '66889',
        ],
        [
            'parent_email' => 'areenrashid@yahoo.com',
            'child_name' => 'Rayan Memon',
            'parent_name' => 'Areen Memon',
            'address' => '456 Taracove Estate Dr NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J4S8',
            'fscd_file' => '69454',
        ],
        [
            'parent_email' => 'shazaalsubaie@hotmail.com',
            'child_name' => 'Ahmed Mohamed',
            'parent_name' => 'Shaza Suliman',
            'address' => '128 Edgebrook Park NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3A 5T9',
            'fscd_file' => '65762',
        ],
        [
            'parent_email' => 'inboxerum@gmail.com',
            'child_name' => 'Anamta Mohtashim',
            'parent_name' => 'Erum Mohtashim',
            'address' => '80 Midridge Garden SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 1C3',
            'fscd_file' => '65318',
        ],
        [
            'parent_email' => 'kathrynshaw@shaw.ca',
            'child_name' => 'Isabella Moonen',
            'parent_name' => 'Kathryn Moonen',
            'address' => '8336 Addison Drive SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2H 1P1',
            'fscd_file' => '46998',
        ],
        [
            'parent_email' => 'msuzy033@gmail.com',
            'child_name' => 'Jad Mustafa',
            'parent_name' => 'Susan Mustafa',
            'address' => '7 - 15 Norquay Ct NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2K 6A4',
            'fscd_file' => '43235',
        ],
        [
            'parent_email' => 'mrs.zakir@yahoo.com',
            'child_name' => 'Syed Mustafa',
            'parent_name' => 'Misbah Zakir',
            'address' => '85 Everbrook DR SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Y 0A4',
            'fscd_file' => '63924',
        ],
        [
            'parent_email' => 'gabrarose@gmail.com',
            'child_name' => 'Abel Mwangi',
            'parent_name' => 'Rose Waburi',
            'address' => '308 255 Taralake Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 0E8',
            'fscd_file' => '60418',
        ],
        [
            'parent_email' => 'ridazara88@gmail.com',
            'child_name' => 'Atta Ali Naqvi',
            'parent_name' => 'Rida Zahra',
            'address' => '159 Whitestone Cres NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T1Y 1S5',
            'fscd_file' => '60182',
        ],
        [
            'parent_email' => 'swoldu1@gmail.com',
            'child_name' => 'Ezra Nigusse',
            'parent_name' => 'Selamawit Habte',
            'address' => '137 Martinpark Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 3M8',
            'fscd_file' => '60710',
        ],
        [
            'parent_email' => 'francisnguyn@gmail.com',
            'child_name' => 'Khai Thuy Nguyen',
            'parent_name' => 'Anh Tuan Nguyen',
            'address' => '62 Brightonstone Passage SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 0K2',
            'fscd_file' => '75912',
        ],
        [
            'parent_email' => 'rubynota@outlook.com',
            'child_name' => 'Adab Nota',
            'parent_name' => 'Parminder Kaur Nota',
            'address' => '865 Corner Meadows Way',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 2C7',
            'fscd_file' => '62435',
        ],
        [
            'parent_email' => 'kharmie_santos@yahoo.com',
            'child_name' => 'Marie Elisabeth Nsimba',
            'parent_name' => 'Carmela Bwanda Vinda',
            'address' => '#338 215 Fairview Dr SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2H 1B7',
            'fscd_file' => '21046',
        ],
        [
            'parent_email' => 'lamya.mulla@gmail.com',
            'child_name' => 'Hasan Nurdin',
            'parent_name' => 'Lamya Mulla',
            'address' => '87 Masters Link SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 2N2',
            'fscd_file' => '62922',
        ],
        [
            'parent_email' => 'limzyluv@gmail.com',
            'child_name' => 'Grace Odediran',
            'parent_name' => 'Halima Idris',
            'address' => '9 Legacy Reach Crescent SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 5A3',
            'fscd_file' => '46328',
        ],
        [
            'parent_email' => 'sophiaeziuloh@yahoo.com',
            'child_name' => 'Chidubem Odinukwe',
            'parent_name' => 'Sophia Odinukwe',
            'address' => '37 Copperstone Place SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 0G5',
            'fscd_file' => '61742',
        ],
        [
            'parent_email' => 'mercyucheoduah@gmail.com',
            'child_name' => 'Michael Jidenna Oduah',
            'parent_name' => 'Mercy Oduah',
            'address' => '137 Amblehurst Green NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1W8',
            'fscd_file' => '64603',
        ],
        [
            'parent_email' => 'marwanmaruf441@gmail.com',
            'child_name' => 'Marwan Oga',
            'parent_name' => 'Fauziya Ibrahim',
            'address' => '769 Southpoint Gate SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 0X2',
            'fscd_file' => '64753',
        ],
        [
            'parent_email' => 'marwanmaruf441@gmail.com',
            'child_name' => 'Maruf Oga',
            'parent_name' => 'Fauziya Ibrahim',
            'address' => '769 Southpoint Gate SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 0X2',
            'fscd_file' => '64754',
        ],
        [
            'parent_email' => 'olasumbophillips@yahoo.com',
            'child_name' => 'Oluwapinrekanmi Oguntoyinbo',
            'parent_name' => 'Margaret Oguntoyinbo',
            'address' => '56 Corner Glen Rd NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 2L3',
            'fscd_file' => '65074',
        ],
        [
            'parent_email' => 'rukevwe.ojaruwedia@gmail.com',
            'child_name' => 'Ogheneochuko Ojaruwedia',
            'parent_name' => 'Rukevwe Ojaruwedia',
            'address' => '124 Lucas Way NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1M3',
            'fscd_file' => '43769',
        ],
        [
            'parent_email' => 'jasmineoshin@gmail.com',
            'child_name' => 'Daniel Ojodun',
            'parent_name' => 'Feyi Ojodun',
            'address' => '591 Cornerstone Ave NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 1V3',
            'fscd_file' => '69297',
        ],
        [
            'parent_email' => 'aibhereelizabeth@gmail.com',
            'child_name' => 'Ian Olorunfemi',
            'parent_name' => 'Elizabeth Olrunfemi',
            'address' => '77 Carringwood close NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 2B1',
            'fscd_file' => '69839',
        ],
        [
            'parent_email' => 'rivas411family@gmail.com',
            'child_name' => 'Catalina Rivas-Oswald',
            'parent_name' => 'Daniel Rivas',
            'address' => '84 Sunridge Cres NW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 2G5',
            'fscd_file' => '47794',
        ],
        [
            'parent_email' => 'temioladapo@gmail.com',
            'child_name' => 'Joshua Otolorin',
            'parent_name' => 'Temi Otolorin',
            'address' => '137 Master Row SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 2R9',
            'fscd_file' => '71390',
        ],
        [
            'parent_email' => 'saypjose@yahoo.com',
            'child_name' => 'Lian Palafox',
            'parent_name' => 'Melissa Jose',
            'address' => '115 Mount Lorette Close SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 2L9',
            'fscd_file' => '36024',
        ],
        [
            'parent_email' => 'vukpham@live.ca',
            'child_name' => 'Zander Pham',
            'parent_name' => 'Kim Anh Vu Pham',
            'address' => '1586 New Brighton Dr SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z 0P8',
            'fscd_file' => '64006',
        ],
        [
            'parent_email' => 'namphan7979@yahoo.com',
            'child_name' => 'Jonathan Nguyen Phan',
            'parent_name' => 'Nam Phan',
            'address' => '116 Pennsylvania Road SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 6R2',
            'fscd_file' => '66804',
        ],
        [
            'parent_email' => 'maryqueenroces@yahoo.com',
            'child_name' => 'Alon Jedrek Roces Racelis',
            'parent_name' => 'Mary Queen Roces Racelis',
            'address' => '3116-550 Belmont St SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 5Y9',
            'fscd_file' => '65586',
        ],
        [
            'parent_email' => 'jhean_a@yahoo.com',
            'child_name' => 'Jayden Racelis',
            'parent_name' => 'Sarah Jane Racelis',
            'address' => '79 Macewan Meadow Crescent NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 3H7',
            'fscd_file' => '51398',
        ],
        [
            'parent_email' => 'tulikakaran@gmail.com',
            'child_name' => 'Yash Raj',
            'parent_name' => 'Tulika Karan',
            'address' => '11 Tuscany Meadows Crescent',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3L 2T9',
            'fscd_file' => '28249',
        ],
        [
            'parent_email' => 'buena1616@hotmail.com',
            'child_name' => 'Ralph Ramones',
            'parent_name' => 'Buena Juan Ramones',
            'address' => '55 Hidden Ranch close, NW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 6C9',
            'fscd_file' => '37825',
        ],
        [
            'parent_email' => 'sniru73@gmail.com',
            'child_name' => 'Aryan Rijal',
            'parent_name' => 'Nirmala Rijal',
            'address' => '436 Walcrest View Walden SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4P1',
            'fscd_file' => '59152',
        ],
        [
            'parent_email' => 'alaine_s_mercado19@yahoo.com',
            'child_name' => 'Zeanna McLaine Rodriguez',
            'parent_name' => 'Alaine Mercado',
            'address' => '15 Legacy Reach View SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4T6',
            'fscd_file' => '66181',
        ],
        [
            'parent_email' => 'alaine_s_mercado19@yahoo.com',
            'child_name' => 'Zane Alesso Rodriguez',
            'parent_name' => 'Alaine Mercado',
            'address' => '15 Legacy Reach View SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4T6',
            'fscd_file' => '55664',
        ],
        [
            'parent_email' => 'irenerosario27@gmail.com',
            'child_name' => 'Gwyneth Raine Rosario',
            'parent_name' => 'Irene Rosarion',
            'address' => '40 Dover Meadow Close SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2B 2E2',
            'fscd_file' => '42775',
        ],
        [
            'parent_email' => 'jocelynsabalbaro@gmail.com',
            'child_name' => 'Allen James Sabalbaro',
            'parent_name' => 'Jocelyn Sabalbaro',
            'address' => '146 Ranch Ridge Court',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 0A5',
            'fscd_file' => '56131',
        ],
        [
            'parent_email' => 'garysaini07@yahoo.com',
            'child_name' => 'Anantvir Singh Saini',
            'parent_name' => 'Gurpreet Saini',
            'address' => '1404 -155 Skyview Ranch Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 0L1',
            'fscd_file' => '69977',
        ],
        [
            'parent_email' => 'sheilabsalas@gmail.com',
            'child_name' => 'Anela Salas',
            'parent_name' => 'Sheila Salas',
            'address' => '804 Sabrina Road SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 0P3',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'sheilabsalas@gmail.com',
            'child_name' => 'Dave Salas',
            'parent_name' => 'Sheila Salas',
            'address' => '804 Sabrina Road SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 0P3',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'reizcelj@yahoo.com',
            'child_name' => 'Kalin Sanchez',
            'parent_name' => 'Reizcel Sanchez',
            'address' => '501 Cranford Drive SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 1W1',
            'fscd_file' => '65073',
        ],
        [
            'parent_email' => 'ggurpreet24@yahoo.com',
            'child_name' => 'Jaisnoor Sandhu',
            'parent_name' => 'Gurpreet Gill',
            'address' => '17 Saddlecrest Manor',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 2K3',
            'fscd_file' => '54857',
        ],
        [
            'parent_email' => 'sjohn_sayoto@yahoo.com',
            'child_name' => 'Kenshin Sayoto',
            'parent_name' => 'Sergio John Sayoto',
            'address' => '1084 Brightoncrest Green SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Z1G8',
            'fscd_file' => '65993',
        ],
        [
            'parent_email' => 'joel.a.shank@gmail.com',
            'child_name' => 'Austin Shank',
            'parent_name' => 'Joel Shank',
            'address' => '2-235056 Range Road 254',
            'city_province' => 'Wheatland County AB',
            'postal_code' => 'T1P 0R4',
            'fscd_file' => '68851',
        ],
        [
            'parent_email' => 'shire.stephanie@gmail.com',
            'child_name' => 'West Shire',
            'parent_name' => 'Stephanie Shire',
            'address' => '2139 32 Avenue SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2T 1W9',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'supreet982@yahoo.com',
            'child_name' => 'Gurdit Singh',
            'parent_name' => 'Supreet Kaur',
            'address' => '225 Strathcona Circle',
            'city_province' => 'Strathmore, Alberta',
            'postal_code' => 'T1P 0B1',
            'fscd_file' => '36205',
        ],
        [
            'parent_email' => 'rajubhattian@gmail.com',
            'child_name' => 'Gurfateh Singh',
            'parent_name' => 'Rajwinder Singh',
            'address' => '175 Covepark Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 5T7',
            'fscd_file' => '68299',
        ],
        [
            'parent_email' => 'gita29@gmail.com',
            'child_name' => 'Avyukt Sivakumar',
            'parent_name' => 'Gitanjali Ramamurthy',
            'address' => '247 Creekside Way SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4B9',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'todusee2u@yahoo.com',
            'child_name' => 'Ibukun Sokoya',
            'parent_name' => 'Temi Sokoya',
            'address' => '92 Howse Cresent NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3P 1L4',
            'fscd_file' => '49079',
        ],
        [
            'parent_email' => 'amgarette@gmail.com',
            'child_name' => 'Zack Lorenzo Solomon',
            'parent_name' => 'Anne Margarette Carino',
            'address' => 'Unit 1404, 115 2 Ave SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2P 3C6',
            'fscd_file' => '56663',
        ],
        [
            'parent_email' => 'ben88032@gmail.com',
            'child_name' => 'David Stacey',
            'parent_name' => 'Ben Stacey',
            'address' => '6 Somerset Court SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2Y 3H4',
            'fscd_file' => '54971',
        ],
        [
            'parent_email' => 'kaitystewart1@hotmail.com',
            'child_name' => 'Olivia Stewart',
            'parent_name' => 'Kaity Stewart',
            'address' => '113 Deerfield Terrace SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2J 6V2',
            'fscd_file' => '47370',
        ],
        [
            'parent_email' => 'rtensou@yahoo.com',
            'child_name' => 'Adonias Tesfaye',
            'parent_name' => 'Root Tensou',
            'address' => '301 - 2200 Woodview Drive SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2W 3N6',
            'fscd_file' => '68619',
        ],
        [
            'parent_email' => 'tidage2001@gmail.com',
            'child_name' => 'Christian Tilahun',
            'parent_name' => 'Tilahun Geleta',
            'address' => '213 Red Sky Way NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3N 1M8',
            'fscd_file' => '63520',
        ],
        [
            'parent_email' => 'kethrany@yahoo.com',
            'child_name' => 'Patrick Touch',
            'parent_name' => 'Rany Keth',
            'address' => '246 Springmere PL',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 1J3',
            'fscd_file' => '61581',
        ],
        [
            'parent_email' => 'jpajarin@icloud.com',
            'child_name' => 'Jomer Torregoza',
            'parent_name' => 'Jolly Pajarin',
            'address' => '55 Falshire Place NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3J 2C6',
            'fscd_file' => '63309',
        ],
        [
            'parent_email' => 'kadiesallia1964@gmail.com',
            'child_name' => 'Samuel Hindolo Vandy',
            'parent_name' => 'Khadijhatu Vandy',
            'address' => '95 Covermeadow Close NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3K 6G9',
            'fscd_file' => '62317',
        ],
        [
            'parent_email' => 'vanessa.estebanv@gmail.com',
            'child_name' => 'Caleb Vargas',
            'parent_name' => 'Vanessa Esteban',
            'address' => '109 Hunterhorn Crescent NE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2K 6H5',
            'fscd_file' => '56429',
        ],
        [
            'parent_email' => 'patibandla1987@gmail.com',
            'child_name' => 'Srihan Vasireddy',
            'parent_name' => 'Sireesha Patibandla',
            'address' => '25 Belmont Common SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4N6',
            'fscd_file' => '41337',
        ],
        [
            'parent_email' => 'sandra@waterman.cc',
            'child_name' => 'Michael Waterman',
            'parent_name' => 'Sandra Waterman',
            'address' => '208 Mahogany Terr SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T3M 0T6',
            'fscd_file' => '30420',
        ],
        [
            'parent_email' => 'kidistwold@gmail.com',
            'child_name' => 'Pharez Werku',
            'parent_name' => 'Kidist Woldu',
            'address' => '108 Viewponte Terrace',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 0R1',
            'fscd_file' => '45711',
        ],
        [
            'parent_email' => 'aesha.mumtaz@yahoo.com',
            'child_name' => 'Elias Weyessa',
            'parent_name' => 'Aesha Mumtaz',
            'address' => '4643 Memorial Drive SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 2P7',
            'fscd_file' => '65058',
        ],
        [
            'parent_email' => 'almin30@yahoo.com',
            'child_name' => 'Alsadiq Yassin',
            'parent_name' => 'Amin Brhanu',
            'address' => '1525 36 Street SE',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2A 1C5',
            'fscd_file' => '58955',
        ],
        [
            'parent_email' => 'zgafar60@gmail.com',
            'child_name' => 'Halima Arike Zakariyau',
            'parent_name' => 'Muinat Adenike Zakariyau',
            'address' => '69 Yorkville Terrace SW',
            'city_province' => 'Calgary, Alberta',
            'postal_code' => 'T2X 4X5',
            'fscd_file' => '44622',
        ],
        [
            'parent_email' => 'hiroke88@gmail.com',
            'child_name' => 'Lyanna Berry Zhu',
            'parent_name' => 'Ying Amanda Li',
            'address' => '420 Dawson Cir',
            'city_province' => 'Chestermere, Alberta',
            'postal_code' => 'T1X 2R8',
            'fscd_file' => 'Private',
        ],
        [
            'parent_email' => 'karimazamani2@gmail.com',
            'child_name' => 'Aahil Zamani',
            'parent_name' => 'Karima Mohammadi',
            'address' => '3226 Chinook Wind Dr SW',
            'city_province' => 'Airdrie, Alberta',
            'postal_code' => 'T4B 5S8',
            'fscd_file' => '42684',
        ],
    ];

    public function __construct(
        private readonly IntakeApprovalService $approvalService,
        private readonly ReferenceNumberGenerator $referenceNumbers,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->directory() as $index => $row) {
            $label = $row['child_name'] !== '' ? $row['child_name'] : "row {$index}";
            $email = $this->cleanEmail($row['parent_email']);

            if ($email === null) {
                $this->warn("Skipped {$label}: no parent email address.");
                $failed++;

                continue;
            }

            if (trim($row['child_name']) === '') {
                $this->warn("Skipped row {$index} <{$email}>: no child name.");
                $failed++;

                continue;
            }

            [$childFirstName, $childLastName] = $this->splitName($row['child_name']);

            if ($this->intakeExists($email, $childFirstName, $childLastName)) {
                $this->line("Skipped {$label}: already a client under {$email}.");
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("Would create {$label} <{$email}> ({$this->fundingLabel($row['fscd_file'])}).");
                $created++;

                continue;
            }

            $this->createClient($row, $email, $childFirstName, $childLastName);
            $this->info("Created {$label} <{$email}> ({$this->fundingLabel($row['fscd_file'])}).");
            $created++;
        }

        $verb = $this->option('dry-run') ? 'Would create' : 'Created';
        $this->newLine();
        $this->info("{$verb} {$created}, skipped {$skipped} existing, {$failed} could not be processed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{parent_email: string, child_name: string, parent_name: string, address: string, city_province: string, postal_code: string, fscd_file: string}  $row
     */
    private function createClient(array $row, string $email, string $childFirstName, string $childLastName): void
    {
        [$city, $province] = $this->splitCityProvince($row['city_province']);
        $isPrivate = $this->isPrivate($row['fscd_file']);

        $intake = Intake::query()->create([
            'child_first_name' => $childFirstName,
            'child_last_name' => $childLastName,
            'status' => 'approved',
            'reviewed' => true,
            'street_address' => trim($row['address']) !== '' ? trim($row['address']) : null,
            'city' => $city,
            'state_province' => $province,
            'postal_code' => trim($row['postal_code']) !== '' ? trim($row['postal_code']) : null,
            'funding_source' => $isPrivate ? 'private' : 'BDS-FSCD',
            'funding_number' => $isPrivate ? null : trim($row['fscd_file']),
            'primary_parent_name' => trim($row['parent_name']),
            'primary_parent_email' => $email,
            'primary_relationship_to_child' => 'Parent',
            'primary_contact_method' => 'email',
            'timeline' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'Intake imported from client directory',
                    'date' => now()->toDateString(),
                    'time' => now()->format('g:i:s A'),
                ],
            ],
            'reference_number' => $this->referenceNumbers->intake(),
        ]);

        // The approval service owns account creation, the welcome email
        // with the temporary password, the Client row and its billing
        // account, so the imported client is indistinguishable from one
        // approved through the admin UI.
        $this->approvalService->promote($intake);

        AuditLogger::log('Created client', 'Clients', "Imported client {$childFirstName} {$childLastName} under {$email} via clients:create");
    }

    /**
     * The rows to process; a seam so tests can run against their own list.
     *
     * @return array<int, array{parent_email: string, child_name: string, parent_name: string, address: string, city_province: string, postal_code: string, fscd_file: string}>
     */
    protected function directory(): array
    {
        return self::DIRECTORY;
    }

    private function intakeExists(string $email, string $childFirstName, string $childLastName): bool
    {
        return Intake::query()
            ->where('primary_parent_email', $email)
            ->where('child_first_name', $childFirstName)
            ->where('child_last_name', $childLastName)
            ->exists();
    }

    private function cleanEmail(string $value): ?string
    {
        $email = Str::lower(trim($value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    private function isPrivate(string $fscdFile): bool
    {
        $value = Str::lower(trim($fscdFile));

        return $value === '' || $value === 'private';
    }

    private function fundingLabel(string $fscdFile): string
    {
        return $this->isPrivate($fscdFile) ? 'private' : 'FSCD #'.trim($fscdFile);
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
     * The sheet writes "Calgary, Alberta", "Landon Alberta" and
     * "Wheatland County AB" interchangeably.
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
}
