<?php

namespace THCFrame\Template;

use THCFrame\Core\Base as Base;
use THCFrame\Core\ArrayMethods as ArrayMethods;
use THCFrame\Core\StringMethods as StringMethods;
use THCFrame\Template\Exception as Exception;

/**
 * Description of Template
 *
 * @author Tomy
 */
class Template extends Base
{

    /**
     * @readwrite
     */
    protected $_implementation;

    /**
     * @readwrite
     */
    protected $_header = "if (is_array(\$_data) && count(\$_data)) extract(\$_data); \$_text = array();";

    /**
     * @readwrite
     */
    protected $_footer = "return implode(\$_text);";

    /**
     * @read
     */
    protected $_code;

    /**
     * @read
     */
    protected $_function;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Template\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf("%s method not implemented", $method));
    }

    /**
     * 
     * @param type $source
     * @param type $expression
     * @return type
     */
    protected function _arguments($source, $expression)
    {
        $args = $this->_array($expression, array(
            $expression => array(
                "opener" => "{",
                "closer" => "}"
            )
        ));

        $tags = $args["tags"];
        $arguments = array();
        $sanitized = StringMethods::sanitize($expression, "()[],.<>*$@");

        foreach ($tags as $i => $tag) {
            $sanitized = str_replace($tag, "(.*)", $sanitized);
            $tags[$i] = str_replace(array("{", "}"), "", $tag);
        }

        if (preg_match("#{$sanitized}#", $source, $matches)) {
            foreach ($tags as $i => $tag) {
                $arguments[$tag] = $matches[$i + 1];
            }
        }

        return $arguments;
    }

    /**
     * 
     * @param type $source
     * @return boolean
     */
    protected function _tag($source)
    {
        $tag = null;
        $arguments = array();

        $match = $this->_implementation->match($source);
        if ($match == null) {
            return false;
        }

        $delimiter = $match["delimiter"];
        $type = $match["type"];

        $start = strlen($type["opener"]);
        $end = strpos($source, $type["closer"]);
        $extract = substr($source, $start, $end - $start);

        if (isset($type["tags"])) {
            $tags = implode("|", array_keys($type["tags"]));
            $regex = "#^(/){0,1}({$tags})\s*(.*)$#";

            if (!preg_match($regex, $extract, $matches)) {
                return false;
            }

            $tag = $matches[2];
            $extract = $matches[3];
            $closer = !!$matches[1];
        }

        if ($tag && $closer) {
            return array(
                "tag" => $tag,
                "delimiter" => $delimiter,
                "closer" => true,
                "source" => false,
                "arguments" => false,
                "isolated" => $type["tags"][$tag]["isolated"]
            );
        }

        if (isset($type["arguments"])) {
            $arguments = $this->_arguments($extract, $type["arguments"]);
        } else if ($tag && isset($type["tags"][$tag]["arguments"])) {
            $arguments = $this->_arguments($extract, $type["tags"][$tag]["arguments"]);
        }

        return array(
            "tag" => $tag,
            "delimiter" => $delimiter,
            "closer" => false,
            "source" => $extract,
            "arguments" => $arguments,
            "isolated" => (!empty($type["tags"]) ? $type["tags"][$tag]["isolated"] : false)
        );
    }

    /**
     * 
     * @param type $source
     * @return type
     */
    protected function _array($source)
    {
        $parts = array();
        $tags = array();
        $all = array();

        $type = null;
        $delimiter = null;

        while ($source) {
            $match = $this->_implementation->match($source);

            $type = $match["type"];
            $delimiter = $match["delimiter"];

            $opener = strpos($source, $type["opener"]);
            $closer = strpos($source, $type["closer"]) + strlen($type["closer"]);

            if ($opener !== false) {
                $parts[] = substr($source, 0, $opener);
                $tags[] = substr($source, $opener, $closer - $opener);
                $source = substr($source, $closer);
            } else {
                $parts[] = $source;
                $source = "";
            }
        }

        foreach ($parts as $i => $part) {
            $all[] = $part;
            if (isset($tags[$i])) {
                $all[] = $tags[$i];
            }
        }

        return array(
            "text" => ArrayMethods::clean($parts),
            "tags" => ArrayMethods::clean($tags),
            "all" => ArrayMethods::clean($all)
        );
    }

    /**
     * 
     * @param type $array
     * @return array
     */
    protected function _tree($array)
    {
        $root = array(
            "children" => array()
        );
        $current = & $root;

        foreach ($array as $i => $node) {
            $result = $this->_tag($node);

            if ($result) {
                $tag = isset($result["tag"]) ? $result["tag"] : "";
                $arguments = isset($result["arguments"]) ? $result["arguments"] : "";

                if ($tag) {
                    if (!$result["closer"]) {
                        $last = ArrayMethods::last($current["children"]);

                        if ($result["isolated"] && is_string($last)) {
                            array_pop($current["children"]);
                        }

                        $current["children"][] = array(
                            "index" => $i,
                            "parent" => &$current,
                            "children" => array(),
                            "raw" => $result["source"],
                            "tag" => $tag,
                            "arguments" => $arguments,
                            "delimiter" => $result["delimiter"],
                            "number" => count($current["children"])
                        );
                        $current = & $current["children"][count($current["children"]) - 1];
                    } else if (isset($current["tag"]) && $result["tag"] == $current["tag"]) {
                        $start = $current["index"] + 1;
                        $length = $i - $start;
                        $current["source"] = implode(array_slice($array, $start, $length));
                        $current = & $current["parent"];
                    }
                } else {
                    $current["children"][] = array(
                        "index" => $i,
                        "parent" => &$current,
                        "children" => array(),
                        "raw" => $result["source"],
                        "tag" => $tag,
                        "arguments" => $arguments,
                        "delimiter" => $result["delimiter"],
                        "number" => count($current["children"])
                    );
                }
            } else {
                $current["children"][] = $node;
            }
        }

        return $root;
    }

    /**
     * 
     * @param type $tree
     * @return type
     */
    protected function _script($tree)
    {
        $content = array();

        if (is_string($tree)) {
            $tree = addslashes($tree);
            return "\$_text[] = \"{$tree}\";";
        }

        if (count($tree["children"]) > 0) {
            foreach ($tree["children"] as $child) {
                $content[] = $this->_script($child);
            }
        }

        if (isset($tree["parent"])) {
            return $this->_implementation->handle($tree, implode($content));
        }

        return implode($content);
    }

    /**
     * 
     * @param type $template
     * @return \THCFrame\Template\Template
     * @throws Exception\Implementation
     */
    public function parse($template)
    {
        if (!is_a($this->_implementation, "THCFrame\Template\Implementation")) {
            throw new Exception\Implementation();
        }

        $array = $this->_array($template);
        $tree = $this->_tree($array["all"]);

        $this->_code = $this->header . $this->_script($tree) . $this->footer;
        $this->_function = create_function("\$_data", $this->code);

        return $this;
    }

    /**
     * 
     * @param type $data
     * @return type
     * @throws Exception\Parser
     */
    public function process($data = array())
    {
        if ($this->_function == null) {
            throw new Exception\Parser();
        }

        try {
            $function = $this->_function;
            return $function($data);
        } catch (\Exception $e) {
            throw new Exception\Parser($e->getMessage());
        }
    }

}
