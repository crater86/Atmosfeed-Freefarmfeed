<?php
include 'key.php';
include 'EpiCurl.php';
include 'EpiOAuth.php';
include 'EpiTwitter.php';
include 'skey.php';
include 'ffskey.php';

$debug = false;
$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
$twitterObj2 = new EpiTwitter($consumer_key, $consumer_secret);
$twitterObj->setToken($atmotok, $atmosecret);
$twitterObj2->setToken($ff_key, $ff_secret);
$twitterInfo = $twitterObj->get_accountVerify_credentials();
$twitterInfo2 = $twitterObj2->get_accountVerify_credentials();
$twitterInfo->response;
$twitterInfo2->response;
if($debug) echo "Got twitter info";
//connect to database check if succeessful
$con = mysql_connect("dburl", "user", "password");
if (!$con)
{
	die('Could not connect: ' . mysql_error());
}
if($debug) echo " Connected to database.";
//select the database to pull data from
mysql_select_db("database", $con);
//get current date to insure current data is posted
$Date = date("Ymd");
//query the database(stations table) for current information
$init = mysql_query("SELECT * FROM stations WHERE date='$Date'");
if($debug) echo " Query Completed.";
while($strow = mysql_fetch_array($init))
{
	if($debug) echo " Entered While loop.";
	$temp = $strow['temp'];
	$wind = $strow['wind'];
	$sid = $strow['stationid'];
	if ($sid == "") $stcode = "UNK:";
	else $stcode = $sid.":";
	$tcode = 1;
	$wcode = 0;
	if($temp >= 32 && $temp <= 90) $tcode = 2;
	else if($temp > 90) $tcode = 3;
	if($wind > 0) $wcode = 1; 
	//query the database(quotes table) for specified quotes
	$result = mysql_query("SELECT * FROM quotes WHERE tcode='".$tcode."'");
	$result2 = mysql_query("SELECT * FROM quotes WHERE wcode='".$wcode."'");
	//variable for status storage
	$yourStatus = "";
	//set variable for counting number of rows
	$numq = 0;
	$numw = 0;
	//go thru each row in table and count how many there are
	while($row = mysql_fetch_array($result))
	{
		$numq = $numq + 1;
	}
	while($row2 = mysql_fetch_array($result2))
	{
		$numw = $numw + 1;
	}

	if($debug) echo " " . $numq;
	//set the status information by use of data stored in table for now it is null
	$yourStatus = "";
	$yourStatus2 = "";
	//generate random number to select quote
	$rnum = rand(1, $numq);
	$rnum2 = rand(1, $numw);
	$qnum = 0;
	$wnum = 0;
	$result = mysql_query("SELECT * FROM quotes WHERE tcode='".$tcode."'");
	$result2 = mysql_query("SELECT * FROM quotes WHERE wcode='".$wcode."'");
	while($row = mysql_fetch_array($result))
	{	$qnum = $qnum + 1;
	if($qnum == $rnum) $yourStatus = $stcode . " " .$row['temp'];
	}
	while($row2 = mysql_fetch_array($result2))
	{	$wnum = $wnum + 1;
	if($wnum == $rnum2) $yourStatus2 = $stcode . " " . $row2['wind'];
	}
	if($debug) echo $yourStatus;
	if($debug) echo " The string length is ".strlen($yourStatus).".";
	$length = strlen($yourStatus);
	$length2 = strlen($yourStatus2);
//	$choose = $rand(1, 2);
	if($length > 5){
		if($debug) echo " Posting status temperature.";
		$status = $twitterObj->post_statusesUpdate(array('status' => $yourStatus));
		$status2 = $twitterObj2->post_statusesUpdate(array('status' => $yourStatus));
	}
//	if($length2 > 5){
//		if($debug) echo " Posting status wind.";
//		$status = $twitterObj->post_statusesUpdate(array('status' => $yourStatus2));
//		$status2 = $twitterObj2->post_statusesUpdate(array('status' => $yourStatus2));
//	}
	$userresult = mysql_query("SELECT * FROM users WHERE ID='$sid' OR SID='$sid' OR TID='$sid' OR FID='$sid' OR FIID='$sid' AND DM='1'");
	while($urow = mysql_fetch_array($userresult))
	{
		$user = $urow['User'];
		$userObj = new EpiTwitter($consumer_key, $consumer_secret);
		$userObj->setToken($urow['token'], $urow['secret']);
		$userInfo = $userObj->get_accountVerify_credentials();
		$id = $userInfo->id;
		if($length > 5) $message = $twitterObj->post_direct_messagesNew(array('screen_name' => $user, 'user_id' => $id, "text" => $yourStatus));
		if($length2 > 5) $message2 = $twitterObj->post_direct_messagesNew(array('screen_name' => $user, 'user_id' => $id, "text" => $yourStatus2));
	}
	$status->response;
	$status2->response;
	if($debug) echo " Your status has been updated.";
}
mysql_close($con);
?>