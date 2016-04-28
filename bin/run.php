<?php

require_once __DIR__."/../config/config.php";
require_once __DIR__."/../src/ImomushiMaster.php";

$master = new ImomushiMaster($config['pipeline_definition']);
while (1)
{
	$master->forwardSegments();
	$master->receive();
}