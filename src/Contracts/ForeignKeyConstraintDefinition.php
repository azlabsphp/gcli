<?php

namespace Drewlabs\ComponentGenerators\Contracts;

interface ForeignKeyConstraintDefinition
{

    /**
     * Local table name
     * 
     * @return string 
     */
    public function getLocalTableName();

    /**
     * Local columns names
     * 
     * @return string|string[] 
     */
    public function localColumns();

    /**
     * Foreign table name
     * 
     * @return string 
     */
    public function getForeignTableName();

    /**
     * Foreign column names
     * 
     * @return string[] 
     */
    public function getForeignColumns();

}