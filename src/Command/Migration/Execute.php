<?php

namespace App\Command\Migration;

use App\Helper\Migration;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Di\Di;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'database:migration:execute', description: 'Execute unplayed migrations')]
class Execute extends Command
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

        $migration->getEventsManager()->attach('db:beforeQuery', function ($e, AdapterInterface $source) use ($output) {
            $output->writeln(sprintf(
                '[<fg=blue>%s</>] Run `%s`',
                $source->getDialectType(),
                $source->getSQLStatement()
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
                $migration->getMigrationAdapter()->begin();
                $migrationObj->setDI(Di::getDefault());
                $migrationObj->run('up');
                $tablePath = 'migrations.table.table';
                $table = $this->config->path($tablePath, 'migrations');
                $migration->getMigrationAdapter()->execute(
                    'INSERT INTO ' . $table . '(id, executed_at) VALUES (?, ?)',
                    [$migrationObj->getVersion(), date_create('now', timezone_open('UTC'))->format(DATE_ATOM)],
                    [Column::BIND_PARAM_INT, Column::BIND_PARAM_STR]
                );
                $migration->getMigrationAdapter()->commit();
                sleep(1);
            } catch (\Exception $e) {
                $migration->getMigrationAdapter()->rollback();
                throw $e;
            }

        }

        return 0;
    }

}