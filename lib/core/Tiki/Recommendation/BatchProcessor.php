<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation;

class BatchProcessor
{
	private $store;
	private $engines;

	function __construct(Store\StoreInterface $store, EngineSet $engines)
	{
		$this->store = $store;
		$this->engines = $engines;
	}

	function process($inputs)
	{
		foreach ($this->combined($inputs) as $entry) {
			list($set, $engine, $input) = $entry;

			foreach ($engine->generate($input) as $rec) {
				if (! $this->store->isReceived($input, $rec)) {
					$set->add($rec);
				}
			}

			if (count($set) > 0) {
				$this->store->store($input, $set);
			}
		}
	}

	private function combined($inputs)
	{
		$generator = $this->engines->getGenerator();
		foreach ($inputs as $input) {
			$current = $generator->current();
			$generator->next();
			if (! $current) {
				return;
			}

			list($set, $engine) = $current;

			yield [$set, $engine, $input];
		}
	}
}
