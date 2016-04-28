<?php

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
	]
];

class Pipeline
{
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_DONE    = 2;

	private $id;
	private $segments;
	private $dependencies;

	public function __construct($id, $pipeline, $args)
	{
		$this->id = $id;
		$this->segments = $pipeline['segments'];
		$this->dependencies = $pipeline['dependencies'];

		foreach ($this->segments as $k => $v)
		{
			if ($v['type'] === 'input')
			{
				$this->segments[$k]['result'] = $args;
				$this->segments[$k]['status'] = self::STATUS_DONE;
			}
			else
			{
				$this->segments[$k]['status'] = self::STATUS_WAITING;
			}
		}
	}

	public function updatePipelineStatus($id, $result)
	{
		$this->segments[$id]['result'] = $result;
		$this->segments[$id]['status'] = self::STATUS_DONE;
	}

	public function getRunnableSegments()
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

	public function getRunningSegments()
	{
		$segments = [];
		foreach ($this->segments as $k => $v)
		{
			if ($v['status'] === self::STATUS_RUNNING)
			{
				$segments[$k] = $v;
			}
		}
		return $segments;
	}

	public function updateSegmentStatus($id, $status)
	{
		$this->segments[$id]['status'] = $status;
	}
}


class ImomushiMaster
{
	const USER_INPUT  = '/tmp/user_input.txt';
	const USER_OUTPUT = '/tmp/user_output.txt';
	const INPUT       = '/tmp/input.txt';
	const OUTPUT      = '/tmp/output.txt';
	const LOG_LEVEL_INFO = 'INFO';
	const LOG_LEVEL_ERROR = 'ERROR';
	
	private $pipeline_definition;
	private $pipelines;
	private $cnt;
	private $dependencies;
	private $file_count;

	public function __construct($pipeline)
	{
		$this->pipeline_definition = $pipeline;
		$this->cnt = 0;
		$this->pipelines = [];
		$this->file_count = [];

		// check results file line count
		$fp = fopen(self::OUTPUT, 'r' );
		for( $count = 0; fgets( $fp ); $count++ );
		$this->file_count['output'] = $count;
		fclose($fp);

		// check results file line count
		$fp = fopen(self::USER_INPUT, 'r' );
		for( $count = 0; fgets( $fp ); $count++ );
		$this->file_count['user_input'] = $count;
		fclose($fp);
	}
	
	public function addPipeline($name, $pipeline, $args)
	{
		$id = $name.':'.++$this->cnt;
		$this->pipelines[$id] = new Pipeline($id, $pipeline, $args);
	}

	private function log($message, $level = self::LOG_LEVEL_INFO)
	{
		echo '['.$level.'] : '.$message."\n";
	}

	private function writeInput($pipeline_id, $segment_id, $function, $args)
	{
		$input = json_encode(['pipeline_id' => $pipeline_id, 'segment_id' => $segment_id, 'function' => $function, 'args' => $args]);
		file_put_contents(self::INPUT, $input."\n", FILE_APPEND);
	}

	private function checkQue($file_name, $file_id)
	{
		$fp = fopen( $file_name, 'r' );
		$count = 0;
		$new_lines = [];
		
		while($line = fgets( $fp ))
		{
			$count++;
			if ($count > $this->file_count[$file_id])
			{
				$this->file_count[$file_id] = $count;
				$new_lines[] = json_decode($line);
			}
		}
		fclose($fp);
		return $new_lines;
	}

	public function receive()
	{
		$changed = false;
		while (1)
		{
			foreach ($this->checkQue(self::OUTPUT, 'output') as $output)
			{
				$this->log('Read output (ID:'.$output->pipeline_id.':'.$output->segment_id.') from '.self::OUTPUT);
				$this->pipelines[$output->pipeline_id]->updatePipelineStatus($output->segment_id, (array)$output->result);
				$changed = true;
			}
			foreach ($this->checkQue(self::USER_INPUT, 'user_input') as $output)
			{
				$this->log('Read piline (ID:'.$output->pipeline_id.') from '.self::USER_INPUT);
				$this->addPipeline($output->pipeline_id, $this->pipeline_definition[$output->pipeline_id], (array)$output->args);
				$changed = true;
			}
			if ($changed) break;
			usleep(10*1000);
		}
	}

	public function forwardSegments()
	{
		$continue = false;
		foreach ($this->pipelines as $id => $pipeline)
		{
			$finished = true;
			foreach ($pipeline->getRunnableSegments() as $k => $v)
			{
				$this->log('Send segment (ID:'.$id.':'.$k.') to '.self::INPUT);
				$this->writeInput($id, $k, $v['function'], $v['input']);
				$pipeline->updateSegmentStatus($k, Pipeline::STATUS_RUNNING);
				$finished = false;
			}
			// Check Running Segments
			foreach ($pipeline->getRunningSegments() as $k => $v)
			{
				$this->log('Watiting running segments (ID:'.$id.':'.$k.')');
				$finished = false;
			}
			if ($finished === true)
			{
				unset($this->pipelines[$id]);
				$this->log('ALL segments for (ID:'.$id.') have been finished.');
			}
			$continue = $continue || !$finished;
		}
		
		return $continue;
	}
}

$master = new ImomushiMaster($pipeline_definition);
while (1)
{
	$master->forwardSegments();
	$master->receive();
}