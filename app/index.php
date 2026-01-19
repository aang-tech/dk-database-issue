<?php
/*
    This PHP file will simulate a connection leak by opening a connection to the database and not closing it.
    Albert Ang
*/

$conn_str = "host=db dbname=coolappdb user=thedbuser password=thepassword";

error_log("Request received, attempting to connect...");
echo "Attempting to open a connection... <br>";

$dbconn = @pg_connect($conn_str);

if ($dbconn) {
    error_log("Connection successful!");
    echo "Connection Successful! <br>";
    sleep(30);

    /*
       Do some business logic  stuffs then close the connection  
       FIX: ALWAYS CLOSE THE CONNECTION WHEN DONE TO AVOID LEAKS
    */
    pg_close($dbconn);
} else {
    error_log("ERROR: Could not connect to database");
    echo "ERROR: Could not connect to database. <br>";
}
error_log("Script ending");
?>