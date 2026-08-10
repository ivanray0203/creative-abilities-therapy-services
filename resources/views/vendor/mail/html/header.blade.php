@props(['url'])
{{--
    Overrides the framework header, which prints `app.name` — the "CATS"
    acronym — as plain text. Every email leads with the logo instead.

    The image is inlined as a `data:` URI rather than linked, so it renders
    without the recipient's client being able to reach `APP_URL`. Note that
    some clients (Gmail among them) refuse `data:` image sources, so the alt
    text still has to carry the name on its own.
--}}
@php($logo = \App\Support\BrandLogo::dataUri())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logo !== '')
<img src="{{ $logo }}" class="logo" alt="{{ config('cats.name') }}">
@else
{{ config('cats.name') }}
@endif
</a>
</td>
</tr>
