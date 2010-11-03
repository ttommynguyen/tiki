<?php

class Search_FormatterTest extends PHPUnit_Framework_TestCase
{
	function testBasicFormatter()
	{
		$plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=object_type})\n");

		$formatter = new Search_Formatter($plugin);

		$output = $formatter->format(array(
			array('object_type' => 'wiki page', 'object_id' => 'HomePage'),
			array('object_type' => 'wiki page', 'object_id' => 'SomePage'),
		));

		$expect = <<<OUT
* HomePage (wiki page)
* SomePage (wiki page)

OUT;
		$this->assertEquals($expect, $output);
	}

	function testSpecifyFormatter()
	{
		global $prefs;
		$prefs['short_date_format'] = 'M j, Y';

		$plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=modification_date format=date})\n");

		$formatter = new Search_Formatter($plugin);

		$output = $formatter->format(array(
			array('object_type' => 'wiki page', 'object_id' => 'HomePage', 'modification_date' => strtotime('2010-10-10 10:10:10')),
			array('object_type' => 'wiki page', 'object_id' => 'SomePage', 'modification_date' => strtotime('2011-11-11 11:11:11')),
		));

		$expect = <<<OUT
* HomePage (Oct 10, 2010)
* SomePage (Nov 11, 2011)

OUT;
		$this->assertEquals($expect, $output);
	}

	function testUnknownFormattingRule()
	{
		$plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=object_type format=doesnotexist})\n");

		$formatter = new Search_Formatter($plugin);

		$output = $formatter->format(array(
			array('object_type' => 'wiki page', 'object_id' => 'HomePage'),
			array('object_type' => 'wiki page', 'object_id' => 'SomePage'),
		));

		$expect = <<<OUT
* HomePage (Unknown formatting rule 'doesnotexist' for 'object_type')
* SomePage (Unknown formatting rule 'doesnotexist' for 'object_type')

OUT;
		$this->assertEquals($expect, $output);
	}

	function testValueNotFound()
	{
		$plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=doesnotexist} ({display name=doesnotexisteither default=Test})\n");

		$formatter = new Search_Formatter($plugin);

		$output = $formatter->format(array(
			array('object_type' => 'wiki page', 'object_id' => 'HomePage'),
		));

		$expect = <<<OUT
* No value for 'doesnotexist' (Test)

OUT;
		$this->assertEquals($expect, $output);
	}
}

