<?php

class Pipeline
{
	const SEGMENT_TYPE_OUTPUT = 'output';
	const SEGMENT_TYPE_INPUT  = 'input';
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_DONE    = 2;

	private $id;
	private $request_id;
	private $segments;
	private $dependencies;

	public function __construct($id, $request_id, $pipeline, $args)
	{
		// Initialize variables
		$this->id           = $id;
		$this->request_id   = $request_id;
		$this->segments     = $pipeline['segments'];
		$this->dependencies = $pipeline['dependencies'];

		// Initialize segments status
		foreach ($this->segments as $k => $v)
		{
			$this->segments[$k]['input'] = [];
			if ($v['type'] === self::SEGMENT_TYPE_INPUT)
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

	public function getRunnableSegments()
	{
		$segments = $this->segments;

		// Remove segements which are done or running.
		foreach($segments as $k => $v)
		{
			if ($v['status'] !== self::STATUS_WAITING)
			{
				unset($segments[$k]);
			}
		}
		
		// Remove segements which cannot be started
		foreach($this->dependencies as $d)
		{
			$to = $d['to'];
			$fr = $d['from'];
			if (isset($segments[$to]))
			{
				if ($this->segments[$fr]['status'] === self::STATUS_DONE)
				{
					$segments[$to]['input'] += $this->segments[$fr]['result'];
				}
				else
				{
					unset($segments[$to]);
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

	public function updateSegmentStatus($id, $status, $result = null)
	{
		$this->segments[$id]['status'] = $status;
		if ($result !== null && $status === self::STATUS_DONE) {
			$this->segments[$id]['result'] = $result;
		}
	}
	public function getRequestId()
	{
		return $this->request_id;
	}
}
