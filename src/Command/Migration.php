<?php

namespace App\Command;

use Phalcon\Migrations\Migrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'database:migration', description: 'Execute migration some migration actions')]
class Migration extends Command
{
    protected function configure()
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'The action to perform (generate/run/list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        try  {
            $callable = match ($input->getArgument('action')) {
                'generate' => [Migrations::class , 'generate'],
                'run' => [Migrations::class , 'run'],
                'list' => [Migrations::class , 'listAll'],
            };
        } catch (\UnhandledMatchError $e) {
            $output->error('Cannot perform action `' . $input->getArgument('action') . '`.');
            return self::INVALID;
        }

        dump($callable);

        return self::SUCCESS;
    }

}