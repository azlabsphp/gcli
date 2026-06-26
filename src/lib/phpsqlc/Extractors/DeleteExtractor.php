<?php

namespace Drewlabs\PHPSQLC\Extractors;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 * delete
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class DeleteExtractor extends AbstractExtractor implements Extractor
{
    /** @var \Drewlabs\PHPSQLC\Options */
    // @phpstan-ignore property.onlyWritten
    private $options;

    public function __construct(?\Drewlabs\PHPSQLC\Options $options = null)
    {
        $this->options = $options;
    }

    public function extract(array $value, array $parsed = []): array
    {
        return [];
    }
}

