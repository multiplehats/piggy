<?php

namespace Piggy\Api\StaticMappers\Forms;

use Piggy\Api\Models\Forms\Form;

class FormMapper
{
    public static function map($data): Form
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
