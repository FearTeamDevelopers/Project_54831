<?php

namespace THCFrame\Template;

use THCFrame\Core\Base as Base;
use THCFrame\Core\StringMethods as StringMethods;
use THCFrame\Template\Exception as Exception;

/**
 * Description of Implementation
 *
 * @author Tomy
 */
class Implementation extends Base
{

    /**
     * 
     * @param type $node
     * @return null
     */
    protected function _handler($node)
    {
        if (empty($node["delimiter"])) {
            return null;
        }

        if (!empty($node["tag"])) {
            return $this->_map[$node["delimiter"]]["tags"][$node["tag"]]["handler"];
        }

        return $this->_map[$node["delimiter"]]["handler"];
    }

    /**
     * 
     * @param type $node
     * @param type $content
     * @return type
     * @throws Exception\Implementation
     */
    public function handle($node, $content)
    {
        try {
            $handler = $this->_handler($node);
            return call_user_func_array(array($this, $handler), array($node, $content));
        } catch (\Exception $e) {
            throw new Exception\Implementation($e->getMessage());
        }
    }

    /**
     * 
     * @param type $source
     * @return null
     */
    public function match($source)
    {
        $type = null;
        $delimiter = null;

        foreach ($this->_map as $_delimiter => $_type) {
            if (!$delimiter || StringMethods::indexOf($source, $type["opener"]) == -1) {
                $delimiter = $_delimiter;
                $type = $_type;
            }

            $indexOf = StringMethods::indexOf($source, $_type["opener"]);

            if ($indexOf > -1) {
                if (StringMethods::indexOf($source, $type["opener"]) > $indexOf) {
                    $delimiter = $_delimiter;
                    $type = $_type;
                }
            }
        }

        if ($type == null) {
            return null;
        }

        return array(
            "type" => $type,
            "delimiter" => $delimiter
        );
    }

}
