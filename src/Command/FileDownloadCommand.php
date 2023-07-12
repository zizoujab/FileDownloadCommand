<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: "file:download", description: "Downloads a file locally")]
class FileDownloadCommand extends Command
{
    public function __construct(private readonly  HttpClientInterface $httpClient, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('url' , InputArgument::OPTIONAL, 'File url ', "https://proof.ovh.net/files/100Mb.dat");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressBar = new ProgressBar($output, 100);
        $url = $input->getArgument('url');
        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use ($progressBar) {
                if ($dlSize && $dlNow > 0 ){
                    $progressBar->setProgress(intval($dlNow*100 / $dlSize));
                    if ($dlNow == $dlSize){
                        $progressBar->finish();
                    }
                }
            }
        ]);
        $filHandler = fopen('./file.dat' , 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($filHandler, $chunk->getContent());
        }

        return Command::SUCCESS;
    }


}