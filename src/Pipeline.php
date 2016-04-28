<?php

class Pipeline
{
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_DONE    = 2;

	private $id;
	private $request_id;
	private $segments;
	private $dependencies;

	public function __construct($id, $request_id, $pipeline, $args)
	{
		$this->id = $id;
		$this->request_id = $request_id;
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
	public function getRequestId()
	{
		return $this->request_id;
	}
}
