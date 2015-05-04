<?php
namespace nyansapow\processors\api;

trait PhpApiUtils
{
    function getNamespacePath($namespace)
    {
        return str_replace('\\', '/',$namespace) . ($namespace != '' ? '/' : 'global_namespace/');
    }
    
    function getNamespaceName($namespace)
    {
        return $namespace == '' ? 'Global Namespace' : $namespace;
    }   
    
    /**
     * 
     * @todo Add a way to resolve methods and properties of a class
     * @param string $vars
     * @return array
     */
    function getTypeLink($vars)
    {
        $varList = explode('|', $vars);
        $types = [];
        foreach($varList as $var)
        {
            if(preg_match("|(\\\\[a-zA-Z0-9_]+)+|", $var))
            {
                $breakDown = explode('\\', $var);
                $type = array_pop($breakDown);
                $link = $this->getNamespacePath(implode('\\', $breakDown)) . "$type.html";
                $types[] = array(
                    'type' => $type,
                    'link' => $this->sitePath . ($link[0] == '/' ? substr($link, 1) : $link)
                );
            }
            else if($var != '')
            {
                $types[] = array(
                    'type' => $var,
                    'link' => "http://php.net/$var"
                );
            }
            else
            {
                $types[] = [];
            }
        }
        return $types;
    }    
}

