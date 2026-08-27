/**
 * The seven public services and the full copy for their detail pages.
 *
 * This is static marketing content: `/services` and `/servicesDetails/{code}`
 * render straight from here with no database round-trip. Sections are all
 * optional, so a service simply leaves out one it has nothing to say about.
 */

/** A titled paragraph — the shape most detail sections take. */
export interface TitledPoint {
    title: string;
    description: string;
}

export interface PointSection {
    title?: string;
    intro?: string;
    items: TitledPoint[];
}

export interface StepSection {
    intro?: string;
    steps: TitledPoint[];
}

/** A section whose body is a bullet list rather than titled points. */
export interface ListSection {
    title?: string;
    intro?: string;
    lead_in?: string | null;
    items: string[];
    closing?: string | null;
}

export interface ExtraSection extends ListSection {
    paragraphs?: string[];
}

export interface FundingOption {
    title: string;
    paragraphs: string[];
}

export interface ServiceDetail {
    how_we_help?: PointSection;
    what_to_expect?: StepSection;
    family_centred?: PointSection;
    who_may_benefit?: ListSection;
    approach?: TitledPoint[];
    settings?: PointSection;
    collaboration?: ListSection;
    extras?: ExtraSection[];
    funding?: { intro?: string; items: FundingOption[] };
    cta?: { title: string; paragraphs: string[] };
}

export interface ServiceEntry {
    /** Doubles as the `/services#CODE` anchor and the detail-page route key. */
    code: string;
    name: string;
    tagline: string;
    /** Card copy on the services index. */
    summary: string;
    /** Opening copy on the detail page. */
    description: string;
    photo: string;
    detail: ServiceDetail;
}

export const ServiceList: ServiceEntry[] = [
    {
        name: 'Speech-Language Therapy',
        tagline: 'Supporting Communication, Connection & Participation',
        code: 'SLTS-202',
        summary:
            'Our Speech-Language Therapy services provide individualized support for children experiencing challenges with speech, language, communication, and social communication. Our Speech-Language Pathologists work collaboratively with families to identify strengths, needs, and meaningful goals that support communication in everyday life.',
        description:
            'Our Speech-Language Therapy services provide individualized support for children with speech, language, communication, and social communication needs. Our Speech-Language Pathologists work collaboratively with families to understand each child’s strengths, needs, and goals while supporting communication in everyday routines, relationships, and activities.',
        photo: '/images/1920x1080/photo_baby_love_1920x1080.jpg.jpg',
        detail: {
            how_we_help: {
                intro: 'Speech-Language Therapy can support children in developing communication skills that help them express themselves, understand others, build relationships, and participate more fully in everyday life.',
                items: [
                    {
                        title: 'Speech Sounds',
                        description:
                            'Supporting children with the development and production of speech sounds to improve clarity and help them communicate more effectively.',
                    },
                    {
                        title: 'Understanding Language',
                        description:
                            'Supporting children in understanding words, directions, questions, concepts, and information communicated by others.',
                    },
                    {
                        title: 'Expressive Language',
                        description:
                            'Helping children develop the words, sentences, and communication skills needed to express their wants, needs, thoughts, ideas, and feelings.',
                    },
                    {
                        title: 'Social Communication',
                        description:
                            'Supporting conversation, interaction, turn-taking, understanding social cues, and communicating with others in different situations.',
                    },
                    {
                        title: 'Early Communication Development',
                        description:
                            'Supporting early communication skills such as gestures, sounds, shared attention, imitation, words, and interaction.',
                    },
                    {
                        title: 'Augmentative & Alternative Communication (AAC)',
                        description:
                            'Supporting children who use or may benefit from additional ways to communicate, including pictures, communication boards, and speech-generating devices.',
                    },
                    {
                        title: 'Functional Communication',
                        description:
                            'Helping children use communication skills during everyday routines, activities, relationships, and community participation.',
                    },
                ],
                title: 'How Speech-Language Therapy Can Help',
            },
            what_to_expect: {
                intro: 'Speech-Language Therapy is individualized based on your child’s age, communication strengths, needs, goals, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s communication, strengths, daily routines, family concerns, and the goals that are important to you.',
                    },
                    {
                        title: 'Observation & Assessment',
                        description:
                            'Depending on your child’s needs and the service being provided, the Speech-Language Pathologist may use observation, play-based activities, informal assessment, and appropriate clinical tools to better understand areas of strength and support.',
                    },
                    {
                        title: 'Individualized Goals',
                        description:
                            'Goals are developed collaboratively with families and focus on communication skills that are meaningful and relevant to your child’s everyday life.',
                    },
                    {
                        title: 'Therapy & Skill Development',
                        description:
                            'Sessions may include play-based activities, structured practice, communication opportunities, modelling, and individualized strategies designed to support your child’s goals.',
                    },
                    {
                        title: 'Family Strategies & Support',
                        description:
                            'Parents and caregivers may receive practical strategies and recommendations that can be used during everyday routines, play, meals, reading, and family interactions.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Your child’s progress is reviewed over time, and goals or strategies may be adjusted as their communication strengths, needs, and skills develop.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in a child’s communication development. We work collaboratively with parents and caregivers to ensure that goals and strategies are practical, meaningful, and relevant to everyday life.',
                items: [
                    {
                        title: 'Collaborative Goal Setting',
                        description:
                            'Families are involved in identifying communication priorities and developing goals that reflect their child’s needs and family routines.',
                    },
                    {
                        title: 'Practical Strategies',
                        description:
                            'We provide strategies that can be incorporated into everyday activities and interactions to create natural opportunities for communication development.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Our Speech-Language Pathologists communicate with families about progress, recommendations, strategies, and adjustments throughout services.',
                    },
                    {
                        title: 'Collaborative Care',
                        description:
                            'When appropriate and with family consent, we may collaborate with other professionals involved in your child’s care to support consistency across services and environments.',
                    },
                ],
                title: 'Family-Centred Speech-Language Therapy',
            },
            who_may_benefit: {
                intro: 'Speech-Language Therapy may be helpful for children who need additional support with speech, language, communication, or participation in everyday interactions.',
                items: [
                    'Speech sound development',
                    'Being understood by others',
                    'Understanding words, questions, or directions',
                    'Expressing wants, needs, thoughts, and feelings',
                    'Building vocabulary and sentence development',
                    'Early communication skills',
                    'Social communication and conversation',
                    'Communicating during everyday routines',
                    'Using gestures, pictures, or other communication methods',
                    'Augmentative and Alternative Communication (AAC)',
                    'Building confidence when communicating with others',
                ],
                closing:
                    'Every child communicates differently. Our team works with families to better understand their child’s communication needs and determine appropriate areas for support.',
                lead_in: 'Children may benefit from support with:',
            },
            approach: [
                {
                    title: 'Child-Centred Care',
                    description:
                        'Support is designed around each child’s strengths, communication needs, interests, developmental stage, and goals.',
                },
                {
                    title: 'Evidence-Based Practice',
                    description:
                        'Our Speech-Language Pathologists use approaches informed by current research, best practices, and applicable professional standards.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners throughout goal setting, intervention, and ongoing communication.',
                },
                {
                    title: 'Functional Communication',
                    description:
                        'We focus on communication skills that can make a meaningful difference in everyday life at home, in the community, and during interactions with others.',
                },
                {
                    title: 'Play-Based Learning',
                    description:
                        'When appropriate, we use engaging and play-based activities to create motivating opportunities for communication, interaction, and skill development.',
                },
                {
                    title: 'Individualized Support',
                    description:
                        'Strategies and activities are adapted to reflect each child’s communication style, strengths, needs, and goals.',
                },
            ],
            settings: {
                intro: 'Speech-Language Therapy may be available in different settings depending on the child’s needs, goals, clinician, and service plan.',
                items: [
                    {
                        title: 'In-Home',
                        description:
                            'Services may be provided in the home, allowing communication strategies and skills to be incorporated into familiar routines and activities.',
                    },
                    {
                        title: 'Community-Based',
                        description:
                            'Sessions may take place in appropriate community settings when this supports the child’s communication and participation goals.',
                    },
                    {
                        title: 'Virtual',
                        description:
                            'Secure virtual sessions may be available when appropriate for the child, family, and type of service being provided.',
                    },
                    {
                        title: 'School or Childcare Collaboration',
                        description:
                            'When appropriate and with family consent, Speech-Language Pathologists may collaborate with schools, childcare providers, or other professionals involved in the child’s support.',
                    },
                ],
                title: 'Where Speech-Language Therapy May Be Provided',
            },
            collaboration: {
                intro: 'When appropriate and with family consent, our Speech-Language Pathologists may work collaboratively with other members of your child’s service team.',
                items: [
                    'Occupational Therapists',
                    'Physiotherapists',
                    'Psychologists',
                    'Behavioural Consultants',
                    'Behavioural & Developmental Aides',
                    'Other professionals involved in your child’s care',
                ],
                title: 'Collaboration with Your Child’s Support Team',
                closing:
                    'Collaboration can help promote consistency and support communication goals across services and everyday environments.',
                lead_in: 'This may include:',
            },
            extras: [
                {
                    items: [
                        'Wants and needs',
                        'Choices and preferences',
                        'Thoughts and ideas',
                        'Feelings',
                        'Questions',
                        'Social messages',
                        'Participation in everyday activities and interactions',
                    ],
                    title: 'Augmentative & Alternative Communication (AAC)',
                    closing:
                        'The goal is to support each child in having meaningful and effective ways to communicate.',
                    lead_in: 'AAC can support children in expressing:',
                    paragraphs: [
                        'Some children communicate using methods in addition to or instead of spoken language. Augmentative and Alternative Communication, commonly known as AAC, may include pictures, symbols, communication boards, or speech-generating devices.',
                        'When appropriate, Speech-Language Pathologists can support children and families in developing effective ways to use AAC as part of everyday communication.',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Speech-Language Therapy through different funding and payment options depending on eligibility, service needs, and available coverage.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Speech-Language Therapy services may be available through Family Support for Children with Disabilities (FSCD) when included within the family’s approved service plan.',
                            'For eligible FSCD-funded services, Creative Abilities Therapy Services provides direct billing at FSCD-approved rates.',
                        ],
                    },
                    {
                        title: 'Private Insurance',
                        paragraphs: [
                            'Some extended health benefit plans may provide coverage for eligible Speech-Language Therapy services. Coverage varies by insurance provider and individual plan.',
                            'We provide appropriate invoices or receipts for insurance submission.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also access Speech-Language Therapy privately when services are not funded through FSCD or insurance.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If you have concerns about your child’s speech, language, communication, or social communication, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s concerns, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Psychology Services & Counselling',
        tagline: 'Supporting Emotional Well-Being, Behaviour & Mental Health',
        code: 'BCCPS-303',
        summary:
            'Our Psychology and Counselling services provide individualized support for children and families experiencing emotional, behavioural, social, or mental health concerns. We work collaboratively with families to better understand each child’s needs and provide strategies and support that promote emotional well-being, confidence, and participation.',
        description:
            'Our Psychology and Counselling services provide individualized support for children and families experiencing emotional, behavioural, social, or mental health concerns. We work collaboratively with families to understand each child’s strengths, needs, and goals while supporting emotional well-being, confidence, coping skills, and participation in everyday life.',
        photo: '/images/1920x1080/photo_nyl_1920x1080.jpg.jpg',
        detail: {
            how_we_help: {
                intro: 'Psychology and Counselling services can support children and families in understanding emotions, developing practical coping strategies, strengthening relationships, and navigating challenges that may affect everyday life.',
                items: [
                    {
                        title: 'Emotional Regulation',
                        description:
                            'Supporting children in recognizing, understanding, and managing emotions in ways that promote well-being and participation.',
                    },
                    {
                        title: 'Anxiety & Worry',
                        description:
                            'Helping children develop strategies to understand and manage worries, fears, stress, and anxious feelings.',
                    },
                    {
                        title: 'Behavioural & Emotional Concerns',
                        description:
                            'Exploring factors that may influence behaviour and emotions while developing individualized strategies that support the child and family.',
                    },
                    {
                        title: 'Coping Skills',
                        description:
                            'Helping children develop practical tools for managing challenges, changes, frustrations, and difficult emotions.',
                    },
                    {
                        title: 'Self-Esteem & Confidence',
                        description:
                            'Supporting children in recognizing their strengths, developing a positive sense of self, and building confidence in everyday situations.',
                    },
                    {
                        title: 'Social & Relationship Skills',
                        description:
                            'Supporting skills related to communication, relationships, social understanding, problem-solving, and navigating interactions with others.',
                    },
                    {
                        title: 'Family Support',
                        description:
                            'Working collaboratively with parents and caregivers to better understand their child’s needs and develop practical strategies that can be used at home and in everyday routines.',
                    },
                ],
                title: 'How Psychology Services & Counselling Can Help',
            },
            what_to_expect: {
                intro: 'Psychology and Counselling services are individualized based on your child’s age, strengths, needs, concerns, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your concerns, your child’s strengths and needs, everyday routines, and the goals that are important to your family.',
                    },
                    {
                        title: 'Understanding Your Child’s Needs',
                        description:
                            'Depending on the service being provided, the clinician may use conversation, observation, questionnaires, clinical tools, or other appropriate methods to better understand your child’s needs.',
                    },
                    {
                        title: 'Individualized Goals',
                        description:
                            'Goals are developed collaboratively with families and are based on the areas of support that are most meaningful for your child and family.',
                    },
                    {
                        title: 'Individualized Support',
                        description:
                            'Sessions may include therapeutic conversations, play-based activities, skill-building, emotional regulation strategies, coping strategies, behavioural support, and other approaches appropriate to your child’s needs.',
                    },
                    {
                        title: 'Family Strategies & Guidance',
                        description:
                            'Parents and caregivers may receive practical strategies, resources, and recommendations to help support their child between sessions and within everyday routines.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Goals and strategies are reviewed over time and may be adjusted as your child’s strengths, needs, and progress change.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in a child’s emotional and behavioural development. We work collaboratively with parents and caregivers to make sure support reflects each child’s needs while also considering family priorities, routines, and values.',
                items: [
                    {
                        title: 'Collaborative Goal Setting',
                        description:
                            'Families are involved in identifying priorities and developing goals that are meaningful to their child’s everyday life.',
                    },
                    {
                        title: 'Practical Strategies',
                        description:
                            'We provide strategies that families can use during everyday routines, interactions, and challenging situations.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Families are kept informed about recommendations, strategies, and progress throughout services.',
                    },
                    {
                        title: 'Collaborative Care',
                        description:
                            'When appropriate and with consent, clinicians may collaborate with other professionals involved in your child’s support to promote consistency across services.',
                    },
                ],
                title: 'Family-Centred Psychology & Counselling',
            },
            who_may_benefit: {
                intro: 'Psychology or Counselling services may be helpful for children and families seeking support with:',
                items: [
                    'Emotional regulation',
                    'Anxiety, worries, or fears',
                    'Stress and coping',
                    'Behavioural concerns',
                    'Self-esteem and confidence',
                    'Social and relationship challenges',
                    'Emotional expression',
                    'Problem-solving skills',
                    'Changes or transitions',
                    'Family concerns related to a child’s emotional or behavioural needs',
                    'Developing practical strategies for everyday challenges',
                ],
                closing:
                    'Every child is different. Our team works with families to determine whether Psychology, Counselling, or another CATS service may best fit their needs.',
                lead_in: null,
            },
            approach: [
                {
                    title: 'Child-Centred Care',
                    description:
                        'Support is designed around each child’s individual strengths, needs, interests, developmental stage, and goals.',
                },
                {
                    title: 'Evidence-Based Practice',
                    description:
                        'Our regulated professionals use approaches informed by current research, best practices, and applicable professional standards.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners throughout planning, intervention, and ongoing support.',
                },
                {
                    title: 'Practical & Meaningful Goals',
                    description:
                        'We focus on skills and strategies that can support children in their everyday lives, relationships, routines, and environments.',
                },
                {
                    title: 'Strengths-Based Support',
                    description:
                        'We recognize and build on what each child already does well while supporting areas where additional help may be beneficial.',
                },
            ],
            settings: {
                intro: 'Psychology and Counselling services may be available in different settings depending on the service, clinician, and needs of the child and family.',
                items: [
                    {
                        title: 'In-Person',
                        description:
                            'In-person sessions may be available when appropriate for the child’s needs and service plan.',
                    },
                    {
                        title: 'Community-Based',
                        description:
                            'Some services may be provided within appropriate community settings when this supports the child’s goals.',
                    },
                    {
                        title: 'Virtual',
                        description:
                            'Secure virtual sessions may be available when appropriate for the child, family, and type of service being provided.',
                    },
                ],
                title: 'Where Services May Be Provided',
            },
            collaboration: {
                intro: 'When appropriate and with family consent, our Psychology and Counselling professionals may collaborate with other members of your child’s support team.',
                items: [
                    'Occupational Therapists',
                    'Speech-Language Pathologists',
                    'Physiotherapists',
                    'Behavioural Consultants',
                    'Behavioural & Developmental Aides',
                    'Other professionals involved in your child’s care',
                ],
                title: 'Collaboration with Your Child’s Support Team',
                closing:
                    'Collaboration helps promote consistency and ensures that each professional understands the goals and priorities that are important to the child and family.',
                lead_in: 'This may include communication with:',
            },
            extras: [],
            funding: {
                intro: 'Families may access Psychology and Counselling services through different funding or payment options depending on eligibility, the service being provided, and available coverage.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Psychology services may be available as part of Family Support for Children with Disabilities (FSCD) funded services when included within the family’s approved service plan.',
                            'Families are encouraged to confirm which services are included within their current FSCD agreement.',
                        ],
                    },
                    {
                        title: 'Private Insurance',
                        paragraphs: [
                            'Some extended health benefit plans may provide coverage for eligible Psychology or Counselling services. Coverage varies by provider and individual plan.',
                            'We provide appropriate invoices or receipts for insurance submission.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also access eligible Psychology or Counselling services privately when services are not funded through FSCD or insurance.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If you are looking for support for your child’s emotional well-being, behaviour, coping skills, or mental health, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s concerns, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Occupational Therapy',
        tagline: 'Supporting Everyday Skills, Participation & Independence',
        code: 'OTS-101',
        summary:
            'Our Occupational Therapy services help children participate more fully in the activities and routines that are important in everyday life. Support may focus on sensory processing, emotional regulation, fine and gross motor skills, self-care, daily living skills, play, and independence.',
        description:
            'Our Occupational Therapy services provide individualized support to help children participate more fully in the activities and routines that are important in everyday life. Our Occupational Therapists work collaboratively with children and families to understand each child’s strengths, needs, and goals while supporting independence, confidence, regulation, and meaningful participation at home and in the community.',
        photo: '/images/1920x1080/photo_teching_1920x1080.jpg',
        detail: {
            how_we_help: {
                intro: 'Occupational Therapy can support children in developing the skills they need to participate in everyday activities, routines, play, learning, and self-care.',
                items: [
                    {
                        title: 'Sensory Processing',
                        description:
                            'Supporting children in understanding and responding to sensory experiences such as touch, movement, sound, body awareness, and other sensory input.',
                    },
                    {
                        title: 'Emotional & Self-Regulation',
                        description:
                            'Helping children develop strategies that support regulation, transitions, attention, participation, and engagement in everyday activities.',
                    },
                    {
                        title: 'Fine Motor Skills',
                        description:
                            'Supporting hand strength, coordination, grasp, manipulation, and other skills used during play, self-care, writing, and everyday tasks.',
                    },
                    {
                        title: 'Gross Motor & Coordination',
                        description:
                            'Supporting body awareness, coordination, balance, motor planning, and participation in physical activities.',
                    },
                    {
                        title: 'Daily Living Skills',
                        description:
                            'Helping children develop greater independence with everyday activities such as dressing, grooming, mealtime routines, and personal care.',
                    },
                    {
                        title: 'Play & Participation',
                        description:
                            'Supporting the skills children need to explore, engage in play, interact with others, and participate in activities that are meaningful to them.',
                    },
                    {
                        title: 'School & Learning Participation',
                        description:
                            'Supporting skills related to classroom participation, attention, organization, handwriting, tool use, and completing everyday school activities when appropriate.',
                    },
                    {
                        title: 'Independence in Everyday Routines',
                        description:
                            'Helping children build practical skills and strategies that support greater independence at home and in the community.',
                    },
                ],
                title: 'How Occupational Therapy Can Help',
            },
            what_to_expect: {
                intro: 'Occupational Therapy is individualized based on your child’s age, strengths, needs, developmental stage, goals, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s strengths, interests, daily routines, challenges, and the areas that are most important to your family.',
                    },
                    {
                        title: 'Observation & Assessment',
                        description:
                            'Depending on your child’s needs and the service being provided, the Occupational Therapist may use observation, play-based activities, informal assessment, standardized assessment tools, and discussion with caregivers to better understand areas of strength and support.',
                    },
                    {
                        title: 'Individualized Goals',
                        description:
                            'Goals are developed collaboratively with families and focus on skills and activities that are meaningful to the child’s everyday life.',
                    },
                    {
                        title: 'Therapy & Skill Development',
                        description:
                            'Sessions may include play-based activities, sensory and movement experiences, fine or gross motor activities, self-care practice, regulation strategies, and other individualized interventions based on the child’s goals.',
                    },
                    {
                        title: 'Family Strategies & Recommendations',
                        description:
                            'Parents and caregivers may receive practical strategies, recommendations, and resources that can be incorporated into everyday routines and activities.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Your child’s progress is reviewed over time, and goals, strategies, or recommendations may be adjusted as their strengths, needs, and skills develop.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in helping children build skills that carry over into everyday life. We work collaboratively with parents and caregivers to ensure that therapy goals and strategies are practical, relevant, and meaningful to the child and family.',
                items: [
                    {
                        title: 'Collaborative Goal Setting',
                        description:
                            'Families are involved in identifying priorities and developing goals that reflect their child’s needs, strengths, routines, and participation.',
                    },
                    {
                        title: 'Practical Strategies',
                        description:
                            'We provide strategies that can be incorporated into everyday activities such as getting dressed, mealtimes, play, transitions, school routines, and community participation.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Our Occupational Therapists communicate with families about progress, recommendations, strategies, and adjustments throughout services.',
                    },
                    {
                        title: 'Collaborative Care',
                        description:
                            'When appropriate and with family consent, we may collaborate with other professionals involved in your child’s support to promote consistency across services and environments.',
                    },
                ],
                title: 'Family-Centred Occupational Therapy',
            },
            who_may_benefit: {
                intro: 'Occupational Therapy may be helpful for children who need additional support participating in everyday activities and routines.',
                items: [
                    'Sensory processing',
                    'Emotional and self-regulation',
                    'Fine motor skills',
                    'Hand strength and coordination',
                    'Gross motor coordination',
                    'Motor planning and body awareness',
                    'Dressing and personal care',
                    'Daily living skills',
                    'Play skills',
                    'Handwriting and school participation',
                    'Attention and engagement in activities',
                    'Transitions and routines',
                    'Independence at home and in the community',
                    'Participation in everyday activities',
                ],
                closing:
                    'Every child develops differently. Our Occupational Therapists work with families to understand how a child’s strengths and needs may be affecting everyday participation and identify appropriate areas for support.',
                lead_in: 'Children may benefit from support with:',
            },
            approach: [
                {
                    title: 'Child-Centred Care',
                    description:
                        'Support is designed around each child’s strengths, interests, needs, developmental stage, routines, and goals.',
                },
                {
                    title: 'Evidence-Based Practice',
                    description:
                        'Our Occupational Therapists use approaches informed by current research, best practices, and applicable professional standards.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners throughout assessment, goal setting, intervention, and ongoing support.',
                },
                {
                    title: 'Functional & Meaningful Goals',
                    description:
                        'We focus on skills that can make a practical difference in a child’s everyday life and help them participate more fully in activities that matter to them.',
                },
                {
                    title: 'Play-Based Learning',
                    description:
                        'When appropriate, therapy uses engaging and play-based activities to support skill development, confidence, participation, and motivation.',
                },
                {
                    title: 'Strengths-Based Support',
                    description:
                        'We build on each child’s existing strengths and abilities while providing individualized support in areas where additional help may be beneficial.',
                },
            ],
            settings: {
                intro: 'Occupational Therapy may be available in different settings depending on the child’s needs, goals, clinician, and service plan.',
                items: [
                    {
                        title: 'In-Home',
                        description:
                            'Services may be provided in the child’s home, allowing skills and strategies to be practised within familiar routines and everyday activities.',
                    },
                    {
                        title: 'Community-Based',
                        description:
                            'Sessions may take place in appropriate community settings when this supports participation, independence, or other identified goals.',
                    },
                    {
                        title: 'Virtual',
                        description:
                            'Secure virtual sessions may be available when appropriate for the child, family, and type of Occupational Therapy service being provided.',
                    },
                    {
                        title: 'School or Childcare Collaboration',
                        description:
                            'When appropriate and with family consent, Occupational Therapists may collaborate with schools, childcare providers, and other professionals involved in the child’s support.',
                    },
                ],
                title: 'Where Occupational Therapy May Be Provided',
            },
            collaboration: {
                intro: 'When appropriate and with family consent, our Occupational Therapists may work collaboratively with other members of your child’s service team.',
                items: [
                    'Speech-Language Pathologists',
                    'Physiotherapists',
                    'Psychologists',
                    'Behavioural Consultants',
                    'Behavioural & Developmental Aides',
                    'Other professionals involved in your child’s care',
                ],
                title: 'Collaboration with Your Child’s Support Team',
                closing:
                    'Collaboration can help promote consistency and support the child’s goals across services, routines, and everyday environments.',
                lead_in: 'This may include:',
            },
            extras: [
                {
                    items: [],
                    title: 'Professional Standards',
                    closing: null,
                    lead_in: null,
                    paragraphs: [
                        'Our Occupational Therapists are regulated professionals who practise in accordance with the standards and requirements of the Alberta College of Occupational Therapists (ACOT).',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Occupational Therapy through different funding and payment options depending on eligibility, service needs, and available coverage.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Occupational Therapy services may be available through Family Support for Children with Disabilities (FSCD) when included within the family’s approved service plan.',
                            'For eligible FSCD-funded services, Creative Abilities Therapy Services provides direct billing at FSCD-approved rates.',
                        ],
                    },
                    {
                        title: 'Private Insurance',
                        paragraphs: [
                            'Some extended health benefit plans may provide coverage for eligible Occupational Therapy services. Coverage varies by insurance provider and individual plan.',
                            'We provide appropriate invoices or receipts for insurance submission.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also access Occupational Therapy privately when services are not funded through FSCD or insurance.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If your child needs support with sensory processing, regulation, everyday skills, independence, motor development, or participation in daily activities, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s concerns, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Physiotherapy',
        tagline: 'Supporting Movement, Mobility & Physical Development',
        code: 'P-303',
        summary:
            'Our Physiotherapy services support children in developing movement, strength, balance, coordination, mobility, and physical confidence. Physiotherapists work collaboratively with children and families to develop individualized goals that promote participation in everyday activities.',
        description:
            'Our Physiotherapy services provide individualized support to help children develop movement, mobility, strength, balance, coordination, and confidence in physical activities. Our Physiotherapists work collaboratively with children and families to understand each child’s strengths, needs, and goals while supporting participation and independence in everyday life.',
        photo: '/images/1920x1080/photo_bnw_1920x1080.jpg',
        detail: {
            how_we_help: {
                intro: 'Physiotherapy can support children in developing physical skills that help them move, participate, play, and engage more confidently in everyday activities.',
                items: [
                    {
                        title: 'Gross Motor Skills',
                        description:
                            'Supporting skills such as sitting, standing, walking, running, jumping, climbing, and other movements used throughout everyday life.',
                    },
                    {
                        title: 'Strength & Endurance',
                        description:
                            'Helping children build the strength and physical endurance needed to participate in play, routines, and community activities.',
                    },
                    {
                        title: 'Balance & Coordination',
                        description:
                            'Supporting balance, body control, coordination, and confidence during movement and physical activities.',
                    },
                    {
                        title: 'Mobility',
                        description:
                            'Supporting children in moving safely and effectively within their home, community, and other everyday environments.',
                    },
                    {
                        title: 'Motor Planning',
                        description:
                            'Helping children develop the ability to plan, organize, and carry out coordinated movements.',
                    },
                    {
                        title: 'Posture & Body Alignment',
                        description:
                            'Supporting positioning, posture, and body awareness during everyday activities and movement.',
                    },
                    {
                        title: 'Physical Participation',
                        description:
                            'Helping children build the physical skills and confidence needed to participate in play, recreation, family activities, and community experiences.',
                    },
                    {
                        title: 'Independence',
                        description:
                            'Supporting physical abilities that can help children become more independent in everyday routines and activities.',
                    },
                ],
                title: 'How Physiotherapy Can Help',
            },
            what_to_expect: {
                intro: 'Physiotherapy is individualized based on your child’s age, physical abilities, strengths, needs, goals, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s movement, physical abilities, daily routines, strengths, challenges, and the goals that are important to your family.',
                    },
                    {
                        title: 'Observation & Assessment',
                        description:
                            'Depending on your child’s needs and the service being provided, the Physiotherapist may use observation, movement activities, informal assessment, and appropriate clinical assessment tools to better understand areas of strength and support.',
                    },
                    {
                        title: 'Individualized Goals',
                        description:
                            'Goals are developed collaboratively with families and focus on physical skills and activities that are meaningful to the child’s everyday life.',
                    },
                    {
                        title: 'Therapy & Skill Development',
                        description:
                            'Sessions may include movement activities, strengthening, balance and coordination activities, mobility practice, gross motor activities, and other individualized interventions based on your child’s goals.',
                    },
                    {
                        title: 'Family Strategies & Recommendations',
                        description:
                            'Parents and caregivers may receive practical activities, strategies, and recommendations that can be incorporated into everyday routines, play, and physical activities.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Your child’s progress is reviewed over time, and goals, activities, or strategies may be adjusted as their strengths, needs, and physical abilities develop.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in supporting children’s physical development and participation. We work collaboratively with parents and caregivers to ensure that goals and strategies are practical, meaningful, and relevant to everyday life.',
                items: [
                    {
                        title: 'Collaborative Goal Setting',
                        description:
                            'Families are involved in identifying priorities and developing goals that reflect their child’s physical abilities, needs, routines, and participation.',
                    },
                    {
                        title: 'Practical Strategies',
                        description:
                            'We provide activities and strategies that can be incorporated into everyday routines, play, recreation, and community participation.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Our Physiotherapists communicate with families about progress, recommendations, strategies, and any adjustments throughout services.',
                    },
                    {
                        title: 'Collaborative Care',
                        description:
                            'When appropriate and with family consent, we may collaborate with other professionals involved in your child’s support to promote consistency across services and environments.',
                    },
                ],
                title: 'Family-Centred Physiotherapy',
            },
            who_may_benefit: {
                intro: 'Physiotherapy may be helpful for children who need additional support with movement, mobility, physical development, or participation in everyday activities.',
                items: [
                    'Gross motor development',
                    'Strength and endurance',
                    'Balance',
                    'Coordination',
                    'Mobility',
                    'Walking and movement skills',
                    'Motor planning',
                    'Posture and positioning',
                    'Body awareness',
                    'Physical confidence',
                    'Participation in play and recreation',
                    'Navigating everyday environments',
                    'Independence in physical activities',
                    'Building skills for community participation',
                ],
                closing:
                    'Every child develops differently. Our Physiotherapists work with families to understand how a child’s physical strengths and needs may affect everyday participation and identify appropriate areas for support.',
                lead_in: 'Children may benefit from support with:',
            },
            approach: [
                {
                    title: 'Child-Centred Care',
                    description:
                        'Support is designed around each child’s strengths, physical abilities, interests, developmental stage, needs, and goals.',
                },
                {
                    title: 'Evidence-Based Practice',
                    description:
                        'Our Physiotherapists use approaches informed by current research, best practices, and applicable professional standards.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners throughout assessment, goal setting, intervention, and ongoing support.',
                },
                {
                    title: 'Functional & Meaningful Goals',
                    description:
                        'We focus on physical skills that can make a practical difference in everyday life and help children participate more fully in activities that matter to them.',
                },
                {
                    title: 'Movement Through Play',
                    description:
                        'When appropriate, therapy incorporates engaging and play-based movement activities to support skill development, confidence, motivation, and participation.',
                },
                {
                    title: 'Strengths-Based Support',
                    description:
                        'We build on each child’s existing abilities while providing individualized support in areas where additional help may be beneficial.',
                },
            ],
            settings: {
                intro: 'Physiotherapy may be available in different settings depending on the child’s needs, goals, clinician, and service plan.',
                items: [
                    {
                        title: 'In-Home',
                        description:
                            'Services may be provided in the child’s home, allowing movement strategies and physical skills to be practised within familiar routines and environments.',
                    },
                    {
                        title: 'Community-Based',
                        description:
                            'Sessions may take place in appropriate community settings when this supports mobility, physical participation, recreation, or other identified goals.',
                    },
                    {
                        title: 'Virtual',
                        description:
                            'Secure virtual sessions may be available when appropriate for the child, family, and type of Physiotherapy service being provided.',
                    },
                    {
                        title: 'School or Childcare Collaboration',
                        description:
                            'When appropriate and with family consent, Physiotherapists may collaborate with schools, childcare providers, and other professionals involved in the child’s support.',
                    },
                ],
                title: 'Where Physiotherapy May Be Provided',
            },
            collaboration: {
                intro: 'When appropriate and with family consent, our Physiotherapists may work collaboratively with other members of your child’s service team.',
                items: [
                    'Occupational Therapists',
                    'Speech-Language Pathologists',
                    'Psychologists',
                    'Behavioural Consultants',
                    'Behavioural & Developmental Aides',
                    'Other professionals involved in your child’s care',
                ],
                title: 'Collaboration with Your Child’s Support Team',
                closing:
                    'Collaboration can help promote consistency and support your child’s goals across services, routines, and everyday environments.',
                lead_in: 'This may include:',
            },
            extras: [
                {
                    items: [],
                    title: 'Professional Standards',
                    closing: null,
                    lead_in: null,
                    paragraphs: [
                        'Our Physiotherapists are regulated professionals who practise in accordance with the standards and requirements of the College of Physiotherapists of Alberta (CPTA).',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Physiotherapy through different funding and payment options depending on eligibility, service needs, and available coverage.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Physiotherapy services may be available through Family Support for Children with Disabilities (FSCD) when included within the family’s approved service plan.',
                            'For eligible FSCD-funded services, Creative Abilities Therapy Services provides direct billing at FSCD-approved rates.',
                        ],
                    },
                    {
                        title: 'Private Insurance',
                        paragraphs: [
                            'Some extended health benefit plans may provide coverage for eligible Physiotherapy services. Coverage varies by insurance provider and individual plan.',
                            'We provide appropriate invoices or receipts for insurance submission.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also access Physiotherapy privately when services are not funded through FSCD or insurance.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If your child needs support with movement, mobility, strength, balance, coordination, physical development, or participation in everyday activities, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s concerns, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Behavioural Therapy & Consulting',
        tagline:
            'Understanding Behaviour, Building Skills & Supporting Everyday Success',
        code: 'BTC-505',
        summary:
            'Our Behavioural Therapy and Consulting services support children and families in understanding behaviour, developing practical strategies, and building meaningful skills. Behavioural Consultants work collaboratively with families and service teams to develop individualized approaches based on each child’s strengths, needs, and goals.',
        description:
            'Our Behavioural Therapy and Consulting services provide individualized support for children and families experiencing behavioural, developmental, emotional, or participation-related challenges. Our Behavioural Consultants work collaboratively with families and service teams to better understand each child’s strengths, needs, and goals while developing practical strategies that can be used in everyday life.',
        photo: '/images/1920x1080/photo_teaching_1920x1080.jpg',
        detail: {
            how_we_help: {
                intro: 'Behavioural support can help families better understand behaviour, identify factors that may be influencing it, and develop strategies that promote communication, regulation, participation, independence, and positive skill development.',
                items: [
                    {
                        title: 'Understanding Behaviour',
                        description:
                            'Helping families and service teams better understand what may be contributing to a child’s behaviour and identifying patterns, needs, and environmental factors.',
                    },
                    {
                        title: 'Emotional Regulation',
                        description:
                            'Supporting children in developing strategies to recognize, communicate, and manage emotions during everyday situations.',
                    },
                    {
                        title: 'Positive Behaviour Support',
                        description:
                            'Developing individualized strategies that encourage positive behaviour, participation, communication, and skill development.',
                    },
                    {
                        title: 'Communication & Self-Advocacy',
                        description:
                            'Supporting children in developing appropriate ways to communicate their wants, needs, preferences, emotions, and boundaries.',
                    },
                    {
                        title: 'Social Skills',
                        description:
                            'Supporting skills related to interaction, cooperation, problem-solving, relationships, and participation with others.',
                    },
                    {
                        title: 'Daily Living Skills',
                        description:
                            'Helping children build skills that support independence during routines such as getting ready, transitions, self-care, household activities, and community participation.',
                    },
                    {
                        title: 'Transitions & Routines',
                        description:
                            'Developing practical strategies to support children during changes, transitions, expectations, and everyday routines.',
                    },
                    {
                        title: 'Parent & Caregiver Support',
                        description:
                            'Providing families with strategies, guidance, and recommendations that can be used consistently across everyday environments.',
                    },
                ],
                title: 'How Behavioural Therapy & Consulting Can Help',
            },
            what_to_expect: {
                intro: 'Behavioural Therapy and Consulting services are individualized based on your child’s strengths, needs, goals, routines, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s strengths, interests, routines, behaviours, areas of concern, and the priorities that are important to your family.',
                    },
                    {
                        title: 'Observation & Information Gathering',
                        description:
                            'Depending on the service being provided, the Behavioural Consultant may use observation, caregiver interviews, questionnaires, review of existing information, and other appropriate methods to better understand your child’s needs.',
                    },
                    {
                        title: 'Individualized Goals & Planning',
                        description:
                            'Goals and strategies are developed collaboratively with families and focus on areas that are meaningful to the child’s everyday life.',
                    },
                    {
                        title: 'Behavioural Strategies & Support',
                        description:
                            'The Behavioural Consultant may develop and demonstrate individualized strategies designed to support behaviour, communication, regulation, participation, and skill development.',
                    },
                    {
                        title: 'Family & Team Collaboration',
                        description:
                            'Parents, caregivers, Behavioural & Developmental Aides, and other members of the child’s service team may receive guidance to support consistency across routines and environments.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Progress, strategies, and goals are reviewed over time and may be adjusted as the child’s strengths, needs, and circumstances change.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in understanding and supporting a child’s behaviour. We work collaboratively with parents and caregivers to develop strategies that are realistic, respectful, and relevant to everyday routines.',
                items: [
                    {
                        title: 'Collaborative Goal Setting',
                        description:
                            'Families help identify priorities and goals based on their child’s strengths, needs, routines, and family circumstances.',
                    },
                    {
                        title: 'Practical Strategies',
                        description:
                            'We focus on strategies that families can realistically use during everyday activities, routines, transitions, and interactions.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Behavioural Consultants communicate with families about strategies, progress, concerns, and recommended adjustments throughout services.',
                    },
                    {
                        title: 'Respectful & Strengths-Based Support',
                        description:
                            'We recognize each child’s strengths and individual differences while supporting the development of skills that promote participation, communication, confidence, and independence.',
                    },
                ],
                title: 'Family-Centred Behavioural Support',
            },
            who_may_benefit: {
                intro: 'Behavioural Therapy and Consulting may be helpful for children and families seeking additional support with:',
                items: [
                    'Emotional regulation',
                    'Challenging or concerning behaviours',
                    'Communication of wants and needs',
                    'Social interaction',
                    'Transitions and changes in routine',
                    'Following everyday routines',
                    'Developing independence',
                    'Daily living skills',
                    'Coping with frustration',
                    'Problem-solving',
                    'Participation at home or in the community',
                    'Building positive replacement skills',
                    'Family strategies for supporting behaviour',
                    'Consistency across caregivers and service providers',
                ],
                closing:
                    'Every child’s behaviour has context. Our Behavioural Consultants work collaboratively with families to better understand each child’s needs and identify appropriate strategies and supports.',
                lead_in: null,
            },
            approach: [
                {
                    title: 'Child-Centred Support',
                    description:
                        'Strategies are developed around each child’s strengths, needs, communication style, developmental stage, routines, and goals.',
                },
                {
                    title: 'Positive & Respectful Strategies',
                    description:
                        'We focus on supportive approaches that promote skill development, participation, communication, and positive relationships.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners throughout observation, planning, implementation, and ongoing review.',
                },
                {
                    title: 'Functional & Meaningful Goals',
                    description:
                        'We focus on behaviours and skills that have a practical impact on the child’s everyday participation, independence, and quality of life.',
                },
                {
                    title: 'Evidence-Informed Practice',
                    description:
                        'Behavioural Consultants use approaches informed by professional knowledge, current evidence, best practices, and the individual needs of the child and family.',
                },
                {
                    title: 'Consistency Across Environments',
                    description:
                        'When appropriate, strategies may be shared across the child’s service team to support consistency in home, community, and other everyday environments.',
                },
            ],
            settings: {
                intro: 'Behavioural Therapy and Consulting may be available in different settings depending on the child’s needs, goals, consultant, and service plan.',
                items: [
                    {
                        title: 'In-Home',
                        description:
                            'Services may take place in the child’s home, allowing the Behavioural Consultant to understand routines and provide strategies within familiar everyday situations.',
                    },
                    {
                        title: 'Community-Based',
                        description:
                            'Services may take place in appropriate community settings when this supports identified behavioural, social, participation, or independence goals.',
                    },
                    {
                        title: 'Virtual',
                        description:
                            'Secure virtual consultation may be available when appropriate for the family and type of service being provided.',
                    },
                    {
                        title: 'School or Childcare Collaboration',
                        description:
                            'When appropriate and with family consent, Behavioural Consultants may collaborate with schools, childcare providers, and other professionals involved in the child’s support.',
                    },
                ],
                title: 'Where Behavioural Services May Be Provided',
            },
            collaboration: {
                intro: 'When appropriate and with family consent, Behavioural Consultants may work collaboratively with other professionals involved in your child’s services.',
                items: [
                    'Behavioural & Developmental Aides',
                    'Occupational Therapists',
                    'Speech-Language Pathologists',
                    'Physiotherapists',
                    'Psychologists',
                    'Other professionals involved in your child’s care',
                ],
                title: 'Collaboration with Your Child’s Support Team',
                closing:
                    'Collaboration can help support consistency across goals, strategies, services, and everyday environments.',
                lead_in: 'This may include:',
            },
            extras: [
                {
                    items: [
                        'Reviewing goals and strategies',
                        'Demonstrating appropriate support approaches',
                        'Providing recommendations to the aide',
                        'Reviewing progress and observations',
                        'Adjusting strategies when appropriate',
                        'Supporting consistency between the family, aide, and service team',
                    ],
                    title: 'Behavioural & Developmental Aide Collaboration',
                    closing:
                        'This collaborative approach helps connect behavioural planning with practical support in the child’s everyday life.',
                    lead_in: 'Collaboration may include:',
                    paragraphs: [
                        'Behavioural Consultants may work closely with Behavioural & Developmental Aides as part of a child’s service team.',
                        'The Behavioural Consultant may help develop goals and strategies, while the Behavioural & Developmental Aide supports the child in practising and developing skills during everyday activities and routines.',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Behavioural Therapy and Consulting through different funding and payment options depending on eligibility, service needs, and available funding.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Behavioural Consulting services may be available through Family Support for Children with Disabilities (FSCD), including Specialized Services (SS) and Behavioural and Developmental Support (BDS), when included within the family’s approved agreement.',
                            'For eligible FSCD-funded services, Creative Abilities Therapy Services provides direct billing at FSCD-approved rates.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also inquire about accessing Behavioural Therapy and Consulting privately when services are not funded through FSCD.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If your family is looking for support with behaviour, emotional regulation, routines, communication, independence, or everyday participation, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s concerns, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Behavioural & Developmental Aide Services',
        tagline:
            'Supporting Skills, Confidence & Independence in Everyday Life',
        code: 'BDAS-303',
        summary:
            'Our Behavioural & Developmental Aides provide individualized support to help children work toward developmental, behavioural, social, communication, and daily living goals. Aides work collaboratively with families and the child’s service team to support consistency, participation, confidence, and independence.',
        description:
            'Our Behavioural & Developmental Aide Services provide individualized, hands-on support for children as they work toward developmental, behavioural, social, communication, and everyday living goals. Behavioural & Developmental Aides work collaboratively with families and the child’s service team to help children practise skills within familiar routines and environments. Support is tailored to each child’s strengths, needs, goals, and family priorities.',
        photo: '/images/1920x1080/photo_baby_1920x1080.jpg',
        detail: {
            how_we_help: {
                intro: 'Aide support gives children opportunities to practise and develop important skills through everyday activities, play, routines, and community experiences.',
                items: [
                    {
                        title: 'Daily Living Skills',
                        description:
                            'Supporting children with everyday routines and skills that promote greater independence and participation.',
                    },
                    {
                        title: 'Emotional Regulation',
                        description:
                            'Helping children practise individualized strategies for recognizing emotions, managing difficult moments, and participating in routines and activities.',
                    },
                    {
                        title: 'Behavioural Support',
                        description:
                            'Using strategies developed with the child’s family and service team to support positive behaviour, participation, and skill development.',
                    },
                    {
                        title: 'Communication Skills',
                        description:
                            'Creating opportunities for children to practise expressing wants, needs, choices, thoughts, and feelings using their preferred communication methods.',
                    },
                    {
                        title: 'Social Skills',
                        description:
                            'Supporting interaction, turn-taking, cooperation, relationship-building, and participation with others.',
                    },
                    {
                        title: 'Play & Engagement',
                        description:
                            'Using play and motivating activities to encourage learning, connection, communication, and skill development.',
                    },
                    {
                        title: 'Routines & Transitions',
                        description:
                            'Supporting children in developing greater confidence and independence during everyday routines, transitions, and changes in activities.',
                    },
                    {
                        title: 'Independence',
                        description:
                            'Helping children practise functional skills that support greater independence at home and in the community.',
                    },
                ],
                title: 'How Behavioural & Developmental Aide Services Can Help',
            },
            what_to_expect: {
                intro: 'Behavioural & Developmental Aide Services are individualized according to the child’s service plan, goals, needs, and family priorities.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s strengths, interests, routines, needs, and the goals that are important to your family.',
                    },
                    {
                        title: 'Service Planning',
                        description:
                            'The child’s goals and strategies are identified through the appropriate service planning process and in collaboration with the family and service team.',
                    },
                    {
                        title: 'Matching with an Aide',
                        description:
                            'When an appropriate aide is available, families are connected with a Behavioural & Developmental Aide based on service needs, location, scheduling, and fit.',
                    },
                    {
                        title: 'Individualized Support Begins',
                        description:
                            'The aide works with the child during scheduled sessions to practise skills, implement appropriate strategies, and support identified goals through everyday activities and routines.',
                    },
                    {
                        title: 'Team Collaboration',
                        description:
                            'The aide communicates and collaborates with the family and appropriate members of the child’s service team to support consistency and shared goals.',
                    },
                    {
                        title: 'Ongoing Progress & Review',
                        description:
                            'Progress, observations, and strategies are reviewed over time, and supports may be adjusted as the child’s needs and goals change.',
                    },
                ],
            },
            family_centred: {
                intro: 'Families are important partners in a child’s development. Our Behavioural & Developmental Aides work collaboratively with parents and caregivers to support strategies and goals in ways that are practical for everyday family life.',
                items: [
                    {
                        title: 'Family Priorities',
                        description:
                            'Support reflects the priorities identified by the family and the child’s service team.',
                    },
                    {
                        title: 'Consistency',
                        description:
                            'Aides help children practise identified skills and strategies regularly within everyday activities.',
                    },
                    {
                        title: 'Communication',
                        description:
                            'Ongoing communication helps families, aides, and service providers stay informed about progress, observations, and areas where additional support may be helpful.',
                    },
                    {
                        title: 'Strengths-Based Support',
                        description:
                            'Aides build on each child’s strengths, interests, and abilities while supporting areas where additional practice and guidance may be beneficial.',
                    },
                ],
                title: 'Family-Centred Aide Support',
            },
            who_may_benefit: {
                intro: 'Behavioural & Developmental Aide Services may be helpful for children who need additional hands-on support to practise skills and strategies in everyday life.',
                items: [
                    'Emotional regulation',
                    'Behavioural goals',
                    'Communication',
                    'Social interaction',
                    'Play skills',
                    'Daily living skills',
                    'Independence',
                    'Routines and transitions',
                    'Following individualized strategies',
                    'Community participation',
                    'Confidence and self-advocacy',
                    'Practising skills introduced by clinicians or consultants',
                    'Generalizing skills across everyday environments',
                ],
                closing:
                    'Every child’s needs are different. The type and frequency of aide support are based on the child’s goals, service plan, family priorities, and available funding or service arrangements.',
                lead_in: 'Children may benefit from support with:',
            },
            approach: [
                {
                    title: 'Child-Centred Support',
                    description:
                        'Services are based on each child’s strengths, interests, needs, abilities, and goals.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners in planning and supporting their child’s development.',
                },
                {
                    title: 'Practical & Functional Goals',
                    description:
                        'We focus on skills that can support participation, independence, confidence, and everyday life.',
                },
                {
                    title: 'Play-Based & Engaging Activities',
                    description:
                        'When appropriate, aides use play, interests, and motivating activities to create opportunities for learning and skill development.',
                },
                {
                    title: 'Positive & Respectful Support',
                    description:
                        'We use supportive approaches that respect the child while encouraging communication, participation, skill development, and positive relationships.',
                },
                {
                    title: 'Team-Based Care',
                    description:
                        'Aides collaborate with families and appropriate service providers to support consistency across the child’s goals and strategies.',
                },
            ],
            settings: {
                intro: 'Children often benefit from opportunities to practise skills in the environments where they naturally use them. Behavioural & Developmental Aides can provide support during everyday activities and routines, helping children apply skills in practical and meaningful ways.',
                items: [
                    {
                        title: 'In-Home Support',
                        description:
                            'Sessions may take place in the child’s home, allowing skills and strategies to be practised within familiar routines and activities.',
                    },
                    {
                        title: 'Community-Based Support',
                        description:
                            'Sessions may take place in appropriate community settings to support participation, independence, social skills, communication, and everyday experiences.',
                    },
                    {
                        title: 'Everyday Routines',
                        description:
                            'Support can be incorporated into activities such as play, getting ready, transitions, household routines, recreation, and community participation depending on the child’s goals.',
                    },
                ],
                title: 'Individualized Support in Everyday Environments',
            },
            collaboration: {
                intro: 'Behavioural & Developmental Aides may work as part of a broader multidisciplinary service team. The aide helps provide practical opportunities for the child to practise identified strategies and skills between clinical or consulting sessions.',
                items: [
                    'Behavioural Consultants',
                    'Occupational Therapists',
                    'Speech-Language Pathologists',
                    'Physiotherapists',
                    'Psychologists',
                    'Other professionals involved in the child’s services',
                ],
                title: 'Working with Your Child’s Service Team',
                closing:
                    'Aides may receive guidance and recommendations from appropriate members of the child’s service team, strategies introduced by clinicians may be incorporated into everyday activities, and observations from aide sessions help the team understand how skills are progressing in everyday environments.',
                lead_in:
                    'Depending on the child’s services, the team may include:',
            },
            extras: [
                {
                    items: [],
                    title: 'Behavioural & Developmental Aides and Behavioural Consultants',
                    closing: null,
                    lead_in: null,
                    paragraphs: [
                        'Behavioural & Developmental Aides and Behavioural Consultants have different but complementary roles.',
                        'Behavioural Consultants may assess needs, develop behavioural strategies, support service planning, provide consultation, and guide appropriate interventions.',
                        'Behavioural & Developmental Aides provide hands-on support with the child and help practise identified strategies and skills during everyday activities and routines.',
                        'Working collaboratively helps connect service planning with practical, ongoing support in the child’s daily life.',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Behavioural & Developmental Aide Services through different funding or payment arrangements depending on eligibility and service needs.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Behavioural & Developmental Aide Services may be available through eligible Family Support for Children with Disabilities (FSCD) funding, including Specialized Services (SS) and Behavioural and Developmental Support (BDS), when included within the family’s approved agreement.',
                            'Eligible FSCD-funded services are billed at applicable FSCD-approved rates.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also inquire about private-pay Behavioural & Developmental Aide Services when appropriate and available.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If your child would benefit from additional support practising developmental, behavioural, social, communication, or everyday living skills, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s needs, and the support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
    {
        name: 'Community & Respite Aide Services',
        tagline:
            'Supporting Participation, Independence & Meaningful Experiences',
        code: 'CRAS-404',
        summary:
            'Our Community and Respite Aide Services provide flexible support for children and families in community and recreational settings. Community support focuses on participation, independence, social experiences, and everyday skills, while respite services provide families and caregivers with additional support while children participate in meaningful and enjoyable activities.',
        description:
            'Our Community & Respite Aide Services provide individualized support for children and families in community and everyday settings. Services are designed to encourage participation, independence, social connection, confidence, and meaningful experiences while also providing families and caregivers with additional support. Community and Respite Aides work collaboratively with families to understand each child’s strengths, needs, interests, routines, and support requirements.',
        photo: '/images/1920x1080/photo_high5_1920x1080.jpg',
        detail: {
            how_we_help: {
                intro: 'Community and respite support can give children opportunities to participate in activities, build everyday skills, develop confidence, and engage with their community in ways that reflect their individual interests and abilities.',
                items: [
                    {
                        title: 'Community Participation',
                        description:
                            'Supporting children in participating in recreational, social, and community-based activities.',
                    },
                    {
                        title: 'Independence',
                        description:
                            'Providing opportunities to practise everyday skills that support greater independence and confidence.',
                    },
                    {
                        title: 'Social Interaction',
                        description:
                            'Supporting opportunities for children to interact, connect, communicate, and participate alongside others.',
                    },
                    {
                        title: 'Recreation & Leisure',
                        description:
                            'Helping children participate in enjoyable and meaningful recreational activities based on their interests and abilities.',
                    },
                    {
                        title: 'Everyday Living Skills',
                        description:
                            'Supporting practical skills that may be used during outings, activities, routines, and community participation.',
                    },
                    {
                        title: 'Communication',
                        description:
                            'Creating opportunities for children to express wants, needs, choices, preferences, and ideas during everyday activities.',
                    },
                    {
                        title: 'Confidence & Self-Advocacy',
                        description:
                            'Encouraging children to make choices, communicate preferences, participate in decision-making, and develop confidence in everyday situations.',
                    },
                    {
                        title: 'Family & Caregiver Respite',
                        description:
                            'Providing families and caregivers with additional support while their child participates in appropriate, engaging, and meaningful activities.',
                    },
                ],
                title: 'How Community & Respite Aide Services Can Help',
            },
            what_to_expect: {
                intro: 'Community & Respite Aide Services are individualized according to the child’s needs, family priorities, service arrangement, and available funding.',
                steps: [
                    {
                        title: 'Getting to Know Your Child & Family',
                        description:
                            'We begin by learning about your child’s strengths, interests, routines, support needs, preferences, and the type of community or respite support your family is looking for.',
                    },
                    {
                        title: 'Understanding Support Needs',
                        description:
                            'Families provide information about important routines, communication needs, safety considerations, interests, and other information that may help us provide appropriate support.',
                    },
                    {
                        title: 'Matching with an Aide',
                        description:
                            'When an appropriate aide is available, we work to connect your family with a Community or Respite Aide based on factors such as location, scheduling, support needs, availability, and fit.',
                    },
                    {
                        title: 'Services Begin',
                        description:
                            'The aide provides scheduled support based on the agreed service arrangement and the needs of the child and family.',
                    },
                    {
                        title: 'Ongoing Communication',
                        description:
                            'Families and aides maintain communication regarding activities, routines, observations, scheduling, and any changes that may affect services.',
                    },
                    {
                        title: 'Ongoing Review',
                        description:
                            'Support arrangements may be reviewed as the child’s needs, interests, schedule, or family circumstances change.',
                    },
                ],
            },
            family_centred: {
                intro: 'Every family’s respite needs are different. We work with families to understand what type of support is appropriate for their child and what will help make respite a positive experience.',
                items: [
                    {
                        title: 'Individualized Support',
                        description:
                            'Respite is adapted to the child’s interests, abilities, communication needs, routines, and level of support.',
                    },
                    {
                        title: 'Family Preferences',
                        description:
                            'Parents and caregivers provide important information about their child’s routines, preferences, activities, and support needs.',
                    },
                    {
                        title: 'Meaningful Activities',
                        description:
                            'Aides engage children in appropriate activities that are enjoyable, engaging, and suited to their interests.',
                    },
                    {
                        title: 'Communication with Families',
                        description:
                            'Families and aides communicate about sessions, activities, scheduling, and any relevant observations or changes.',
                    },
                ],
                title: 'Family-Centred Respite Support',
            },
            who_may_benefit: {
                intro: 'Community & Respite Aide Services may be helpful for children and families looking for additional support with:',
                items: [
                    'Community participation',
                    'Recreation and leisure',
                    'Social opportunities',
                    'Independence',
                    'Everyday living skills',
                    'Communication in community settings',
                    'Confidence',
                    'Making choices',
                    'Participating in activities outside the home',
                    'Developing familiarity with community environments',
                    'Meaningful recreational experiences',
                    'Additional caregiver support',
                    'Respite for parents and caregivers',
                ],
                closing:
                    'Every child and family has different needs. The type and frequency of Community or Respite Aide Services will depend on the individual service arrangement, family priorities, available funding, and aide availability.',
                lead_in: null,
            },
            approach: [
                {
                    title: 'Child-Centred Support',
                    description:
                        'Activities and support are based on each child’s strengths, interests, preferences, needs, and abilities.',
                },
                {
                    title: 'Family Collaboration',
                    description:
                        'Parents and caregivers are valued partners in determining appropriate activities, routines, expectations, and support needs.',
                },
                {
                    title: 'Meaningful Participation',
                    description:
                        'We focus on helping children participate in activities and experiences that are enjoyable and relevant to their everyday lives.',
                },
                {
                    title: 'Strengths-Based Support',
                    description:
                        'We build on what each child already enjoys and does well while creating opportunities for greater confidence and independence.',
                },
                {
                    title: 'Respectful Support',
                    description:
                        'Aides provide support in a way that respects each child’s communication, preferences, abilities, and individuality.',
                },
                {
                    title: 'Community Inclusion',
                    description:
                        'We encourage opportunities for children to participate in community activities and experiences alongside others whenever appropriate.',
                },
            ],
            settings: {
                intro: 'Community & Respite Aide Services may take place in different environments depending on the child’s needs, family preferences, activities, and service arrangement.',
                items: [
                    {
                        title: 'In the Community',
                        description:
                            'Support may take place in appropriate community settings such as recreation facilities, parks, libraries, playgrounds, community programs, or other agreed locations.',
                    },
                    {
                        title: 'In-Home Respite',
                        description:
                            'When appropriate and included within the service arrangement, respite support may take place within the family home.',
                    },
                    {
                        title: 'Recreational Settings',
                        description:
                            'Children may participate in appropriate recreational or leisure activities based on their interests, abilities, and support needs.',
                    },
                ],
                title: 'Where Services May Be Provided',
            },
            collaboration: {
                intro: 'Families provide important information before services begin so aides can better understand the child’s support needs.',
                items: [
                    'Communication',
                    'Allergies',
                    'Medical or health considerations relevant to the service',
                    'Emergency contacts',
                    'Behavioural or safety considerations',
                    'Sensory needs',
                    'Mobility or accessibility',
                    'Personal care needs',
                    'Preferred activities',
                    'Important routines',
                    'Community safety considerations',
                ],
                title: 'Safety & Planning',
                closing:
                    'Activities and locations should be appropriate for the child’s needs, abilities, and agreed service arrangement.',
                lead_in: 'This may include information related to:',
            },
            extras: [
                {
                    items: [
                        'Recreation and leisure activities',
                        'Community programs',
                        'Parks and playgrounds',
                        'Libraries',
                        'Indoor recreation facilities',
                        'Social activities',
                        'Community events',
                        'Everyday community outings',
                        'Activities related to the child’s interests and goals',
                    ],
                    title: 'Community Aide Services',
                    closing:
                        'Community support is individualized and based on the child’s needs, abilities, interests, family priorities, and service arrangement.',
                    lead_in: 'Support may include participation in:',
                    paragraphs: [
                        'Community Aide Services focus on helping children participate more fully in their communities while developing skills, confidence, and independence.',
                    ],
                },
                {
                    items: [
                        'Play and recreational activities',
                        'Arts and crafts',
                        'Games and activities',
                        'Community outings',
                        'Social participation',
                        'Movement and active play',
                        'Activities based on the child’s interests',
                        'Support with appropriate everyday routines',
                    ],
                    title: 'Respite Aide Services',
                    closing:
                        'Respite services focus on providing supportive and meaningful experiences for the child while giving parents and caregivers time away from their caregiving responsibilities.',
                    lead_in: 'Respite may include:',
                    paragraphs: [
                        'Respite Aide Services provide families and caregivers with additional support while offering children opportunities to participate in enjoyable and appropriate activities.',
                    ],
                },
                {
                    items: [
                        'Building community confidence by practising navigating community environments with appropriate support',
                        'Making choices about activities, preferences, and participation',
                        'Practising everyday skills such as communication, social interaction, and independence',
                        'Exploring interests through activities selected around the child’s abilities and preferences',
                    ],
                    title: 'Individualized Community Support',
                    closing: null,
                    lead_in: 'This may include:',
                    paragraphs: [
                        'Community support looks different for every child. Some children may benefit from support developing independence in community environments, while others may need additional assistance participating in recreational or social activities.',
                        'Our Community Aides can help create opportunities for children to practise skills during real-life experiences while supporting participation in activities that are meaningful to them.',
                    ],
                },
                {
                    items: [],
                    title: 'Community & Respite Aides Compared with Behavioural & Developmental Aides',
                    closing: null,
                    lead_in: null,
                    paragraphs: [
                        'Community & Respite Aides and Behavioural & Developmental Aides provide different types of support.',
                        'Community & Respite Aides primarily focus on recreation, community participation, meaningful activities, independence, and respite support for families.',
                        'Behavioural & Developmental Aides provide more goal-directed developmental support and may work alongside clinicians or consultants to help children practise individualized strategies and skills identified within their service plan.',
                        'The appropriate service depends on the child’s needs, family priorities, funding arrangement, and service goals.',
                    ],
                },
            ],
            funding: {
                intro: 'Families may access Community & Respite Aide Services through different funding or payment arrangements depending on eligibility and service needs.',
                items: [
                    {
                        title: 'FSCD',
                        paragraphs: [
                            'Eligible Community and Respite Aide Services may be available through Family Support for Children with Disabilities (FSCD) when included within the family’s approved agreement.',
                            'Eligible FSCD-funded services are billed at applicable FSCD-approved rates.',
                            'Families are encouraged to review their FSCD agreement to confirm the type and amount of aide or respite support available to them.',
                        ],
                    },
                    {
                        title: 'Private Pay',
                        paragraphs: [
                            'Families may also inquire about private-pay Community or Respite Aide Services when appropriate and available.',
                        ],
                    },
                ],
            },
            cta: {
                title: 'Ready to Get Started?',
                paragraphs: [
                    'If your family is looking for community participation support, recreational opportunities, additional assistance with independence, or respite services, our team is here to help.',
                    'Complete our intake form to tell us about your child, your family’s needs, and the type of support you are looking for. Our team will review your information and connect with you about appropriate next steps.',
                ],
            },
        },
    },
];

export function findService(code: string): ServiceEntry | undefined {
    return ServiceList.find((service) => service.code === code);
}
