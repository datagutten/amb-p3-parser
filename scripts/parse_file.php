#!/usr/bin/php
<?php

use datagutten\amb\parser\exceptions\AmbParseError;
use datagutten\amb\parser\parser;

require $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';
$data = file_get_contents($argv[1]);
$records = parser::get_records($data);
foreach ($records as $record)
{
    try
    {
        print_r(parser::parse($record));
    }
    catch (AmbParseError $e)
    {
        echo $e->getMessage() . "\n";
    }
}

