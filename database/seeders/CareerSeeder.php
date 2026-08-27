<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;

/**
 * The seven contractor postings listed on `/careers`.
 *
 * Each row's `detail` payload drives the posting's detail page. Sections that
 * read identically across every posting (the application process, the
 * contractor terms, most of "What We Offer") are built here from shared
 * helpers so a rate change or a policy reword happens in one place.
 */
class CareerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->careers() as $career) {
            Career::updateOrCreate(['position' => $career['position']], $career);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function careers(): array
    {
        return [
            $this->speechLanguagePathologist(),
            $this->psychologist(),
            $this->occupationalTherapist(),
            $this->physiotherapist(),
            $this->behaviouralConsultant(),
            $this->behaviouralDevelopmentalAide(),
            $this->communityRespiteAide(),
        ];
    }

    /**
     * Fields every posting shares.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function posting(array $attributes, array $detail): array
    {
        return array_merge([
            'location' => 'Calgary & Surrounding Areas',
            'contract' => 'Independent Contractor',
            'schedule' => 'Flexible, based on availability',
            'level' => 'Contract Position',
            'is_active' => true,
            'highlights' => [],
            'required_documents' => [],
            'benefits' => [],
        ], $attributes, [
            'detail' => array_merge([
                'contractor' => $this->contractorTerms(),
                'closing_title' => 'Ready to Join Our Team?',
            ], $detail),
        ]);
    }

    /**
     * The "Independent Contractor Opportunity" block. `$policies` names the
     * policies reviewed at orientation, which vary slightly by posting.
     *
     * @return array<string, mixed>
     */
    private function contractorTerms(string $policies = 'contractor responsibilities, compensation, documentation, indirect activities, cancellations, service locations, safety procedures, and other policies'): array
    {
        return [
            'title' => 'Independent Contractor Opportunity',
            'paragraphs' => [
                'This position is offered on an independent contractor basis and is not an employee position.',
                'Contractors maintain flexibility in their availability and caseload. Hours may vary depending on client referrals, family schedules, geographic location, service needs, and contractor availability and are not guaranteed.',
                "Details regarding {$policies} are reviewed during the Contract Review and Orientation process.",
            ],
        ];
    }

    /**
     * The "What We Offer" points, with the rate line and the closing point
     * supplied per posting.
     *
     * @param  array<int, array<string, string>>  $tail
     * @return array<int, array<string, string>>
     */
    private function offers(string $rate, string $collaborators, array $tail): array
    {
        return array_merge([
            [
                'title' => 'Competitive Contract Rates',
                'description' => "Starting at {$rate}/hour, with compensation based on the services provided.",
            ],
            [
                'title' => 'Flexible Scheduling',
                'description' => 'Set your availability based on your schedule, preferred caseload, and service availability.',
            ],
            [
                'title' => 'Flexible Caseload',
                'description' => 'Caseloads are based on client referrals, family schedules, geographic location, service needs, and your availability.',
            ],
            [
                'title' => 'Multidisciplinary Collaboration',
                'description' => $collaborators,
            ],
            [
                'title' => 'Administrative Support',
                'description' => 'Receive administrative support related to service coordination, documentation processes, scheduling, and other operational needs.',
            ],
            [
                'title' => 'Professional Development',
                'description' => 'Access workshops, shared resources, learning opportunities, and professional development opportunities when available.',
            ],
        ], $tail);
    }

    /**
     * @return array<string, string>
     */
    private function flexibleServiceDelivery(): array
    {
        return [
            'title' => 'Flexible Service Delivery',
            'description' => 'Depending on the child’s needs and service arrangement, services may be provided in home, community, virtual, and other appropriate settings.',
        ];
    }

    /**
     * The FSCD participation block shared by the clinical postings.
     *
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function fscd(string $role, array $items): array
    {
        return [
            'title' => 'FSCD Services',
            'intro' => "{$role} may support children and families receiving services through Family Support for Children with Disabilities (FSCD).",
            'lead_in' => "Depending on the child’s approved services, {$role} may participate in:",
            'items' => $items,
            'closing' => "{$role} working within FSCD-funded services are expected to complete documentation, planning, and reporting requirements associated with the child’s approved services.",
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function collaboration(array $items): array
    {
        return [
            'title' => 'Multidisciplinary Collaboration',
            'intro' => 'Depending on the child’s approved services and needs, you may collaborate with:',
            'items' => $items,
            'closing' => 'Our team values open communication, shared planning, and collaboration to support consistency across goals, services, and everyday environments.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function speechLanguagePathologist(): array
    {
        return $this->posting([
            'position' => 'Speech-Language Pathologist (SLP)',
            'rate' => 'Starting at $65.44/hour',
            'hours' => 'Approximately 15–30 hours/week, based on caseload, referrals, and availability',
            'sort_order' => 1,
            'short_description' => 'Provide individualized Speech-Language Therapy to children and families across Calgary and surrounding communities.',
            'about_description' => 'Creative Abilities Therapy Services is seeking a compassionate and collaborative Speech-Language Pathologist (SLP) to provide individualized services to children and families.',
            'skills' => [
                'Speech sound development',
                'Receptive and expressive language',
                'Social communication',
                'Early communication development',
                'Functional communication',
                'Augmentative and Alternative Communication (AAC)',
                'Parent and caregiver strategies',
                'Communication within everyday routines and environments',
            ],
            'responsibilities' => [
                'Conducting appropriate speech and language assessments and observations',
                'Developing individualized communication goals and recommendations',
                'Providing direct Speech-Language Therapy services',
                'Supporting speech, language, communication, and social communication needs',
                'Supporting Augmentative and Alternative Communication (AAC), when appropriate',
                'Providing practical strategies and recommendations to parents and caregivers',
                'Completing required clinical documentation, reports, and progress updates',
                'Participating in service planning and team meetings when required',
                'Collaborating with members of the child’s multidisciplinary service team',
                'Communicating professionally with families and other appropriate service providers',
                'Providing services in home, community, and other appropriate settings',
                'Practising in accordance with applicable professional and regulatory standards',
            ],
            'qualifications' => [
                'A Master’s degree in Speech-Language Pathology or equivalent education required for professional practice',
                'Current registration and good standing with the Alberta College of Speech-Language Pathologists and Audiologists (ACSLPA)',
                'Current professional liability insurance',
                'Experience or interest in working with children and families',
                'Strong verbal and written communication skills',
                'Strong documentation and organizational skills',
                'Ability to work independently while collaborating effectively within a multidisciplinary team',
                'Commitment to individualized, family-centred, and evidence-based practice',
            ],
        ], [
            'intro' => [
                'As part of our multidisciplinary team, you will work collaboratively with families, clinicians, consultants, and support professionals to help children develop communication skills that support participation in everyday life.',
                'Our services are family-centred, individualized, and provided in home, community, and other appropriate settings throughout Calgary and surrounding communities.',
            ],
            'role_summary' => 'The Speech-Language Pathologist provides assessment, intervention, consultation, recommendations, and family support based on each child’s communication strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As a Speech-Language Pathologist with CATS, responsibilities may include:',
            'collaboration' => array_merge($this->collaboration([
                'Occupational Therapists',
                'Physiotherapists',
                'Psychologists',
                'Behavioural Consultants',
                'Behavioural & Developmental Aides',
                'Clinical Coordinators',
                'Other professionals involved in the child’s support',
            ]), [
                'closing' => 'Our team values communication, collaboration, and coordinated service planning to support consistency across goals, services, and everyday environments.',
            ]),
            'qualifications_note' => 'Experience supporting children with developmental, communication, behavioural, or complex needs is considered an asset.',
            'offers' => $this->offers('$65.44', 'Work alongside clinicians, consultants, aides, Clinical Coordinators, and other professionals supporting children and families.', [
                [
                    'title' => 'Home & Community-Based Practice',
                    'description' => 'Provide services within everyday environments throughout Calgary and surrounding communities.',
                ],
            ]),
            'fscd' => null,
            'contractor' => $this->contractorTerms('contractor responsibilities, compensation, documentation, cancellations, service locations, and other policies'),
            'closing' => 'If you are passionate about supporting children and families and value individualized, collaborative, and family-centred care, we would be happy to hear from you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function psychologist(): array
    {
        return $this->posting([
            'position' => 'Psychologist',
            'rate' => 'Starting at $63.94/hour',
            'hours' => 'Approximately 15–30 hours/week, based on caseload, referrals, and availability',
            'sort_order' => 2,
            'short_description' => 'Provide individualized psychological services supporting children’s emotional well-being, behaviour, and mental health.',
            'about_description' => 'Creative Abilities Therapy Services is seeking a compassionate and collaborative Psychologist to provide individualized psychological services to children and families.',
            'skills' => [
                'Emotional regulation',
                'Anxiety, worry, and stress',
                'Behavioural and emotional concerns',
                'Coping skills',
                'Self-esteem and confidence',
                'Social and relationship skills',
                'Problem-solving',
                'Mental health and emotional well-being',
                'Parent and caregiver strategies',
                'Consultation with multidisciplinary service teams',
            ],
            'responsibilities' => [
                'Conducting appropriate psychological assessments, observations, and clinical interviews',
                'Identifying strengths, needs, and areas for support',
                'Developing individualized goals, recommendations, and intervention strategies',
                'Providing psychological intervention and therapeutic support',
                'Supporting emotional regulation, coping, behaviour, and mental health needs',
                'Providing consultation and practical strategies to parents and caregivers',
                'Completing required clinical documentation, reports, and progress updates',
                'Participating in service planning and multidisciplinary team meetings when required',
                'Collaborating with clinicians, consultants, aides, and other service providers',
                'Communicating professionally with families and appropriate members of the child’s support team',
                'Providing consultation and guidance to Behavioural & Developmental Aides when appropriate',
                'Maintaining accurate and timely documentation',
                'Practising in accordance with applicable professional, ethical, and regulatory standards',
            ],
            'qualifications' => [
                'Graduate-level education in Psychology that meets the requirements for professional practice in Alberta',
                'Current registration and good standing with the College of Alberta Psychologists (CAP)',
                'Current professional liability insurance',
                'Experience or interest in working with children and families',
                'Knowledge of child development, emotional regulation, behaviour, and mental health',
                'Strong clinical assessment, intervention, and consultation skills',
                'Strong verbal and written communication skills',
                'Strong documentation and organizational skills',
                'Ability to work independently while collaborating effectively within a multidisciplinary team',
                'Commitment to individualized, family-centred, evidence-based, and ethical practice',
            ],
        ], [
            'intro' => [
                'As part of our multidisciplinary team, you will work collaboratively with families, clinicians, consultants, and support professionals to support children’s emotional well-being, behaviour, coping skills, development, and participation in everyday life.',
                'Our services are family-centred, individualized, and provided in home, community, virtual, and other appropriate settings throughout Calgary and surrounding communities.',
            ],
            'role_summary' => 'The Psychologist provides psychological assessment, intervention, consultation, recommendations, and family support based on each child’s strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As a Psychologist with CATS, responsibilities may include:',
            'collaboration' => $this->collaboration([
                'Occupational Therapists',
                'Speech-Language Pathologists',
                'Physiotherapists',
                'Behavioural Consultants',
                'Behavioural & Developmental Aides',
                'Clinical Coordinators',
                'Other professionals involved in the child’s support',
            ]),
            'qualifications_note' => 'Experience supporting children with developmental, behavioural, emotional, or complex needs is considered an asset.',
            'offers' => $this->offers('$63.94', 'Work alongside Occupational Therapists, Speech-Language Pathologists, Physiotherapists, Behavioural Consultants, Behavioural & Developmental Aides, Clinical Coordinators, and other professionals.', [
                $this->flexibleServiceDelivery(),
            ]),
            'fscd' => $this->fscd('Psychologists', [
                'Behavioural and Developmental Support (BDS)',
                'Specialized Services (SS)',
                'Service planning and goal development',
                'Consultation with families and service teams',
                'Progress monitoring and documentation',
                'Multidisciplinary team meetings',
                'Guidance and recommendations for Behavioural & Developmental Aides',
            ]),
            'closing' => 'If you are passionate about supporting children and families and value collaborative, individualized, and family-centred psychological care, we would be happy to hear from you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function occupationalTherapist(): array
    {
        return $this->posting([
            'position' => 'Occupational Therapist (OT)',
            'rate' => 'Starting at $61.59/hour',
            'hours' => 'Approximately 15–30 hours/week, based on caseload, referrals, and availability',
            'sort_order' => 3,
            'short_description' => 'Help children build the skills that support participation, independence, regulation, and everyday functioning.',
            'about_description' => 'Creative Abilities Therapy Services is seeking a compassionate and collaborative Occupational Therapist (OT) to provide individualized services to children and families.',
            'skills' => [
                'Sensory processing',
                'Emotional and self-regulation',
                'Fine motor skills',
                'Gross motor coordination',
                'Motor planning',
                'Daily living skills',
                'Self-care',
                'Play and participation',
                'School participation',
                'Independence in everyday routines',
                'Parent and caregiver strategies',
            ],
            'responsibilities' => [
                'Conducting appropriate Occupational Therapy assessments and observations',
                'Identifying strengths, needs, and areas for support',
                'Developing individualized goals and recommendations',
                'Providing direct Occupational Therapy intervention',
                'Supporting sensory processing, regulation, motor development, daily living skills, and participation',
                'Developing practical strategies for parents and caregivers',
                'Completing required clinical documentation, reports, and progress updates',
                'Participating in service planning and multidisciplinary team meetings when required',
                'Collaborating with clinicians, consultants, aides, and other service providers',
                'Communicating professionally with families and appropriate members of the child’s support team',
                'Providing guidance and recommendations to Behavioural & Developmental Aides when appropriate',
                'Providing services in home, community, virtual, and other appropriate settings',
                'Practising in accordance with applicable professional and regulatory standards',
            ],
            'qualifications' => [
                'A degree in Occupational Therapy that meets the requirements for professional practice in Alberta',
                'Current registration and good standing with the Alberta College of Occupational Therapists (ACOT)',
                'Current professional liability insurance',
                'Experience or interest in working with children and families',
                'Knowledge of child development, sensory processing, regulation, motor development, and everyday participation',
                'Strong assessment, intervention, and consultation skills',
                'Strong verbal and written communication skills',
                'Strong documentation and organizational skills',
                'Ability to work independently while collaborating effectively within a multidisciplinary team',
                'Commitment to individualized, family-centred, evidence-based, and ethical practice',
            ],
        ], [
            'intro' => [
                'As part of our multidisciplinary team, you will work collaboratively with families, clinicians, consultants, and support professionals to help children build skills that support participation, independence, regulation, and everyday functioning.',
                'Our services are family-centred, individualized, and provided in home, community, virtual, and other appropriate settings throughout Calgary and surrounding communities.',
            ],
            'role_summary' => 'The Occupational Therapist provides assessment, intervention, consultation, recommendations, and family support based on each child’s strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As an Occupational Therapist with CATS, responsibilities may include:',
            'collaboration' => $this->collaboration([
                'Speech-Language Pathologists',
                'Physiotherapists',
                'Psychologists',
                'Behavioural Consultants',
                'Behavioural & Developmental Aides',
                'Clinical Coordinators',
                'Other professionals involved in the child’s support',
            ]),
            'qualifications_note' => 'Experience supporting children with developmental, sensory, behavioural, feeding, motor, or complex needs is considered an asset.',
            'offers' => $this->offers('$61.59', 'Work alongside Speech-Language Pathologists, Physiotherapists, Psychologists, Behavioural Consultants, Behavioural & Developmental Aides, Clinical Coordinators, and other professionals.', [
                $this->flexibleServiceDelivery(),
            ]),
            'fscd' => $this->fscd('Occupational Therapists', [
                'Behavioural and Developmental Support (BDS)',
                'Specialized Services (SS)',
                'Service planning and goal development',
                'Assessment and intervention',
                'Consultation with families and service teams',
                'Progress monitoring and documentation',
                'Multidisciplinary team meetings',
                'Guidance and recommendations for Behavioural & Developmental Aides',
            ]),
            'closing' => 'If you are passionate about supporting children and families and value individualized, collaborative, and family-centred Occupational Therapy, we would be happy to hear from you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function physiotherapist(): array
    {
        return $this->posting([
            'position' => 'Physiotherapist (PT)',
            'rate' => 'Starting at $53.73/hour',
            'hours' => 'Approximately 15–30 hours/week, based on caseload, referrals, and availability',
            'sort_order' => 4,
            'short_description' => 'Support children in developing movement, mobility, strength, coordination, and physical confidence.',
            'about_description' => 'Creative Abilities Therapy Services is seeking a compassionate and collaborative Physiotherapist (PT) to provide individualized services to children and families.',
            'skills' => [
                'Gross motor development',
                'Strength and endurance',
                'Balance and coordination',
                'Mobility',
                'Motor planning',
                'Posture and positioning',
                'Body awareness',
                'Walking and movement skills',
                'Physical participation',
                'Recreation and play',
                'Independence in everyday activities',
                'Parent and caregiver strategies',
            ],
            'responsibilities' => [
                'Conducting appropriate Physiotherapy assessments and observations',
                'Identifying physical strengths, needs, and areas for support',
                'Developing individualized goals and recommendations',
                'Providing direct Physiotherapy intervention',
                'Supporting gross motor development, strength, balance, coordination, mobility, and physical participation',
                'Developing practical activities and strategies for parents and caregivers',
                'Completing required clinical documentation, reports, and progress updates',
                'Participating in service planning and multidisciplinary team meetings when required',
                'Collaborating with clinicians, consultants, aides, and other service providers',
                'Communicating professionally with families and appropriate members of the child’s support team',
                'Providing guidance and recommendations to Behavioural & Developmental Aides when appropriate',
                'Providing services in home, community, virtual, and other appropriate settings',
                'Practising in accordance with applicable professional and regulatory standards',
            ],
            'qualifications' => [
                'A degree in Physiotherapy that meets the requirements for professional practice in Alberta',
                'Current registration and good standing with the College of Physiotherapists of Alberta (CPTA)',
                'Current professional liability insurance',
                'Experience or interest in working with children and families',
                'Knowledge of child development, gross motor development, mobility, balance, coordination, and physical participation',
                'Strong assessment, intervention, and consultation skills',
                'Strong verbal and written communication skills',
                'Strong documentation and organizational skills',
                'Ability to work independently while collaborating effectively within a multidisciplinary team',
                'Commitment to individualized, family-centred, evidence-based, and ethical practice',
            ],
        ], [
            'intro' => [
                'As part of our multidisciplinary team, you will work collaboratively with families, clinicians, consultants, and support professionals to help children develop movement, mobility, strength, coordination, and physical skills that support participation and independence in everyday life.',
                'Our services are family-centred, individualized, and provided in home, community, virtual, and other appropriate settings throughout Calgary and surrounding communities.',
            ],
            'role_summary' => 'The Physiotherapist provides assessment, intervention, consultation, recommendations, and family support based on each child’s physical strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As a Physiotherapist with CATS, responsibilities may include:',
            'collaboration' => $this->collaboration([
                'Occupational Therapists',
                'Speech-Language Pathologists',
                'Psychologists',
                'Behavioural Consultants',
                'Behavioural & Developmental Aides',
                'Clinical Coordinators',
                'Other professionals involved in the child’s support',
            ]),
            'qualifications_note' => 'Experience supporting children with developmental, physical, motor, mobility, or complex needs is considered an asset.',
            'offers' => $this->offers('$53.73', 'Work alongside Occupational Therapists, Speech-Language Pathologists, Psychologists, Behavioural Consultants, Behavioural & Developmental Aides, Clinical Coordinators, and other professionals.', [
                $this->flexibleServiceDelivery(),
            ]),
            'fscd' => $this->fscd('Physiotherapists', [
                'Behavioural and Developmental Support (BDS)',
                'Specialized Services (SS)',
                'Service planning and goal development',
                'Assessment and intervention',
                'Consultation with families and service teams',
                'Progress monitoring and documentation',
                'Multidisciplinary team meetings',
                'Guidance and recommendations for Behavioural & Developmental Aides',
            ]),
            'closing' => 'If you are passionate about supporting children and families and value individualized, collaborative, and family-centred Physiotherapy, we would be happy to hear from you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function behaviouralConsultant(): array
    {
        return $this->posting([
            'position' => 'Behavioural Consultant/Therapist (BC)',
            'rate' => 'Starting at $50.21/hour',
            'hours' => 'Approximately 15–30 hours/week, based on caseload, referrals, and availability',
            'sort_order' => 5,
            'short_description' => 'Provide behavioural consultation, service planning, and practical strategies for children, families, and support teams.',
            'about_description' => 'Creative Abilities Therapy Services is seeking a compassionate and collaborative Behavioural Consultant/Therapist to provide individualized behavioural and developmental support to children and families.',
            'skills' => [
                'Emotional regulation',
                'Behavioural concerns',
                'Communication and self-advocacy',
                'Social skills',
                'Daily living skills',
                'Independence',
                'Routines and transitions',
                'Coping and problem-solving',
                'Positive behaviour support',
                'Parent and caregiver strategies',
                'Behavioural & Developmental Aide guidance',
                'Team consultation and service planning',
            ],
            'responsibilities' => [
                'Gathering information about the child’s strengths, needs, routines, and behavioural concerns',
                'Conducting appropriate observations and behavioural assessments within your scope of practice',
                'Identifying factors that may influence behaviour and participation',
                'Developing individualized behavioural goals, recommendations, and strategies',
                'Supporting positive behaviour, emotional regulation, communication, and skill development',
                'Providing consultation and guidance to parents and caregivers',
                'Developing practical strategies that can be incorporated into everyday routines',
                'Providing direction and recommendations to Behavioural & Developmental Aides',
                'Monitoring implementation of behavioural strategies and reviewing progress',
                'Participating in service planning and multidisciplinary team meetings',
                'Completing required documentation, reports, and progress updates',
                'Collaborating with clinicians and other professionals involved in the child’s services',
                'Communicating professionally with families and appropriate members of the service team',
                'Practising within your professional scope, training, and applicable standards',
            ],
            'qualifications' => [
                'Relevant post-secondary education and professional preparation appropriate to the Behavioural Consultant/Therapist role',
                'Education, training, and experience that support competent behavioural consultation within the applicant’s scope of practice',
                'Appropriate professional registration or credentials when required by the applicant’s profession',
                'Current professional liability insurance, when applicable',
                'Experience working with children and families',
                'Knowledge of child development, behaviour, emotional regulation, and positive behavioural support',
                'Strong consultation, communication, and collaboration skills',
                'Strong documentation and organizational skills',
                'Ability to work independently while collaborating effectively within a multidisciplinary team',
                'Ability to provide guidance and support to Behavioural & Developmental Aides',
                'Commitment to individualized, family-centred, respectful, and evidence-informed practice',
            ],
        ], [
            'intro' => [
                'As part of our multidisciplinary team, you will work collaboratively with families, clinicians, Behavioural & Developmental Aides, and other professionals to better understand behaviour, develop practical strategies, and support meaningful goals in everyday life.',
                'Our services are family-centred, individualized, and provided in home, community, virtual, and other appropriate settings throughout Calgary and surrounding communities.',
            ],
            'role_summary' => 'The Behavioural Consultant/Therapist provides behavioural consultation, assessment, service planning, intervention strategies, family guidance, and team support based on each child’s strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As a Behavioural Consultant/Therapist with CATS, responsibilities may include:',
            'collaboration' => array_merge($this->collaboration([
                'Psychologists',
                'Occupational Therapists',
                'Speech-Language Pathologists',
                'Physiotherapists',
                'Behavioural & Developmental Aides',
                'Clinical Coordinators',
                'Other professionals involved in the child’s support',
            ]), [
                'closing' => 'Our team values communication, shared planning, and coordinated service delivery to help create consistency across goals, strategies, and everyday environments.',
            ]),
            'qualifications_note' => 'Experience supporting children with developmental, behavioural, communication, emotional, or complex needs is considered an asset.',
            'offers' => $this->offers('$50.21', 'Work alongside Psychologists, Occupational Therapists, Speech-Language Pathologists, Physiotherapists, Behavioural & Developmental Aides, Clinical Coordinators, and other professionals.', [
                $this->flexibleServiceDelivery(),
            ]),
            'extras' => [
                [
                    'title' => 'Behavioural & Developmental Aide Collaboration',
                    'paragraphs' => [
                        'Behavioural Consultants play an important role in supporting the work of Behavioural & Developmental Aides.',
                    ],
                    'lead_in' => 'Responsibilities may include:',
                    'items' => [
                        'Developing and reviewing behavioural strategies',
                        'Providing recommendations and guidance to aides',
                        'Demonstrating appropriate support approaches',
                        'Reviewing observations and progress',
                        'Supporting consistency in implementation',
                        'Adjusting strategies when appropriate',
                        'Collaborating with families and other members of the service team',
                    ],
                    'closing' => 'This helps connect behavioural planning with practical support during the child’s everyday routines and activities.',
                ],
            ],
            'fscd' => array_merge($this->fscd('Behavioural Consultants', [
                'Behavioural and Developmental Support (BDS)',
                'Specialized Services (SS)',
                'Service Providers Program Plans (SPPP)',
                'Individualized Service Plans (ISP), when applicable',
                'Behavioural assessment and consultation',
                'Goal and strategy development',
                'Family consultation',
                'Behavioural & Developmental Aide guidance',
                'Progress monitoring and documentation',
                'Multidisciplinary team meetings',
                'Collaboration with Clinical Coordinators and other service providers',
            ]), [
                'closing' => 'Behavioural Consultants working within FSCD-funded services are expected to complete the documentation, planning, reporting, and collaboration requirements associated with the child’s approved services.',
            ]),
            'closing' => 'If you are passionate about supporting children and families and value individualized, collaborative, and family-centred behavioural support, we would be happy to hear from you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function behaviouralDevelopmentalAide(): array
    {
        return $this->posting([
            'position' => 'Behavioural & Developmental Aide',
            'rate' => 'Starting at $20.28/hour',
            'hours' => 'Approximately 9–15 hours/week, based on caseload, referrals, family schedules, and availability',
            'sort_order' => 6,
            'short_description' => 'Work directly with children to practise developmental, behavioural, communication, social, and everyday living skills.',
            'about_description' => 'Creative Abilities Therapy Services is seeking compassionate, dependable, and collaborative Behavioural & Developmental Aides to provide individualized support to children and families.',
            'skills' => [
                'Emotional regulation',
                'Behavioural goals',
                'Communication skills',
                'Social interaction',
                'Play and engagement',
                'Daily living skills',
                'Independence',
                'Routines and transitions',
                'Community participation',
                'Confidence and self-advocacy',
                'Practising strategies recommended by clinicians or consultants',
                'Generalizing skills across everyday environments',
            ],
            'responsibilities' => [
                'Providing individualized direct support to assigned children',
                'Building positive and respectful relationships with children and families',
                'Supporting developmental, behavioural, communication, social, and everyday living goals',
                'Implementing appropriate strategies recommended by the child’s clinicians or consultants',
                'Using play, everyday routines, and meaningful activities to support skill development',
                'Supporting emotional regulation, participation, and independence',
                'Encouraging appropriate communication and self-advocacy',
                'Supporting children during transitions and everyday routines',
                'Observing and documenting relevant progress during sessions',
                'Communicating regularly with families and appropriate members of the service team',
                'Participating in team meetings, planning, or consultation when required',
                'Completing required documentation and timesheets accurately and on time',
                'Maintaining professional boundaries and confidentiality',
                'Following CATS policies, service plans, and applicable safety procedures',
                'Providing services in home and community settings as assigned',
            ],
            'qualifications' => [
                'Experience or a strong interest in working with children with developmental, behavioural, or disability-related support needs',
                'A compassionate, respectful, and child-centred approach',
                'Strong communication and interpersonal skills',
                'Reliability, professionalism, and good time management',
                'Ability to follow individualized service plans, strategies, and professional recommendations',
                'Ability to work independently while collaborating with families and multidisciplinary teams',
                'Strong observation and documentation skills',
                'Ability to maintain confidentiality and professional boundaries',
                'Willingness to provide services in family homes and community settings',
                'Commitment to individualized, family-centred, and respectful support',
            ],
        ], [
            'intro' => [
                'As part of a child’s service team, you will work directly with children to help them practise developmental, behavioural, communication, social, and everyday living skills within familiar routines and environments.',
                'Behavioural & Developmental Aides work collaboratively with families and appropriate clinicians or consultants to support individualized goals and strategies.',
            ],
            'role_summary' => 'The Behavioural & Developmental Aide provides hands-on support to children based on their individual strengths, needs, goals, and service plan.',
            'responsibilities_lead_in' => 'As a Behavioural & Developmental Aide with CATS, responsibilities may include:',
            'collaboration' => [
                'title' => 'Working with the Service Team',
                'intro' => 'Behavioural & Developmental Aides may work as part of a multidisciplinary team. Depending on the child’s approved services, the team may include:',
                'items' => [
                    'Behavioural Consultants',
                    'Psychologists',
                    'Occupational Therapists',
                    'Speech-Language Pathologists',
                    'Physiotherapists',
                    'Clinical Coordinators',
                    'Other professionals involved in the child’s support',
                ],
                'closing' => 'Aides help provide practical opportunities for children to practise identified strategies and skills between clinical or consulting sessions. Strategies introduced by clinicians or consultants may be incorporated into everyday activities, and aides share relevant observations with families and the service team to support ongoing planning and review.',
            ],
            'qualifications_lead_in' => 'We are looking for applicants who demonstrate:',
            'qualifications_note' => 'Education or experience in child development, psychology, education, disability studies, social services, behavioural support, health care, or a related field is considered an asset. Experience supporting neurodiverse children or children with developmental and behavioural needs is also considered an asset.',
            'offers' => $this->offers('$20.28', 'Work alongside Behavioural Consultants, Psychologists, Occupational Therapists, Speech-Language Pathologists, Physiotherapists, Clinical Coordinators, and other professionals.', [
                [
                    'title' => 'Meaningful Community-Based Work',
                    'description' => 'Support children in environments where everyday skills can be practised, including family homes and community settings.',
                ],
            ]),
            'fscd' => [
                'title' => 'FSCD Services',
                'intro' => 'Behavioural & Developmental Aides may support children and families receiving services through Family Support for Children with Disabilities (FSCD).',
                'lead_in' => 'Depending on the child’s approved services, aides may work within:',
                'items' => [
                    'Behavioural and Developmental Support (BDS) — aides provide hands-on support based on the child’s identified behavioural and developmental goals and may work alongside up to two clinicians.',
                    'Specialized Services (SS) — aides may work as part of a more intensive multidisciplinary team that includes a Clinical Coordinator and multiple clinicians.',
                ],
                'closing' => 'In both service models, the aide supports the child in practising strategies and developing skills within everyday routines and environments.',
            ],
            'extras' => [
                [
                    'title' => 'Documentation & Team Communication',
                    'paragraphs' => [
                        'Behavioural & Developmental Aides are responsible for completing required service documentation and maintaining communication with appropriate members of the child’s team.',
                    ],
                    'lead_in' => 'This may include:',
                    'items' => [
                        'Session documentation',
                        'Progress observations',
                        'Timesheets',
                        'Participation in team communication',
                        'Reviewing recommendations and strategies',
                        'Communicating relevant updates or concerns',
                        'Supporting required FSCD documentation processes when applicable',
                    ],
                    'closing' => 'Specific documentation expectations are reviewed during onboarding and the Contract Review and Orientation.',
                ],
            ],
            'contractor' => $this->contractorTerms('contractor responsibilities, compensation, direct and indirect service activities, documentation, cancellations, service locations, safety procedures, and other policies'),
            'closing' => 'If you are passionate about supporting children as they build skills, confidence, participation, and independence, we would be happy to hear from you.',
        ]);
    }

    /**
     * The listing copy for this posting was supplied, but not a full detail
     * write-up, so it carries only the shared sections for now.
     *
     * @return array<string, mixed>
     */
    private function communityRespiteAide(): array
    {
        return $this->posting([
            'position' => 'Community & Respite Aide',
            'rate' => 'Starting at $15.01/hour',
            'hours' => 'Approximately 9–15 hours/week, based on caseload and availability',
            'sort_order' => 7,
            'short_description' => 'Support children in community and recreational settings while providing families with respite.',
            'about_description' => 'Creative Abilities Therapy Services is seeking compassionate and dependable Community & Respite Aides to support children in community and recreational settings while providing families and caregivers with additional support.',
            'skills' => [],
            'responsibilities' => [],
            'qualifications' => [],
        ], [
            'intro' => [],
            'offers' => $this->offers('$15.01', 'Work alongside Behavioural Consultants, clinicians, Clinical Coordinators, and other professionals supporting children and families.', [
                [
                    'title' => 'Meaningful Community-Based Work',
                    'description' => 'Support children in environments where everyday skills can be practised, including family homes and community settings.',
                ],
            ]),
            'fscd' => null,
            'closing' => 'If you are passionate about supporting children as they participate in their community, we would be happy to hear from you.',
        ]);
    }
}
