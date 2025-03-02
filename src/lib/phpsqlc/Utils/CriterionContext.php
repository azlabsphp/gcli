<?php

namespace Drewlabs\PHPSQLC\Utils;

interface CriterionContext
{
    public const HAVING = 'having';
    public const WHERE = 'where';
    public const JOIN = 'join';
}