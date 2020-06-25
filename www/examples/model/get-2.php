<?php

require_once 'waggo_example.php';

$price = new WGMModel('waggo6_example_price');
$price->get();

print_r($price->avars);
