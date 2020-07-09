<?php

namespace App\Command;

use App\Telegram\BotApi;
use App\Telegram\Entity\Photo;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\Config\Util\Exception\XmlParsingException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Class ParserShafaCommand
 * @package App\Command
 */
class ParserShafaCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var BotApi
     */
    private $botApi;

    /**
     * ParserShafaCommand constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->botApi = new BotApi($this->client);

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('parser:shafa');
        $this->setDescription('Parse liked products from https://shafa.ua');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Starting parser');
        $html = $this->client
            ->get(
                'https://shafa.ua/my/favourites',
                [
                    'query' => [
                        'page' => 1,
                    ],
                    'cookies' => CookieJar::fromArray(
                        [
                            'sessionid' => $_ENV['SHAFA_SESSION_ID'],
                        ],
                        'shafa.ua'
                    ),
                    'headers' => [
                        'x-pjax' => true,
                    ],
                ]
            )
            ->getBody()
            ->getContents();

        $crawler = new Crawler($html);

        foreach ($crawler->filter('li') as $parent) {
            $parentCrawler = new Crawler($parent);
            $photo = new Photo();
            preg_match('/\/([0-9]+)/isu', $parentCrawler->filter('img.js-lazy-img')->attr('data-src'), $image);
            $imageId = $image[1] ?? null;
            $name = $parentCrawler->filter('[data-product-name]')->attr('data-product-name');
            $price = $parentCrawler->filter('[data-product-price]')->attr('data-product-price');
            $path = $parentCrawler->filter('a.js-ga-onclick')->attr('href');
            $photo->setPhoto("https://images.shafastatic.net/{$imageId}");
            $photo->setCaption("$name\n\nPrice: $price UAH\n\nhttps://shafa.ua$path");
            $photo->setParseMode(Photo::PARSE_MODE_MARKDOWN);

            $this->botApi->sendPhoto($photo);
        }

        return 0;
    }
}
