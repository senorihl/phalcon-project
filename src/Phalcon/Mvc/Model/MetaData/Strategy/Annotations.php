<?php

namespace App\Phalcon\Mvc\Model\MetaData\Strategy;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\ModelInterface;

class Annotations implements \Phalcon\Mvc\Model\MetaData\Strategy\StrategyInterface
{
    private ?\Phalcon\Mvc\Model\MetaData\Strategy\StrategyInterface $rootStrategy = null;
    private ?\Phalcon\Annotations\Adapter\AdapterInterface $annotations = null;
    private ?\Phalcon\Mvc\Model\ManagerInterface $manager = null;

    public function getRootStrategy()
    {
        if (is_null($this->rootStrategy)) {
            $this->rootStrategy = new \Phalcon\Mvc\Model\MetaData\Strategy\Annotations();
        }

        return $this->rootStrategy;
    }

    private function getAnnotations()
    {
        if (is_null($this->annotations)) {
            $this->annotations = \Phalcon\Di\Di::getDefault()->get('annotations');
        }

        return $this->annotations;
    }

    private function getManager()
    {
        if (is_null($this->manager)) {
            $this->manager = \Phalcon\Di\Di::getDefault()->get('modelsManager');
        }

        return $this->manager;
    }

    public function getMetaData(ModelInterface $model, DiInterface $container): array
    {
        $metaData = $this->getRootStrategy()->getMetaData($model, $container);
        list(,$propertyMap) = $this->getColumnMaps($model, $container);
        $metaData['unique'] = [];
        $metaData['indexes'] = [];
        $metaData['sizes'] = [];
        $metaData['sql_default'] = [];

        $reflection = $this->getAnnotations()->get(get_class($model));
        $collection = $reflection->getClassAnnotations();

        if ($collection) {
            if ($collection->has('Index')) {
                $arguments = $collection->get('Index')->getArguments();
                $name = $arguments['name'];

                if (!is_array($arguments['columns'])) {
                    $arguments['columns'] = [$arguments['columns']];
                }

                if (isset($arguments['unique'])) {
                    $metaData['unique'][$name] = $arguments['columns'];
                }
            }

            if ($collection->has('HasMany')) {
                $this->getManager()->addHasMany($model, ...$collection->get('HasMany')->getArguments());
            }

            if ($collection->has('HasManyToMany')) {
                $this->getManager()->addHasManyToMany($model, ...$collection->get('HasManyToMany')->getArguments());
            }

            if ($collection->has('HasOne')) {
                $this->getManager()->addHasOne($model, ...$collection->get('HasOne')->getArguments());
            }

            if ($collection->has('BelongsTo')) {
                $this->getManager()->addBelongsTo($model, ...$collection->get('BelongsTo')->getArguments());
            }

            if ($collection->has('HasOneThrough')) {
                $this->getManager()->addHasOneThrough($model, ...$collection->get('HasOneThrough')->getArguments());
            }
        }

        foreach ($reflection->getPropertiesAnnotations() as $name => $collection) {
            if ($collection->has('BelongsTo')) {
                $arguments = $collection->get('BelongsTo')->getArguments();
                $referencedModelName = array_shift($arguments);
                $referencedColumn = array_shift($arguments);
                $options = array_shift($arguments) ?? [];

                if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                    throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                }

                $this->getManager()->addBelongsTo($model, $name, $referencedModelName, $referencedColumn, $options);
            } elseif ($collection->has('HasOne')) {
                $arguments = $collection->get('HasOne')->getArguments();
                $referencedModelName = array_shift($arguments);
                $referencedColumn = array_shift($arguments);
                $options = array_shift($arguments) ?? [];

                if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                    throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                }

                $this->getManager()->addHasOne($model, $name, $referencedModelName, $referencedColumn, $options ?? []);
            } elseif ($collection->has('HasMany')) {
                $arguments = $collection->get('HasMany')->getArguments();
                $referencedModelName = array_shift($arguments);
                $referencedColumn = array_shift($arguments);
                $options = array_shift($arguments) ?? [];

                if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                    throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                }

                $this->getManager()->addHasOne($model, $name, $referencedModelName, $referencedColumn, $options ?? []);
            }

            if ($collection->has('Column')) {
                $arguments = $collection->get('Column')->getArguments();
                if (isset($arguments['size'])) {
                    $metaData['sizes'][$propertyMap[$name]] = intval($arguments['size']);
                }
                if (isset($arguments['sql_default'])) {
                    $metaData['sql_default'][$propertyMap[$name]] = $arguments['sql_default'];
                }
                if (isset($arguments['unique'])) {
                    if (!is_array($arguments['unique'])) {
                        $arguments['unique'] = [$arguments['unique']];
                    }

                    foreach ($arguments['unique'] as $partsOf) {
                        if (is_string($partsOf)) {
                            if (!array_key_exists($partsOf, $metaData['unique'])) {
                                $metaData['unique'][$partsOf] = [];
                            }
                            $metaData['unique'][$partsOf][] = $propertyMap[$name];
                        } elseif (boolval($partsOf) === true) {
                            if (!array_key_exists($propertyMap[$name] . '_uniq', $metaData['unique'])) {
                                $metaData['unique'][$propertyMap[$name] . '_uniq'] = [];
                            }
                            $metaData['unique'][$propertyMap[$name] . '_uniq'][] = $propertyMap[$name];
                        }
                    }
                }
                if (isset($arguments['index'])) {
                    if (!is_array($arguments['index'])) {
                        $arguments['index'] = [$arguments['index']];
                    }

                    foreach ($arguments['index'] as $partsOf) {
                        if (is_string($partsOf)) {
                            if (!array_key_exists($partsOf, $metaData['indexes'])) {
                                $metaData['indexes'][$partsOf] = [];
                            }
                            $metaData['indexes'][$partsOf][] = $propertyMap[$name];
                        }
                    }
                }
            }
        }

        return $metaData;
    }

    public function getColumnMaps(ModelInterface $model, DiInterface $container): array
    {
        $reflection = $this->getAnnotations()->get(get_class($model));
        $columnMap = array();
        $reverseColumnMap = array();

        foreach ($reflection->getPropertiesAnnotations() as $name => $collection) {
            if ($collection->has('Column')) {
                $arguments = $collection->get('Column')->getArguments();
                if (isset($arguments['column'])) {
                    $columnName = $arguments['column'];
                } else {
                    $columnName = $name;
                }
                $columnMap[$columnName] = $name;
                $reverseColumnMap[$name] = $columnName;
            }
        }
        return array(
            MetaData::MODELS_COLUMN_MAP => $columnMap,
            MetaData::MODELS_REVERSE_COLUMN_MAP => $reverseColumnMap
        );
    }
}