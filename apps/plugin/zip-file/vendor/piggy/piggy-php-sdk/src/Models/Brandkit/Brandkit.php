<?php

namespace Piggy\Api\Models\Brandkit;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Brandkit\BrandkitMapper;

class Brandkit
{
    /**
     * @var string|null
     */
    protected $small_logo_url;

    /**
     * @var string|null
     */
    protected $large_logo_url;

    /**
     * @var string|null
     */
    protected $cover_image_url;

    /**
     * @var string|null
     */
    protected $primary_color;

    /**
     * @var string|null
     */
    protected $secondary_color;

    /**
     * @var string|null
     */
    protected $tertiary_color;

    /**
     * @var string|null
     */
    protected $quaternary_color;

    /**
     * @var string|null
     */
    protected $font_color;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $corner_theme;

    /**
     * @var string|null
     */
    protected $font_family;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/brand-kit';

    public function __construct(
        ?string $small_logo_url = null,
        ?string $large_logo_url = null,
        ?string $cover_image_url = null,
        ?string $primary_color = null,
        ?string $secondary_color = null,
        ?string $tertiary_color = null,
        ?string $quaternary_color = null,
        ?string $font_color = null,
        ?string $description = null,
        ?string $corner_theme = null,
        ?string $font_family = null
    ) {
        $this->small_logo_url = $small_logo_url;
        $this->large_logo_url = $large_logo_url;
        $this->cover_image_url = $cover_image_url;
        $this->primary_color = $primary_color;
        $this->secondary_color = $secondary_color;
        $this->tertiary_color = $tertiary_color;
        $this->quaternary_color = $quaternary_color;
        $this->font_color = $font_color;
        $this->description = $description;
        $this->corner_theme = $corner_theme;
        $this->font_family = $font_family;
    }

    public function getLargeLogoUrl(): ?string
    {
        return $this->large_logo_url;
    }

    public function getSmallLogoUrl(): ?string
    {
        return $this->small_logo_url;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->cover_image_url;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primary_color;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondary_color;
    }

    public function getTertiaryColor(): ?string
    {
        return $this->tertiary_color;
    }

    public function getQuaternaryColor(): ?string
    {
        return $this->quaternary_color;
    }

    public function getFontColor(): ?string
    {
        return $this->font_color;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCornerTheme(): ?string
    {
        return $this->corner_theme;
    }

    public function getFontFamily(): ?string
    {
        return $this->font_family;
    }

    /**
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(): Brandkit
    {
        $response = ApiClient::get(self::resourceUri);

        return BrandkitMapper::map($response->getData());
    }
}
