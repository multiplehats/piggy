<?php

namespace Piggy\Api\Models\ContactsPortal;

class ContactsPortalAuthUrl
{
    /**
     * @var string
     */
    protected $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
