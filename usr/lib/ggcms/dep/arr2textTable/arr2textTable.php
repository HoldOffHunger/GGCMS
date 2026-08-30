<?php
	#SOURCE: https://github.com/aidanlister/code/blob/master/function.array2table.php
function arr2textTable($a, $b = array(), $c = 0) {
    $d = array();
    $e = "+";
    $f = "|";
    $g = 0;
    foreach ($a as $h)
        foreach ($h AS $i => $j) {
            $j = substr(str_replace(array("\n","\r","\t","  "), " ", $j), 0, 48);
            $k = strlen($j);
            $l = strlen($i);
            $k = $l > $k ? $l : $k;
            if (!isset($d[$i]) || $k > $d[$i])
                $d[$i] = $k;
        }
    foreach ($d as $m => $h) {
        $e .= str_pad("", $h + 2, "-") . "+";
            if (strlen($m) > $h)
                $m = substr($m, 0, $h - 1);
            $f .= " " . str_pad($m, $h, " ", isset($b[$g]) ? $b[$g] : $c) . " |";
            $g++;
    }
    $n = "{$e}\n{$f}\n{$e}\n";
    foreach ($a as $h) {
        $n .= "|";
        $g = 0;
        foreach ($h as $i => $o) {
            $n .= " " . str_pad($o, $d[$i], " ", isset($b[$g]) ? $b[$g] : $c) . " |";
            $g++;
        }
        $n .= "\n";
    }
    $p = array(
        "`((?:https?|ftp)://\S+[[:alnum:]]/?)`si",
        "`((?<!//)(www\.\S+[[:alnum:]]/?))`si"
    );
    $q = array(
        "<a href=\"$1\" rel=\"nofollow\">$1</a>",
        "<a href=\"http://$1\" rel=\"nofollow\">$1</a>"
    );
    return preg_replace($p, $q, "{$n}{$e}\n");
}
?>