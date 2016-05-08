<?php

class Pipeline
{
	const SEGMENT_TYPE_OUTPUT = 'output';
	const SEGMENT_TYPE_INPUT  = 'input';
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_DONE    = 2;
	const STATUS_SKIP    = 3;

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
            // Error Config
            $skip = (isset($this->segments[$k]['error_config']) && isset($this->segments[$k]['error_config']['skip'])) ? $this->segments[$k]['error_config']['skip'] : false;
            $report = (isset($this->segments[$k]['error_config']) && isset($this->segments[$k]['error_config']['report'])) ? $this->segments[$k]['error_config']['report'] : true;
            $this->segments[$k]['error_config'] = compact('skip', 'report');
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
				if (in_array($this->segments[$fr]['status'], [self::STATUS_DONE, self::STATUS_SKIP]))
				{
					$segments[$to]['input'] = $this->segments[$fr]['result'];
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
        $skipped = [];
		$this->segments[$id]['status'] = $status;
		if ($result !== null && $status === self::STATUS_DONE) {
			$this->segments[$id]['result'] = (array)$result['values'];
            if ($result['status'] !== 200)
            {
                if ($this->segments[$id]['error_config']['skip'] === false)
                {
                    // Skip Remaining Segments
                    foreach ($this->segments as $k => $v)
                    {
                        if ($v['status'] === self::STATUS_WAITING && $v['type'] !== self::SEGMENT_TYPE_OUTPUT)
                        {
                            $this->segments[$k]['status'] = self::STATUS_SKIP;
                            $this->segments[$k]['result'] = ['dummy' => true];
                            $skipped[$k] = $this->segments[$k];
                        }
                    }
                }
                if ($this->segments[$id]['error_config']['report'] === true)
                {
                    // Call Error Report Pipeline
                }
            }
		}
        return $skipped;
	}
	public function getRequestId()
	{
		return $this->request_id;
	}
}
