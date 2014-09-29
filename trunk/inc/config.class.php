<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class PluginReservationConfig extends CommonDBTM {


    /**
     * Récupère les informations de configuration enregistrées
     * Retourne un tableau avec ID + valeur
     * @global type $DB
     * @return string
     */
    function getConfiguration()
        {
        global $DB;
        
        $query = "SELECT * FROM glpi_plugin_reservation_config WHERE valeur=1";
        if ($result = $DB->query($query))
            {
            if ($DB->numrows($result) > 0)
                {
                $i = 0;
                while ($row = $DB->fetch_assoc($result)) 
                    {
                    if (!empty($row['propriete'])){$config['propriete'] = $row['propriete'];}
                    else{$config['propriete'] = "";}
                    if (!empty($row['valeur'])){$config['valeur'] = $row['valeur'];}
                    else{$config['valeur'] = 0;}
                    $retour[$i] = $config;
                    $i++;
                    }
                }  
            }
        return $retour;
        }


    function setConfiguration($id=null,$valeur)
        {
        global $DB;
        
        
        if($id != null)
            {
            if($valeur != "delStatut")
                {$query = "UPDATE glpi_plugin_monplugin_config SET statut='$valeur' WHERE id='$id'";}
            else //suppression du statut (on passe la vie à 0)
                {$query = "UPDATE glpi_plugin_monplugin_config SET vie='0' WHERE id='$id'";}
            $DB->query($query) or die($DB->error());
            }
        else
            {
            $query = "INSERT INTO glpi_plugin_monplugin_config (statut,vie) VALUES ('$valeur','1')";
            $DB->query($query) or die($DB->error());
            }
        }


function showForm() 
{

echo "<div class='center'>";
echo "<form method='post' action='".$this->getFormURL()."'>";

echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

echo "<th>Mail aux utilisateurs avec reservation depassée</th>";
echo "<tr>";
echo "<td> lundi : </td><td> <INPUT type=\"checkbox\" name=\"lundi\" value=\"1\" checked> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> mardi : </td><td> <INPUT type=\"checkbox\" name=\"mardi\" value=\"1\" checked> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> mercredi : </td><td> <INPUT type=\"checkbox\" name=\"mercredi\" value=\"1\" checked> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> jeudi : </td><td> <INPUT type=\"checkbox\" name=\"jeudi\" value=\"1\" checked> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> vendredi : </td><td> <INPUT type=\"checkbox\" name=\"vendredi\" value=\"1\" checked> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> samedi : </td><td> <INPUT type=\"checkbox\" name=\"samedi\" value=\"1\"> </td>";
echo "</tr>";
echo "<tr>";
echo "<td> dimanche : </td><td> <INPUT type=\"checkbox\" name=\"dimanche\" value=\"1\"> </td>";
echo "</tr>";





echo "</table>";
Html::closeForm();
echo "</div>";




}



}
?>
