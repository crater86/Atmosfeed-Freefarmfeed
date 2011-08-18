<?php
/*
 * Auhor: Ryan Romero
 * Updated: 2-11-2010
 */
include 'key.php';
include 'EpiCurl.php';
include 'EpiOAuth.php';
include 'EpiTwitter.php';
include 'skey.php';
include 'ffskey.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
$twitterObj2 = new EpiTwitter($consumer_key, $consumer_secret);
$twitterObj->setToken($atmotok, $atmosecret);
$twitterObj2->setToken($ff_key, $ff_secret);
$twitterInfo = $twitterObj->get_accountVerify_credentials();
$twitterInfo2 = $twitterObj2->get_accountVerify_credentials();
$twitterInfo->response;
$twitterInfo2->response;
//echo $twitterInfo->screen_name;

//connect to database check if succeessful
$con = mysql_connect("dburl", "user", "pass");
if (!$con)
{
	die('Could not connect: ' . mysql_error());
}
//select the database to pull data from
mysql_select_db("database", $con);
//get current date to insure current data is posted
$Date = date("Ymd");
//query the database(stations table) for current information
$result = mysql_query("SELECT * FROM stations WHERE date='$Date'");
//variable for status storage
$yourStatus = "";
//go thru each row in table
while($row = mysql_fetch_array($result))
{	//set the status information by use of data stored in table
	$yourStatus = $row['stationid'].": It is ".$row['weather'].". The temperature is ".$row['temp']
	."*F."." The wind speed is ".$row['wind']." knots and wind is blowing"
	.$row['winddir'].". Windchill is ".$row['windchill'].".";
	$sid = $row['stationid'];
	$result2 = mysql_query("SELECT * FROM users WHERE ID='$sid' OR SID='$sid' OR TID='$sid' OR FID='$sid' OR FIID='$sid' AND DM='1'");
	while($row2 = mysql_fetch_array($result2))
	{
		$user = $row2['User'];
		$userObj = new EpiTwitter($consumer_key, $consumer_secret);
		$userObj->setToken($row2['token'], $row2['secret']);
		$userInfo = $userObj->get_accountVerify_credentials();
		$id = $userInfo->id;
		$message = $twitterObj->post_direct_messagesNew(array('screen_name' => $user, 'user_id' => $id, "text" => $yourStatus));
	}
$status = $twitterObj->post_statusesUpdate(array('status' => $yourStatus));
$status2 = $twitterObj2->post_statusesUpdate(array('status' => $yourStatus));
$status->response;
$status2->response;
echo " Your status has been updated";

}
mysql_close($con);
?>
