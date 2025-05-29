<?php

namespace App\Helper;

use App\Helper\Migration\Script;
use Exception;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Column;
use Phalcon\Di\Di;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;

class Migration implements EventsAwareInterface
{
    private \Phalcon\Config\Config $config;
    private Manager $eventsManager;

    public function __construct()
    {
        $this->config = Di::getDefault()->get('config');
        $this->eventsManager = Di::getDefault()->get('eventsManager');
    }


    private ?AbstractAdapter $adapter = null;

    public function getMigrationAdapter(): AbstractAdapter {

        if (is_null($this->adapter)) {
            $servicePath = 'migrations.table.service';
            $service = $this->config->path($servicePath);
            $this->adapter = Di::getDefault()->get($service);
            $this->adapter->setEventsManager($this->eventsManager);
        }

        return $this->adapter;
    }

    public function getLastMigration(): ?string
    {
        $tablePath = 'migrations.table.table';
        $table = $this->config->path($tablePath, 'migrations');
        return
            $this->getMigrationAdapter()
                ->query('SELECT id FROM ' . $table . ' ORDER BY id DESC LIMIT 1')
                ->fetchArray()['id'] ?? null;
    }

    /**
     * @return string[]
     */
    public function getMigrations(): array
    {
        return array_filter(get_application_classes(), function (string $class) {
            return in_array(Migration\Script::class, class_parents($class));
        });
    }

    /**
     * @return string[]
     */
    public function getModels(): array
    {
        return array_filter(get_application_classes(), function (string $class) {
            return in_array(\Phalcon\Mvc\Model::class, class_parents($class));
        });
    }

    public function generate(string $contentUp = '', string $contentDown = '', string &$filename = null)
    {
        $version = date('YmdHis');

        return $this->generateMigrationFile($version, $contentUp, '', $filename);
    }

    private function generateMigrationFile(string $version, string $contentUp = '', string $contentDown = '', string &$classFile = null)
    {
        $namespaceName = 'App\Migrations';
        $classVersion = preg_replace('/[^0-9A-Za-z]/', '', $version);
        $className = 'Migration_' . $classVersion;
        $classDirectory = BASE_PATH . '/src/' .
            preg_replace('#^App/#', '', str_replace('\\', '/', $namespaceName));
        $classFile = $classDirectory . '/' . $className . '.php';
        $file = new PhpFile();
        $file->addComment('File was auto-generated');
        $ns = $file->addNamespace('App\Migrations');
        $ns->addUse(Script::class, 'MigrationScript');
        $class = $ns->addClass($className);
        $class->setExtends(Script::class);
        $class
            ->addMethod('up')
            ->setProtected()
            ->setBody($contentUp);
        $class
            ->addMethod('down')
            ->setProtected()
            ->setBody($contentDown);
        $class
            ->addMethod('getVersion')
            ->setPublic()
            ->setReturnType('int')
            ->setBody('return intval("' . $classVersion . '");');

        $printer = new PsrPrinter();

        // @codeCoverageIgnoreStart
        if (!file_exists($classDirectory) && !is_dir($classDirectory)) {
            mkdir($classDirectory, 0755, true);
        }
        // @codeCoverageIgnoreEnd

        return file_put_contents($classFile, $printer->printFile($file));
    }

    public function getEventsManager(): ManagerInterface|null
    {
        return $this->eventsManager;
    }

    public function setEventsManager(ManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    public function ensureMigrationTable(): bool
    {
        $servicePath = 'migrations.table.service';
        $service = $this->config->path($servicePath);

        if (is_null($service)) {
            throw new Exception('You must define '.$servicePath.' in your configuration.');
        } elseif (!Di::getDefault()->get($service) instanceof AbstractAdapter) {
            throw new Exception('The service ('.$servicePath.') is not an instance of '.AbstractAdapter::class.'.');
        } elseif (!Di::getDefault()->get($service) instanceof AbstractPdo) {
            throw new Exception('The service ('.$servicePath.') is not an instance of '.AbstractPdo::class.', and cannot be used.');
        }

        $tablePath = 'migrations.table.table';
        $table = $this->config->path($tablePath, 'migrations');

        /** @var AbstractPdo $adapter */
        $adapter = $this->getMigrationAdapter();

        if ($adapter->tableExists($table)) {
            return true;
        }

        return $adapter->createTable($table, '', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'notNull' => true,
                        'primary' => true,
                    ]
                ),
                new Column(
                    'executed_at',
                    [
                        'type' => Column::TYPE_DATETIME,
                        'notNull' => true,
                    ]
                ),
            ]
        ]);
    }
}