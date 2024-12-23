<?php


if (!array_key_exists('phalcon_commands', $GLOBALS)) {
    $GLOBALS['phalcon_commands'] = [];
}

$GLOBALS['phalcon_commands'][] = \App\Command\Migration\Diff::class;
$GLOBALS['phalcon_commands'][] = \App\Command\Migration\Down::class;
$GLOBALS['phalcon_commands'][] = \App\Command\Migration\Execute::class;
$GLOBALS['phalcon_commands'][] = \App\Command\Migration\Up::class;
$GLOBALS['phalcon_commands'][] = \App\Command\Migration\Generate::class;
