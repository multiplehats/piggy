<?php

namespace Piggy\Api\Mappers\Forms;

use Piggy\Api\Models\Forms\Form;
use stdClass;

class FormMapper
{
    public function map(stdClass $data): Form
    {
        return new Form(
            $data->name,
            $data->status,
            $data->url,
            $data->uuid,
            $data->type
        );
    }
}
