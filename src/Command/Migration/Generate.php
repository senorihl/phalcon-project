<?php

namespace App\Command\Migration;

use App\Helper\Migration;
use App\Helper\Text;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'database:migration:generate', description: 'Generate an empty migration file')]
class Generate extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = new Migration();
        $result = $helper->generate('', '', $filename);

        if (false !== $result) {
            $output->writeln(sprintf(
                'Created file <fg=green>%s</> (%s)',
                $filename,
                Text::formatBytes($result)
            ));
        }

        return false !== $result;
    }

}