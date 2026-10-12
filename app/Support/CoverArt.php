<?php

namespace App\Support;

class CoverArt
{
    public static function svg(string $title): string
    {
        $h = crc32($title);
        $hue1 = $h % 360;
        $hue2 = ($hue1 + 40) % 360;
        $initials = mb_strtoupper(collect(explode(' ', $title))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">
          <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="hsl($hue1,70%,38%)"/><stop offset="100%" stop-color="hsl($hue2,80%,22%)"/>
          </linearGradient></defs>
          <rect width="640" height="360" fill="url(#g)"/>
          <text x="50%" y="54%" text-anchor="middle" font-family="Inter,Arial" font-size="120" font-weight="600" fill="rgba(255,255,255,.92)">$initials</text>
        </svg>
        SVG;
    }
}
