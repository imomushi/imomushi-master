<?php

$config = [];

$config['pipeline_definition'] = [
/* PIPELINE EXAMPLE */
/*
	'PIPELINE_NAME' => [
		'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/PATH1/PATH2',
				'protocol' => 'http',
				'method' => 'post', // default: 'get'
			],
			2 => [
				'type' => 'function',
				'function' => 'FUNCTION_NAME',
				'config' => [
                    'ENV_VALUE_NAME' => getenv('ENVIRONMENTAL_VALUE')
                ],
				'error_config' => [
					'skip'   => true, // default: false
					'report' => true, // default: true
				],
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
				'to' => 99,
			]
		]
	],
*/
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
	'error_pattern' => [
        'segments' => [
			1 => [
				'type' => 'input',
				'path' => '/imomushi/basic/error',
				'protocol' => 'http',
			],
			2 => [
				'type' => 'function',
				'function' => 'BasicError',
				'error_config' => [
					'skip'   => true,
					'report' => false,
				],
			],
			3 => [
				'type' => 'function',
				'function' => 'BasicError',
				'error_config' => [
					'skip'   => false,
					'report' => true,
				],
			],
			4 => [
				'type' => 'function',
				'function' => 'BasicEcho',
				'config' => [
					'text'   => 'Segment 4',
				],
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
				'from' => 3,
				'to' => 4,
			],[
				'from' => 4,
				'to' => 99,
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
				'method' => 'post',
			],
			2 => [
				'type' => 'function',
				'function' => 'LineRequestParser',
			],
			3 => [
				'type' => 'function',
				'function' => 'LineSendMessage',
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
