<?php

$w = 1400;
$h = 200;
$im = imagecreatetruecolor($w, $h);

$lerp = static fn (int $a, int $b, float $t): int => (int) round($a + (($b - $a) * $t));

$c1 = [0x63, 0x66, 0xf1];
$c2 = [0x8b, 0x5c, 0xf6];
$c3 = [0x06, 0xb6, 0xd4];

for ($x = 0; $x < $w; $x++) {
    $t = $x / ($w - 1);

    if ($t <= 0.48) {
        $u = $t / 0.48;
        $r = $lerp($c1[0], $c2[0], $u);
        $g = $lerp($c1[1], $c2[1], $u);
        $b = $lerp($c1[2], $c2[2], $u);
    } else {
        $u = ($t - 0.48) / 0.52;
        $r = $lerp($c2[0], $c3[0], $u);
        $g = $lerp($c2[1], $c3[1], $u);
        $b = $lerp($c2[2], $c3[2], $u);
    }

    $col = imagecolorallocate($im, $r, $g, $b);
    imageline($im, $x, 0, $x, $h - 1, $col);
}

$path = __DIR__.'/../public/images/pdf-header-gradient.png';
imagepng($im, $path);
imagedestroy($im);

echo $path.' ('.filesize($path)." bytes)\n";
