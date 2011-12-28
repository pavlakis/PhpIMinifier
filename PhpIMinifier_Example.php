<?php

/***
 *
 ************************
 * Example configuration*
 ************************  
 * 
 * PhpIMinify
 * 
 * To run:
 * 
 * php PhpIMinifier_Example.php  > logMinifierOutput.txt
 * 
 */

include 'PhpIMinifier.php';

$yuiLocation    = '/Users/antonis/Sites/yui/build/yuicompressor-2.4.8pre.jar';
$public         = '/Users/antonis/Sites/branches/development/public/';
$version        = '1112v4';


$css    = $public . 'css/';
$js     = $public . 'js/custom/';


echo "\n";
echo 'Starting minification of the CSS files';
echo "\n";


$cssMinify = new PhpIMinifier($css, $version, 'css');
$cssMinify->setYuiCompressorExec($yuiLocation);
$cssMinify->setOutputActivity(true);
$cssMinify->run();

// get all output and errors as a string
echo $cssMinify;

echo "\n";
echo 'Starting minification of the JS files';
echo "\n";

$jsMinify = new PhpIMinifier($js, $version, 'js');
$jsMinify->setYuiCompressorExec($yuiLocation);
$jsMinify->setOutputActivity(true);
$jsMinify->run();

echo $jsMinify;