<?php

$fp = @fsockopen("host.docker.internal", 6001, $errno, $errstr, 5);

if ($fp) {
    echo "CONNECTED\n";
    fclose($fp);
} else {
    echo "FAILED\n";
    echo $errno . "\n";
    echo $errstr . "\n";
}