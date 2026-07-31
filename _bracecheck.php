<?php
$src = file_get_contents($argv[1]);
$tokens = token_get_all($src);
$depth = 0;
$min = 0; $minLine = 0;
$inBlock = false;
foreach ($tokens as $t) {
    if (!is_array($t)) {
        $c = $t;
    } else {
        $c = $t[1];
        $line = $t[2];
    }
    // only track within login region by line
    if (is_array($t)) {
        $ln = $t[2];
        if ($ln >= 464 && $ln <= 622) $inBlock = true;
    }
    $opens = substr_count($c, '{');
    $closes = substr_count($c, '}');
    if ($opens || $closes) {
        $depth += $opens - $closes;
        if (is_array($t) && $t[2] >= 464 && $t[2] <= 8504) {
            if ($depth < $min) { $min = $depth; $minLine = $t[2]; }
        }
    }
}
echo "global brace balance = $depth (should be 0)\n";
echo "min depth in 464..8504 = $min at line $minLine\n";
