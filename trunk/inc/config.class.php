<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class PluginReservationConfig extends CommonDBTM {


    function getConfiguration()
        {
        global $DB;
        
        $query = "SELECT * FROM glpi_plugin_reservation_config WHERE actif=1";
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


function setConfiguration($week=null)
{
    global $DB;       

    $query = "UPDATE glpi_plugin_reservation_config SET actif=0";
        $DB->query($query) or die($DB->error());
    foreach($week as $day)
    {
        $query = "UPDATE glpi_plugin_reservation_config SET actif=1 WHERE jour='$day'";
        $DB->query($query) or die($DB->error());
    }
}


function showForm() 
{

$config = $this->getConfiguration();
echo "<div class='center'>";
echo "<form method='post' action='".$this->getFormURL()."'>";

echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th>Mail aux utilisateurs avec reservation depassée</th>";
echo "<tr>";
echo "<td> lundi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"lundi\" ".(isset($config['lundi'])?'checked':'')." > </td>";
echo "</tr>";
echo "<tr>";
echo "<td> mardi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"mardi\"".(isset($config['mardi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> mercredi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"mercredi\" ".(isset($config['mercredi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> jeudi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"jeudi\" ".(isset($config['jeudi'])?'checked':'')."> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> vendredi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"vendredi\" ".(isset($config['vendredi'])?'checked':'')." ></td>";
echo "</tr>";
echo "<tr>";
echo "<td> samedi : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"samedi\" ".(isset($config['samedi'])?'checked':'')." ></td>";
echo "</tr>";
echo "<tr>";
echo "<td> dimanche : </td><td> <INPUT type=\"checkbox\" name=\"week[]\" value=\"dimanche\" ".(isset($config['dimanche'])?'checked':'')."> </td>";

echo "</tr>";


echo "<input type=\"submit\" value=\"Valider\">";



echo "</table>";
Html::closeForm();
echo "</div>";




}



}
?>
