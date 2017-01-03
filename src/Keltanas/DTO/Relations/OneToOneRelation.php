<?php

namespace Keltanas\DTO\Relations;

use Keltanas\DTO\AbstractDTO;

class OneToOneRelation extends AbstractRelation
{
    /**
     * {@inheritdoc}
     */
    public function perform($models, $DTOs)
    {
        $field = $this->fieldName;
        $property = $this->fieldParams;
        $service = $this->container->get($property->getSource());
        $method = $property->getSourceMethod() ?: 'findById';

        $sourceField = $property->getSourceField();
        $keysValues = array_unique(array_column($models, $sourceField));
        $relModels = array_map([$service, $method], $keysValues);

        array_map(function(AbstractDTO $DTO, $model)
        use ($property, $sourceField, $field, $relModels, &$cache)
        {
            foreach ($relModels as $relModel) {
                if ((string)$model->$sourceField === (string)$relModel->id) {
                    $cacheKey = get_class($relModel) . '_' . $relModel->id;
                    if (!isset($cache[$cacheKey])) {
                        $cache[$cacheKey] = $relModel;
                        if ($property->getAssembler() && $property->getAssemblerMethod()) {
                            $assembler = $this->container->get($property->getAssembler());
                            $assemblerMethod = $property->getAssemblerMethod();
                            $cache[$cacheKey] = $assembler->$assemblerMethod($cache[$cacheKey]);
                        }
                    }
                    $DTO->$field = $cache[$cacheKey];
                    break;
                }
            }
        }, $DTOs, $models);
    }
}