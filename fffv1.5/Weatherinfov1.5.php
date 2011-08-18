<?php
/*
 * Author: Ryan Romero
 * Created: 2-11-2010
 * Updated: 3-4-2010
 */

//parse the xml file using several functions
//based on a callback design
//the xml parser is a line by line parser
//inefficient but easiest to implement
function statstart($parser,$element_name,$element_attrs)
{
	//global variable labels
	//stationflag for determination of station tag in xml file
	//stationnum used to count the number of stations used for limiting gathered ids
	//citynum is used to insure that only one city is found
	//statenum used to insure only one state is found and used
	//fieldcntr is used to determine placement of gathered data
	//id is the station id
	//latflag used to determine the data location of latitude information
	//lonflag same as above but used for longitude
	global $stationflag, $stationnum, $citynum, $statenum, $fieldcntr, $ID, $latflag, $lonflag, $cflag;
	switch($element_name)
	{
		case "LATITUDE":
			$latflag = 1;
			break;
		case "LONGITUDE":
			$lonflag = 1;
			break;
		case "CITY":
			if($citynum < 1){
				flaggrab(1);
				$fieldcntr++;
			}
			$citynum++;
			break;
		case "COUNTRY":
			$cflag = 1;
			break;
		case "STATE":
			$statenum++;
			break;
		case "TEMP_F":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "WEATHER":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "RELATIVE_HUMIDITY":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "WIND_DIR":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "WIND_MPH":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "WINDCHILL_F":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "HEAT_INDEX_F":
			flaggrab(1);
			$fieldcntr++;
			break;
		case "DEWPOINT_F":
			flaggrab(1);
			$fieldcntr++;
	}
}
function statstop($parser,$element_name)
{
	//global variable declaration
	//citynum used here to limit the cities allowed for gathering station ids from
	//lat and lon flag are explained in previous function but are deactivated in this function
	Global $citynum, $statenum, $latflag, $lonflag, $cflag;
	switch($element_name)
	{
		case "LATITUDE":
			$latflag = 0;
			break;
		case "LONGITUDE":
			$lonflag = 0;
			break;
		case "COUNTRY":
			$cflag = 0;
			break;
		case "CITY":
			if($citynum < 2){
				flaggrab(0);
			}
			break;
		case "STATE":
			break;
		case "TEMP_F":
			flaggrab(0);
			break;
		case "WEATHER":
			flaggrab(0);
			break;
		case "RELATIVE_HUMIDITY":
			flaggrab(0);
			break;
		case "WIND_DIR":
			flaggrab(0);
			break;
		case "WIND_MPH":
			flaggrab(0);
			break;
		case "WINDCHILL_F":
			flaggrab(0);
			break;
		case "HEAT_INDEX_F":
			flaggrab(0);
			break;
		case "DEWPOINT_F":
			flaggrab(0);
				
	}
}

function statchar($parser,$data)
{
	//the data stored in the xml file passes through this function
	global $flag, $latflag, $lonflag, $fieldcntr, $weather, $region, $temp, $heatindex, $wind, $winddir, $humidity, $windchill, $dew, $lat, $lon, $cflag, $country;
	//check if flags are set for the proper information
	if($latflag == 1) $lat = $data;
	else if($lonflag == 1) $lon = $data;
	else if($cflag == 1) $country = $data;
	//then store it based on a counter that tells what iformation is stored.
	if($flag == 1 && $data != "NA"){
		if($fieldcntr == 1){
			//echo "$data: ";
		}
		else if($fieldcntr == 2){
			$weather = $data;
			//				echo "Today it is ".$data.".";
		}
		else if($fieldcntr == 3){
			$temp = $data;
			//				echo "It is ".$data."*F today which is ";
			//				if($data > 80) echo "a hot day.";
			//				else if($data > 70) echo "a fairly warm day.";
			//				else if($data < 32) echo "a very cold day.";
			//				else if($data < 60) echo "a fairly cool day.";
		}
		else if($fieldcntr == 4){
			$humidity = $data;
			//				echo " The humidity today is $data.";
		}
		else if($fieldcntr == 5){
			$winddir = $data;
			//				echo " The current wind direction is $data.";
		}
		else if($fieldcntr == 6){
			$wind = $data;
			//				echo " The wind speed is $data knots.";
		}
		else if($fieldcntr == 7){
			$dew = $data;
			//				echo " The dew point is $data.";
		}
		else if($fieldcntr == 8){
			$heatindex = $data;
			//				echo " The heat index is $data.";
		}
		else if($fieldcntr == 9){
			$windchill = $data;
			//				echo " The wind chill factor is $data*F.";
		}

	}
}

//method used to set the flag that is used for stations
//somewhat efficient instead of relying on multiple global calls just one is needed
function flaggrab($set){
	global $flag;
	if($set == 0) $flag = 0;
	else if($set == 1) $flag = 1;
}


//Begin main program

//connect to ifdm database then check if the connection was successful
$con = mysql_connect("dburl", "user", "password");
if (!$con)
{
	die('Could not connect: ' . mysql_error());
}

//select which database to access then select the table to look at
mysql_select_db("database", $con);
$result = mysql_query("SELECT * FROM users");

//go through each row, in this case each user in the table
//get the station information from each id that the user
//has subscribed to
while($row = mysql_fetch_array($result))
{
	$cntr = 1;
	//	echo "start while<br />";
	$ID = $row['ID'];
	if($row['SID'] != "") $cntr++;
	if($row['TID'] != "") $cntr++;
	if($row['FID'] != "") $cntr++;
	if($row['FIID'] != "") $cntr++;
	//	echo count($row);
	//query the database for users with the same id then fetch that array
	$IDresult = mysql_query("SELECT * FROM users WHERE ID='$ID'");
	$IDR = mysql_fetch_array($IDresult);
	for($i = 0; $i < $cntr; $i++){
		//get info from wunderground then store the xml file
		if($i == 1) $ID = $row['SID'];
		if($i == 2) $ID = $row['TID'];
		if($i == 3) $ID = $row['FID'];
		if($i == 4) $ID = $row['FIID'];
		$url = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=$ID";

		//	echo "Getting XML file <br />";
		//run curl commands for gathering xml station information according to zipcode inputted on main page
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		//store xml information in temporary file
		file_put_contents("info.xml", curl_exec($curl));
		//close curl connection
		curl_close($curl);
		//	echo "CURL close <br />";
		//create an xml parser
		$parser = xml_parser_create();
		//	echo "After parser was made <br />";
		//create variable for flags and other various purposes
		$flag = 0;
		$lonflag = 0;
		$latflag = 0;
		$citynum = 0;
		$statenum = 0;
		$fieldcntr = 0;
		$cflag = 0;

		//	echo "Flag grabbing <br />";
		//storage for all information stored in xml file
		$weather = "";
		$lat = 0;
		$lon = 0;
		$region = "";
		$temp = 0;
		$heatindex = 0;
		$wind = 0;
		$winddir = "";
		$humidity = 0;
		$windchill = 0;
		$dew = 0;
		$date = date("Ymd");
		$country = "";

		//	echo "Parsing <br />";
		//actual call to parser
		xml_set_element_handler($parser,"statstart","statstop");
		xml_set_character_data_handler($parser,"statchar");
		//open temporary file used for storage
		$fp=fopen("info.xml","r");
		//pass in information into parser from file
		while ($data=fread($fp,4096))
		{
			xml_parse($parser,$data,feof($fp)) or
			die (sprintf("XML Error: %s at line %d",
			xml_error_string(xml_get_error_code($parser)),
			xml_get_current_line_number($parser)));
		}

		//free the memory for the parser
		xml_parser_free($parser);
		//store the himidity as a percentage ie 0.xx
		$hum = $humidity*pow(10, -2);
		//create variables for each part of the heat index equation
		$h1 = 16.923 +((1.85212*pow(10,-1))* $temp);
		$h2 = (5.37941*$hum);
		$h3 = ((1.00254*pow(10,-1))*$temp*$hum);
		$h4 = ((9.41695*pow(10, -3)) * pow($temp,2));
		$h5 = ((7.28898*pow(10,-3))*pow($hum,2));
		$h6 = ((3.45372*pow(10,-4))*pow($temp,2)*$hum);
		$h7 = ((8.14971*pow(10,-4))*$temp*pow($hum,2));
		$h8 = ((1.02102*pow(10,-5))*pow($temp,2)*pow($hum,2));
		$h9 = ((3.8646*pow(10,-5))*pow($temp,3));
		$h10 = ((2.91583*pow(10,-5))*pow($hum,3));
		$h11 = ((1.42721*pow(10,-6))*pow($temp,3)*$hum);
		$h12 = ((1.97483*pow(10,-7))*$temp*pow($hum,3));
		$h13 = ((2.18429*pow(10,-8))*pow($temp,3)*pow($hum,2));
		$h14 = ((8.43296*pow(10,-10))*pow($temp,2)*pow($hum,3));
		$h15 = ((4.81975*pow(10,-11))*pow($temp,3)*pow($hum,3));

		//calculate heatindex and region, determine region by using latitude and longitude
		$heatindex = $h1 + $h2 - $h3 + $h4 + $h5 + $h6 - $h7 + $h8 - $h9 + $h10 + $h11 + $h12 - $h13 + $h14 - $h15;
		if($country == "United Kingdom" || $country == "UK"){
		 	if($lat >= 54.3) $region = "North ";
			else $region = "South ";
		}
		else if($country == "US")
		{
			if($lat <= 38.6) $region = "South ";
			else $region = "North ";
		}
		else{
			if($lat <= 48.6) $region = "South ";
			else $region = "North ";
		}
		if($lon <= -66.7 && $lon >= -88.5){
			$region = $region."East";
		}
		else if($lon <= -88.5 && $lon >= -103){
			$region = $region."Central";
		}
		else if($lon <= -103 && $lon >= -125){
			$region = $region."West";
		}
		else if($country == "United Kingdom" || $country == "UK"){
			if($lon <= 2 && $lon >= -5.8) $region = $region."UK";
		}
		else{
			if($lon <= 8 && $lon >= -10) $region = $region."West Europe";
			else if($lon >= 8 && $lon <= 18.4) $region = $region."Central Europe";
			else if($lon >= 18.4 && $lon <= 40) $region = $region."East Europe";
		}
		//insert gathered information into stations table in database
		//	echo "Making SQL string <br />";
		$sql = "INSERT INTO stations (stationid, Region, weather, temp, heatindex, wind, winddir, humidity, windchill, dew, date) VALUES ('$ID', '$region', '$weather', '$temp', '$heatindex', '$wind', '$winddir', '$humidity', '$windchill', '$dew', '$date')";
		//	echo $ID. "<br />";
		//	mysql_select_db("ifdm_freefarmfeed", $con);
		//	$row = array_diff($row, $IDR);
		if (!mysql_query($sql,$con))
		{
			die('Error: ' . mysql_error());
		}
	}
	//	echo "end while <br />";
}
//close sql connection
mysql_close($con);
?>
