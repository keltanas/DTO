<?php

namespace Keltanas\DTO\Relations;

interface RelationInterface
{
    /**
     * @param $models
     * @param $DTOs
     *
     * @return void
     */
    public function perform($models, $DTOs);
}
