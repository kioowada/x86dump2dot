<?php

function getInstructionSize($line) {
    $byteList = explode(" ", rtrim(explode("\t", $line)[1]));
    return count($byteList);
}

