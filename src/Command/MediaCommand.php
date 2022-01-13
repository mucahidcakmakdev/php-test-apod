<?php

namespace App\Command;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class MediaCommand extends Command {

    private $em;
    private $mediaRepository;
    private $nasaApiKey;
    protected static $defaultName = 'app:run:media';

    public function __construct(
            EntityManagerInterface $em,
            MediaRepository $mediaRepository,
            $nasaApiKey
    ) {
        $this->em = $em;
        $this->mediaRepository = $mediaRepository;
        $this->nasaApiKey = $nasaApiKey;

        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {

        $date = date('Y-m-d');
        $io = new SymfonyStyle($input, $output);

        $exist = $this->mediaRepository->findOneBy(['date' => $date]);
        if ($exist) {
            $io->error("The picture of the day $date has already been added");
            return Command::FAILURE;
        }

        $httpClient = HttpClient::create();
        $response = $httpClient->request('GET', 'https://api.nasa.gov/planetary/apod',
                ['query' => ['api_key' => $this->nasaApiKey, 'date' => $date]]
        );
        $data = $response->toArray();

        $media = new Media();
        $media->setDate($data['date'] ?? '');
        $media->setExplanation($data['explanation'] ?? '');
        $media->setMediaType($data['media_type'] ?? '');
        $media->setTitle($data['title'] ?? '');
        $media->setUrl($data['url'] ?? '');

        $this->em->persist($media);

        $this->em->flush();

        $io->success("The NASA picture of the day $date has been saved");

        return Command::SUCCESS;
    }

}
