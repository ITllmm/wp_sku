<?php

    function sku_config_helper($configname)
    {
        $result = include(dirname(__DIR__).'/config.php');
        return isset($result[$configname]) ? $result[$configname] : '';
    }

