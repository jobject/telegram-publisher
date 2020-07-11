<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Telegram\BotApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * PublisherProductCommand constructor.
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param BotApi $botApi
     */
    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        BotApi $botApi
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->botApi = $botApi;

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
        $output->writeln('Starting publisher');

        $products = $this->productRepository->findBy(
            ['published' => false],
            null,
            10
        );

        $output->writeln('Found ' . count($products) . ' products');

        foreach ($products as $product) {
            $product->setPublished($this->botApi->sendProduct($product));
            $output->writeln("Publish product {$product->getExternalId()}");
        }

        $this->entityManager->flush();

        $output->writeln('Publishing was finished');

        return 0;
    }
}
