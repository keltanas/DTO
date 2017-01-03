<?php

namespace Keltanas\DTO\Relations;

use Keltanas\DTO\AbstractDTO;
use Keltanas\DTO\Relations\AbstractRelation;

class OneToCallRelation extends AbstractRelation
{
    /**
     * {@inheritdoc}
     */
    public function perform($models, $DTOs)
    {
        $field = $this->fieldName;
        $property = $this->fieldParams;
        $source = $this->container->get($property->getSource());
        $sourceMethod = $property->getSourceMethod() ?: null;

        $cache = [];
        array_map(function(AbstractDTO $DTO, $model)
        use ($source, $sourceMethod, $field, $property, &$cache)
        {
            $cacheKey = get_class($model) . '_' . $model->id;
            if (!isset($cache[$cacheKey])) {
                $cache[$cacheKey] = $source->$sourceMethod($model);

                if ($property->getAssembler() && $property->getAssemblerMethod()) {
                    $assembler = $this->container->get($property->getAssembler());
                    $assemblerMethod = $property->getAssemblerMethod();
                    $cache[$cacheKey] = $assembler->$assemblerMethod($cache[$cacheKey]);
                }
            }
            $DTO->$field = $cache[$cacheKey];
        }, $DTOs, $models);
    }
}
