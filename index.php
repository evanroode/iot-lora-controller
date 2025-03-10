<?php
/*
*IoT LoRa Gateway Controller
*Copyright (C) 2018-2019  Nebra LTD. T/a Pi Supply

*This program is free software: you can redistribute it and/or modify
*it under the terms of the GNU General Public License as published by
*the Free Software Foundation, either version 3 of the License, or
*(at your option) any later version.
*
*This program is distributed in the hope that it will be useful,
*but WITHOUT ANY WARRANTY; without even the implied warranty of
*MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*GNU General Public License for more details.
*
*You should have received a copy of the GNU General Public License
*along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/


include('inc/header.php');

/*
* Lets load most of the information required to fill this page's details out
*
*/

//Linux uptime
$uptime = shell_exec('uptime -p');
$packetForwarder = shell_exec('systemctl is-active iot-lora-gateway.service');
if(strlen($packetForwarder) < 9) {
  $packetForwarder = 1;
}
else {
$packetForwarder = 0;
  }

//Lets check for external internet connectivity by doing a http request to three servers
$internetCheck1 = file_get_contents('https://1.1.1.1');
$internetStatus = 0;
if($internetCheck1 == FALSE) {
  $internetStatus++;
}
//If the number is greater than 0 then either one of the sites is down, if all three are down there is likely an internet issue.
//Get Public IP Address from httpbin
$gatewayIpAddress = explode(",",json_decode($internetCheck1,true)['origin'])[0];

$configHandler = fopen($configLocation, 'r');
$currentConfig = fread($configHandler, filesize($configLocation));

$jsonDecoded = json_decode($currentConfig,true)['gateway_conf'];
$jsonServers = $jsonDecoded['servers'][0];
$gatewayConfigured = 1;
if($jsonServers == NULL) {
  $gatewayConfigured = 0;
  $jsonServers['serv_gw_id'] = "GATEWAY CONFIG IS MISSING";
}


if($jsonServers['serv_type'] == "ttn") {

//Lets get the data from the NOC api
$ttnNocStatus = json_decode(file_get_contents('http://noc.thethingsnetwork.org:8085/api/v2/gateways/'.trim($jsonServers['serv_gw_id'])),true);


if($ttnNocStatus["rx_ok"]) {
$packetsRx = $ttnNocStatus["rx_ok"];
}
else {
  $packetsRx = "0";
}
if($ttnNocStatus["tx_in"]) {
  $packetsTx = $ttnNocStatus["tx_in"];
}
else {
$packetsTx = " 0";
}
}

$cpuTemp = shell_exec("cat /sys/class/thermal/thermal_zone0/temp");
$cpuTemp = $cpuTemp/1000;


?>
<h1>IoT LoRa Gateway Status Page</h1>
<h2>Gateway ID: <?php echo($jsonServers['serv_gw_id']);?></h2>

<?php
if($gatewayConfigured == 0) {
  echo '
  <div class="ui divided grid stackable">
    <div class="row">
        <div class="column wide">
      <div class="ui error message">
          <h3>Gateway Is Not Configured!</h3>
          There is no configuration file detected for this gateway. Please use the change configuration tab to configure this gateway.
      </div>
    </div>
  </div>
  </div>

   ';
}


 ?>


<div class="ui divided grid stackable">

    <div class="three column row">
    <div class="column wide">
      <?php
      //Change the alert box's colour based on the status.
      if($internetStatus == 0) {
        echo("<div class=\"ui positive message segment\">");
      }
      elseif($internetStatus == 3) {
        echo("<div class=\"ui error message segment\">");
      }
      else {
        echo("<div class=\"ui warning message segment\">");
      }
      ?>

          <h3>Internet Connectivity <i class="globe icon"></i></h3>
            <?php
            //Change the text based on the status.

            if($internetStatus == 0) {
              echo("All good!");
            }
            elseif($internetStatus == 1) {
              echo("There might be an issue of this gateway connecting to the internet, please check and reload.");
            }
            ?>
      </div>
    </div>
    <div class="column wide">
      <?php
      //Change the alert box's colour based on the status.
      if($packetForwarder == 1) {
        echo("<div class=\"ui positive message segment\">");
      }
      else {
        echo("<div class=\"ui error message segment\">");
      }
       ?>
          <h3>Packet Forwarder <i class="microchip icon"></i></h3>
          The packet forwarder service is <?php if($packetForwarder==0){echo("not ");}?>running.
      </div>
    </div>

    <div class="column wide">
      <div class="ui info message segment">
          <h3>Uptime <i class="calendar check icon"></i></h3>
          This gateway has been online for:<br/>
          <?php echo($uptime); ?>
      </div>
    </div>
      </div>
  </div>
<div class="ui divided grid stackable">

<!--  <div class="row">
      <div class="column wide">
    <div class="ui positive message">
        <strong>Gateway Public IP Address:</strong> <?php echo($gatewayIpAddress);?>
    </div>
  </div>
</div>
-->

  <div class="row">
    <div class="column">
    <div class="ui positive message">
        <strong>Configured TTN Server:</strong> <?php echo($jsonServers['server_address']);?>
    </div>
  </div>
</div>

<div class="row">
  <div class="column">
  <div class="ui info message">
      <strong>CPU Temperature:</strong>
      <div class="ui teal progress" id="progressBar" data-percent="<?php echo($cpuTemp); ?>">
        <div class="bar"></div>

        <div class="label"><?php echo($cpuTemp); ?> Degrees C</div>
      </div>
  </div>
</div>
</div>
</div>


<?php
if($jsonServers['serv_type'] == "ttn") {

echo '
<br/>
<hr/>
<br/>
<div class="ui divided grid stackable centered">

  <h2>Packet Statistics</h2>
    <div class="two column row">
      <div class="column wide">
<div class="ui statistics">
  <div class="statistic">
    <div class="value">
      <i class="arrow down icon"></i> '.$packetsRx.'
    </div>
    <div class="label">
      Packets Recieved
    </div>
  </div>
</div>
</div>
<div class="column wide">
<div class="ui statistics">
  <div class="statistic">
    <div class="value">
      <i class="arrow up icon"></i> '.$packetsTx.'
    </div>
    <div class="label">
      Packets Transmitted
    </div>
  </div>
</div>

  </div>
</div>
<h4>Packet statistics are from The Things Network Console</h4>
<br/>
<h4>
<a class="twitter-share-button"
  href="https://twitter.com/intent/tweet?text=My%20@PiSupply%20IoT%20LoRa%20Gateway%20has%20recieved%20<?php echo $packetsRx;?>%20Packets%20on%20%23thethingsnetwork%20&hashtags=IoTLoraGateway,IoT,TTN,LoRaWAN&related=PiSupply,TheThingsNtwrk"
  data-size="large"
  >
  <link rel="me"
  href="https://twitter.com/pisupply"
>
Tweet</a>
</h4>
</div>

';
}
?>

<br/><br/>



<br/><br/>








<script>
$('#progressBar').progress();
</script>
<?php
include('inc/footer.php');
?>
