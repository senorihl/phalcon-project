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

#[AsCommand(name: 'database:migration:down', description: 'Reverse previously played migration')]
class Down extends Command
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

            if ($lastMigration !== null && intval($lastMigration) >= $migrationObj->getVersion()) {
                $nextMigrations[$migrationObj->getVersion()] = $migrationObj;
            }
        }

        if (empty($nextMigrations)) {
            $output->writeln('No migration to run');
        }

        ksort($nextMigrations);
        $nextMigrations = array_reverse($nextMigrations);

        foreach ($nextMigrations as $migrationObj) {
            $output->writeln(sprintf('Run migration <fg=green>%s</>', get_class($migrationObj)));

            try {
                $migration->getMigrationAdapter()->begin();
                $migrationObj->setDI(Di::getDefault());
                $migrationObj->run('down');
                $tablePath = 'migrations.table.table';
                $table = $this->config->path($tablePath, 'migrations');
                $migration->getMigrationAdapter()->execute(
                    'DELETE FROM ' . $table . ' WHERE id = ?',
                    [$migrationObj->getVersion()],
                    [Column::BIND_PARAM_INT]
                );
                $migration->getMigrationAdapter()->commit();
                break;
            } catch (\Exception $e) {
                $migration->getMigrationAdapter()->rollback();
                throw $e;
            }

        }

        return 0;
    }

}