<?php

namespace App\Command\Migration;

use Nette\PhpGenerator\PsrPrinter;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Migration\Action\Generate as GenerateAction;
use Phalcon\Migrations\Migrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @property-read AdapterInterface db
 */
#[AsCommand(name: 'database:migration:generate', description: 'Generate a migration file')]
class Generate extends Command
{
    protected function configure()
    {
        $this
            ->addOption('directory', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Migrations directory.')
            ->addOption('table', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Table to migrate. Table name or table prefix with asterisk. Default: all')
            ->addOption('migrate', null, InputOption::VALUE_REQUIRED, 'Version to migrate')
            ->addOption('descr', null, InputOption::VALUE_REQUIRED, 'Migration description (used for timestamp based migration)')
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Export data [always|oncreate] (Import data when run migration)')
            ->addOption('exportDataFromTables', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Export data from specific tables, use comma separated string')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces to overwrite existing migrations')
            ->addOption('ts-based', null, InputOption::VALUE_NONE, 'Timestamp based migration version')
            ->addOption('log-in-db', null, InputOption::VALUE_NONE, 'Keep migrations log in the database table rather than in file')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Attempt requested operation without making changes to system (Generating only)')
            ->addOption('no-auto-increment', null, InputOption::VALUE_NONE, 'Disable auto increment (Generating only)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var AdapterInterface $db */
        $db = \Phalcon\Di\Di::getDefault()->get('db');
        $className = 'Migration_' . date('YmdHis');
        $printer       = new PsrPrinter();
        $snippet       = new Snippet();

        $action = new GenerateAction($db->getType());
        $action->createEntity($className)
            ->addMorph($snippet, $table, $skipRefSchema, self::$skipAI)
            ->addUp($table, $exportData, $shouldExportDataFromTable)
            ->addDown($table, $exportData, $shouldExportDataFromTable)
            ->addAfterCreateTable($table, $exportData)
            ->createDumpFiles(
                $table,
                self::$migrationPath,
                self::$connection,
                $version,
                $exportData,
                $shouldExportDataFromTable
            )
        ;
    }

}