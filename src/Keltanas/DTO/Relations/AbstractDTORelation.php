<?php

namespace Keltanas\DTO\Relations;

use Keltanas\DTO\Annotation\Field;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractRelation implements RelationInterface
{
    use ContainerAwareTrait;

    /** @var string */
    protected $fieldName;

    /** @var Field */
    protected $fieldParams;

    /**
     * Relation constructor.
     *
     * @param string $fieldName
     * @param Field  $fieldParams
     */
    public function __construct($fieldName, Field $fieldParams)
    {
        $this->fieldName = $fieldName;
        $this->fieldParams = $fieldParams;
    }
}
