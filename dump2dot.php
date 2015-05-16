#!/usr/bin/php
<?php

require "branchinstructions.php";
require "jumpinstructions.php";
require "returninstructions.php";
require "instruction.php";
require "basicblock.php";

$filename = "./branch";

$fp = fopen($filename, 'r');

if (!fp) {
    die("could not open file  $filename");
}

$allInstructions = array();
$entryPoint = null;
$entryPointName = "main"; // TODO parameterize
/*
 * 全命令を構造体にまとめる
 */
do { // 関数単位/*{{{*/
    do {
        $line = rtrim(fgets($fp));
    } while (!preg_match("/<*>\:\z/", $line));

    $isMainFunc = preg_match("/<main>\:\z/", $line)?true:false;

    while (($str = rtrim(fgets($fp))) != "") { // 命令単位
        list($addr, $dummy, $mnemonic) = explode("\t", $str);

        // addr
        $addr = "0x" . rtrim(ltrim($addr),":");

        // opcode
        $mnemonic = ltrim($mnemonic);
        $idx = strpos($mnemonic, " ");
        if ($idx) {
            $opcode = substr($mnemonic, 0, $idx);
            $param = ltrim(substr($mnemonic, $idx));
        } else {
            $opcode = $mnemonic;
            $param = "";
        }

        // 次の命令のアドレス
        $instSize = getInstructionSize($str);
        $nextAddrValue = $addr + $instSize;
        $nextAddr = "0x" . dechex($nextAddrValue);

        // 追加
        $allInstructions[$addr] = array(
            "addr"          => $addr,
            "opcode"        => $opcode,
            "param"         => $param,
            "isEntryPoint"  => $isMainFunc,
            "nextAddr"      => $nextAddr,
            "instSize"      => $instSize,
            "mnemonic"      => $mnemonic,
        );


        if ($isMainFunc) {
            $entryPoint = $addr;
            $isMainFunc = false;
        }
    }
} while (!feof($fp));/*}}}*/

if ($entryPoint == null) {
    die("could not find main entry point");
}

/*
 * こっからフロー解析
 */

// 関数のコールスタック
$callStack = array();
$savedCallStack = array();
function function_call($returnAddr, $name) {
    global $callStack;
//    echo "### FUNCTION $name CALLED\n";

    array_push($callStack, array(
        "name" => $name,
        "return" => $returnAddr,
    ));
}
function function_return() {
    global $callStack;
    $ret = array_pop($callStack);

//    echo "#### FUNCTION $name RETURNED\n";

    return $ret;
}
function save_callstack() {
    global $savedCallStack;
    global $callStack;

    $savedCallStack = array();
    for ($i = 0; $i < count($callStack); $i++) {
        array_push($savedCallStack, $callStack[$i]);
    }
}
function restore_callstack() {
    global $savedCallStack;
    global $callStack;

    $callStack = array();
    for ($i = 0; $i < count($savedCallStack); $i++) {
        array_push($callStack, $savedCallStack[$i]);
    }
}



function_call(null, "main");

echo "digraph main {\n";
echo "  graph [nodesep = 0.7];\n";
echo "  node [shape = record, height = 0.01,\n";
echo "   fontname = \"Helvetica\", fontsize = 9];\n";

startAnalysis($allInstructions[$entryPoint]);
bb_printAllLinks();
echo "}\n";

function startAnalysis($nowInst) {
    global $callStack;
    global $jiArray;
    global $biArray;
    global $riArray;
    global $bb_currentBB;
    global $allInstructions;

    if (bb_checkExistance($nowInst["addr"]) == true) {
        // このBBはもう解析済みなので飛ばす
        return;
    }

    $bb_currentBB["entryPoint"] = $nowInst["addr"];

    while (count($callStack) > 0) {
        $bbEnd = false; // ベーシックブロックの区切り

        if (in_array($nowInst["opcode"], $biArray)) { // 条件分岐
            $nextAddr = "0x" . explode(" ", $nowInst["param"])[0];
            $returnAddr = $nowInst["nextAddr"];

            bb_addInstruction($nowInst);
            bb_setTaken($nextAddr);
            bb_setFallthrough($returnAddr);

            bb_printBox();
            bb_save();
            bb_clearCurrentBB();

            // DFSで探索する
            // TODO ここでコールスタックをそれぞれにセーブしないといけない
            save_callstack();
            startAnalysis($allInstructions[$nextAddr]);
            restore_callstack();
            startAnalysis($allInstructions[$returnAddr]);

            return;
        } else if (in_array($nowInst["opcode"], $jiArray)) { // ジャンプ(関数CALL)
//            echo "JUMP\n";

            $nextAddr = "0x" . explode(" ", $nowInst["param"])[0];
            $returnAddr = $nowInst["nextAddr"];

            // スタック操作
            function_call($returnAddr, "TODO"); // TODO name
            $bbEnd = true; // これどうしよう? FIXME

            bb_setCall($nextAddr);
        } else if (in_array($nowInst["opcode"], $riArray)) { // 関数RET
            $call = function_return();
            $nextAddr = $call["return"];
            $bbEnd = true; // これもどうしよう? FIXME
            bb_setReturn($nextAddr);
        } else {
            $nextAddr = $nowInst["nextAddr"];
        }

        // BBに $nowInst を追加
        bb_addInstruction($nowInst);

        $nowInst = $allInstructions[$nextAddr];

        if ($bbEnd) {
            bb_printBox();
            bb_save();
            bb_clearCurrentBB();

            $bb_currentBB["entryPoint"] = $nextAddr;
        }
    }
}

/*
// とりあえずnextAddrをたどって出してみる
$nowInst = $allInstructions[$entryPoint];
do {
    echo $nowInst["addr"]. "->";

    // ジャンプの確認
    if (in_array($nowInst["opcode"], $jiArray)) {
        echo "JUMP";
        echo "\n\n";

        $addr = "0x" . explode(" ", $nowInst["param"])[0];
        echo "$addr\n";

        echo "\n\n";
        $nextAddr = $addr;
    } else {
        $nextAddr = $nowInst["nextAddr"];
    }

    if ($nowInst["addr"] == $exitPoint) {
        break;
    }
    $nowInst = $allInstructions[$nextAddr];
} while ($nowInst);
 */
