<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ParserShafaCommand constructor.
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param Client $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Client $client,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->client = $client;
        $this->logger = $logger;

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
        while (true) {
            $this->logger->info('Starting parser');

            $page = 1;
            $isContinue = true;

            while ($isContinue) {
                $isContinue = $this->parsePage($page++);
            }

            $this->logger->info("Parsing was finished\nSleep for one hour");
            sleep(3600);
        }

        return 0;
    }

    /**
     * @param int $page
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function parsePage(int $page): bool
    {
        $this->logger->info("Page: $page");

        $html = $this->client
            ->get(
                'https://shafa.ua/my/favourites',
                [
                    'query' => [
                        'page' => $page,
                    ],
                    'cookies' => CookieJar::fromArray(
                        [
                            'sessionid' => getenv('SHAFA_SESSION_ID'),
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

        $isNotEmpty = false;

        foreach ((new Crawler($html))->filter('li.b-catalog__item') as $item) {
            $crawler = new Crawler($item);
            $externalId = $crawler->filter('[data-id]')->attr('data-id');
            $product = $this->productRepository->findOneByExternalId(
                $externalId
            );
            if ($product) {
                continue;
            }

            $isNotEmpty = true;
            $product = new Product();
            $product->setExternalId($externalId);

            preg_match(
                '/\/([0-9]+)/isu',
                $crawler->filter('img.js-lazy-img')->attr('data-src'),
                $image
            );
            $imageId = $image[1] ?? null;
            $product->setPhoto("https://images.shafastatic.net/{$imageId}");
            $description = htmlentities(
                $crawler->filter('[data-product-name]')->attr(
                    'data-product-name'
                )
            );
            $price = $crawler->filter('[data-product-price]')->attr(
                'data-product-price'
            );
            $url = "https://shafa.ua{$crawler->filter('a.js-ga-onclick')->attr('href')}";
            $product->setCaption(
                "$description\n\n<b>Ціна:</b> $price грн.\n\n<a href=\"{$url}\">{$url}</a>"
            );
            $product->setParseMode('html');
            $this->entityManager->persist($product);

            $this->logger->info("Save product {$product->getExternalId()}");
        }

        $this->entityManager->flush();

        return $isNotEmpty;
    }
}
