{{-- Decorative, non-interactive health-doodle wallpaper behind patient pages.
     Pass `contained` => true to fill the nearest positioned parent instead of the viewport,
     and `light` => true for white strokes on a dark/colored surface. --}}
<div class="pointer-events-none {{ ($contained ?? false) ? 'absolute' : 'fixed' }} inset-0 z-0 overflow-hidden {{ ($light ?? false) ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}" aria-hidden="true">
    <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
        <defs>
            {{-- 40x40 line icons --}}
            <g id="dd-heart"><path d="M20 34C6 24 4 14 11 9c4-3 8-1 9 3 1-4 5-6 9-3 7 5 5 15-9 25z"/></g>
            <g id="dd-cross"><path d="M15 5h10v10h10v10H25v10H15V25H5V15h10z"/></g>
            <g id="dd-pill"><rect x="3" y="13" width="34" height="14" rx="7"/><path d="M20 13v14M9 18v4"/></g>
            <g id="dd-syringe"><path d="M3 14v12M3 20h9M12 15h16v10H12zM28 20h10M17 15v4M22 15v4M27 15v3"/></g>
            <g id="dd-thermo"><path d="M16 8a4 4 0 0 1 8 0v16.5a7 7 0 1 1-8 0zM20 14v14"/><circle cx="20" cy="29" r="2"/></g>
            <g id="dd-drop"><path d="M20 4C20 4 8 18 8 26a12 12 0 0 0 24 0C32 18 20 4 20 4z"/><path d="M14 27a6 6 0 0 0 5 5"/></g>
            <g id="dd-steth"><path d="M9 4v14a8 8 0 0 0 16 0V4M17 26v3a7 7 0 0 0 14 0v-4"/><circle cx="31" cy="21" r="3"/></g>
            <g id="dd-ecg"><path d="M1 20h10l4-11 6 22 4-15 3 4h11"/></g>
            <g id="dd-bandage"><rect x="3" y="13" width="34" height="14" rx="7"/><rect x="14" y="13" width="12" height="14"/><path d="M18 18h.01M22 18h.01M18 22h.01M22 22h.01"/></g>
            <g id="dd-hospital"><path d="M6 36V12h28v24M2 36h36M17 36v-8h6v8M20 15v8M16 19h8"/></g>
            <g id="dd-ambulance"><path d="M3 28V13h20v15M23 18h8l6 6v4M3 28h4M15 28h8M31 28h6M13 16v6M10 19h6"/><circle cx="11" cy="29" r="3"/><circle cx="30" cy="29" r="3"/></g>
            <g id="dd-spark"><path d="M10 2v16M2 10h16"/></g>

            <pattern id="dd-pattern" width="300" height="300" patternUnits="userSpaceOnUse">
                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke-opacity=".13">
                    <use href="#dd-heart"     transform="translate(18 16) rotate(-12 20 20)"/>
                    <use href="#dd-pill"      transform="translate(105 6) rotate(28 20 20)"/>
                    <use href="#dd-cross"     transform="translate(205 22) rotate(10 20 20) scale(.85)"/>
                    <use href="#dd-thermo"    transform="translate(252 96) rotate(14 20 20) scale(.9)"/>
                    <use href="#dd-syringe"   transform="translate(24 96) rotate(-28 20 20)"/>
                    <use href="#dd-ecg"       transform="translate(96 92) scale(1.1)"/>
                    <use href="#dd-drop"      transform="translate(186 126) rotate(-8 20 20) scale(.85)"/>
                    <use href="#dd-hospital"  transform="translate(12 178)"/>
                    <use href="#dd-steth"     transform="translate(92 168) rotate(10 20 20)"/>
                    <use href="#dd-bandage"   transform="translate(158 196) rotate(-24 20 20) scale(.9)"/>
                    <use href="#dd-ambulance" transform="translate(228 190) scale(.85)"/>
                    <use href="#dd-heart"     transform="translate(60 248) rotate(14 20 20) scale(.6)"/>
                    <use href="#dd-cross"     transform="translate(130 254) rotate(-10 20 20) scale(.55)"/>
                    <use href="#dd-pill"      transform="translate(236 252) rotate(-35 20 20) scale(.6)"/>
                    <use href="#dd-spark"     transform="translate(170 52) scale(.5)"/>
                    <use href="#dd-spark"     transform="translate(58 62) scale(.4)"/>
                    <use href="#dd-spark"     transform="translate(280 160) scale(.45)"/>
                    <use href="#dd-spark"     transform="translate(120 226) scale(.4)"/>
                    <circle cx="150" cy="24" r="2.5"/>
                    <circle cx="20" cy="150" r="2.5"/>
                    <circle cx="214" cy="170" r="2.5"/>
                    <circle cx="288" cy="40" r="2.5"/>
                    <circle cx="70" cy="140" r="2"/>
                    <circle cx="190" cy="270" r="2"/>
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#dd-pattern)"/>
    </svg>
</div>
