<?php

$bb_currentBB = array(
    "entryPoint"  => null,
    "instPool"    => array(),
    "instCount"   => 0
    "fallthrough" => null,
    "taken"       => null,
);

$bb_pool = array();

function bb_clearCurrentBB() {
    global $bb_currentBB;

    $bb_currentBB = array(
        "entryPoint"  => null,
        "instPool"    => array(),
        "instCount"   => 0
        "fallthrough" => null,
        "taken"       => null,
    );
}

function bb_addInstruction($inst) {
    global $bb_currentBB;

    array_push($bb_currentBB["instPool"], $inst);
    $bb_currentBB["instCount"]++;
}

function bb_printBox() {
    global $bb_currentBB;

    $recordStr = "  bb" . $bb_currentBB["entryPoint"] . " [label={" . $bb_currentBB["entryPoint"] . " | ";

    // for-each-instructions
    $instStr = "";
    while (($inst = array_pop($bb_currentBB["instPool"])) != null) {
        $instStr = $inst["addr"] . " : " . $inst["opcode"] . " " . $inst["param"] . "\\n" . $instStr;
    }

    echo $recordStr . $instStr . "}]\n";
}

function bb_printLink() {
    global $bb_currentBB;

    if ($bb_currentBB["fallthrough"]) {
        echo "  bb" . $bb_currentBB["entryPoint"] . " -> bb" . $bb_currentBB["fallthrough"] . "\n";
    }

    if ($bb_currentBB["taken"]) {
        echo "  bb" . $bb_currentBB["entryPoint"] . " -> bb" . $bb_currentBB["taken"] . "\n";
    }
}

function bb_save() {
    global $bb_currentBB;
    global $bb_pool;

    array_push($bb_pool, $bb_currentBB);
}

