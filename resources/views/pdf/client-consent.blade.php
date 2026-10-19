<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 56px 60px 48px 60px; }
        body { font-family: Helvetica, sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.5; }
        .page-break { page-break-before: always; }
        table.header { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.header td { vertical-align: middle; }
        .logo { width: 1.3in; }
        .doc-title { font-family: Helvetica-Bold, sans-serif; font-size: 20pt; letter-spacing: 0.5px; margin: 0 0 6px 0; }
        .intro { font-size: 9.5pt; color: #444; margin: 0; }
        .rule { border-top: 1px solid #ccc; margin: 10px 0 14px 0; }
        .label { color: #d97a3a; font-size: 9pt; margin-bottom: 3px; }
        .field { border: 1px solid #b8c0cc; padding: 6px 8px; min-height: 16px; margin-bottom: 12px; }
        .field-hint { color: #d97a3a; font-size: 8pt; margin: -9px 0 12px 0; }
        table.fields { width: 100%; border-collapse: collapse; }
        table.fields td { vertical-align: top; padding-right: 10px; }
        table.fields td:last-child { padding-right: 0; }
        p { margin: 0 0 9px 0; text-align: justify; }
        .section { font-family: Helvetica-Bold, sans-serif; text-decoration: underline; margin: 14px 0 6px 0; }
        .indent { margin-left: 22px; }
        .indent-2 { margin-left: 44px; }
        table.signatures { width: 100%; border-collapse: collapse; margin-top: 24px; page-break-inside: avoid; }
        table.signatures td { width: 50%; vertical-align: bottom; padding-right: 24px; }
        table.signatures td:last-child { padding-right: 0; }
        .sig-space { height: 54px; }
        .sig-rule { border-top: 1px solid #333; padding-top: 4px; font-size: 9pt; }
        .checkbox { display: inline-block; width: 10px; height: 10px; border: 1px solid #333; margin-right: 6px; }
        .instructions { border: 1px solid #d97a3a; background: #fdf3ec; padding: 10px 12px; font-size: 9.5pt; margin-bottom: 16px; }
    </style>
</head>
<body>
    @php($logoPath = public_path('CatsLogo/web-app-manifest-512x512.png'))
    @php($dob = $dateOfBirth?->format('m-d-Y') ?? '')

    {{-- Page 1: who the consent is for --}}
    <div class="instructions">
        <strong>How to return this form:</strong> print these pages, sign and date each signature line,
        then scan or photograph every page and upload it through the link in your email.
        Please check the details below and correct anything that is wrong by hand.
    </div>

    <div class="label">Child's Full Name</div>
    <div class="field">{{ $childName }}</div>

    <table class="fields">
        <tr>
            <td style="width: 50%;">
                <div class="label">Child's Date of Birth</div>
                <div class="field">{{ $dob }}</div>
            </td>
            <td>
                <div class="label">Reference Number</div>
                <div class="field">{{ $intake?->reference_number }}</div>
            </td>
        </tr>
    </table>

    <div class="label">Primary Parent/Legal Guardian's Full Name</div>
    <div class="field">{{ $parentName }}</div>

    {{-- Therapy Consent Form --}}
    <div class="page-break"></div>
    <table class="header">
        <tr>
            <td style="width: 1.6in;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo">
                @endif
            </td>
            <td>
                <p class="doc-title">THERAPY CONSENT FORM</p>
                <p class="intro">All information you share will remain confidential and will be used exclusively to plan and provide therapy services that best meet your or your child's needs. Thank you!</p>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <p>I, <strong>{{ $parentName !== '' ? $parentName : '______________________' }}</strong>, confirm that <strong>{{ $childName !== '' ? $childName : '______________________' }}</strong> was born on <strong>{{ $dob !== '' ? $dob : '__________' }}</strong> and agree to the following on the date signed below.</p>

    <p>I consent to {{ $legalName }} and its independent contractors providing therapy services to my child, including but not limited to occupational therapy, speech-language therapy, physiotherapy, behavioral consulting or psychological support, counseling, and behavioral aide services.</p>

    <p>I authorize {{ $legalName }} and its independent contractors to obtain, share, and release information to and from individuals and professionals involved in my child's care. This may include, but is not limited to, past or current Occupational Therapists, Speech-Language Pathologists, Physiotherapists, Behaviour Consultants, Psychologists, Counselors, Physicians, and educators. I understand that all such information will be used solely to provide, coordinate, or improve therapy services for my child and will be handled in accordance with applicable privacy and confidentiality laws, including regulations governing the collection, storage, and disclosure of personal health information.</p>

    <p>I understand that therapy services involve active participation by my child and that outcomes cannot be guaranteed. I acknowledge that while therapy interventions are designed to provide benefit, there may be limitations, potential risks, or unforeseen outcomes associated with therapy. I agree that {{ $legalName }} and its independent contractors are not liable for any indirect, incidental, or unforeseen outcomes arising from participation in therapy, except in cases of proven negligence.</p>

    <p>I understand that my child's participation in therapy is voluntary, and I may withdraw consent at any time by providing written notice to {{ $legalName }}. I acknowledge that withdrawal may affect my child's access to services and that certain documentation and records created by {{ $legalName }} and its contractors may be retained for legally required periods and may be used for clinical, administrative, or regulatory purposes.</p>

    <p>I consent to the use of telehealth or virtual therapy services as appropriate. I understand the risks associated with electronic communication, including potential breaches of confidentiality, technical issues, and limitations of remote observation. I accept these risks and agree that {{ $legalName }} and its contractors are not responsible for technology failures beyond their reasonable control.</p>

    <p>In the event of an emergency during therapy sessions, I authorize {{ $legalName }} and its contractors to take all reasonable steps to ensure my child's safety, including contacting emergency medical services if necessary. I understand that I will be notified as soon as possible following any such emergency.</p>

    <p>I understand the policies regarding scheduling and cancellations. I agree to provide advance notice for cancellations as required by {{ $legalName }}, and I acknowledge that failure to provide proper notice may result in fees or rescheduling limitations.</p>

    <p>I understand that therapy services are billed according to the rates provided to me by {{ $legalName }}. I agree to pay all fees for services rendered in a timely manner. I understand that insurance coverage or government-funded programs may apply and that I am responsible for any amounts not covered. I authorize {{ $legalName }} to submit claims on my behalf and to communicate with relevant insurance or funding bodies regarding coverage and payment of services.</p>

    <p>I confirm that I have read, understood, and had the opportunity to ask questions regarding the nature of the therapy services, the roles of all involved professionals, the limits of confidentiality, the risks and benefits associated with participation, telehealth provisions, emergency procedures, scheduling policies, and billing and payment terms. I provide my informed consent for my child to receive therapy services under the terms described above.</p>

    <table class="signatures">
        <tr>
            <td><div class="sig-space"></div><div class="sig-rule">Primary Parent/Legal Guardian's Signature</div></td>
            <td><div class="sig-space"></div><div class="sig-rule">Today's Date (MM-DD-YYYY)</div></td>
        </tr>
    </table>

    {{-- Family Service Agreement --}}
    <div class="page-break"></div>
    <table class="header">
        <tr>
            <td style="width: 1.6in;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo">
                @endif
            </td>
            <td>
                <p class="doc-title">FAMILY SERVICE AGREEMENT</p>
                <p class="intro">All information you share will remain confidential and will be used exclusively to plan and provide therapy services that best meet your or your child's needs. Thank you!</p>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <p class="section">BACKGROUND</p>
    <p class="indent">A. Creative Abilities provides inclusive navigation assistance to children with disabilities (the "Children"), helping them unlock their potential to achieve meaningful lives.</p>
    <p class="indent">B. Creative Abilities is a collective of multidisciplinary professionals passionate about working in the field of disabilities, dedicated to providing culturally inclusive support to ensure every child-client thrives.</p>
    <p class="indent">C. Creative Abilities' mission is to provide exceptional care and support to its clients while fostering a professional and collaborative work environment.</p>
    <p class="indent">D. The Client represents the Children and their family, who are identified as beneficiaries of the Family Support for Children with Disabilities ("FSCD") program of the Government of Alberta, who require occupational therapy, psychological, speech therapy, and counselling services.</p>
    <p class="indent">E. The Client acknowledges and believes that Creative Abilities possesses the necessary qualifications, experience, skills, and abilities to provide services to the Client.</p>
    <p class="indent">F. Creative Abilities agrees to provide occupational therapy, psychological services, speech therapy, and counselling services to the Client on the terms and conditions set out in this Agreement.</p>
    <p><strong>IN CONSIDERATION</strong> of the provisions contained in this Agreement and for other good and valuable consideration, the receipt and sufficiency of which consideration is acknowledged, the Parties agree as follows:</p>

    <p class="section">DESCRIPTION OF SERVICES</p>
    <p class="indent">1. The Client agrees to engage the services of Creative Abilities to provide the former and their families with multidisciplinary support or other form of therapeutic services based on the following disciplines and professionals (the "Services"):</p>
    <p class="indent-2">a. Occupational Therapy. Creative Abilities will provide occupational therapy services to help the Children and their families achieve independent living. In doing so, Creative Abilities' Occupational Therapists ("OTs") will assess the Children's capabilities and subsequently create customized interventions that promote skill development for everyday tasks. OTs will also recommend adaptive tools and environmental modifications to ensure the Children's safety at home and in the community. Creative Abilities will ensure that their OTs adhere to the regulations established by the College of Occupational Therapists of Alberta.</p>
    <p class="indent-2">b. Psychological Services. Creative Abilities will have Registered Psychologists or Registered Provisional Psychologists ("Psychologists") who will provide mental health support to the Children and their families, conducting assessments and developing personalized treatment plans to address emotional and psychological needs. Psychologists will equip the Children and their families with coping strategies to navigate challenges related to the Children's condition. Creative Abilities will ensure that their Psychologists adhere to the regulations established by the College of Alberta Psychologists.</p>
    <p class="indent-2">c. Behavioural Consultants. Creative Abilities will have Behavioural Consultants ("BCs") who will be responsible for developing individualized behaviour intervention plans for Children experiencing behavioural challenges. The BCs will conduct assessments and train the Children's families and caregivers in effective strategies for behaviour management, which will be crucial in fostering positive behaviour change within the context of the Children's overall care. Creative Abilities will ensure that the BCs adhere to the regulations established by their respective regulatory boards or associations.</p>
    <p class="indent-2">d. Speech Language Pathology. Creative Abilities will ensure that its Speech Language Pathologists ("SLPs") will aid in diagnosing and treating communication and swallowing disorders in children. The SLPs will create tailored interventions to improve speech, language, and cognitive-communication skills, working closely with the Children and their families to enhance overall communication. Creative Abilities will ensure that the SLPs adhere to the regulations established by the College of Alberta Speech-Language Pathologists and Audiologists.</p>
    <p class="indent-2">e. Physical Therapy. Creative Abilities Physical Therapists ("PTs") will work closely with the Children to enhance their physical functions and mobility through tailored exercise programs and rehabilitation activities. PTs will educate both the Children and their families about pain management techniques and strategies to improve mobility. Creative Abilities will ensure that the PTs adhere to the regulations established by Physiotherapy Alberta College + Association.</p>
    <p class="indent-2">Collectively, the service providers mentioned in paragraphs a through e above are referred to as medical professionals (the "Medical Professionals" or "Medical Professional," as applicable).</p>
    <p class="indent-2">f. Behavioural Aide. Creative Abilities will ensure that it has a multidisciplinary team ("Behavioural Aide"), in addition to the professionals mentioned above, who will assist with the Children's treatment plans and facilitate activities during any therapy sessions. The Aide will work under the supervision of the Medical Professionals mentioned above to help and enhance the therapeutic experience for both the Children and their families.</p>
    <p class="indent-2">g. Respite Aide. A Respite Aide ("RA") is a form of aide support, providing assistance with the Children and their families' daily routines, which include, but are not limited to, meal preparation, playtime, and personal care. In contrast to an Aide, RAs do not participate in the clinical treatment plans prepared by OTs, Psychologists, BCs, SLPs, and PTs. Instead, RAs provide supervision and physical assistance to the Children, whether they are at home or in a public space, while engaging in their current activities, such as sports, outdoor activities, or music lessons.</p>
    <p class="indent-2">Collectively, the service providers mentioned in paragraphs f and g above are referred to as aide support ("Aide Support").</p>
    <p class="indent">2. The Services will also include any other tasks related to the above that both Creative Abilities and the Client may agree on.</p>
    <p class="indent">3. Creative Abilities agrees to provide the Services mentioned above to the Client.</p>

    <p class="section">PERFORMANCE OF SERVICES</p>
    <p class="indent">4. Both parties agree to take all necessary steps to ensure that the terms of this Agreement take effect as intended.</p>
    <p class="indent">5. The Client agrees to participate in the therapeutic process fully and to discuss openly with their respective Medical Professionals or Aide Support any concerns that may arise during Creative Abilities' duration of services.</p>
    <p class="indent">6. The Client's appointments or meetings with Medical Professionals will be referred to as therapy sessions (the "Therapy Sessions").</p>
    <p class="indent">7. The Client agrees and accepts that if a Medical Professional identifies that a Behavioural Aide is needed in the treatment plan for the Client, it is then expected that a Behavioural Aide will always be present in Therapy Sessions. This is to ensure that the services provided by the Medical Professionals are effectively executed and that any necessary modifications to the Children's or clients' therapy plans are implemented promptly.</p>

    <p class="section">PLACE OF SERVICES</p>
    <p class="indent">8. Services to the Client will be rendered in a community-based approach, which can include home visits or other social activities, whether at home or in a public space.</p>

    <p class="section">SCHEDULING OF APPOINTMENT</p>
    <p class="indent">9. Creative Abilities will make every effort to accommodate the Client's scheduling requests.</p>
    <p class="indent">10. The Client, although not necessary, is strongly encouraged to book their next appointment during their Therapy Sessions with the Medical Professionals or activities with the Aide Support. The attending Medical Professionals or Aide Support will hold any appointment times offered for the next 24 hours.</p>
    <p class="indent">11. If the Client fails to confirm the offered appointment times within the 24 hours mentioned in the preceding paragraph, the appointment will automatically be cancelled.</p>

    <p class="section">CANCELLATION OF APPOINTMENT</p>
    <p class="indent">12. The Client is required to provide Creative Abilities with a minimum of 24 hours' notice to cancel their appointment.</p>
    <p class="indent">13. Notwithstanding the preceding, cancellation of an appointment with a Behavioural Aide must be done 48 hours prior to the scheduled appointment. Since Behavioural Aides make transportation and living arrangements ahead of the scheduled appointment, they should be given sufficient time to cancel prior hotel and transportation reservations.</p>
    <p class="indent">14. If the Client cancels or misses a session without providing the required notice as provided in the preceding paragraphs, Creative Abilities reserves the right to charge the Client for the missed session at the regular hourly rate (the "Cancellation Fee").</p>

    <p class="section">RESCHEDULING OF APPOINTMENT BY THE CLIENT</p>
    <p class="indent">15. Creative Abilities will follow guidelines and procedures recommended by the Government of Alberta and relevant professional colleges to ensure the health and safety of clients. The Client is required to cancel Therapy Sessions or activities if they are experiencing any illness or contagious condition. This policy is in place to protect the health and safety of the Client and Creative Abilities staff members, as well as to prevent the spread of illnesses to other Clients.</p>
    <p class="indent-2">a. Notification Requirement. If a client is feeling unwell or exhibits symptoms of illness, they must notify Creative Abilities as soon as possible to cancel the session. This cancellation should occur at least 24 hours in advance of the scheduled appointment.</p>
    <p class="indent-2">b. Rescheduling. The Client will have the opportunity to reschedule their appointment for a later date once they are well enough to participate in the Therapy Sessions or activities. Creative Abilities' staff member will assist in finding a mutually agreeable time for the rescheduled session.</p>
    <p class="indent-2">c. Cancellation Fees. Reasonable accommodations will be made in cases where the Client cancels due to illness with less than 24 or 48 hours' notice, whichever is applicable. However, Creative Abilities reserves the right to apply the regular Cancellation Fee to the Client.</p>

    <p class="section">RESCHEDULING OF APPOINTMENT BY CREATIVE ABILITIES</p>
    <p class="indent">16. Creative Abilities understands that unforeseen circumstances can arise, necessitating the cancellation of scheduled appointments. Should the attending cancel an appointment due to emergency situations, medical conditions or severe weather conditions, the following will be observed:</p>
    <p class="indent-2">a. Emergency Situations. If a Creative Abilities staff member encounters an emergency that prevents them from attending a scheduled session, the Client will be notified as soon as possible. Alternative appointment times will be offered at the earliest available opportunity.</p>
    <p class="indent-2">b. Medical Conditions. Should a Creative Abilities staff member become ill or experience a medical condition that affects their ability to provide service, Creative Abilities will contact the Client to inform them of the cancellation. The staff member will inform the Client when they expect to return to work, and efforts will be made to schedule the next appointment at a mutually agreeable time.</p>
    <p class="indent-2">c. Weather Conditions. In cases of severe weather conditions that may compromise safety, Creative Abilities staff members reserve the right to cancel appointments to protect the health and safety of clients and staff. Clients will be informed of the cancellation as soon as possible, and efforts will be made to reschedule the appointment promptly.</p>
    <p class="indent-2">Creative Abilities will strive to provide the Client with as much notice as possible and facilitate the rescheduling of appointments to ensure the Client's continuity of care.</p>

    <p class="section">DURATION OF SERVICES</p>
    <p class="indent">17. Both the Client and the attending Medical Professionals and/or Behavioural Aide agree that each Therapy Session will last 60 minutes ("Service Duration").</p>
    <p class="indent">18. However, in cases where the attending Creative Abilities staff member assessed that the Client needs more than the regular Service Duration, the attending Creative Abilities staff member shall notify the Client that the next upcoming appointment shall have a longer service duration.</p>
    <p class="indent">19. Further to the preceding paragraph, it is understood that the Service Duration is strictly for face-to-face therapy interaction or services. This does not include the 15 minutes spent by the Creative Abilities staff member on documentation, for which the Client will be charged separately.</p>

    <p class="section">APPOINTMENT TIME</p>
    <p class="indent">20. Creative Abilities will strictly adhere to the allotted appointment times. If the Client has questions or concerns they wish to discuss, they should raise these before the therapy session commences to provide the Creative Abilities staff member with the opportunity to address them. Both parties agree that:</p>
    <p class="indent-2">a. If the Client is late for an appointment, the session will still end at the scheduled time, and the full appointment fee will be charged; or</p>
    <p class="indent-2">b. If the Creative Abilities staff member is late for the appointment, they will either stay for the designated amount of time, provided their schedule allows, or the Client will not be charged for the time the staff member was late.</p>

    <p class="section">CONFIDENTIALITY AND OWNERSHIP OF INFORMATION</p>
    <p class="indent">21. All conversations, medical records, communications, and correspondence related to the Services are considered confidential (the "Confidential Information"). Creative Abilities agrees that it will not disclose, divulge, reveal, report or use, for any purpose, any Confidential Information it has obtained, except as authorized by the Client or as required by law. The obligations of confidentiality will apply during the Term and will survive indefinitely upon the termination of this Agreement.</p>
    <p class="indent">22. All written and oral information and materials disclosed or provided by the Client to Creative Abilities under this Agreement are Confidential Information, regardless of whether they were provided before or after the date of this Agreement or how they were provided to Creative Abilities.</p>
    <p class="indent">23. Confidential Information may only be disclosed if any of the following occurs:</p>
    <p class="indent-2">a. The Client, the Children or their families are being an imminent danger to themselves or someone else;</p>
    <p class="indent-2">b. Suspicion of child abuse or a child needs protection, or a suspicion of a vulnerable adult being abused or neglected; or</p>
    <p class="indent-2">c. If a judge requests information about a certain therapy with a signed, valid court order or subpoena.</p>
    <p class="indent">24. The Client acknowledges that the situations mentioned above may occur, and Creative Abilities, to enhance safety, may need to divulge the Confidential Information to third parties as required.</p>
    <p class="indent">25. If more than one person participates in counselling, the information about each individual is owned by that specific individual. Hence, all individuals involved in the Confidential Information must consent to the release of the information. When data about the Client is shared with others, it will be done only on a minimal, need-to-know basis. A Behavioural Aide or RA may consult with their supervisor for supervision purposes, on a need-to-know basis only, without disclosing the client's identity.</p>
    <p class="indent">26. Creative Abilities complies with the Personal Information Act of Alberta and the Freedom of Information and Protection of Privacy Act of Alberta. As defined in the Personal Information Act of Alberta, Protected Health Information includes the individual's name, address, phone number, health information, health insurance information, payment or billing information, and other related information (the "Protected Health Information"). However, Protected Health Information does not include Creative Abilities' session notes.</p>
    <p class="indent">27. Creative Abilities ensures that it uses a secure site to maintain the Client's Personal Health Information, keeping their information protected and confidential. All information shared in sessions, as well as all treatment and progress notes, is recorded and secured on this site.</p>
    <p class="indent">28. Creative Abilities ensures that the information recorded is directly related to and necessary for the administration and delivery of the Services. The release of the Client's, the Children's, or their families' information cannot be made without their written authorization.</p>
    <p class="indent">29. The Client, Children or their families also have the right to request and be provided with their records from Creative Abilities.</p>

    <p class="section">RESPECTFUL ENVIRONMENT POLICY</p>
    <p class="indent">30. Creative Abilities commits to maintaining a safe and respectful therapeutic environment for all clients, staff members, and visitors. The following policies are in place to uphold this commitment per the Alberta Human Rights Act and the Occupational Health and Safety Act:</p>
    <p class="indent-2">a. Respect. All individuals are expected to treat each other with dignity and respect. Neither party will tolerate disparaging remarks, derogatory comments, or any form of disrespectful behaviour.</p>
    <p class="indent-2">b. No Discrimination. In alignment with the Alberta Human Rights Act, Creative Abilities operates under strict non-discrimination policies. No individual shall be discriminated against on the basis of race, ethnicity, gender, sexual orientation, age, disability, religion, or any other protected characteristic. Both parties will value diversity and strive for inclusiveness.</p>
    <p class="indent-2">c. No Harassment. Harassment in any form - verbal, physical, or emotional - is strictly prohibited. This includes unwanted advances, offensive comments, or any behaviour that creates an intimidating or hostile environment. Any reports of harassment will be taken seriously and addressed promptly by Creative Abilities.</p>
    <p class="indent-2">d. No Violence. Creative Abilities has a zero-tolerance policy for violence. Any threats or acts of violence, whether verbal or physical, towards clients, staff members, or visitors will result in immediate termination of the Services and may involve law enforcement.</p>
    <p class="indent-2">e. No Dual Relationships. Creative Abilities maintains professional boundaries between clients and staff members. Dual relationships, where a staff member has both a professional and a personal relationship with a Client, the Children or any of their family members, are strictly prohibited. This is to ensure that therapeutic integrity is maintained and the Client's, the Children's or their families' best interests are prioritized. Any potential dual relationships should be discussed with the appropriate staff member immediately.</p>
    <p class="indent-2">f. Workplace Safety. Pursuant to the Occupational Health and Safety Act of Alberta, Creative Abilities ensures a safe work environment for staff members and clients. All individuals present in the Therapy Sessions or other activities are expected to comply with safety protocols and promptly report any hazards or unsafe conditions to a staff member.</p>
    <p class="indent">31. By participating in services provided by Creative Abilities, the Client acknowledges their understanding of and agreement to adhere to the above-mentioned policies, thereby contributing to a positive and supportive environment for everyone involved in therapy. Violations of this policy may result in the termination of Services.</p>

    <p class="section">FEES PROCEDURE</p>
    <p class="indent">32. The hourly fee for services provided by Creative Abilities will depend on the specific type and location of the Service. It is essential for the Client to discuss the fee structure with Creative Abilities prior to commencing any Services. Billing will occur in 15-minute increments, ensuring that the Client is only charged for the actual time spent on their Services.</p>
    <p class="indent">33. Billable services include, but are not limited to, the following:</p>
    <p class="indent-2">a. Assessments. This includes reviewing previous reports and administering, analyzing, and interpreting formal and informal test results, as well as the time required to analyze audio and/or video recordings.</p>
    <p class="indent-2">b. Planning. This is the Time dedicated to planning and preparing treatment sessions.</p>
    <p class="indent-2">c. Treatments. This refers to the direct Services and work provided to the Client, the Children, or their families.</p>
    <p class="indent-2">d. Consultations. Meetings with the Client, other family members, Creative Abilities staff members, including Medical Professionals and Aide Support, school personnel, and government agency personnel.</p>
    <p class="indent-2">e. Documentation. This encompasses treatment notes and the development of plans such as Individual Service Plans (ISP) and Service Provider Program Plans (SPPP), along with any updates, reports, or letters. Time spent on documentation is billed separately from the Therapy Sessions.</p>
    <p class="indent-2">f. Materials Development. This refers to the creation of specific visuals and social stories for the Client's use.</p>
    <p class="indent-2">g. Travel Time. This is the time spent travelling to and from Creative Abilities' main address or other service locations.</p>
    <p class="indent-2">h. Communication. This includes phone calls, emails, administrative tasks, and any other related professional services.</p>
    <p class="indent">34. The Client is aware that fees are subject to change. Any changes in fees will be communicated to the Client or other family members with at least 30 days' advance notice. Before initiating services, Creative Abilities and the Client will collaborate to discuss the specific services required.</p>

    <p class="section">BILLING PROCEDURE</p>
    <p class="indent">35. All services will be billed every month. For clients who are paying privately, invoices will be sent via email by the middle of the following month. Any questions regarding the content of the invoice must be raised by the due date stated on the invoice, as payments are required to be made by this due date. If payment is not received on time, an interest charge of 2% per month will be added to the late payment, as provided in the invoice.</p>
    <p class="indent">36. For clients utilizing the FSCD funding, it is the Client's responsibility to review the invoice and return a signed copy to the designated FSCD Billing Administrator. The Client's contract for specific Medical Professionals, such as OTs, Psychologists, BCs, SLPs, and PTs, will be billed one and a half (1.5) hours for every hour of services. The Client's or the Children's parent or guardian's signature is required by FSCD for Creative Abilities to obtain reimbursement for the services it rendered.</p>
    <p class="indent">37. Creative Abilities is designated to bill FSCD for all services that are funded by FSCD. However, in cases where Services exceed the available FSCD funding, the Client or the Children's parents or guardians will be responsible for covering the overage, especially in the cases of RAs where RA services are not covered under the FSCD funding.</p>

    <p class="section">PAYMENT PROCEDURE</p>
    <p class="indent">38. Payments for Services rendered will be processed as follows:</p>
    <p class="indent-2">a. Invoice Timing. The Client will receive an invoice by the middle of the following month for all Services provided during the previous month.</p>
    <p class="indent-2">b. Payment Methods. The Client can make payments via accepted methods, including credit or debit cards, bank transfers, or cheques. Details of each method are provided on the invoices.</p>
    <p class="indent-2">c. Due Date. Payment must be made by the due date stated in the invoice. It is the Client's responsibility to ensure that payments are made on time.</p>
    <p class="indent-2">d. Late Payments. If payment is not received by the Due Date, an interest charge, as per paragraph 27, will be applied to the Client.</p>
    <p class="indent-2">e. FSCD Funding. This refers to paragraphs 28 and 29 mentioned above.</p>
    <p class="indent-2">f. Direct Billed Insurance. Creative Abilities will forward its invoices directly to the Client's insurance company.</p>
    <p class="indent">39. Creative Abilities reserves the right to modify payment procedures, provided that prior notice is given to the Client before the modifications are implemented.</p>

    <p class="section">COMPLAINTS</p>
    <p class="indent">40. Misunderstandings and other complex or uncomfortable issues may arise in the client counsellor relationship, which are not uncommon during the therapy process. If a Client is unhappy with their services, they are encouraged to discuss this with their attending Creative Abilities Medical Professionals or Aide Support. The respective Medical Professional or Aide Support must respond within seventy-two (72) hours of a complaint.</p>
    <p class="indent">41. The Client can also communicate their grievances to the managing director of Creative Abilities, Ms. {{ $director }}.</p>

    <p class="section">EMERGENCY PROCEDURES</p>
    <p class="indent">42. In the event of an emergency, clients are advised to call 911 or visit the nearest emergency hospital. The Client acknowledges that Creative Abilities is not equipped to handle urgent crises outside its regular service hours. The Client is expected to have a plan for emergency situations involving the Children.</p>

    <p class="section">TERMINATION OF SERVICES</p>
    <p class="indent">43. Either party may terminate this Agreement by providing written notice to the other party. The Client is encouraged to discuss any concerns regarding termination with their attending Medical Professional or Aide Support to ensure clarity and understanding.</p>
    <p class="indent">44. Termination of either party of this Agreement shall be governed by the following:</p>
    <p class="indent-2">a. Client Withdrawal. The client has the right to withdraw from services at any time without penalty, provided they give the Creative Abilities Therapy Services at least 30 days' written notice.</p>

    <p class="section">SEVERABILITY</p>
    <p class="indent">45. The Parties acknowledge that this Agreement is reasonable, valid, and enforceable; however, if any part of this Agreement is held by a court of competent jurisdiction to be invalid, it is the intent of the parties that such provision be reduced in scope only to the extent deemed necessary to render the provision reasonable and enforceable and the remainder of the provisions of this Agreement shall in no way be affected or invalidated as a result.</p>
    <p class="indent">46. Where any provision in this Agreement is found to be unenforceable, the Principal and the Contractor shall then make reasonable efforts to replace the invalid or unenforceable provision with a valid and enforceable substitute provision, the effect of which is as close as possible to the intended effect of the original invalid or unenforceable provision.</p>

    <p class="section">GOVERNING LAW</p>
    <p class="indent">47. This Agreement shall be governed by and construed in accordance with the laws of the Province of Alberta.</p>
    <p class="indent">48. The courts of the Province of Alberta shall have jurisdiction to settle any dispute arising out of or in connection with this Agreement.</p>

    <p class="section">GENERAL PROVISIONS</p>
    <p class="indent">49. This Agreement contains all terms and conditions agreed to by the parties. Statements or representations which may have been made by any party to this Agreement in the negotiation stages of this Agreement may in some way be inconsistent with this final written Agreement. All such statements are declared to be of no value to either party. Only the written terms of this Agreement shall bind the parties.</p>
    <p class="indent">50. This Agreement may only be amended or modified by a written instrument executed by all of the Parties.</p>
    <p class="indent">51. A waiver by one party of any right or benefit provided in this Agreement does not infer or permit a further waiver of that right or benefit, nor does it infer or permit a waiver of any other right or benefit provided in this Agreement.</p>
    <p class="indent">52. This Agreement shall not be assigned either in whole or in part by any party without the written consent of the other party.</p>
    <p class="indent">53. This Agreement shall pass to the benefit of and be binding upon the parties' respective heirs, executors, administrators, successors, and permitted assigns.</p>
    <p class="indent">54. The clauses, paragraphs, and subparagraphs contained in this Agreement are intended to be read and construed independently of each other. If any part of this Agreement is held to be invalid, this invalidity shall not affect the operation of any other part of this Agreement.</p>
    <p class="indent">55. All of the rights, remedies and benefits provided in this Agreement shall be cumulative and shall not be exclusive of any other such rights, remedies and benefits allowed by law or equity.</p>
    <p class="indent">56. Time is of the essence in this Agreement.</p>
    <p class="indent">57. This Agreement may be executed in counterpart.</p>
    <p class="indent">58. Headings are inserted for the convenience of the Parties only and are not to be considered when interpreting this Agreement. Words in the singular mean and include the plural and vice versa. Words in the masculine gender include the feminine gender and vice versa. Words in the neuter gender include the masculine gender and the feminine gender and vice versa.</p>
    <p class="indent">59. This Agreement may be executed and delivered by electronic means and each of the Parties may rely on such electronic execution as though it were an original hand-written signature.</p>

    <p><strong>IN WITNESS WHEREOF</strong>, the Parties have duly affixed their signatures under hand and seal on the date first written above.</p>

    <p style="margin-top: 16px;"><strong>{{ Str::upper($legalName) }}</strong></p>
    <p><strong>{{ $director }}</strong>, Director<br>I have the authority to bind the Corporation</p>

    <table class="signatures">
        <tr>
            <td><div class="sig-space"></div><div class="sig-rule">Primary Parent/Legal Guardian's Signature</div></td>
            <td><div class="sig-space"></div><div class="sig-rule">Today's Date (MM-DD-YYYY)</div></td>
        </tr>
    </table>

    {{-- Funding Source --}}
    <div class="page-break"></div>
    <table class="header">
        <tr>
            <td style="width: 1.6in;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo">
                @endif
            </td>
            <td>
                <p class="doc-title">FUNDING SOURCE</p>
                <p class="intro">All information you share will remain confidential and will be used exclusively to plan and provide therapy services that best meet your or your child's needs. Thank you!</p>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table class="fields">
        <tr>
            <td style="width: 50%;">
                <div class="label">What is your funding source for your services?</div>
                <div class="field">{{ $fundingSource }}</div>
            </td>
            <td>
                <div class="label">Do you have an approved contract with FSCD?</div>
                <div class="field">
                    <span class="checkbox"></span> Yes &nbsp;&nbsp;&nbsp;
                    <span class="checkbox"></span> No
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Start date of the Contract</div>
                <div class="field"></div>
                <div class="field-hint">MM-DD-YYYY</div>
            </td>
            <td>
                <div class="label">Child's FSCD File ID Number</div>
                <div class="field">{{ $isFscd ? $fscdNumber : '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">FSCD Case Worker's Full Name</div>
                <div class="field">{{ $isFscd ? $caseWorkerName : '' }}</div>
            </td>
            <td>
                <div class="label">FSCD Case Worker's Email</div>
                <div class="field">{{ $isFscd ? $caseWorkerEmail : '' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin-top: 8px; font-size: 9pt; color: #555;">
        If you have an approved FSCD contract, please include a copy of your child's FSCD Agreement
        with the pages you upload.
    </p>
</body>
</html>
