<?php

namespace App\Helper\Migration;

use Exception;
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Di\Injectable;

class Difference extends Injectable
{

    public function detailPostgresTable(string $modelName, \Phalcon\Mvc\ModelInterface $model)
    {
        $foreignKeys = array();
        $tableDefinition = array();

        $table = $model->getSource();
        $oldColumn = null;
        $fieldTypes = $this->modelsMetadata->getDataTypes($model);
        $notNullFields = $this->modelsMetadata->getNotNullAttributes($model);
        $identityField = $this->modelsMetadata->getIdentityField($model);
        $primaryKeys = $this->modelsMetadata->getPrimaryKeyAttributes($model);
        $indexes = $this->modelsMetadata->readMetaData($model)['indexes'] ?? [];
        $uniques = $this->modelsMetadata->readMetaData($model)['unique'] ?? [];
        $defaultSQL = $this->modelsMetadata->readMetaData($model)['sql_default'] ?? [];
        dump($defaultSQL);
        $sizesFields = $this->modelsMetadata->readMetaData($model)['sizes'] ?? [];
        $referencesFields = $this->modelsManager->getRelations($modelName);

        if ($referencesFields) {
            foreach ($referencesFields as $referenceField) {
                if ($referenceField->getType() === \Phalcon\Mvc\Model\Relation::HAS_MANY) {
                    continue;
                }

                $fields = $referenceField->getFields();
                if (is_string($fields)) {
                    $fields = array($fields);
                }

                $c = $this->modelsMetadata->readColumnMap($model);
                $relation = array('table' => $table);
                foreach ($fields as $f) {
                    $relation['fields'][] = $c[1][$f];
                }

                $fields = $referenceField->getReferencedFields();
                if (is_string($fields)) {
                    $fields = array($fields);
                }
                $m = $this->modelsManager->load(trim($referenceField->getReferencedModel(), '\\'));
                $relation['referencedTable'] = $m->getSource();
                $c = $this->modelsMetadata->readColumnMap($m);
                foreach ($fields as $f) {
                    $relation['referencedFields'][] = $c[1][$f];
                }

                $relation['action'] = $referenceField->getOption('action') ? $referenceField->getOption('action') : 'RESTRICT';

                $defaultName = strtolower($relation['table'] . '_' . $relation['referencedTable']) . '_fkey';
                $relation['name'] = $referenceField->getOption('name') ? $referenceField->getOption('name') : $defaultName;
                $foreignKeys[] = $relation;
            }
        }

        foreach ($fieldTypes as $fieldName => $type) {
            $fieldDefinition = array();
            $fieldDefinition['type'] = $type;

            if (in_array($fieldName, $notNullFields)) {
                $fieldDefinition['notNull'] = true;
            }

            if (in_array($fieldName, $primaryKeys)) {
                $fieldDefinition['primary'] = true;
            }

            if (isset($defaultSQL[$fieldName])) {
                $fieldDefinition['default_sql'] = $defaultSQL[$fieldName];
            }

            if (isset($sizesFields[$fieldName])) {
                $fieldDefinition['size'] = $sizesFields[$fieldName];
            }

            if ($identityField === $fieldName) {
                $fieldDefinition['autoIncrement'] = true;
            }

            if ($oldColumn !== null) {
                $fieldDefinition['after'] = $oldColumn;
            } else {
                $fieldDefinition['first'] = true;
            }

            $oldColumn = $fieldName;
            $tableDefinition[] = new Column($fieldName, $fieldDefinition);
        }
        $primaryKeyName = $table . '_pkey';
        $indexesDefinition = [
            new Index($primaryKeyName, $primaryKeys)
        ];

        foreach ($indexes as $name => $columns) {
            $indexesDefinition[] = new Index($name, $columns);
        }

        foreach ($uniques as $name => $columns) {
            $indexesDefinition[] = new Index($name, $columns, 'UNIQUE');
        }

        return array(
            'table' => $table,
            'tableDefinition' => $tableDefinition,
            'indexesDefinition' => $indexesDefinition,
            'foreignKeys' => $foreignKeys,
            'dbAdapter' => $model->getWriteConnectionService()
        );

    }

    public function detailMysqlTable(string $modelName, \Phalcon\Mvc\ModelInterface $model)
    {
        //dd($model->getSource());
    }

    public function morphTable(?array $tableDetails, \Phalcon\Mvc\ModelInterface $model)
    {
        $tableName = $tableDetails['table'];
        $definition = ["columns" => $tableDetails['tableDefinition'], "indexes" => $tableDetails['indexesDefinition']];
        $foreignKeys = $tableDetails['foreignKeys'];
        $dbAdapter = $tableDetails['dbAdapter'];
        $dialectType = $model->getReadConnection()->getDialectType();
        $ignoreDropForeignKeys = array();
        $connection = $model->getReadConnection();
        $dialect = $connection->getDialect();

        if ($dialectType === 'postgresql') {
            $schema = $connection->getDescriptor()['schema'] ?? 'public';
        } elseif ($dialectType === 'mysql') {
            $schema = $connection->getDescriptor()['dbname'];
        }

        $sql = array();
        $tableExists = $connection->tableExists($tableName, $schema);

        if (isset($definition['columns'])) {
            if (count($definition['columns']) == 0) {
                throw new Exception('Table must have at least one column');
            }
            $fields = array();

            foreach ($definition['columns'] as $tableColumn) {
                if (!is_object($tableColumn)) {
                    throw new Exception('Table must have at least one column');
                }
                $fields[$tableColumn->getName()] = $tableColumn;
            }

            if ($tableExists) {
                $localFields = array();
                $description = $connection->describeColumns($tableName, $schema);
                foreach ($description as $field) {
                    $localFields[$field->getName()] = $field;
                }

                foreach ($fields as $fieldName => $tableColumn) {
                    if (!isset($localFields[$fieldName])) {
                        /**
                         * ADD COLUMN
                         */
                        $rawSql = $connection->getDialect()->addColumn($tableName, $schema, $tableColumn);
                        $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                    } else {

                        /**
                         * ALTER TABLE
                         */
                        $changed = false;

                        if ($localFields[$fieldName]->getType() != $tableColumn->getType()) {
                            if (
                                $dialectType === 'mysql' || $localFields[$fieldName]->getType() !== \Phalcon\Db\Column::TYPE_INTEGER
                                || $localFields[$fieldName]->getSize() !== 1 || $tableColumn->getType() !== \Phalcon\Db\Column::TYPE_BOOLEAN
                            ) {
                                $changed = true;
                            }
                        }

                        if ($tableColumn->isNotNull() != $localFields[$fieldName]->isNotNull()) {
                            $changed = true;
                        }

                        if ($tableColumn->getSize() && $tableColumn->getSize() != $localFields[$fieldName]->getSize()) {
                            $changed = true;
                        }

                        if ($changed == true) {
                            $existingForeignKeys = [];

                            // We check if there is a foreign key constraint
                            if ($dialectType === 'mysql') {
                                $results = $connection->query("SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . $schema . "' AND REFERENCED_TABLE_NAME = '" . $tableName . "' AND REFERENCED_COLUMN_NAME = '" . $tableColumn->getName() . "'");
                                foreach ($results->fetchAll() as $r) {
                                    $rules = $connection->query('SELECT UPDATE_RULE, DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME="' . $r['CONSTRAINT_NAME'] . '" AND CONSTRAINT_SCHEMA ="' . $r['TABLE_SCHEMA'] . '"');
                                    $rules = $rules->fetch();
                                    $r['UPDATE_RULE'] = $rules['UPDATE_RULE'];
                                    $r['DELETE_RULE'] = $rules['DELETE_RULE'];

                                    /**
                                     * DROP FOREIGN KEY BECAUSE WE CHANGE THE CURRENT COLUMN
                                     */
                                    $rawSql = $connection->getDialect()->dropForeignKey($r['TABLE_NAME'], $r['TABLE_SCHEMA'], $r['CONSTRAINT_NAME']);
                                    $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                                    $existingForeignKeys[] = $r;
                                }
                            } elseif ($dialectType === 'postgresql') {
                                $sqlconstraint = $this->getPGSQLConstraint($tableName, $tableColumn->getName());
                                $results = $connection->query($sqlconstraint);
                                foreach ($results->fetchAll() as $r) {
                                    $r['UPDATE_RULE'] = $r['on_update'];
                                    $r['DELETE_RULE'] = $r['on_delete'];
                                    $r['TABLE_NAME'] = $r['table_name'];
                                    $r['TABLE_SCHEMA'] = $r['constraint_schema'];
                                    $r['CONSTRAINT_NAME'] = $r['constraint_name'];
                                    $r['REFERENCED_TABLE_SCHEMA'] = $r['constraint_schema'];
                                    $r['REFERENCED_TABLE_NAME'] = $r['references_table'];
                                    $r['REFERENCED_COLUMN_NAME'] = $r['references_field'];
                                    $r['COLUMN_NAME'] = $r['column_name'];

                                    /**
                                     * DROP FOREIGN KEY BECAUSE WE CHANGE THE CURRENT COLUMN
                                     */
                                    $rawSql = $connection->getDialect()->dropForeignKey($r['TABLE_NAME'], $r['TABLE_SCHEMA'], $r['CONSTRAINT_NAME']);
                                    $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                                    $existingForeignKeys[] = $r;
                                }
                            }

                            /**
                             * ALTER TABLE
                             */
                            if ($dialectType === 'postgresql') {
                                $rawSql = $connection->getDialect()->modifyColumn($tableName, $schema, $tableColumn, $localFields[$fieldName]);
                            } else {
                                $rawSql = $connection->getDialect()->modifyColumn($tableName, $schema, $tableColumn);
                            }
                            $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';

                            if ($existingForeignKeys) {
                                foreach ($existingForeignKeys as $r) {
                                    /**
                                     * ADD FOREIGN KEY AFTER DROP ONE (TO CHANGE IT)
                                     */
                                    $rawSql = $connection->getDialect()->addForeignKey(
                                        $r['TABLE_NAME'],
                                        $r['TABLE_SCHEMA'],
                                        new Reference(
                                            $r['CONSTRAINT_NAME'],
                                            array(
                                                "referencedSchema" => $r['REFERENCED_TABLE_SCHEMA'],
                                                "referencedTable" => $r['REFERENCED_TABLE_NAME'],
                                                "columns" => array($r['COLUMN_NAME']),
                                                "referencedColumns" => array($r['REFERENCED_COLUMN_NAME']),
                                                'onUpdate' => $r['UPDATE_RULE'],
                                                'onDelete' => $r['DELETE_RULE'],
                                            )
                                        )
                                    );
                                    $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                                }
                            }
                        }
                    }
                }

                foreach ($localFields as $fieldName => $localField) {
                    if (!isset($fields[$fieldName])) {
                        if ($dialectType === 'mysql') {
                            // We check if there is a foreign key constraint
                            $results = $connection->query("SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . $schema . "' AND TABLE_NAME = '" . $tableName . "' AND COLUMN_NAME = '" . $fieldName . "'");
                            foreach ($results->fetchAll() as $r) {
                                $ignoreDropForeignKeys[] = $r['CONSTRAINT_NAME'];
                                $rawSql = $connection->getDialect()->dropForeignKey($r['TABLE_NAME'], $r['TABLE_SCHEMA'], $r['CONSTRAINT_NAME']);
                                $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                            }
                        } elseif ($dialectType === 'postgresql') {
                            $sqlconstraint = $this->getPGSQLConstraint($tableName, $fieldName);
                            $results = $connection->query($sqlconstraint);
                            foreach ($results->fetchAll() as $r) {
                                $ignoreDropForeignKeys[] = $r['CONSTRAINT_NAME'];
                                $rawSql = $connection->getDialect()->dropForeignKey($r['table_name'], $r['constraint_schema'], $r['constraint_name']);
                                $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                            }
                        }
                        $rawSql = $connection->getDialect()->dropColumn($tableName, $schema, $fieldName);
                        $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                    }
                }
            } else {
                /**
                 * CREATE TABLE IF NOT EXISTS
                 */
                $rawSql = $connection->getDialect()->createTable($tableName, $schema, $definition);
                if ($dialectType === 'postgresql') {
                    $sqlInstructions = explode(';', $rawSql);
                    foreach ($sqlInstructions as $instruction) {
                        if ($instruction !== "" && strpos($instruction, '_pkey" ON') === false) {
                            $sql[] = '$this->' . $dbAdapter . '->query(\'' . $instruction . '\');';
                        }
                    }
                } else {
                    $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                }
            }
        }

        /**
         * DROP FOREIGN KEY
         */
        if ($tableExists === true && ($dialectType === 'mysql' || $dialectType === 'postgresql')) {
            $actualReferences = $connection->describeReferences($tableName, $schema);
            /* @var $actualReference \Phalcon\Db\Reference */
            foreach ($actualReferences as $actualReference) {
                $foreignKeyExists = false;

                for ($i = count($foreignKeys) - 1; $i >= 0; --$i) {
                    if ($dialectType === 'mysql') {
                        $rules = $connection->query('SELECT UPDATE_RULE, DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME="' . $actualReference->getName() . '" AND CONSTRAINT_SCHEMA ="' . $actualReference->getReferencedSchema() . '"');
                        $rules = $rules->fetch();

                        if (
                            $tableName === $foreignKeys[$i]['table']
                            && $actualReference->getReferencedTable() === $foreignKeys[$i]['referencedTable']
                            && count(array_diff($actualReference->getColumns(), $foreignKeys[$i]['fields'])) === 0
                            && count(array_diff($actualReference->getReferencedColumns(), $foreignKeys[$i]['referencedFields'])) === 0
                            // TODO : réactiver cette ligne si Phalcon prend en compte la méthode : && $actualReference->getOnUpdate() === $foreignKeys[$i]['action']
                            && $rules['UPDATE_RULE'] === $foreignKeys[$i]['action']
                            // TODO : réactiver cette ligne si Phalcon prend en compte la méthode : && $actualReference->getOnDelete() === $foreignKeys[$i]['action']) {
                            && $rules['DELETE_RULE'] === $foreignKeys[$i]['action']
                        ) {
                            $foreignKeyExists = true;
                            array_splice($foreignKeys, $i, 1);
                            break;
                        }
                    } else {
                        if (
                            $tableName === $foreignKeys[$i]['table']
                            && $actualReference->getReferencedTable() === $foreignKeys[$i]['referencedTable']
                            && count(array_diff($actualReference->getColumns(), $foreignKeys[$i]['fields'])) === 0
                            && count(array_diff($actualReference->getReferencedColumns(), $foreignKeys[$i]['referencedFields'])) === 0
                        ) {
                            $foreignKeyExists = true;
                            array_splice($foreignKeys, $i, 1);
                            break;
                        }
                    }
                }

                if (!$foreignKeyExists && !in_array($actualReference->getName(), $ignoreDropForeignKeys)) {
                    $rawSql = $connection->getDialect()->dropForeignKey(
                        $tableName,
                        $actualReference->getReferencedSchema(),
                        $actualReference->getName()
                    );
                    $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                }
            }
        }

        /**
         * ADD FOREIGN KEY
         */
        if ($foreignKeys) {
            foreach ($foreignKeys as $foreignKey) {
                $rawSql = $connection->getDialect()->addForeignKey(
                    $tableName,
                    $schema,
                    new Reference(
                        $foreignKey['name'],
                        array(
                            "referencedSchema" => $schema,
                            "referencedTable" => $foreignKey['referencedTable'],
                            "columns" => $foreignKey['fields'],
                            "referencedColumns" => $foreignKey['referencedFields'],
                            'onUpdate' => $foreignKey['action'],
                            'onDelete' => $foreignKey['action']
                        )
                    )
                );
                $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
            }
        }

        /**
         * INDEXES
         */
        if (isset($definition['indexes'])) {
            if ($tableExists) {
                $indexes = array();
                foreach ($definition['indexes'] as $tableIndex) {
                    $indexes[$tableIndex->getName()] = $tableIndex;
                }

                if ($dbAdapter === 'dbPostgresql') {
                    $rawSql = $connection->getDialect()->modifyColumn($tableName, $schema, $tableColumn, $localFields[$fieldName]);
                }

                $localIndexes = array();
                $actualIndexes = $connection->describeIndexes($tableName, $schema);
                foreach ($actualIndexes as $actualIndex) {
                    $deleted = true;

                    foreach ($definition['indexes'] as $tableIndex) {
                        // hack for encoging problem
                        $tableIndexName = $tableIndex->getName();
                        $actualIndexName = $actualIndex->getName();
                        if ($tableIndexName === $actualIndexName) {
                            $deleted = false;
                            $localIndexes[$actualIndex->getName()] = $actualIndex->getColumns();
                            break;
                        } elseif (substr((string)$actualIndex->getName(), 0, 3) !== 'IDX' && ($dbAdapter !== 'dbCassandra') && ($dbAdapter !== 'dbPostgresql')) {
                            $deleted = false;
                            break;
                        }
                    }


                    if ($deleted) {
                        $rawSql = $connection->getDialect()->dropIndex($tableName, $schema, $actualIndexName);
                        $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                    }
                }

                foreach ($definition['indexes'] as $tableIndex) {
                    $tableIndexName = $tableIndex->getName();
                    if (!isset($localIndexes[$tableIndexName])) {
                        if ($tableIndexName == 'PRIMARY') {
                            $rawSql = $connection->getDialect()->addPrimaryKey($tableName, $schema, $tableIndex);
                            $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                        } else {
                            $rawSql = $connection->getDialect()->addIndex($tableName, $schema, $tableIndex);
                            $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                        }
                    } else {
                        $changed = false;
                        if (count($tableIndex->getColumns()) != count($localIndexes[$tableIndexName])) {
                            $changed = true;
                        } else {
                            foreach ($tableIndex->getColumns() as $columnName) {
                                if (!in_array($columnName, $localIndexes[$tableIndexName])) {
                                    $changed = true;
                                    break;
                                }
                            }
                        }
                        if ($changed) {
                            if ($tableIndex->getName() == 'PRIMARY') {
                                $rawSql = $connection->getDialect()->dropPrimaryKey($tableName, $schema);
                                $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';

                                $rawSql = $connection->getDialect()->addPrimaryKey($tableName, $schema, $tableIndex);
                                $sql[] = '$this->' . $dbAdapter . '->query(\'' . $rawSql . '\');';
                            }
                        }
                    }
                }
            }
        }

        return $sql;
    }

    private function getPGSQLConstraint(string $tableName, string $fieldName)
    {
        $sqlconstraint = <<<EOT
SELECT 
tc.constraint_name, 
tc.constraint_schema, 
tc.table_name, 
kcu.column_name, 
rc.update_rule AS on_update, 
rc.delete_rule AS on_delete,
ccu.table_name AS references_table,
ccu.column_name AS references_field
FROM information_schema.table_constraints tc
LEFT JOIN information_schema.key_column_usage kcu
  ON tc.constraint_catalog = kcu.constraint_catalog
  AND tc.constraint_schema = kcu.constraint_schema
  AND tc.constraint_name = kcu.constraint_name
LEFT JOIN information_schema.referential_constraints rc
  ON tc.constraint_catalog = rc.constraint_catalog
  AND tc.constraint_schema = rc.constraint_schema
  AND tc.constraint_name = rc.constraint_name
LEFT JOIN information_schema.constraint_column_usage ccu
  ON rc.unique_constraint_catalog = ccu.constraint_catalog
  AND rc.unique_constraint_schema = ccu.constraint_schema
  AND rc.unique_constraint_name = ccu.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name='$tableName' AND kcu.column_name='$fieldName'
EOT;
        return $sqlconstraint;
    }
}