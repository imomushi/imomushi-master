<?php

$config = [];

$config['pipeline_definition'] = [
	'fbbot' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/fbbot',
				'protocol' => 'http',
				'method' => 'post',
			],
			2 => [
				'type' => 'function',
				'function' => 'FacebookRequestParser',
			],
			3 => [
				'type' => 'function',
				'function' => 'FacebookSendMessage',
				'config' => [
					 'access_token' => getenv('FB_ACCESS_TOKEN')
				]
			],
			99 => [
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
				'from' => 1,
				'to' => 99,
			]
		]
	],
	'fbbot_verify' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/fbbot',
				'protocol' => 'http',
			],
			2 => [
				'type' => 'function',
				'function' => 'FBBotVerify',
				'config' => ['verify_token' => getenv('VERIFY_TOKEN')]
			],
			3 => [
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
			]
		]
	],
	'rakuten' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/rakuten',
				'protocol' => 'http',
				'method' => 'post',
			],
			2 => [
				'type' => 'function',
				'function' => 'LineRequestParser',
			],
			3 => [
				'type' => 'function',
				'function' => 'RakutenApi',
				'config' => [
					 'application_id'     => getenv('RAKUTEN_APPLICATION_ID'),
				]
			],
			4 => [
				'type' => 'function',
				'function' => 'LineApi',
				'config' => [
					 'line_channel_id'     => getenv('LINE_CHANNEL_ID'),
					 'line_channel_secret' => getenv('LINE_CHANNEL_SECRET'),
					 'line_channel_mid'    => getenv('LINE_CHANNEL_MID'),
				]
			],
			5 => [
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
				'from' => 3,
				'to' => 4,
			],[
				'from' => 2,
				'to' => 4,
			],[
				'from' => 1,
				'to' => 5,
			]
		]
	],
	'repeat' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/repeat',
				'protocol' => 'http',
			],
			2 => [
				'type' => 'function',
				'function' => 'LineRequestParser',
			],
			3 => [
				'type' => 'function',
				'function' => 'LineEchoBack',
				'config' => [
					 'line_channel_id'     => getenv('LINE_CHANNEL_ID'),
					 'line_channel_secret' => getenv('LINE_CHANNEL_SECRET'),
					 'line_channel_mid'    => getenv('LINE_CHANNEL_MID'),
				]
			],
			4 => [
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
				'from' => 1,
				'to' => 4,
			]
		]
	],
	'pipeline1' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/bot',
				'protocol' => 'http',
			],
			2 => [
				'type' => 'function',
				'function' => 'EchoStdout',
			],
			3 => [
				'type' => 'function',
				'function' => 'EchoStdout',
			],
			4 => [
				'type' => 'function',
				'function' => 'EchoStdout',
			],
			5 => [
				'type' => 'function',
				'function' => 'EchoStdout',
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
				'from' => 5,
				'to' => 6,
			]
		]
	],
	'pipeline2' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/hoge',
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
