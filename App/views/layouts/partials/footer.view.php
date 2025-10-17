<?php

use App\Core\Config;
// Load config PHP files
Config::load(dirname(__DIR__) . '/config');
?>

<footer>
    &copy; <?= date('Y') ?> Basic Schools Studnet Records Management System (BaStuRMS)
    <div>All rights reserved.</div>
    <h1><?=strtoupper(Config::get('school'))?></h1>
</footer>

