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
    define('ENV', 'cli');
    $wrap = "\n";
}
else
{
    define('ENV', 'web');
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
    unset($key);
    unset($value);
    unset($item);
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
    unset($key);
    unset($value);
    unset($item);
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
    unset($key);
    unset($item);
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
    unset($key);
    unset($item);
    unset($diffFromServer2ToServer1);
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
            ob_end_clean();
            die($e->getMessage());
        }
    }
    unset($key);
    unset($item);
    unset($diffFromServer1ToServer2);
}

$sameFromServer1ToServer2 = array_intersect($tableList1, $tableList2);
if(!is_array($sameFromServer1ToServer2) || empty($sameFromServer1ToServer2))
{
    unset($tableList2);
    unset($tableList1);
    unset($sameFromServer1ToServer2);
}
else
{
    //compare table Structured
    foreach($sameFromServer1ToServer2 as $key => $value)
    {
        $tmp1 = $db1->query("SHOW FULL COLUMNS FROM $value");
        $tmp2 = $db2->query("SHOW FULL COLUMNS FROM $value");
        if(count($tmp1) < count($tmp2))
        {
            $tmpIds = array();
            foreach($tmp1 as $node => $item)
            {
                $tmpIds[] = $item['Field'];
            }
            unset($node);
            unset($node);
            foreach($tmp2 as $node => $item)
            {
                if(!isset($tmpIds[$item['Field']]))
                {
                    echo " DROP COLUMN `$item[Field]` ," . $wrap;
                    unset($tmp2[$node]);
                }
            }
            unset($tmpIds);
            unset($node);
            unset($node);
        }
        $name = $tmp1[0]['Field'];
        if($tmp1 != $tmp2)
        {
            echo INTERLACED . $wrap;
            echo '-- Alter table `' . $value . '`' . $wrap;
            echo INTERLACED . $wrap;
            echo "ALTER TABLE `$value` " . $wrap;
        }
        $desc = '';
        foreach($tmp1 as $node => $item)
        {
            if(isset($tmp2[$node]))
            {
                if($tmp2[$node] == $item)
                {
                    continue;
                }
                else
                {
                    if($tmp2[$node]['Field'] != $item['Field'])
                    {
                        $desc .= " ADD COLUMN `$item[Field]` " . $item['Type'];
                        if(strpos($item['Type'], 'char') !== false || strpos($item['Type'], 'text') !== false)
                        {
                            $desc .= " COLLATE $item[Collation]";
                        }
                        if($item['Null'] == 'NO')
                        {
                            $desc .= " NOT NULL DEFAULT $item[Default]";
                        }
                        else
                        {
                            $desc .= " NULL";
                        }
                        if(!empty($item['Comment']))
                        {
                            $desc .= " COMMENT $item[Comment]";
                        }
                        $desc .= " after $name ," . $wrap;
                    }
                    else
                    {
                        $desc .= " CHANGE COLUMN `$item[Field]` " . $item['Type'];
                        if(strpos($item['Type'], 'char') !== false || strpos($item['Type'], 'text') !== false)
                        {
                            $desc .= " COLLATE $item[Collation]";
                        }
                        if($item['Null'] == 'NO')
                        {
                            $desc .= " NOT NULL DEFAULT '$item[Default]'";
                        }
                        else
                        {
                            $desc .= " NULL";
                        }
                        if(!empty($item['Comment']))
                        {
                            $desc .= " COMMENT '$item[Comment]'";
                        }
                        $desc .= " after `$name` ," . $wrap;
                    }
                }
            }
            else
            {
                $desc .= " ADD COLUMN `$item[Field]` " . $item['Type'];
                if(strpos($item['Type'], 'char') !== false || strpos($item['Type'], 'text') !== false)
                {
                    $desc .= " COLLATE $item[Collation]";
                }
                if($item['Null'] == 'NO')
                {
                    $desc .= " NOT NULL DEFAULT $item[Default]";
                }
                else
                {
                    $desc .= " NULL";
                }
                if(!empty($item['Comment']))
                {
                    $desc .= " COMMENT $item[Comment]";
                }
                $desc .= " after $name ," . $wrap;
            }
            $name = $item['Field'];
        }
        if(!empty($desc))
        {
            $desc = trim($desc , ','.$wrap);
            $desc .= ' ;' . $wrap;
        }
        echo $desc;
        unset($desc);
        unset($tmp1);
        unset($tmp2);
        unset($sameFromServer1ToServer2[$key]);
    }
}


$info = ob_get_contents();
ob_end_clean();

switch(ENV)
{
    case 'cli':
        echo "\n";
        echo $info;
        break;
    case 'web':
        echo '<pre>';
        echo $info;
        break;
    default:
        $fileName = isset($fileName) ? ROOT_PATH . $fileName : ROOT_PATH . 'sql.sql';
        break;
}