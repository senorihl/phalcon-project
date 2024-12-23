<?php

namespace App\Command\Migration;

use App\Helper\Migration;
use Doctrine\SqlFormatter\SqlFormatter;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Di\Di;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'database:migration:up', description: 'Execute next unplayed migration')]
class Up extends Command
{
    private Config $config;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->config = Di::getDefault()->get('config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migration = new Migration();
        $output = new SymfonyStyle($input, $output);

        $migration->getEventsManager()->attach('db:beforeQuery', function ($e, AdapterInterface $source) use ($output) {
            $formatter = new SqlFormatter();
            $output->writeln(sprintf(
                '[<fg=blue>%s</>] Run `%s`',
                $source->getDialectType(),
                trim($formatter->highlight($formatter->compress($source->getSQLStatement())))
            ), OutputInterface::VERBOSITY_VERBOSE);
        });

        $migration->ensureMigrationTable();
        $lastMigration = $migration->getLastMigration();
        $nextMigrations = [];

        foreach ($migration->getMigrations() as $class) {
            /** @var \App\Helper\Migration\Script $migration */
            $migrationObj = new $class();

            if ($lastMigration === null) {
                $nextMigrations[$migrationObj->getVersion()] = $migrationObj;
            } elseif (intval($lastMigration) < $migrationObj->getVersion()) {
                $nextMigrations[$migrationObj->getVersion()] = $migrationObj;
            }
        }

        if (empty($nextMigrations)) {
            $output->writeln('No migration to run');
        }

        ksort($nextMigrations);

        foreach ($nextMigrations as $migrationObj) {
            $output->writeln(sprintf('Run migration <fg=green>%s</>', get_class($migrationObj)));

            try {
                $migrationObj->setDI(Di::getDefault());
                $migrationObj->run('up');
                $tablePath = 'migrations.table.table';
                $table = $this->config->path($tablePath, 'migrations');
                $migration->getMigrationAdapter()->execute(
                    'INSERT INTO ' . $table . '(id, executed_at) VALUES (?, ?)',
                    [$migrationObj->getVersion(), date_create('now', timezone_open('UTC'))->format(DATE_ATOM)],
                    [Column::BIND_PARAM_INT, Column::BIND_PARAM_STR]
                );
                break;
            } catch (\Exception $e) {
                $migration->getMigrationAdapter()->rollback();
                throw $e;
            }

        }

        return 0;
    }

}