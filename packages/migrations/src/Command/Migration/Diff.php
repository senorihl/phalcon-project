<?php

namespace App\Command\Migration;

use App\Helper\Migration;
use App\Helper\Text;
use Phalcon\Di\Di;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'database:migration:diff', description: 'Generate new migration file based on differences between model manager and database')]
class Diff extends Command
{

    private \Phalcon\Mvc\Model\Manager $modelsManager;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->modelsManager = Di::getDefault()->get('modelsManager');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = new Migration();
        $diff = new Migration\Difference();
        $sql = array();
        $globalTablesDetails = array();
        $models = [];

        foreach ($helper->getModels() as $modelName) {
            $reflectionClass = new \ReflectionClass($modelName);

            if ($reflectionClass->isAbstract()) {
                $output->writeln('Class <fg=blue>' . $modelName . '</> is abstract and was ignored during the generation of diff');
                continue;
            }

            $models[$modelName] = $this->modelsManager->load($modelName);
        }

        foreach ($models as $modelName => $model) {
            if ($model->getReadConnection()->getDialectType() === 'postgresql') {
                $tableDetails = $diff->detailPostgresTable($modelName, $model, $models);
            } elseif ($model->getReadConnection()->getDialectType() === 'mysql') {
                $tableDetails = $diff->detailMysqlTable($modelName, $model, $models);
            } else {
                throw new \Exception('Unhandled dialect ' . $model->getReadConnection()->getDialectType());
            }

            $globalTablesDetails[] = $tableDetails;
            $sqlInstruction = $diff->morphTable($tableDetails, $model);
            array_push($sql, ...$sqlInstruction);
        }

        $result = $helper->generate(join("\n\n", $sql), '', $filename);

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