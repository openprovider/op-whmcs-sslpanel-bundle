<?php

include __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

define("CLIENTAREA", true);

require("init.php");

$ca = new WHMCS_ClientArea();

$ca->setPageTitle("Your Page Title Goes Here");

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('generateToken.php', 'Your Custom Page Name');

$ca->initPage();

$ca->requireLogin();

if ($ca->isLoggedIn()) {
    $clientName = Capsule::table('tblclients')->where('id', '=', $ca->getUserID())->pluck('firstname');
}

$ca->output();
