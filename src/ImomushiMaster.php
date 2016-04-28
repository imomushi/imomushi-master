<?php

require_once __DIR__."/Pipeline.php";

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
	
	public function addPipeline($request_id, $name, $pipeline, $args)
	{
		$id = $name.':'.++$this->cnt;
		$this->pipelines[$id] = new Pipeline($id, $request_id, $pipeline, $args);
	}

	private function log($message, $level = self::LOG_LEVEL_INFO)
	{
		echo '['.$level.'] : '.$message.PHP_EOL;
	}

	private function writeInput($pipeline_id, $segment_id, $function, $args)
	{
		$input = json_encode(['pipeline_id' => $pipeline_id, 'segment_id' => $segment_id, 'function' => $function, 'args' => $args]);
		file_put_contents(self::INPUT, $input.PHP_EOL, FILE_APPEND);
	}

	private function writeUserOutput($request_id, $args)
	{
		$output = json_encode(['request_id' => $request_id, 'args' => $args]);
		file_put_contents(self::USER_OUTPUT, $output.PHP_EOL, FILE_APPEND);
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
				$this->addPipeline($output->request_id, $output->pipeline_id, $this->pipeline_definition[$output->pipeline_id], (array)$output->args);
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
				switch ($v['type'])
				{
				case 'output':
					$this->log('Send segment (ID:'.$id.':'.$k.') to '.self::USER_OUTPUT);
					$this->writeUserOutput($pipeline->getRequestId(), $v['input']);
					$pipeline->updateSegmentStatus($k, Pipeline::STATUS_DONE);
					break;
				default:
					$this->log('Send segment (ID:'.$id.':'.$k.') to '.self::INPUT);
					$this->writeInput($id, $k, $v['function'], $v['input']);
					$pipeline->updateSegmentStatus($k, Pipeline::STATUS_RUNNING);
					$finished = false;
				}
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
