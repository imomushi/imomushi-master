<?php

$pipeline_definition = [
    'name' => 'PipelineName',
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
		]
	]
];

class imomushiMaster
{
	const INPUT = '/tmp/input.txt';
	const OUTPUT = '/tmp/output.txt';
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_DONE = 2;
	
	private $segments;
	private $dependencies;

	public function __construct($pipeline)
	{
		$this->segments = $pipeline['segments'];
		$this->dependencies = $pipeline['dependencies'];

		foreach ($this->segments as $k => $v)
		{
			// TODO : mark 'input' segments as finished
			if ($v['type'] === 'input')
			{
				$this->segments[$k]['result'] = ['text' => 'ABCDEFG'];
				$this->segments[$k]['status'] = self::STATUS_DONE;
			}
			else
			{
				$this->segments[$k]['status'] = self::STATUS_WAITING;
			}
		}
	}
	
	private function writeInput($id, $function, $args)
	{
		$input = json_encode(['id' => $id, 'function' => $function, 'args' => $args]);
		file_put_contents(self::INPUT, $input."\n", FILE_APPEND);
	}

	private function updatePipelineStatus($id, $result)
	{
		$this->segments[$id]['result'] = $result;
		$this->segments[$id]['status'] = self::STATUS_DONE;
	}

	private function getRunnableSegments()
	{
		$segments = $this->segments;

		// Remove segements which are done or running.
		foreach($segments as $k => $v)
		{
			if ($segments[$k]['status'] !== self::STATUS_WAITING)
			{
				unset($segments[$k]);
			}
		}
		
		// Remove segements which cannot be started
		foreach($this->dependencies as $d)
		{
			if (isset($segments[$d['to']]))
			{
				if (isset($this->segments[$d['from']]['result']))
				{
					if (isset($segments[$d['to']]['input']))
					{
						$segments[$d['to']]['input'] = array_merge($segments[$d['to']]['input'], $this->segments[$d['from']]['result']);
					}
					else
					{
						$segments[$d['to']]['input'] = $this->segments[$d['from']]['result'];
					}
				}
				else
				{
					unset($segments[$d['to']]);
				}
			}
		}
		return $segments;
	}

	public function receiveResult()
	{
		// TODO
		foreach ($this->segments as $k => $v)
		{
			if ($v['status'] === self::STATUS_RUNNING)
			{
				$this->updatePipelineStatus($k, ['hoge'.$k => 'fuga']);
			}
		}
	}

	public function forwardSegments()
	{
		$continue = false;
		foreach ($this->getRunnableSegments() as $k => $v)
		{
			$this->writeInput($k, $v['function'], $v['input']);
			$this->segments[$k]['status'] = self::STATUS_RUNNING;
			$continue = true;
		}
		return $continue;
	}
}

$master = new imomushiMaster($pipeline_definition);
while ($master->forwardSegments())
{
	$master->receiveResult();
}
