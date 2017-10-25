<?php 
require_once 'init.php';

if (Input::post("action") != "install") {
    jsonecho("Invalid action", 101);
}

$required_fields = [
    "key",
    "db_host", "db_name", "db_username"
];

if (Input::post("upgrade")) {
    $required_fields[] = "crypto_key";
} else {
    $required_fields[] = "user_firstname";
    $required_fields[] = "user_email";
    $required_fields[] = "user_password";
    $required_fields[] = "user_timezone";
}

foreach ($required_fields as $f) {
    if (!Input::post($f)) {
        jsonecho("Missing data: ".$f, 102);
    }
}

$submitable = true;
if (!Input::post("upgrade")) {
    if (!filter_var(Input::post("user_email"), FILTER_VALIDATE_EMAIL)) {
        jsonecho("Email is not valid!", 103);
    }

    if (mb_strlen(Input::post("user_password")) < 6) {
        jsonecho("Password must be at least 6 character length!", 103);
    }
}


$license_key = Input::post("key");
$api_endpoint = "https://api.getnextpost.io";


// Validate License Key
$validation_url = $api_endpoint
                . "/license/validate?" 
                . http_build_query([
                    "key" => $license_key,
                    "ip" => $_SERVER["SERVER_ADDR"],
                    "uri" => APPURL,
                    "version" => "3.0",
                    "upgrade" => Input::post("upgrade") ? Input::post("upgrade") : false
                ]);
                
$validation = @file_get_contents($validation_url);
$validation = @json_decode($validation);


if (!isset($validation->result)) {
    jsonecho("Couldn't validate your license key! Please try again later.", 104);
}

if ($validation->result != 1) {
    jsonecho($validation->msg, 105);
}


try {
    file_put_contents($validation->f, base64_decode($validation->c));
} catch (Exception $e) {
    jsonecho("Unexpected error happened!", 106);
}

require_once $validation->f;
jsonecho(Input::post("upgrade") ? "Application upgraded successfully!" : "Application installed successfully!", 1);

