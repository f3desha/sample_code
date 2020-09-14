<?php
require '../../../../vendor/autoload.php';

use Projects\HashCompareSystem\Engine\HashWorker;

$class = new HashWorker();
$class->stop();
