<?php

/**
 * An intermediate class for core processors.
 *
 * @package UpgradeToOmekaS
 */
abstract class UpgradeToOmekaS_Processor_AbstractCore extends UpgradeToOmekaS_Processor_Abstract
{

    public $minVersion = '2.3.1';
    public $maxVersion = '2.5.9';
    protected $_isCore = true;

    public $module = array(
        'type' => 'integrated',
    );
}
