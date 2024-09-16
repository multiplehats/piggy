<?php

namespace Piggy\Api\Http\Responses;

use stdClass;

class Response
{
    /**
     * @var stdClass|array<mixed, mixed>|mixed
     */
    protected $data;

    /**
     * @var stdClass|array<mixed, mixed>|mixed
     */
    protected $meta;

    /**
     * Response constructor.
     *
     * @param  stdClass|array<mixed, mixed>|mixed  $data
     * @param  stdClass|array<mixed, mixed>|mixed  $meta
     */
    public function __construct($data, $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * @return stdClass|array<mixed, mixed>|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return stdClass|array<mixed, mixed>|mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }
}
