<?php

defined('XAPP') || require_once(dirname(__FILE__) . '/../../core/core.php');

xapp_import('xapp.Rpc.Exception');
xapp_import('xapp.Util.Json.*');

/**
 * Rpc mapper class
 *
 * @package Rpc
 * @class Xapp_Rpc_Mapper
 * @error 174
 * @author Frank Mueller <set@cooki.me>
 */
class Xapp_Rpc_Mapper extends Xapp_Util_Json_Mapper
{
    /**
     * contains the maps registered with name identifier
     *
     * @var array
     */
    protected $_maps = array();


    /**
     * register mapping string/file or array/object with mapper instance with name identifier passed in first argument.
     * the map is expected to be an object with json path placeholder that get replaced in rpc response handling. see
     * Xapp_Util_Json_Path for more info
     *
     * @error 17401
     * @param int|string $name expects mapping name identifier
     * @param mixed $map expects a valid mapping string/file or array/object
     * @return $this
     * @throws Xapp_Rpc_Exception
     */
    public function registerMap($name, $map)
    {
        if(is_string($map))
        {
            if(xapp_is('file', $map))
            {
                if(($map = file_get_contents($file = $map)) === false)
                {
                    throw new Xapp_Rpc_Exception(xapp_sprintf(__("unable to load mapping object from file: %s"), $file), 1740102);
                }
            }
            if(Xapp_Util_Json::isJson($map))
            {
                $map = Xapp_Util_Json::decode($map);
            }
            $this->_maps[$name] = $map;
        }else if(is_object($map)){
            $this->_maps[$name] = $map;
        }else if(is_array($map)){
            $this->_maps[$name] = xapp_array_to_object($map);
        }else{
            throw new Xapp_Rpc_Exception(__("passed map is not a mappable value"), 1740101);
        }
        return $this;
    }


    /**
     * auto register directories and all containing files that pass regex pattern in second argument which defaults to only
     * allow for .json files. to change the pattern pass any valid regex string containing all filter rules. the third
     * argument can be used to pass exclude rules/pattern either a single or array value for multiple rules. the rules
     * must be valid regex rules without delimiter e.g. "my\.json" or "\/mydir.*". see Xapp_Rpc_Mapper::registerMap for
     * more info
     *
     * @error 17402
     * @see Xapp_Rpc_Mapper::registerMap
     * @param string|array $dir expects dir or array of dirs to auto register
     * @param null|string $regex expects optional positive iterator regex pattern
     * @param null|string|mixed $exclude expects optional exclude values
     * @return $this
     * @throws Xapp_Rpc_Exception
     */
    public function autoRegisterMaps($dir, $regex = null, $exclude = null)
    {
        if(!is_array($dir))
        {
            $dir = array($dir);
        }
        if(is_null($regex))
        {
            $regex = '\.json';
        }
        if(!empty($exclude))
        {
            $exclude = (array)$exclude;
            foreach($exclude as &$e)
            {
                $e = xapp_regex_delimit($e, '^$ ');
            }
            $exclude = '/('.implode('|', (array)$exclude).')$/i';
        }
        foreach($dir as $d)
        {
            if(is_dir($d))
            {
                $iterator = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, RecursiveIteratorIterator::CHILD_FIRST)), '/'.xapp_regex_delimit($regex, '^$ ').'$/i', RecursiveRegexIterator::MATCH);
                foreach($iterator as $i)
                {
                    if($i->isFile())
                    {
                        $file = $i->__toString();
                        $name = basename(substr($file, 0, strripos($file, '.')));
                        if(!empty($exclude))
                        {
                            if(preg_match($exclude, $file)) continue;
                        }
                        $this->registerMap($name, $file);
                    }
                }
            }else{
                throw new Xapp_Rpc_Exception(xapp_sprintf(__("passed dir: %s is not a readable directory"), $d), 1740201);
            }
        }
        return $this;
    }


    /**
     * unregister map by name identifier previously registered with instance
     *
     * @error 17403
     * @param int|string $name expects name identifier
     * @return $this
     */
    public function unregisterMap($name)
    {
        if(array_key_exists($name, $this->_maps))
        {
            unset($this->_maps[$name]);
        }
        return $this;
    }


    /**
     * checks if a map registered with name identifier has been registered previously
     *
     * @error 17404
     * @param int|string $name expects name identifier
     * @return bool
     */
    public function isRegisteredMap($name)
    {
        return ((array_key_exists($name, $this->_maps)) ? true : false);
    }


    /**
     * execute mapping - see parent implementation Xapp_Util_Json_Mapper::map for more details
     *
     * @error 17405
     * @see Xapp_Util_Json_Mapper
     * @param mixed $map expects a valid mappable value
     * @param bool $encode $encode expects options encoding flag
     * @param bool $exception $exception expects optional exception flag
     * @return bool|mixed
     * @throws Exception|Xapp_Util_Json_Exception
     */
    public function map($map, $encode = false, $exception = true)
    {
        if(is_string($map) && array_key_exists($map, $this->_maps))
        {
            $map = $this->_maps[$map];
        }
        return parent::map($map, $encode, $exception);
    }
}