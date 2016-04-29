<?php
require_once("../../../KPhpLib/KNewsInfo.php");

$KNewsInfoConfig = array(
	"file" => "./test1.json",
	"maxEntry" => 3,
);

runSender($KNewsInfoConfig);
