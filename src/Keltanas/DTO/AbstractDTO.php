<?php
/**
 * This file is part of the @package@.
 *
 * @author : Nikolay Ermin <nikolay.ermin@sperasoft.com>
 * @version: @version@
 */

namespace Keltanas\DTO;

abstract class AbstractDTO implements \JsonSerializable, \ArrayAccess
{
    protected $data = [];

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return
            array_key_exists($name, $this->data);
    }

    public function __get($name)
    {
        return isset($this->data[$name]) && $this->data[$name] ? $this->data[$name] : null;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = $this->getData();
        foreach ($result as &$value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getData();
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}