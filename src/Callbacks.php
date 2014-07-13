<?php

namespace nyansapow;

/**
 * Callback methods called from within php-markdown to allow for integration
 * of other libraries.
 */
class Callbacks
{
    public function codeBlock($codeblock, $lang = false)
    {
        if($lang === false)
        {
            $codeblock = "<pre>" . htmlspecialchars($codeblock, ENT_NOQUOTES) . "</pre>";            
        }
        else
        {
            $geshi = new \GeSHi(htmlspecialchars_decode($codeblock), $lang);
            $geshi->set_header_type(GESHI_HEADER_PRE);
            $codeblock = $geshi->parse_code();
        }
        
        return $codeblock;
    }
}
