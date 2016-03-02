<?php
/**
 * index file
 *
 * @category  enter file
 * @package   AutoLoad
 * @author    xiang wu <yijianlingchen@outlook.com>
 * @copyright Copyright (c) 2016
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   1.0.0
 */

define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

require(ROOT_PATH . "config.php");

define('INTERLACED', '-- ------------------------------------------------------');

if(php_sapi_name() === 'cli')
{
    $wrap = "\n";
}
else
{
    $wrap = '<br>';
}


$header = <<<EOF
-- ------------------------------------------------------
-- MySQL Database Compare
-- BY yijianlingchen's Tools
-- Version:$version
-- Time:$date
-- Here is only $database1 SQL database as a reference.
-- The generated SQL is used to make the
-- database $database2 and $database1 have
-- consistent database table structure.
-- Note: This does not delete the database table $database2 unique.
-- ------------------------------------------------------
EOF;

require(ROOT_PATH . "core/AutoLoad.class.php");

$classLoader = new DbCompare\core\Autoload();
$classLoader->registerNamespace('DbCompare\core', ROOT_PATH . "core");
$classLoader->register();

try
{
    $db1 = new DbCompare\core\MySQL($host1, $user1, $password1, $database1);
}
catch (Exception $e)
{
    die($e->getMessage());
}

try
{
    $db2 = new DbCompare\core\MySQL($host2, $user2, $password2, $database2);
}
catch (Exception $e)
{
    die($e->getMessage());
}

try
{
    $list1 = $db1->query("SHOW TABLES");
}
catch (Exception $e)
{
    die($e->getMessage());
}

try
{
    $list2 = $db2->query("SHOW TABLES");
}
catch (Exception $e)
{
    die($e->getMessage());
}

if(is_array($list1) && count($list1) > 0)
{
    $tableList1 = array();
    foreach($list1 as $key => $value)
    {
        foreach($value as $item)
        {
            $tableList1[] = $item;
        }
        unset($list1[$key]);
    }
}
else
{
    die('DataBase one is empty!');
}

if(is_array($list2) && count($list2) > 0)
{
    $tableList2 = array();
    foreach($list2 as $key => $value)
    {
        foreach($value as $item)
        {
            $tableList2[] = $item;
        }
        unset($list2[$key]);
    }
}

ob_start();
echo $header . $wrap.$wrap;

$diffFromServer1ToServer2 = array_values(array_diff($tableList1, $tableList2));
if(!empty($diffFromServer1ToServer2) && is_array($diffFromServer1ToServer2))
{
    echo INTERLACED . $wrap;
    echo '-- There is some table in database ' . $database1 . ' but no in ' . $database2 . '.' . $wrap;
    foreach($diffFromServer1ToServer2 as $key => $item)
    {
        echo '-- TABLE: ' . $item . $wrap;
    }
    echo INTERLACED . $wrap . $wrap . $wrap;
}

$diffFromServer2ToServer1 = array_values(array_diff($tableList2, $tableList1));
if(!empty($diffFromServer2ToServer1) && is_array($diffFromServer2ToServer1))
{
    echo INTERLACED . $wrap;
    echo '-- There is some table in database ' . $database2 . ' but no in ' . $database1 . '.' . $wrap;
    foreach($diffFromServer2ToServer1 as $key => $item)
    {
        echo '-- TABLE: ' . $item . $wrap;
    }
    echo INTERLACED . $wrap . $wrap . $wrap;
}

if(is_array($diffFromServer1ToServer2) && !empty($diffFromServer1ToServer2))
{
    foreach($diffFromServer1ToServer2 as $key => $item)
    {
        try
        {
            $result = $db1->query('SHOW CREATE TABLE ' . $item);
            if(is_array($result) && !empty($result))
            {
                echo INTERLACED . $wrap;
                echo '-- Table structure for table `' . $result[0]['Table'] . '`' . $wrap;
                echo INTERLACED . $wrap;
                echo $result['0']['Create Table'] . ';' . $wrap.$wrap.$wrap.$wrap;
            }
            unset($result);
            unset($diffFromServer1ToServer2[$key]);
        }
        catch(Exception $e)
        {
            die($e->getMessage());
        }
    }
}

$info = ob_get_contents();
ob_end_clean();

$fileName = isset($fileName) ? ROOT_PATH . $fileName : ROOT_PATH . 'sql.sql';
file_put_contents($fileName, $info);