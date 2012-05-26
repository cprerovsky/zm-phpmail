#!/usr/bin/php
<?php
mysql_connect("localhost", "root") or exit(mysql_error());
mysql_select_db("zm") or exit(mysql_error());
$img_path = "/usr/share/zoneminder/events/Monitor-1/";

// open alert handler
$last_event = file_get_contents("zm_event");
if (!$last_event) {
	$last_event = 0;
}
$res = mysql_query("SELECT Id, UNIX_TIMESTAMP(StartTime) ts, StartTime FROM Events WHERE Id > " . $last_event);

while ($row = mysql_fetch_assoc($res)) {
	echo "new event " . $row["Id"] . "\n";

	// save last event id
	`echo ${row["Id"]} > zm_event`;

	$path = $img_path . date("y/m/d/H/i/s/", $row["ts"]);
	$filename = "005-capture.jpg";

	mail_attachment($path, $filename, 'c.prerovsky@gmail.com', 'zone.surveillance@gmail.com', 
		'ZoneMinder', 'ZoneMinder Alert', 'Alert @' . $row['StartTime']);
}

function mail_attachment($path, $filename, $mailto, $from_mail, $from_name, $subject, $message) {
    $file = $path . $filename;
    $replyto = $from_mail;
    $file_size = filesize($file);
    $handle = fopen($file, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    $uid = md5(uniqid(time()));
    $name = basename($file);
    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $header .= "This is a multi-part message in MIME format.\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $header .= $message."\r\n\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n"; // use different content types here
    $header .= "Content-Transfer-Encoding: base64\r\n";
    $header .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    $header .= $content."\r\n\r\n";
    $header .= "--".$uid."--";
    mail($mailto, $subject, "", $header);
}
?>
