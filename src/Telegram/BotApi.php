<?php

namespace App\Telegram;

use App\Entity\Photo;
use App\Entity\Product;
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
     * @param Photo $product
     * @return bool
     */
    public function sendProduct(Product $product): bool
    {
        if (!$this->token || !$this->chatId || !$product->getPhoto()) {
            return false;
        }

        try {
            $this->client->request(
                'POST',
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
        }

        return true;
    }
}
