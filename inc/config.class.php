<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include (GLPI_ROOT."/plugins/reservation/inc/includes.php");

class PluginReservationConfig extends CommonDBTM {

    function getConfigurationValue($name, $defaultValue = 0)
        {
            global $DB;
            $query = "SELECT * FROM glpi_plugin_reservation_config WHERE name='".$name."'";
        $value = $defaultValue;
        if ($result = $DB->query($query))
            {
            if ($DB->numrows($result) > 0)
                {
                while ($row = $DB->fetch_assoc($result)) 
                    {
                        $value = $row['value'];
                    }
                }
            }
        return $value;

        }

function setConfigurationValue($name,$value=1)
{
    global $DB;

    $query = "INSERT INTO glpi_plugin_reservation_config (name,value) VALUES('".$name."','".$value."') ON DUPLICATE KEY UPDATE value=Values(value)";
        $DB->query($query) or die($DB->error());
}


function setMailAutomaticAction($value=1)
{
    global $DB;

        $query = "UPDATE `glpi_crontasks` SET state='".$value."' WHERE name = 'MailUserDelayedResa'";
        $DB->query($query) or die($DB->error());
}


function getConfigurationWeek()
{
        global $DB;

        $query = "SELECT * FROM glpi_plugin_reservation_configdayforauto WHERE actif=1";
        if ($result = $DB->query($query))
            {
            if ($DB->numrows($result) > 0)
                {
                while ($row = $DB->fetch_assoc($result))
                    {
                        $config[$row['jour']] = $row['actif'];
                    }
                }
            }
        return $config;
}


function setConfigurationWeek($week=null)
{
    global $DB;

    $query = "UPDATE glpi_plugin_reservation_configdayforauto SET actif=0";
        $DB->query($query) or die($DB->error());
    foreach($week as $day)
    {
        $query = "UPDATE glpi_plugin_reservation_configdayforauto SET actif=1 WHERE jour='$day'";
        $DB->query($query) or die($DB->error());
    }
}


function showForm() 
{
$late_mail = $this->getConfigurationValue("late_mail");
$config = $this->getConfigurationWeek();

//$_POST["late_mail"] = isset($_POST["late_mail"]) ? $_POST["late_mail"] : 0;

//print_r($_POST);



echo "<form method='post' action='".$this->getFormURL()."'>";

echo "<div class='center'>";

echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th>".__('Method used to send e-mails to users with late reservations')."</th>";
echo "<tr>";
echo "<td>";
echo "<input type=\"hidden\" name=\"late_mail\" value=\"0\">";
echo "<input type=\"checkbox\" name=\"late_mail\" value=\"1\" ".($late_mail? 'checked':'')."> ".__('Automatic')." (".__('Using the configurable automatic action').") </td>";
echo "</tr>";

echo "</table>";

if($late_mail)
{
echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th colspan=2>".__('Days when e-mails for late reservations are sent')."</th>";
echo "<tr>";
echo "<td> ".__('Monday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"lundi\" ".(isset($config['lundi'])?'checked':'')." > </td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Tuesday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"mardi\" ".(isset($config['mardi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Wednesday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"mercredi\" ".(isset($config['mercredi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Thursday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"jeudi\" ".(isset($config['jeudi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Friday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"vendredi\" ".(isset($config['vendredi'])?'checked':'')." ></td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Saturday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"samedi\" ".(isset($config['samedi'])?'checked':'')." ></td>";
echo "</tr>";
echo "<tr>";
echo "<td> ".__('Sunday')." : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"dimanche\" ".(isset($config['dimanche'])?'checked':'')."> </td>";

echo "</tr>";
echo "</table>";
}

echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th>".('Tab Configuration')."</th>",
/*$usetab = $this->getConfigurationValue("usetab");
echo "<tr>";
echo "<input type=\"hidden\" name=\"usetab\" value=\"0\">";
echo "<td> <input type=\"checkbox\" name=\"usetab\" value=\"1\" ".($usetab? 'checked':'')."> ".__('Tab')."</td>";
echo "</tr>";

if ($usetab)
{*/
  $tabcurrent = $this->getConfigurationValue("tabcurrent",1);
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"tabcurrent\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"tabcurrent\" value=\"1\" ".($tabcurrent? 'checked':'')."> ".__('Current Reservation tab')."</td>";
  echo "</tr>";

  $tabcoming = $this->getConfigurationValue("tabcoming");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"tabcoming\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"tabcoming\" value=\"1\" ".($tabcoming? 'checked':'')."> ".__('Incoming Reservation tab')."</td>";
  echo "</tr>";
/*}
*/
echo "</table>";


echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th>".__('ToolTip Configuration')."</th>";

$tooltip = $this->getConfigurationValue("tooltip");
echo "<tr>";
echo "<input type=\"hidden\" name=\"tooltip\" value=\"0\">";
echo "<td> <input type=\"checkbox\" name=\"tooltip\" value=\"1\" ".($tooltip? 'checked':'')."> ".__('ToolTip')."</td>";
echo "</tr>";

if ($tooltip)
{

  $comment = $this->getConfigurationValue("comment");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"comment\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"comment\" value=\"1\" ".($comment? 'checked':'')."> ".__('Comment')."</td>";
  echo "</tr>";

  $location = $this->getConfigurationValue("location");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"location\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"location\" value=\"1\" ".($location? 'checked':'')."> ".__('Location')."</td>";
  echo "</tr>";

  $serial = $this->getConfigurationValue("serial");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"serial\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"serial\" value=\"1\" ".($serial? 'checked':'')."> ".__('Serial number')."</td>";
  echo "</tr>";

  $inventory = $this->getConfigurationValue("inventory");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"inventory\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"inventory\" value=\"1\" ".($inventory? 'checked':'')."> ".__('Inventory number')."</td>";
  echo "</tr>";

  $group = $this->getConfigurationValue("group");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"group\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"group\" value=\"1\" ".($group? 'checked':'')."> ".__('Group')."</td>";
  echo "</tr>";

  $man_model = $this->getConfigurationValue("man_model");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"man_model\" value=\"0\">";
  echo "<td style=\"padding-left:20px;\">";
  echo "<input type=\"checkbox\" name=\"man_model\" value=\"1\" ".($man_model? 'checked':'')."> ".__('Manufacturer') ." & ".__('Model')."</td>";
  echo "</tr>";

  $status = $this->getConfigurationValue("status");
  echo "<tr>";
  echo "<input type=\"hidden\" name=\"status\" value=\"0\">";
//  echo "<td> <input type=\"checkbox\" name=\"status\" value=\"1\" ".($status? 'checked':'')."> ".__('Status')."</td>";
  echo "</tr>";
}

echo "</table>";

echo "<input type=\"submit\" value='"._sx('button','Save')."'>";
echo "</div>";


Html::closeForm();




}



}
?>
