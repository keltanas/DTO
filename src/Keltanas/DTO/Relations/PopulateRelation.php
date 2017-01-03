<?php

namespace Keltanas\DTO\Relations;

use Keltanas\DTO\Annotation\Field;
use Keltanas\DTO\Exception;
use Keltanas\DTO\AbstractAssembler;
use Illuminate\Support\Str;

class PopulateRelation extends AbstractRelation
{
    /** @var AbstractAssembler */
    protected $assembler;

    /**
     * PopulateRelation constructor.
     *
     * @param AbstractAssembler $assembler
     */
    public function __construct($fieldName, Field $fieldParams, AbstractAssembler $assembler)
    {
        $this->assembler = $assembler;
        parent::__construct($fieldName, $fieldParams);
    }

    public function perform($models, $DTOs)
    {
        $field = $this->fieldName;
        $property = $this->fieldParams;

        $method = $property->getSourceMethod();
        if (!$method) {
            $method = Str::camel('populate_' . $field . '_field');
        }
        if (0 !== strpos($method, 'populate')) {
            throw new Exception('Populate method must starts from "populate" prefix');
        }

        $this->assembler->$method($DTOs, $models);
    }
}