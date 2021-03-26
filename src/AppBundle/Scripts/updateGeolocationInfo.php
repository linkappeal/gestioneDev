<?php 

        /*   1) GEOPLUGIN  120 req/minute
         *   2) FREEGEOIP  15000 req/hour
         *   3) IPINFO     1,000 req/day
         *   4) IPINFODB   2 req/sec (poco preciso)
         *   5) UDHARI     (?)
         *   6) 
         * 
         * 
         */
	$servername = "127.0.0.1";
	$username = "root";
	$password = null;
	$dbname = "symfony";
	
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	} 

	$sql = "SELECT id, indirizzo_ip FROM lead_uni where indirizzo_ip is not null";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	   
            // output data of each row
	    while($row = $result->fetch_assoc()) {
                //print_r($row);
                $ip = $row["indirizzo_ip"];
                // ottengo informazioni sulla localit�

               
                echo PHP_EOL."________ UDHARI _____________".PHP_EOL;

                $json = file_get_contents("http://udhari.net/api/ip/{$ip}?pretty=true");
                $details = json_decode($json, true);
                print_r($details);  
                
                usleep(0050000);
                
                $sql  = "update lead_uni set citta = coalesce(nullif(citta,''),'".$details['location']['city_name']."'),";
                $sql .= " provincia = coalesce(nullif(provincia,''),'".$details['location']['subdivision_2_iso_code']."'),";
                $sql .= " regione = coalesce(nullif(regione,''),'".$details['location']['subdivision_1_name']."'),";
                $sql .= " nazione = coalesce(nullif(nazione,''),'".$details['location']['country_name']."'),";
                $sql .= " cap = coalesce(nullif(cap,''),'".$details['postal_code']."'),";
                $sql .= " latitudine = coalesce(nullif(latitudine,''),'".$details['latitude']."'),";
                $sql .= " longitudine = coalesce(nullif(longitudine,''),'".$details['longitude']."')";
                $sql .=  "where id = ".$row['id'];
                
                echo $sql;
                $ins = $conn->query($sql);

            }
        } else {
            echo "0 results";
        }
        
        $conn->close();
	