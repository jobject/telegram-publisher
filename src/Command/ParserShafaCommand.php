<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
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
     * ParserShafaCommand constructor.
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param Client $client
     */
    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Client $client
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->client = $client;

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
            $output->writeln('Starting parser');

            $page = 1;
            $isContinue = true;

            while ($isContinue) {
                $isContinue = $this->parsePage($output, $page++);
            }

            $output->writeln('Parsing was finished');
            $output->writeln('Sleep for one hour');
            sleep(3600);
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param int $page
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function parsePage(OutputInterface $output, int $page): bool
    {
        $output->writeln("Page: $page");

        $html = $this->client
            ->get(
                'https://shafa.ua/my/favourites',
                [
                    'query' => [
                        'page' => $page,
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
            $product->setParseMode('markdown');
            $this->entityManager->persist($product);

            $output->writeln("Save product {$product->getExternalId()}");
        }

        $this->entityManager->flush();

        return $isNotEmpty;
    }
}
