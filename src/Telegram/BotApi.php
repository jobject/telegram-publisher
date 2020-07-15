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
        $this->token = getenv('TELEGRAM_TOKEN');
        $this->chatId = getenv('TELEGRAM_CHAT_ID');
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
            dd(
                $exception->getMessage(),
                $product,
                $exception->getTraceAsString()
            );
        }

        return true;
    }

    /**
     * @param Product[] $products
     * @return bool
     */
    public function sendProducts(array $products): bool
    {
        if (!$this->token || !$this->chatId || empty($products)) {
            return false;
        }

        try {
            $this->client->post(
                "https://api.telegram.org/bot{$this->token}/sendMediaGroup",
                [
                    'json' => [
                        'chat_id' => $this->chatId,
                        'media' => array_map(
                            function (Product $product): array {
                                return [
                                    'type' => 'photo',
                                    'media' => $product->getPhoto(),
                                    'caption' => $product->getCaption(),
                                    'parse_mode' => $product->getParseMode(),
                                ];
                            },
                            $products
                        ),
                    ]
                ]
            );
        } catch (Exception $exception) {
            dd(
                $exception->getMessage(),
                $product,
                $exception->getTraceAsString()
            );
        }

        return true;
    }
}
