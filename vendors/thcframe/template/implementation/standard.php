<?php

namespace THCFrame\Template\Implementation;

use THCFrame\Template as Template;
use THCFrame\Core\StringMethods as StringMethods;

/**
 * Description of Standard
 *
 * @author Tomy
 */
class Standard extends Template\Implementation
{

    protected $_map = array(
        "echo" => array(
            "opener" => "{echo",
            "closer" => "}",
            "handler" => "_echo"
        ),
        "script" => array(
            "opener" => "{script",
            "closer" => "}",
            "handler" => "_script"
        ),
        "statement" => array(
            "opener" => "{",
            "closer" => "}",
            "tags" => array(
                "foreach" => array(
                    "isolated" => false,
                    "arguments" => "{element} in {object}",
                    "handler" => "_each"
                ),
                "for" => array(
                    "isolated" => false,
                    "arguments" => "{initialization} {condition} {incrementation}",
                    "handler" => "_for"
                ),
                "if" => array(
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_if"
                ),
                "elseif" => array(
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_elif"
                ),
                "else" => array(
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_else"
                ),
                "macro" => array(
                    "isolated" => false,
                    "arguments" => "{name}({args})",
                    "handler" => "_macro"
                ),
                "literal" => array(
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_literal"
                )
            )
        )
    );

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _echo($tree, $content)
    {
        $raw = $this->_script($tree, $content);
        return "\$_text[] = {$raw}";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _script($tree, $content)
    {
        $raw = !empty($tree["raw"]) ? $tree["raw"] : "";
        return "{$raw};";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _each($tree, $content)
    {
        $object = $tree["arguments"]["object"];
        $element = $tree["arguments"]["element"];

        return $this->_loop(
                        $tree, "foreach ({$object} as {$element}_i => {$element}) {{$content}}"
        );
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _for($tree, $content)
    {
        $initialization = $tree["arguments"]["initialization"];
        $condition = $tree["arguments"]["condition"];
        $incrementation = $tree["arguments"]["incrementation"];

        return $this->_loop(
                        $tree, "for ({$initialization}; {$condition}; {$incrementation}) {{$content}}"
        );
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _if($tree, $content)
    {
        $raw = $tree["raw"];
        return "if ({$raw}) {{$content}}";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _elif($tree, $content)
    {
        $raw = $tree["raw"];
        return "elseif ({$raw}) {{$content}}";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _else($tree, $content)
    {
        return "else {{$content}}";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _macro($tree, $content)
    {
        $arguments = $tree["arguments"];
        $name = $arguments["name"];
        $args = $arguments["args"];

        return "function {$name}({$args}) {
                \$_text = array();
                {$content}
                return implode(\$_text);
            }";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _literal($tree, $content)
    {
        $source = addslashes($tree["source"]);
        return "\$_text[] = \"{$source}\";";
    }

    /**
     * 
     * @param type $tree
     * @param type $inner
     * @return type
     */
    protected function _loop($tree, $inner)
    {
        $number = $tree["number"];
        $object = $tree["arguments"]["object"];
        $children = $tree["parent"]["children"];
        $objectCount = count($object);

        if (!empty($children[$number + 1]["tag"]) && $children[$number + 1]["tag"] == "else") {
            return "if (is_array({$object}) && {$objectCount} > 0) {{$inner}}";
        }

        return $inner;
    }

}
