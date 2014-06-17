#!/usr/bin/php
<?php

// Simple script to test new functionality of flatfile.php
// Not yet exhuastive or thorough, but getting there.

error_reporting(E_ALL);

define('TESTFILE', 'temp_data_file.txt');
define('ORIGFILE', 'flatfile_data1.txt');
define('TOTALROWS', 5);
function setUp()
{
	copy(ORIGFILE, TESTFILE);
	$GLOBALS['db'] = new Flatfile();
	$GLOBALS['db2'] = new Flatfile(); // used for checking if db was used for writing
}

require('../flatfile.php');


setUp();

/* Reading */

// SELECT_ALL
$rows = $db->selectAll(TESTFILE);
assert('count($rows) == TOTALROWS');

// SELECT_WHERE - simple
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(0, '=', 'id1'));
assert('count($rows) == 1');

// NotWhere
$rows = $db->selectWhere(TESTFILE, new NotWhere(new SimpleWhereClause(0, '=', 'id1')));
assert('count($rows) == (TOTALROWS - 1)');

// SimpleWhereClause < - integer
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '<', 5, INTEGER_COMPARISON));
assert('count($rows) == 0');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '<', 456, INTEGER_COMPARISON));
assert('count($rows) == 4');

// SimpleWhereClause > - integer
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '>', 5, INTEGER_COMPARISON));
assert('count($rows) == 4');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '>', 456, INTEGER_COMPARISON));
assert('count($rows) == 0');

// SimpleWhereClause =   - integer
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '=', 1, INTEGER_COMPARISON));
assert('count($rows) == 3');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '=', 2, INTEGER_COMPARISON));
assert('count($rows) == 1');

// SimpleWhereClause !=   - integer
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '!=', 1, INTEGER_COMPARISON));
assert('count($rows) == 2');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '!=', 2, INTEGER_COMPARISON));
assert('count($rows) == 4');


// SimpleWhereClause < - float
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '<', 1, NUMERIC_COMPARISON));
assert('count($rows) == 0');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '<', 456, NUMERIC_COMPARISON));
assert('count($rows) == 4');

// SimpleWhereClause > - float
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '>', 1, NUMERIC_COMPARISON));
assert('count($rows) == 3');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '>', 1.1, NUMERIC_COMPARISON));
assert('count($rows) == 2');

// SimpleWhereClause =   - float
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '=', 1, NUMERIC_COMPARISON));
assert('count($rows) == 2');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '=', 2, NUMERIC_COMPARISON));
assert('count($rows) == 1');

// SimpleWhereClause !=   - float
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '!=', 1, NUMERIC_COMPARISON));
assert('count($rows) == 3');
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '!=', 2, NUMERIC_COMPARISON));
assert('count($rows) == 4');



// SimpleWhereClause < - string
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '<', '5', STRING_COMPARISON));
assert('count($rows) == 3');

// SimpleWhereClause > - string
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '>', '5', STRING_COMPARISON));
assert('count($rows) == 1');

// SimpleWhereClause = - string
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '=', '1', STRING_COMPARISON));
assert('count($rows) == 1');

// SimpleWhereClause != - string
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(3, '!=', '1', STRING_COMPARISON));
assert('count($rows) == 4');

// SimpleWhereClause:  >= and <=
// (Shouldn't have to test all comparison types as
//  they are all handled in the same way
// SimpleWhereClause <=
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '<=', 6, INTEGER_COMPARISON));
assert('count($rows) == 2');

// SimpleWhereClause >=
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(2, '>=', 6, INTEGER_COMPARISON));
assert('count($rows) == 4');


// LikeWhereClause 
$rows = $db->selectWhere(TESTFILE, new LikeWhereClause(1, '%bc'));
assert('count($rows) == 3');
$rows = $db->selectWhere(TESTFILE, new LikeWhereClause(1, 'd%'));
assert('count($rows) == 1');
$rows = $db->selectWhere(TESTFILE, new LikeWhereClause(1, 'def'));
assert('count($rows) == 1');
$rows = $db->selectWhere(TESTFILE, new LikeWhereClause(1, 'a'));
assert('count($rows) == 0');


// ListWhereClause
$rows = $db->selectWhere(TESTFILE, new ListWhereClause(0, array('id1','id2')));
assert('count($rows) == 2');

$rows = $db->selectWhere(TESTFILE, new ListWhereClause(3, array(1,2), INTEGER_COMPARISON));
assert('count($rows) == 4');



// SELECT_WHERE - OrWhereClause
$rows = $db->selectWhere(TESTFILE, new OrWhereClause(
 					new SimpleWhereClause(0, '=', 'id1'),
					new SimpleWhereClause(0, '=', 'id2')));
assert('count($rows) == 2');

// SELECT_WHERE - AndlWhereClause
$rows = $db->selectWhere(TESTFILE, new AndWhereClause(
 					new SimpleWhereClause(1, '=', 'abc'),
					new SimpleWhereClause(2, '=', '123')));
assert('$rows[0][0] == "id1"');
assert('count($rows) == 1');


// LIMIT
// limit: all rows
$rows = $db->selectWhere(TESTFILE, NULL, -1);
assert('count($rows) == TOTALROWS');

// limit: all rows - second syntax
$rows = $db->selectWhere(TESTFILE, NULL, array(0,-1));
assert('count($rows) == TOTALROWS');

// limit: one row
$rows = $db->selectWhere(TESTFILE, NULL, 1);
assert('count($rows) == 1');

// limit: one row
$rows = $db->selectWhere(TESTFILE, NULL, array(2,3));
assert('count($rows) == 1');

// ORDER BY

// order by ascending
$rows = $db->selectWhere(TESTFILE, NULL, -1, new OrderBy(0, ASCENDING));
assert('$rows[0][0] == \'id1\'');

// order by descending
$rows = $db->selectWhere(TESTFILE, NULL, -1, new OrderBy(0, DESCENDING));
assert('$rows[0][0] == \'id5\'');

// order by ascending integer
$rows = $db->selectWhere(TESTFILE, NULL, -1, new OrderBy(2, ASCENDING, INTEGER_COMPARISON));
assert('$rows[0][2] == 5');
assert('$rows[1][2] == 6');
assert('$rows[2][2] == 123');
assert('$rows[3][2] == 123');
assert('$rows[4][2] == 456');

// order by descending integer
$rows = $db->selectWhere(TESTFILE, NULL, -1, new OrderBy(2, DESCENDING, INTEGER_COMPARISON));
assert('$rows[0][2] == 456');
assert('$rows[1][2] == 123');
assert('$rows[2][2] == 123');
assert('$rows[3][2] == 6');
assert('$rows[4][2] == 5');

// order by ascending string
$rows = $db->selectWhere(TESTFILE, NULL, -1, new OrderBy(2, ASCENDING, STRING_COMPARISON));
assert('$rows[0][2] == \'123\'');
assert('$rows[1][2] == \'123\'');
assert('$rows[2][2] == \'456\'');
assert('$rows[3][2] == \'5\'');
assert('$rows[4][2] == \'6\'');

// Complex order by - two keys
$orderBy = array();
$orderBy[] = new OrderBy(2, ASCENDING, STRING_COMPARISON);
$orderBy[] = new OrderBy(0, ASCENDING, STRING_COMPARISON);
$rows = $db->selectWhere(TESTFILE, NULL, -1, $orderBy);
assert('$rows[0][2] == \'123\'');
assert('$rows[1][2] == \'123\'');
assert('$rows[2][2] == \'456\'');
assert('$rows[3][2] == \'5\'');
assert('$rows[4][2] == \'6\'');

assert('$rows[0][0] == \'id1\'');
assert('$rows[1][0] == \'id2\'');

// And check the reverse for the second sort
$orderBy = array();
$orderBy[] = new OrderBy(2, ASCENDING, STRING_COMPARISON);
$orderBy[] = new OrderBy(0, DESCENDING, STRING_COMPARISON);
$rows = $db->selectWhere(TESTFILE, NULL, -1, $orderBy);
assert('$rows[0][2] == \'123\'');
assert('$rows[1][2] == \'123\'');
assert('$rows[2][2] == \'456\'');
assert('$rows[3][2] == \'5\'');
assert('$rows[4][2] == \'6\'');

assert('$rows[0][0] == \'id2\'');
assert('$rows[1][0] == \'id1\'');




/* Writing: */

// DELETE_ALL
setUp();
$db->deleteAll(TESTFILE);
$rows = $db2->selectAll(TESTFILE);
assert('count($rows) == 0');

// DELETE_WHERE
setUp();
$db->deleteWhere(TESTFILE, new SimpleWhereClause(0, '!=', 'id2'));
$rows = $db2->selectAll(TESTFILE);
assert('count($rows) == 1');
assert('$rows[0][0] == "id2"');

// UPDATE_SET_WHERE 
// All rows
setUp();
$db->updateSetWhere(TESTFILE, array(0 => 'joe'), NULL);
$rows = $db2->selectAll(TESTFILE);
for ($i = 0; $i < TOTALROWS; $i++) {
	assert ('$rows[$i][0] == "joe"');
}

// UPDATE_SET_WHERE 
// Selected rows
setUp();
$selector = new SimpleWhereClause(1, '=', 'abc');
$db->updateSetWhere(TESTFILE, array(0 => 'joe'), $selector);
$rows = $db2->selectAll(TESTFILE);
for ($i = 0; $i < TOTALROWS; $i++) {
	if ($i == 0 || $i == 2 || $i == 3)
		assert ('$rows[$i][0] == "joe"');
	else
		assert ('$rows[$i][0] == "id' . ($i + 1) . '"');
	
}

// INSERT_WITH_AUTO_ID
setUp();
$db->insertWithAutoId(TESTFILE, 2, array('id6', 'pete', 0));
$rows = $db->selectWhere(TESTFILE, new SimpleWhereClause(0, '=', 'id6'));
assert('$rows[0][2] == 457');


// End Tests //

// headers_sent returns true if any of the asserts failed and produces output
if (headers_sent()) exit(1); 
?>
