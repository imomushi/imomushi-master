<?php

$config = [];

$config['pipeline_definition'] = [
	'pipeline1' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/bot',
				'protocol' => 'http',
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
				'protocol' => 'http',
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
				'from' => 1,
				'to' => 6,
			]
		]
	],
	'pipeline2' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/hoge',
				'protocol' => 'http',
			],
			2 => [
				'type' => 'output',
				'protocol' => 'http',
			]
		],
		'dependencies' => [
			[
				'from' => 1,
				'to' => 2,
			]
		]
	]
];
