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
    
    function getTypeLink($vars)
    {
        $varList = explode('|', $vars);
        $types = [];
        foreach($varList as $var)
        {
            if(preg_match("|(\\\\[a-zA-Z0-9_]+)+|", $var))
            {
                var_dump($var, $this->sitePath);
                $breakDown = explode('\\', $var);
                $type = array_pop($breakDown);
                $link = $this->getNamespacePath(implode('\\', $breakDown)) . "$type.html";
                $types[] = array(
                    'type' => $type,
                    'link' => $this->sitePath.  substr($link, 1)
                );
            }
            else 
            {
                $types[] = array(
                    'type' => $var,
                    'link' => "http://php.net/$var"
                );
            }
        }
        return $types;
    }    
}

