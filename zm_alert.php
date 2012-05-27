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

	$m = new SimpleMail("zone.surveillance@gmail.com", "ZoneMinder", "ZoneMinder Alert", 'Alert @' . $row['StartTime']);
	
	$m->attach($path, "001-capture.jpg");
	$m->attach($path, "010-capture.jpg");
	$m->attach($path, "020-capture.jpg");
	$m->attach($path, "030-capture.jpg");
	$m->attach($path, "040-capture.jpg");
	$m->attach($path, "050-capture.jpg");

	$m->send("c.prerovsky@gmail.com");
}

class SimpleMail {
	var $header;
	var $uid;
	var $subject;

	function __construct($from_mail, $from_name, $subject, $message) {
		$this->subject = $subject;
		$this->uid = md5(uniqid(time()));

		$this->header = "From: ".$from_name." <".$from_mail.">\r\n";
    		$this->header .= "Reply-To: $from_mail\r\n";
    		$this->header .= "MIME-Version: 1.0\r\n";
    		$this->header .= "Content-Type: multipart/mixed; boundary=\"".$this->uid."\"\r\n\r\n";
    		$this->header .= "This is a multi-part message in MIME format.\r\n";
    		$this->header .= "--".$this->uid."\r\n";
		$this->header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    		$this->header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    		$this->header .= $message."\r\n\r\n";
    		$this->header .= "--".$this->uid."\r\n";
	}

	function send($to) {
		mail($to, $this->subject, "", $this->header);
	}

	function attach($path, $filename) {
		$file = $path . $filename;
		if (!file_exists($file)) {
			return;
		}

		echo "Attaching file $path$filename\n";

    		$file_size = filesize($file);
		$handle = fopen($file, "r");
    		$content = fread($handle, $file_size);
    		fclose($handle);
    		$content = chunk_split(base64_encode($content));

		$this->header .= "Content-Type: image/jpeg; name=\"$filename\"\r\n"; // use different content types here
    		$this->header .= "Content-Transfer-Encoding: base64\r\n";
    		$this->header .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    		$this->header .= $content."\r\n\r\n";
    		$this->header .= "--".$this->uid."\r\n";
	}
}


?>
