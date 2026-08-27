@component('mail::message')
# Your Interview Has Been Rescheduled

Hi {{ $application->first_name }},

Your interview for the **{{ $application->position_applied }}** position has been moved.

@if ($previousSchedule !== null)
**Previously:** ~~{{ $previousSchedule }}~~
@endif

@component('mail::table')
| | |
|:--|:--|
| **Now** | {{ $application->interview_schedule ?? 'To be confirmed' }} |
| **Format** | {{ $application->interview_platform_label ?? 'To be confirmed' }} |
| **Reference** | {{ $application->reference_number }} |
@endcomponent

@if ($application->interview_platform === 'video')
We will send you the meeting link before the interview.
@elseif ($application->interview_platform === 'phone')
We will call you on {{ $application->phone }} at the new time.
@elseif ($application->interview_platform === 'in-person')
We will confirm the address with you before the interview.
@endif

If this new time does not work for you, reply to this email and we will rearrange it.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
