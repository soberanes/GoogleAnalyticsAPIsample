<?php
session_start();
require_once 'google-api-php-client/autoload.php';

$client = new Google_Client();
$client->setApplicationName('Analytics API app');

// Visit https://console.developers.google.com/ to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId('669691641866-9ehgpi6kci8tqtqp9b2l7lpq3v29ejsd.apps.googleusercontent.com');
$client->setClientSecret('D1KzuVWDyJo3Hq9rBKwX2Ljd');
$client->setRedirectUri('http://localhost/Analytics/index.php');
$client->setDeveloperKey('AIzaSyC4HE0kk4pdtvfhRdPlBsLQ2YomDkpV-RA');
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
//$client->setUseObjects(true);

if (isset($_GET['code'])) {
	$client->authenticate($_GET['code']);
	$_SESSION['token'] = $client->getAccessToken();
	$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  	$client->setAccessToken($_SESSION['token']);
}

function getResults($analytics, $profileId) {

    return $analytics->data_ga->get(
	    'ga:' . $profileId,
	    '2010-09-16', //Sep 16, 2014
		'2014-10-16', //Oct 16, 2014
		'ga:users'
	);
}

if (!$client->getAccessToken()) {
  	$authUrl = $client->createAuthUrl();
  	print "<a class='login' href='$authUrl'>Connect Me!</a>";

} else {
	// Create analytics service object. See next step below.

	$analytics = new Google_Service_Analytics($client);
	runMainDemo($analytics,'52077567');
}

/** functions **/
function runMainDemo(&$analytics,$accountID) {
	try {
		// Step 2. Get the user's first view (profile) ID.
		$profileId = getFirstProfileId($analytics,$accountID);
		if (isset($profileId)) {

			// Step 3. Query the Core Reporting API.
			$results = getResults($analytics, $profileId);

			// Step 4. Output the results.
			printResults($results);
		}
	} catch (apiServiceException $e) {
		// Error from the API.
		print 'There was an API error : ' . $e->getCode() . ' : ' . $e->getMessage();
	} catch (Exception $e) {
		print 'There wan a general error : ' . $e->getMessage();
	}

}

function getFirstprofileId(&$analytics, $accountID) {
	$accounts = $analytics->management_accounts->listManagementAccounts();

	if (count($accounts->getItems()) > 0) {
		$items = $accounts->getItems();
		$firstAccountId = $items[2]->getId();
		//echo "<pre>";var_dump($firstAccountId); //38208530 velas
		
		$webproperties = $analytics->management_webproperties
		    					   ->listManagementWebproperties($accountID);

		if (count($webproperties->getItems()) > 0) {
			$items = $webproperties->getItems();
			$firstWebpropertyId = $items[0]->getId();

			$profiles = $analytics->management_profiles
			  					  ->listManagementProfiles($accountID, $firstWebpropertyId);

			if (count($profiles->getItems()) > 0) {
				$items = $profiles->getItems();
				return $items[0]->getId();

			} else {
				throw new Exception('No views (profiles) found for this user.');
			}
		} else {
			throw new Exception('No webproperties found for this user.');
		}
	} else {
		throw new Exception('No accounts found for this user.');
	}
}

function printResults(&$results) {
	
  //echo "<pre>";var_dump($results);
  if (count($results->getRows()) > 0) {
    $profileName = $results->getProfileInfo()->getProfileName();
    $rows = $results->getRows();
    $sessions = $rows[0][0];

    print "<p>First view (profile) found: ".$profileName."</p>";
    print "<p>Total users: ".$sessions."</p>";

  } else {
    print '<p>No results found.</p>';
  }
}