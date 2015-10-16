#!/usr/bin/env php
<?php

require __DIR__ . '/init.php';

$doc = <<<DOC

The options are as follows:
   [-a, --api <path> ]             Path to api.xml, default: ./api.xml
   [-f, --function <name>]         Name function from api.xml
   [-h, --help ]                   This help
   [-v, --version ]                Version URFAClient


DOC;

$options = getopt("a:f:v", array('api:', 'function:', 'version'));

$api_xml = __DIR__ . '/api.xml';
if (isset($options['a']))
{
    $api_xml = $options['a'];
    unset($options['a']);
}
if (isset($options['api']))
{
    $api_xml = $options['api'];
    unset($options['api']);
}

if ( ! $options) die($doc);

if (isset($options['v']) OR isset($options['version'])) die('URFAClient ' . URFAClient::VERSION . "\n");

$function = (isset($options['f'])) ? $options['f'] : NULL;
$function = (isset($options['function'])) ? $options['function'] : $function;

if ($function)
{
    try
    {
        $api = new URFAClient_Cmd($api_xml);
        var_export($api->options($function));
        die("\n");
    }
    catch (Exception $e)
    {
        die('Error: ' . $e->getMessage() . "\n");
    }
}
