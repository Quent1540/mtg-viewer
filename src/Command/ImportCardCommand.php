<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Add a short description for your command',
)]
class ImportCardCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private array                           $csvHeader = []
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limite le nombre de cartes importées');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');

        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';

        $this->logger->info('Importing cards from ' . $filepath);

        if (!is_readable($filepath)) {
            $io->error('File not found');
            $this->logger->error('File not found: ' . $filepath);
            return Command::FAILURE;
        }

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            $io->error('Unable to open file');
            $this->logger->error('Unable to open file: ' . $filepath);
            return Command::FAILURE;
        }

        $limit = $input->getOption('limit') !== null ? (int)$input->getOption('limit') : null;
        $start = microtime(true);

        $i = 0;
        $this->csvHeader = fgetcsv($handle);
        if ($this->csvHeader === false) {
            $io->error('CSV header invalid or empty');
            $this->logger->error('CSV header invalid or empty in ' . $filepath);
            fclose($handle);
            return Command::FAILURE;
        }

        $uuidInDatabase = array_flip($this->entityManager->getRepository(Card::class)->getAllUuids());

        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Importing cards...');

        try {
            while (($row = $this->readCSV($handle)) !== false) {
                if ($limit !== null && $i >= $limit) {
                    break;
                }

                $i++;

                if (!isset($row['uuid']) || $row['uuid'] === '') {
                    $this->logger->warning(sprintf('Ligne %d : uuid manquant, ligne ignorée.', $i));
                    continue;
                }

                if (!isset($uuidInDatabase[$row['uuid']])) {
                    $this->addCard($row);
                }

                if ($i % 2000 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $progressIndicator->advance();
                }
            }

            $this->entityManager->flush();
            $progressIndicator->finish('Importing cards done.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur pendant l\'import: ' . $e->getMessage());
            $io->error($e->getMessage());
            fclose($handle);
            return Command::FAILURE;
        }

        fclose($handle);

        $end = microtime(true);
        $timeElapsed = $end - $start;
        $io->success(sprintf('Imported %d cards in %.2f seconds', $i, $timeElapsed));
        return Command::SUCCESS;
    }

    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }

        //Sauter les lignes mal formées
        if (count($row) !== count($this->csvHeader)) {
            return $this->readCSV($handle);
        }

        return array_combine($this->csvHeader, $row);
    }

    private function addCard(array $row)
    {
        $uuid = $row['uuid'];

        $card = new Card();
        $card->setUuid($uuid);
        $card->setManaValue($row['manaValue']);
        $card->setManaCost($row['manaCost']);
        $card->setName($row['name']);
        $card->setRarity($row['rarity']);
        $card->setSetCode($row['setCode']);
        $card->setSubtype($row['subtypes']);
        $card->setText($row['text']);
        $card->setType($row['type']);
        $this->entityManager->persist($card);
    }
}
