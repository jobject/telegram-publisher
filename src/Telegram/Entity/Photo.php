<?php

namespace App\Telegram\Entity;

/**
 * Class Photo
 * @package App\Entity
 */
class Photo
{
    public const PARSE_MODE_MARKDOWN = 'markdown';
    public const PARSE_MODE_HTML = 'html';

    /**
     * @var string|null
     */
    private $photo;

    /**
     * @var string|null
     */
    private $caption;

    /**
     * @var string|null
     */
    private $parseMode;

    /**
     * @return string|null
     */
    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    /**
     * @param string|null $photo
     */
    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @param string|null $caption
     */
    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    /**
     * @return string|null
     */
    public function getParseMode(): ?string
    {
        return $this->parseMode;
    }

    /**
     * @param string|null $parseMode
     */
    public function setParseMode(?string $parseMode): void
    {
        $this->parseMode = $parseMode;
    }
}
