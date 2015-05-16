<?php

$bb_currentBB = array(
    "entryPoint"  => null,
    "instPool"    => array(),
    "instCount"   => 0,
    "fallthrough" => null,
    "taken"       => null,
    "call"        => null,
    "return"      => null,
);

$bb_pool = array();

function bb_clearCurrentBB() {
    global $bb_currentBB;

    $bb_currentBB = array(
        "entryPoint"  => null,
        "instPool"    => array(),
        "instCount"   => 0,
        "fallthrough" => null,
        "taken"       => null,
        "call"        => null,
        "return"      => null,
    );
}

function bb_checkExistance($entryPoint) {
    if (isset($bb_pool[$entryPoint])) {
        return true;
    } else {
        return false;
    }
}

function bb_printAllLinks() {
    global $bb_pool;

    foreach ($bb_pool as $bb) {
        bb_printLink($bb);
    }
}

function bb_setCall($addr) {
    global $bb_currentBB;
    $bb_currentBB["call"] = $addr;
}

function bb_setReturn($addr) {
    global $bb_currentBB;
    $bb_currentBB["return"] = $addr;
}

function bb_setTaken($addr) {
    global $bb_currentBB;
    $bb_currentBB["taken"] = $addr;
}

function bb_setFallthrough($addr) {
    global $bb_currentBB;
    $bb_currentBB["fallthrough"] = $addr;
}

function bb_addInstruction($inst) {
    global $bb_currentBB;

    array_push($bb_currentBB["instPool"], $inst);
    $bb_currentBB["instCount"]++;
}

function bb_printBox() {
    global $bb_currentBB;

    $recordStr = "  bb" . $bb_currentBB["entryPoint"] . " [label=\"{" . $bb_currentBB["entryPoint"] . " | ";

    // for-each-instructions
    $instStr = "";
    while (($inst = array_pop($bb_currentBB["instPool"])) != null) {
        $mne = str_replace("<", "\<", $inst["mnemonic"]);
        $mne = str_replace(">", "\>", $mne);
        $instStr = "{" . $inst["addr"] . " | " . $mne . "}|" . $instStr;
    }
    $instStr = rtrim($instStr, "|");

    echo $recordStr . $instStr . "}\"];\n";
}

function bb_printLink($bb) {

    if ($bb["fallthrough"]) {
        echo "  bb" . $bb["entryPoint"] . " -> bb" . $bb["fallthrough"] . " [style = dashed];\n";
    }

    if ($bb["taken"]) {
        echo "  bb" . $bb["entryPoint"] . " -> bb" . $bb["taken"] . ";\n";
    }

    if ($bb["call"]) {
        echo "  bb" . $bb["entryPoint"] . " -> bb" . $bb["call"] . " [arrowtail = odot];\n";
    }

    if ($bb["return"]) {
        echo "  bb" . $bb["entryPoint"] . " -> bb" . $bb["return"] . " [arrowtail = dot];\n";
    }

}

function bb_save() {
    global $bb_currentBB;
    global $bb_pool;

    $bb_pool[$bb_currentBB["entryPoint"]] = $bb_currentBB;
//    array_push($bb_pool, $bb_currentBB);
}

