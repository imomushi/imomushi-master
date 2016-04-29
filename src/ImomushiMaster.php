<?php

require_once __DIR__."/Pipeline.php";

class ImomushiMaster
{
	const FILE_REQUEST_INPUT  = '/tmp/user_input.txt';
	const FILE_REQUEST_OUTPUT = '/tmp/user_output.txt';
	const FILE_SEGMENT_INPUT  = '/tmp/input.txt';
	const FILE_SEGMENT_OUTPUT = '/tmp/output.txt';
	const LOG_LEVEL_INFO  = 'INFO';
	const LOG_LEVEL_ERROR = 'ERROR';
	const SLEEP_MSEC = 10;
	
	private $pipeline_definition;
	private $pipelines;
	private $pipeline_id_max;
	private $file_line_count;

	public function __construct($pipeline)
	{
		// Initialize variables
		$this->pipeline_definition = $pipeline;
		$this->pipeline_id_max = 0;
		$this->pipelines = [];
		$this->file_line_count = [];

		// check current line count
		$this->file_line_count[self::FILE_SEGMENT_OUTPUT] = $this->countLine(self::FILE_SEGMENT_OUTPUT);
		$this->file_line_count[self::FILE_REQUEST_INPUT]  = $this->countLine(self::FILE_REQUEST_INPUT);
	}

	public function receive()
	{
		$changed = false;
		while (1)
		{
			foreach ($this->checkQue(self::FILE_SEGMENT_OUTPUT) as $output)
			{
				$this->log('Read output (ID:'.$output->pipeline_id.':'.$output->segment_id.') from '.self::FILE_SEGMENT_OUTPUT);
				$this->pipelines[$output->pipeline_id]->updateSegmentStatus($output->segment_id, Pipeline::STATUS_DONE, (array)$output->result);
				$changed = true;
			}
			foreach ($this->checkQue(self::FILE_REQUEST_INPUT) as $output)
			{
				$this->log('Read piline (ID:'.$output->pipeline_id.') from '.self::FILE_REQUEST_INPUT);
				$this->addPipeline($output->request_id, $output->pipeline_id, $this->pipeline_definition[$output->pipeline_id], (array)$output->args);
				$changed = true;
			}
			if ($changed) break;
			usleep(self::SLEEP_MSEC*1000);
		}
	}

	public function forwardSegments()
	{
		foreach ($this->pipelines as $id => $pipeline)
		{
			$finished = true;
			foreach ($pipeline->getRunnableSegments() as $k => $v)
			{
				switch ($v['type'])
				{
				case Pipeline::SEGMENT_TYPE_OUTPUT:
					$this->log('Send segment (ID:'.$id.':'.$k.') to '.self::FILE_REQUEST_OUTPUT);
					$this->writeRequestOutput($pipeline->getRequestId(), $v['input']);
					$pipeline->updateSegmentStatus($k, Pipeline::STATUS_DONE);
					break;
				default:
					$this->log('Send segment (ID:'.$id.':'.$k.') to '.self::FILE_SEGMENT_INPUT);
                    $config = (isset($v['config'])) ? $v['config'] : [];
					$this->writeSegmentInput($id, $k, $v['function'], array_merge($config, $v['input']));
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
		}
	}

	private function checkQue($file_name)
	{
		$fp = fopen( $file_name, 'r' );
		$count = 0;
		$new_lines = [];
		
		while($line = fgets( $fp ))
		{
			$count++;
			if ($count > $this->file_line_count[$file_name])
			{
				$this->file_line_count[$file_name] = $count;
				$new_lines[] = json_decode($line);
			}
		}
		fclose($fp);
		return $new_lines;
	}

	private function addPipeline($request_id, $name, $pipeline, $args)
	{
		$id = $name.':'.++$this->pipeline_id_max;
		$this->pipelines[$id] = new Pipeline($id, $request_id, $pipeline, $args);
	}

	private function writeSegmentInput($pipeline_id, $segment_id, $function, $args)
	{
		$input = json_encode(['pipeline_id' => $pipeline_id, 'segment_id' => $segment_id, 'segment' => $function, 'args' => $args]);
		file_put_contents(self::FILE_SEGMENT_INPUT, $input.PHP_EOL, FILE_APPEND);
	}

	private function writeRequestOutput($request_id, $args)
	{
		$output = json_encode(['request_id' => $request_id, 'args' => $args]);
		file_put_contents(self::FILE_REQUEST_OUTPUT, $output.PHP_EOL, FILE_APPEND);
	}

	private function countLine($file_path)
	{
		$fp = fopen($file_path, 'r' );
		for( $count = 0; fgets( $fp ); $count++ );
		fclose($fp);
		return $count;
	}

	private function log($message, $level = self::LOG_LEVEL_INFO)
	{
		echo '['.$level.'] : '.$message.PHP_EOL;
	}
}
