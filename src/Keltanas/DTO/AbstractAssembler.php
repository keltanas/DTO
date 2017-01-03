<?php
/**
 * This file is part of the @package@.
 *
 * @author: keltanas
 * @version: @version@
 */


namespace Keltanas\DTO;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Keltanas\DTO\Annotation\Field;
use Keltanas\DTO\Exception;
use Keltanas\DTO\Relations\RelationInterface;
use Keltanas\DTO\Relations\OneToCallRelation;
use Keltanas\DTO\Relations\OneToOneRelation;
use Keltanas\DTO\Relations\PopulateRelation;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractAssembler
{
    use ContainerAwareTrait;

    /** @var Reader */
    protected $reader;

    /**
     * Class for DTO
     *
     * @return string
     */
    protected abstract function getDTOClass();

    /**
     * Class for Model
     *
     * @return string
     */
    protected abstract function getModelClass();

    /**
     * @param Reader $reader
     */
    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param Reader $reader
     *
     * @return Field[]
     */
    protected function getProperties(Reader $reader)
    {
        $properties = [];

        $refClass = new \ReflectionClass($this->getDTOClass());
        foreach ($refClass->getProperties() as $property) {
            foreach ($reader->getPropertyAnnotations($property) as $annotations) {
                if ($annotations instanceof Field) {
                    $properties[$property->getName()] = $annotations;
                }
            }
        }

        return $properties;
    }

    /**
     * @return Reader
     * @throws \Keltanas\DTO\Exception
     */
    protected function getReader()
    {
        if (! $this->reader instanceof Reader) {
            throw new Exception('Need to annotation reader class');
        }

        return $this->reader;
    }

    /**
     * Custom populate DTO
     *
     * @param array $DTOs
     * @param array $models
     * @param array $groups
     * @param array $fields
     */
    protected function populateDTO(array $DTOs, array $models, array $groups = [], array $fields = [])
    {
        $properties = $this->getProperties($this->getReader());
        $accessor = PropertyAccess::createPropertyAccessor();

        $negativeFields = array_filter($fields, function ($field) {
            return '-' === $field[0];
        });
        if ($negativeFields) {
            $fields = array_diff($fields, $negativeFields);
        }

        if (!$fields) {
            $fields = array_keys($properties);
        }

        if ($negativeFields) {
            $fields = array_diff($fields, array_map(function($f)  {
                return trim($f, '-');
            }, $negativeFields));
        }

        $with = [];

        array_map(function(AbstractDTO $DTO, $model) use ($accessor, $fields, $properties, $groups, &$with) {
            foreach ($fields as $field) {
                $property = array_key_exists($field, $properties) && $properties[$field] ? $properties[$field] : null;
                if (null === $property) {
                    continue;
                }
                if ($groups && $property->getGroups() && !array_intersect($property->getGroups(), $groups)) {
                    continue;
                }
                $property->setName($field);
                if ($property->getSource() || $property->getSourceType()) {
                    $with[$field] = array_key_exists($field, $with) && $with[$field] ? $with[$field] : $property;
                    continue;
                }

                if ($property->getAssembler() && $property->getAssemblerMethod()) {
                    $assembler = $this->container->get($property->getAssembler());
                    $assemblerMethod = $property->getAssemblerMethod();
                    $DTO->$field = $assembler->$assemblerMethod($accessor->getValue($model, $field));
                    continue;
                }

                $DTO->$field = $property->convertType($property, $accessor->getValue($model, $field));
            }
        }, $DTOs, $models);

        //
        // WITH
        //
        $cache = [];

        array_map(function($field, Field $property) use ($DTOs, $models, &$cache) {
            $relation = null;
            switch ($property->getSourceType())
            {
                case Field::ONE_TO_ONE:
                    $relation = new OneToOneRelation($field, $property);
                    break;

                case Field::ONE_TO_CALL:
                    $relation = new OneToCallRelation($field, $property);
                    break;

                case Field::POPULATE:
                    $relation = new PopulateRelation($field, $property, $this);
            }

            if ($relation instanceof RelationInterface) {
                $relation->perform($models, $DTOs);
            }

        }, array_keys($with), $with);
    }

    /**
     * @return AbstractDTO
     */
    protected function createEmptyDTO()
    {
        $class = $this->getDTOClass();
        return new $class();
    }

    /**
     * @param object[] $models
     * @return Collection
     */
    public function createEmptyDTOCollection(array $models)
    {
        return new ArrayCollection(array_map(function() {
            return $this->createEmptyDTO();
        }, $models));
    }

    /**
     * Must not call as public. Need to redefined under other name
     *
     * @param object[] $models
     * @param array $fields
     * @param array $groups
     * @return Collection
     */
    protected function createDTOCollection(array $models, array $groups = [], array $fields = [])
    {
        $collection = $this->createEmptyDTOCollection($models);
        $this->populateDTO($collection->toArray(), $models, $groups, $fields);

        return $collection;
    }

    /**
     * Must not call as public. Need to redefined under other name
     *
     * @param $model
     * @param array     $groups
     * @param array     $fields
     *
     * @return AbstractDTO
     * @throws Exception
     */
    protected function createDTO($model = null, array $groups = [], array $fields = [])
    {
        if (null === $model) {
            throw new Exception(sprintf('%s should not be equals NULL', $this->getModelClass()));
        }

        $DTO = $this->createEmptyDTO();
        $this->populateDTO([$DTO], [$model], $groups, $fields);

        return $DTO;
    }

}