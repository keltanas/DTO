<?php

namespace Keltanas\DTO\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use Keltanas\DTO\Exception;

/**
 * Class Field
 * @Annotation
 * @Target({"PROPERTY"})
 * @Attributes({
 *   @Attribute("type", type = "string"),
 *   @Attribute("required",  type = "boolean"),
 *   @Attribute("groups",  type = "array<string>"),
 *   @Attribute("source",  type = "string"),
 *   @Attribute("sourceType",  type = "string"),
 *   @Attribute("sourceField",  type = "string"),
 *   @Attribute("sourceMethod",  type = "string"),
 *   @Attribute("assembler",  type = "string"),
 *   @Attribute("assemblerMethod",  type = "string"),
 * })
 */
class Field implements \JsonSerializable
{
    const TYPE_INTEGER = 'integer';
    const TYPE_DOUBLE = 'double';
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_DATETIME = 'datetime';

    const TYPE_ALL = [
        Field::TYPE_INTEGER,
        Field::TYPE_DOUBLE,
        Field::TYPE_STRING,
        Field::TYPE_BOOLEAN,
        Field::TYPE_ARRAY,
        Field::TYPE_OBJECT,
        Field::TYPE_DATETIME,
    ];

    const ONE_TO_ONE = 'one_to_one';
    const ONE_TO_MANY = 'one_to_many';
    const ONE_TO_CALL = 'one_to_call';
    const SELF_PROPERTY = 'self_property';
    const POPULATE = 'populate';

    const GROUPS_NORMAL = ['admin', 'user', 'guest'];

    /**
     * @var string
     * @Required()
     * @Enum(Field::TYPE_ALL)
     */
    private $type;

    /** @var string */
    private $name;

    /** @var bool */
    private $required;

    /** @var array */
    private $groups;

    /** @var string */
    private $source;

    /** @var string */
    private $sourceType;

    /** @var string */
    private $sourceField;

    /** @var string */
    private $sourceMethod;

    /** @var string */
    private $assembler;

    /** @var string */
    private $assemblerMethod;

    /**
     * Field constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->type = $values['type'];
        $this->required = $this->fromArray($values, 'required', false);
        $this->groups = $this->fromArray($values, 'groups', []);
        $this->source = $this->fromArray($values, 'source', null);
        $this->sourceType = $this->fromArray($values, 'sourceType', null);
        $this->sourceField = $this->fromArray($values, 'sourceField', null);
        $this->sourceMethod = $this->fromArray($values, 'sourceMethod', null);
        $this->assembler = $this->fromArray($values, 'assembler', null);
        $this->assemblerMethod = $this->fromArray($values, 'assemblerMethod', null);
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed
     */
    private function fromArray($array, $key, $default = null)
    {
        return array_key_exists($key, $array) && $array[$key] ? $array[$key] : $default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @return string
     */
    public function getSourceField()
    {
        return $this->sourceField;
    }

    /**
     * @return string
     */
    public function getSourceMethod()
    {
        return $this->sourceMethod;
    }

    /**
     * @return string
     */
    public function getAssembler()
    {
        return $this->assembler;
    }

    /**
     * @return string
     */
    public function getAssemblerMethod()
    {
        return $this->assemblerMethod;
    }

    public function convertType(Field $property, $value)
    {
        try {
            switch ($property->getType()) {
                case Field::TYPE_STRING:
                    return (string) $value;
                case Field::TYPE_INTEGER:
                    return (integer) $value;
                case Field::TYPE_DOUBLE:
                    return (double) $value;
                case Field::TYPE_BOOLEAN:
                    return (boolean) $value;
                case Field::TYPE_DATETIME:
                    return $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value;
                case Field::TYPE_ARRAY:
                    return is_scalar($value) ? [$value] : $value;
                default:
                    return $value;
            }
        } catch (\ErrorException $e) {
            throw new Exception('Field: ' . $property->getName() . '. Message: ' . $e->getMessage(), 0, $e);
        }
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
            'groups' => $this->groups,
        ];
    }
}