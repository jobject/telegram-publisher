<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Telegram\BotApi;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTimeZone;
use DateTime;

/**
 * Class PublisherProductCommand
 * @package App\Command
 */
class PublisherProductCommand extends Command
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
     * @var BotApi
     */
    private $botApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PublisherProductCommand constructor.
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param BotApi $botApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        BotApi $botApi,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->botApi = $botApi;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('publisher:product');
        $this->setDescription('Publish products to telegram');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        while (true) {
            $this->logger->info('Starting publisher');

            $currentHour = (new DateTime(
                'now', new DateTimeZone('Europe/Kiev')
            ))->format('G');

            if ($currentHour >= 8 && $currentHour < 23) {
                $products = $this->productRepository->findBy(
                    ['published' => false],
                    null,
                    10
                );

                $this->logger->info('Found ' . count($products) . ' products');

                foreach ($products as $product) {
                    $product->setPublished(
                        $this->botApi->sendProduct($product)
                    );
                    $this->logger->info(
                        "Publish product {$product->getExternalId()}"
                    );
                }

                $this->entityManager->flush();

                $this->logger->info('Publishing was finished');
            } else {
                $this->logger->info('Publishing was skipped');
            }

            $this->logger->info('Sleep for half an hour');
            sleep(1800);
        }

        return 0;
    }
}
