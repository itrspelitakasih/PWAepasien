@php($themeCss = \App\Models\Setting::current()->themeCss())
@if ($themeCss)
    <meta name="theme-color" content="{{ \App\Models\Setting::current()->theme_color }}">
    <style>{!! $themeCss !!}</style>
@endif
