<?php

namespace App\Telegram;

use App\Entity\Product;
use GuzzleHttp\Client;
use Exception;

/**
 * Class BotApi
 * @package App\Telegram
 */
class BotApi
{
    /**
     * @var Client
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
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->token = $_ENV['TELEGRAM_TOKEN'];
        $this->chatId = $_ENV['TELEGRAM_CHAT_ID'];
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function sendProduct(Product $product): bool
    {
        if (!$this->token || !$this->chatId || !$product->getPhoto()) {
            return false;
        }

        try {
            $this->client->post(
                "https://api.telegram.org/bot{$this->token}/sendPhoto",
                [
                    'json' => [
                        'chat_id' => $this->chatId,
                        'photo' => $product->getPhoto(),
                        'caption' => $product->getCaption(),
                        'parse_mode' => $product->getParseMode(),
                    ]
                ]
            );
        } catch (Exception $exception) {
            dd($exception->getMessage(), $exception->getTraceAsString());
        }

        return true;
    }
}
