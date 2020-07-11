<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Telegram\BotApi;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use InvalidArgumentException;

/**
 * Class ParserShafaCommand
 * @package App\Command
 */
class ParserShafaCommand extends Command
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
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

        foreach ((new Crawler($html))->filter('li.b-catalog__item') as $item) {
            $crawler = new Crawler($item);
            $externalId = $crawler->filter('[data-id]')->attr('data-id');
            $product = $this->productRepository->findOneByExternalId(
                $externalId
            );
            if ($product) {
               continue;
            }

            $product = new Product();
            $product->setExternalId($externalId);

            preg_match(
                '/\/([0-9]+)/isu',
                $crawler->filter('img.js-lazy-img')->attr('data-src'),
                $image
            );
            $imageId = $image[1] ?? null;
            $product->setPhoto("https://images.shafastatic.net/{$imageId}");
            $name = $crawler->filter('[data-product-name]')->attr(
                'data-product-name'
            );
            $price = $crawler->filter('[data-product-price]')->attr(
                'data-product-price'
            );
            $path = $crawler->filter('a.js-ga-onclick')->attr('href');
            $product->setCaption(
                "$name\n\nPrice: $price UAH\n\nhttps://shafa.ua$path"
            );
            $product->setParseMode(Photo::PARSE_MODE_MARKDOWN);
            $product->setPublished($this->botApi->sendProduct($product));
            $this->entityManager->persist($product);

            $output->writeln("Publish product {$product->getExternalId()}");
        }

        $this->entityManager->flush();

        $output->writeln('Parsing was finished');

        return 0;
    }
}
