<?php

namespace App\Telegram;

use App\Telegram\Entity\Photo;
use GuzzleHttp\ClientInterface;
use Exception;

/**
 * Class BotApi
 * @package App\Telegram
 */
class BotApi
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string|null
     */
    private $token;

    /**
     * @var string|null
     */
    private $chatId;

    /**
     * BotApi constructor.
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->token = $_ENV['TELEGRAM_TOKEN'];
        $this->chatId = $_ENV['TELEGRAM_CHAT_ID'];
    }

    /**
     * @param Photo $photo
     * @return bool
     */
    public function sendPhoto(Photo $photo): bool
    {
        if (!$this->token || !$this->chatId || !$photo->getPhoto()) {
            return false;
        }

        try {
            $this->client->request(
                'POST',
                "https://api.telegram.org/bot{$this->token}/sendPhoto",
                [
                    'json' => [
                        'chat_id' => $this->chatId,
                        'photo' => $photo->getPhoto(),
                        'caption' => $photo->getCaption(),
                        'parse_mode' => $photo->getParseMode(),
                    ]
                ]
            );
        } catch (Exception $exception) {}

        return true;
    }
}
