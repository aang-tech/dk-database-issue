<?php
/*
    This PHP file will simulate a connection leak by opening a connection to the database and not closing it.
    Albert Ang
*/

$conn_str = "host=db dbname=support_db user=support_user password=password123";

error_log("Request received, attempting to connect...");
echo "Attempting to open a connection... <br>";

$dbconn = @pg_connect($conn_str);

if ($dbconn) {
    error_log("Connection successful!");
    echo "Connection Successful! <br>";
    // // Keep the connection open for 30 seconds to simulate a leak
    sleep(30);
    // // We "forget" to close the connection here - intentional bug

} else {
    error_log("ERROR: Could not connect to database");
    echo "ERROR: Could not connect to database. <br>";
}
error_log("Script ending");
?>