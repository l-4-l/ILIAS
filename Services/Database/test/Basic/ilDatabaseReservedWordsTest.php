<?php

/**
 * Class ilDatabaseReservedWordsTest
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilDatabaseReservedWordsTest extends PHPUnit_Framework_TestCase
{

	protected function setUp()
	{
		parent::setUp();
		require_once('./Services/Database/classes/MDB2/class.ilDBMySQL.php');
		require_once('./Services/Database/classes/PDO/class.ilDBPdoMySQLInnoDB.php');
		require_once('./Services/Database/classes/class.ilDBConstants.php');
		global $ilDB, $DIC;
		$ilDB = new ilDBPdoMySQLInnoDB();
		$DIC['ilDB'] = $ilDB;
	}


	/**
	 * @dataProvider reservedData
	 * @param $word
	 * @param $is_reserved
	 */
	public function testReservedMDB2($word, $is_reserved)
	{
		$this->assertEquals($is_reserved, ilDBMySQL::isReservedWord($word));
	}


	/**
	 * @dataProvider reservedData
	 * @param $word
	 * @param $is_reserved
	 */
	public function testReservedPDO($word, $is_reserved)
	{
		$this->assertEquals($is_reserved, ilDBPdoMySQLInnoDB::isReservedWord($word));
	}


	/**
	 * @return array
	 */
	public function reservedData()
	{
		return [
			[ 'order', true ],
			[ 'myfield', false ],
			[ 'number', true ],
			[ 'null', true ],
			[ 'sensitive', true ],
			[ 'usage', true ],
			[ 'analyze', true ],
		];
	}
}
