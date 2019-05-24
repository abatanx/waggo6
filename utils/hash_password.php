<?php

require __DIR__ . '/../waggo-cli.php';

if( count($argv)<=2 )
{
	die("Usage: {$argv[0]} domain[:port] {raw-password-string}\n");
}

echo wg_password_hash($argv[2]) . "\n";
