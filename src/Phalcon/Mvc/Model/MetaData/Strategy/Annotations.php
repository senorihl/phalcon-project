<?php

namespace App\Phalcon\Mvc\Model\MetaData\Strategy;

use Phalcon\Annotations\Annotation;
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

        foreach (($collection?->getAnnotations() ?? []) as $annotation) {
            /** @var Annotation $annotation */
            switch ($annotation->getName()) {
                case 'Index': {
                    $arguments = $annotation->getArguments();
                    $name = $arguments['name'];

                    if (!is_array($arguments['columns'])) {
                        $arguments['columns'] = [$arguments['columns']];
                    }

                    if (isset($arguments['unique'])) {
                        $metaData['unique'][$name] = $arguments['columns'];
                    } else {
                        $metaData['indexes'][$name] = $arguments['columns'];
                    }
                    break;
                }
                case 'HasMany': {
                    $this->getManager()->addHasMany($model, ...$annotation->getArguments());
                    break;
                }
                case 'HasManyToMany': {
                    $this->getManager()->addHasManyToMany($model, ...$annotation->getArguments());
                    break;
                }
                case 'HasOne': {
                    $this->getManager()->addHasOne($model, ...$annotation->getArguments());
                    break;
                }
                case 'BelongsTo': {
                    $this->getManager()->addBelongsTo($model, ...$annotation->getArguments());
                    break;
                }
                case 'HasOneThrough': {
                    $this->getManager()->addHasOneThrough($model, ...$annotation->getArguments());
                    break;
                }
            }
        }

        foreach ($reflection->getPropertiesAnnotations() as $name => $collection) {
            foreach ($collection->getAnnotations() as $annotation) {
                /** @var Annotation $annotation */
                switch ($annotation->getName()) {
                    case 'BelongsTo': {
                        $arguments = $annotation->getArguments();
                        $referencedModelName = array_shift($arguments);
                        $referencedColumn = array_shift($arguments);
                        $options = array_shift($arguments) ?? [];

                        if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                            throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                        }

                        $this->getManager()->addBelongsTo($model, $name, $referencedModelName, $referencedColumn, $options);
                        break;
                    }
                    case 'HasOne': {
                        $arguments = $annotation->getArguments();
                        $referencedModelName = array_shift($arguments);
                        $referencedColumn = array_shift($arguments);
                        $options = array_shift($arguments) ?? [];

                        if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                            throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                        }

                        $this->getManager()->addHasOne($model, $name, $referencedModelName, $referencedColumn, $options);
                        break;
                    }
                    case 'HasMany': {
                        $arguments = $annotation->getArguments();
                        $referencedModelName = array_shift($arguments);
                        $referencedColumn = array_shift($arguments);
                        $options = array_shift($arguments) ?? [];

                        if (!in_array(\Phalcon\Mvc\Model::class, class_parents($referencedModelName))) {
                            throw new \Exception('Unable to reference a non-Model object ' . $referencedModelName . ' in ' . get_class($model));
                        }

                        $this->getManager()->addHasMany($model, $name, $referencedModelName, $referencedColumn, $options);
                        break;
                    }
                    case 'Column': {
                        $arguments = $annotation->getArguments();
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
                                } elseif (boolval($partsOf) === true) {
                                    if (!array_key_exists($propertyMap[$name] . '_idx', $metaData['indexes'])) {
                                        $metaData['indexes'][$propertyMap[$name] . '_idx'] = [];
                                    }
                                    $metaData['indexes'][$propertyMap[$name] . '_idx'][] = $propertyMap[$name];
                                }
                            }
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