@component('mail::message')
# You're Invited to an Interview

Hi {{ $application->first_name }},

We would like to meet you about the **{{ $application->position_applied }}** position.

@component('mail::table')
| | |
|:--|:--|
| **When** | {{ $application->interview_schedule ?? 'To be confirmed' }} |
| **Format** | {{ $application->interview_platform_label ?? 'To be confirmed' }} |
| **Reference** | {{ $application->reference_number }} |
@endcomponent

@if ($application->interview_platform === 'video' && $application->interview_meeting_link !== null)
Join the video call at the time above using the Google Meet link below.

@component('mail::button', ['url' => $application->interview_meeting_link])
Join Google Meet
@endcomponent

Or copy this link into your browser: {{ $application->interview_meeting_link }}
@elseif ($application->interview_platform === 'video')
We will send you the meeting link before the interview.
@elseif ($application->interview_platform === 'phone')
We will call you on {{ $application->phone }} at the time above.
@elseif ($application->interview_platform === 'in-person')
We will confirm the address with you before the interview.
@endif

If this time does not work for you, reply to this email and we will rearrange it.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
