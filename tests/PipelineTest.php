<?php
require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../Pipeline.php";

class PipelineTest extends PHPUnit_Framework_TestCase
{
	private $pipeline_definition;

	public function setUp()
	{
		$this->pipeline_definition = [
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
					],
					6 => [
						'type' => 'output',
						'protocol' => 'HTTP',
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
			]
		];
	}

	public function testConstruct()
	{
		$pipeline = new Pipeline(1, 1, $this->pipeline_definition['pipeline1'], []);
	}
}
