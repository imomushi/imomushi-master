<?php

require_once __DIR__."/ImomushiMaster.php";

$pipeline_definition = [
	'pipeline1' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'protocol' => 'HTTP',
			],
			2 => [
				'type' => 'function',
				'function' => 'func1',
			],
			3 => [
				'type' => 'function',
				'function' => 'func2',
			],
			4 => [
				'type' => 'function',
				'function' => 'func2',
			],
			5 => [
				'type' => 'function',
				'function' => 'func3',
			],
			6 => [
				'type' => 'output',
				'protocol' => 'HTTP',
			]
		],
		'dependencies' => [
			[
				'from' => 1,
				'to' => 2,
			],[
				'from' => 2,
				'to' => 3,
			],[
				'from' => 2,
				'to' => 4,
			],[
				'from' => 3,
				'to' => 5,
			],[
				'from' => 4,
				'to' => 5,
			],[
				'from' => 5,
				'to' => 6,
			]
		]
	]
];

$master = new ImomushiMaster($pipeline_definition);
while (1)
{
	$master->forwardSegments();
	$master->receive();
}