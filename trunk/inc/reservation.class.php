<?php



//if (!defined('GLPI_ROOT')) {
//  die("Sorry. You can't access directly to this file");
//}


class PluginReservationReservation extends CommonDBTM {

  static function getTypeName($nb=0) {
    return _n('Reservation', 'Reservation', $nb, 'Reservation');
  }



  function isNewItem() {
    return false;
  }


  /**
   * Définition des onglets
   **/
  function defineTabs($options=array()) {
    $ong = array();
    $this->addStandardTab(__CLASS__, $ong, $options);
    return $ong;
  }

  /**
   * Définition du nom de l'onglet
   **/
  function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
    $ong = array();
    $ong[1] = 'Réservations en cours';
    $ong[2] = 'Matériel disponible / faire une réservation';
    return $ong;
  }


  /**
   * Définition du contenu de l'onglet
   **/
  static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

    $monplugin = new self();
    switch ($tabnum) {
      case 1 : // mon premier onglet
	$monplugin->showCurrentResa();
	break;

      case 2 : // mon second onglet
	$monplugin->showDispoAndFormResa();
	break;
    }
    return true;
  }


  /**
   * Affiche le cadre avec la date de debut / date de fin
   **/
  function showFormDate() {

    if(isset($_GET['resareturn'])) {
      $_POST['reserve'] = $_SESSION['reserve'];

    } else if(!isset($_POST['reserve'])) {
      $jour = date("d",time());
      $mois = date("m",time());
      $annee = date("Y",time());
      $begin_time                 = time();
      //$begin_time                -= ($begin_time%HOUR_TIMESTAMP);
      $_POST['reserve']["begin"]  = date("Y-m-d H:i:s",$begin_time);
      //$_POST['reserve']["begin"]  = date("Y-m-d H:i:s",mktime(8,0,0,$mois,$jour,$annee));
      //$_POST['reserve']["end"]    = date("Y-m-d H:i:s",$begin_time+HOUR_TIMESTAMP);
      if($begin_time > mktime(19,0,0,$mois,$jour,$annee))
	$_POST['reserve']["end"] = date("Y-m-d H:i:s",$begin_time + 3600);
      else
	$_POST['reserve']["end"]    = date("Y-m-d H:i:s",mktime(19,0,0,$mois,$jour,$annee));
    }

    echo "<div id='viewresasearch'  class='center'>";
    echo "<form method='post' name='form' action='".Toolbox::getItemTypeSearchURL(__CLASS__)."'>";
    echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
    echo "<th colspan='3'>Choisissez une date</th></tr>";


    echo "<tr class='tab_bg_2'><td>".__('Start date')."</td><td>";
    Html::showDateTimeField("reserve[begin]", array('value'      =>  $_POST['reserve']["begin"],
	  'maybeempty' => false));
    echo "</td><td rowspan='3'>";
    echo "<input type='submit' class='submit' name='submit' value=\""._sx('button', 'Search')."\">";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td>".__('End date')."</td><td>";
    Html::showDateTimeField("reserve[end]", array('value'      =>  $_POST['reserve']["end"],
	  'maybeempty' => false));
    echo "</td></tr>";

    echo "</td></tr>";

    echo "</table>";
    Html::closeForm();
    echo "</div>";

  }



  /**
   * Fonction permettant d'afficher les materiels disponibles et de faire une nouvelle reservation
   * C'est juste une interface differente de celle de GLPI. Pour les nouvelles reservations, on utilise les fonctions du coeur de GLPI
   **/
  function showDispoAndFormResa(){
    global $DB, $CFG_GLPI;
    $showentity = Session::isMultiEntitiesMode();


    $begin = $_SESSION['reserve']["begin"];
    $end   = $_SESSION['reserve']["end"];
    $left = "";
    $where = "";

    // TODO : A debuguer ! le premier form suivant est jsute là pour contourner un pb : si on l'enleve, le second form necessaire est zappé lors de la construction de la page web... 
    echo "<div class='center'>";
     // <form  method='post' name='form' action='".Toolbox::getItemTypeSearchURL(__CLASS__)."'></form> 
     echo "<form name='form' method='GET' action='../../../front/reservation.form.php'>";
    echo "<table style=\"border-spacing:20px;\">";
    echo "<tr>";

    foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
      if (!($item = getItemForItemtype($itemtype))) {
	continue;
      }

      $itemtable = getTableForItemType($itemtype);
      $otherserial = "'' AS otherserial";

      if ($item->isField('otherserial')) {
	$otherserial = "`$itemtable`.`otherserial`";
      }

      if (isset($begin) && isset($end)) {
	$left = "LEFT JOIN `glpi_reservations`
	  ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
	      AND '". $begin."' < `glpi_reservations`.`end`
	      AND '". $end."' > `glpi_reservations`.`begin`)";
	$where = " AND `glpi_reservations`.`id` IS NULL ";
      }

      $query = "SELECT `glpi_reservationitems`.`id`,
	`glpi_reservationitems`.`comment`,
	`$itemtable`.`id` AS materielid,
	`$itemtable`.`name` AS name,
	`$itemtable`.`entities_id` AS entities_id,
	$otherserial,
	`glpi_locations`.`completename` AS location,
	`glpi_reservationitems`.`items_id` AS items_id
	  FROM `glpi_reservationitems`
	  $left
	  INNER JOIN `$itemtable`
	  ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
	      AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
	  LEFT JOIN `glpi_locations`
	  ON (`$itemtable`.`locations_id` = `glpi_locations`.`id`)
	  WHERE `glpi_reservationitems`.`is_active` = '1'
	  AND `glpi_reservationitems`.`is_deleted` = '0'
	  AND `$itemtable`.`is_deleted` = '0'
	  $where ".
	  getEntitiesRestrictRequest(" AND", $itemtable, '',
	      $_SESSION['glpiactiveentities'],
	      $item->maybeRecursive())."
	  ORDER BY `$itemtable`.`entities_id`,
	`$itemtable`.`name`";


      if ($result = $DB->query($query)) {

	if($DB->numrows($result)) {
	  echo "<td>";
	  echo "<table class='tab_cadre'>";
	  echo "<tr><th colspan='".($showentity?"6":"5")."'>".$item->getTypeName()."</th></tr>\n"; 
	}
	while ($row = $DB->fetch_assoc($result)) {
	  echo "<tr class='tab_bg_2'><td>";
	  echo "<input type='checkbox' name='item[".$row["id"]."]' value='".$row["id"]."'>".
	    "</td>";
	  $typename = $item->getTypeName();
	  if ($itemtype == 'Peripheral') {
	    $item->getFromDB($row['items_id']);
	    if (isset($item->fields["peripheraltypes_id"]) && ($item->fields["peripheraltypes_id"] != 0)) {
	      $typename = Dropdown::getDropdownName("glpi_peripheraltypes",
		  $item->fields["peripheraltypes_id"]);
	    }
	  }
	  echo "<td white-space: nowrap ><a href='/front/".Toolbox::strtolower($itemtype).".form.php?id=".$row['materielid']."&forcetab=Reservation$1"."'>".
	    sprintf(__('%1$s'), $row["name"])."</a></td>";
	  echo "<td>".nl2br($row["comment"])."</td>";
	  if ($showentity) {
	    echo "<td>".Dropdown::getDropdownName("glpi_entities", $row["entities_id"]).
	      "</td>";
	  }
	  echo "<td><a title=\"Voir le planning\" href='../../../front/reservation.php?reservationitems_id=".$row['id']."'>".
	    "<img title=\"\" alt=\"\" src=\"/glpi/pics/reservation-3.png\"></img></a></td>";
	  echo "</tr>\n";
	  $ok = true;
	}
      }
      if($DB->numrows($result)) {
	echo "</td>";
	echo "</table>\n"; 
      }
    }     

    echo "</tr>";
    if ($ok) {
      echo "<tr class='tab_bg_1 center'><td colspan='".($showentity?"5":"4")."'>";
      echo "<input type='submit' value=\"Réserver\" class='submit'></td></tr>\n";
    }

    echo "</table>\n";
    #echo "<input type='hidder' name='
    echo "<input type='hidden' name='id' value=''>";
    Html::closeForm(); //echo "</form>";// No CSRF token needed
    echo "</div>\n";
  }



  /**
   * Fonction permettant de marquer une reservation comme rendue 
   * Si elle etait dans la table glpi_plugin_reservation_manageresa (c'etait donc une reservation prolongée), on insert la date de retour à l'heure actuelle ET on met à jour la date de fin de la vraie reservation.
   * Sinon, on insert une nouvelle entree dans la table pour avoir un historique du retour de la reservation ET on met à jour la date de fin de la vraie reservation
   **/
  function resaReturn($resaid)
  {
    global $DB, $CFG_GLPI;
    // on cherche dans la table de gestion des resa du plugin
    $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = ".$resaid;
    $trouve = 0;
    if ($result = $DB->query($query)) {
      if($DB->numrows($result))
	$trouve = 1;
    }

    $ok = 0;
    if($trouve) {
      // maj de la date de retour dans la table manageresa du plugin
      $query = "UPDATE `glpi_plugin_reservation_manageresa` SET `date_return` = '".date("Y-m-d H:i:s",time())."' WHERE `resaid` = '".$resaid."';";
      $DB->query($query) or die("error on 'update' into glpi_plugin_reservation_manageresa / hash: ". $DB->error());
      $ok = 1;
    }
    else {
      $temps = time();
      // insertion de la reservation dans la table manageresa
      $query = "INSERT INTO  `glpi_plugin_reservation_manageresa` (`resaid`, `date_return`, `date_theorique`) VALUES ('".$resaid."', '". date("Y-m-d H:i:s",$temps)."', '". date("Y-m-d H:i:s",$temps)."');";
      $DB->query($query) or die("error on 'insert' into glpi_plugin_reservation_manageresa / hash: ". $DB->error());
      $ok = 1;
    }


    //update de la vrai reservation
    if($ok) {
      $query = "UPDATE `glpi_reservations` SET `end`='". date("Y-m-d H:i:s",time())."' WHERE `id`='".$resaid."';";
      $DB->query($query) or die("error on 'update' into glpi_reservations / hash: ". $DB->error()); 
    }
  }


  /**
   * Fonction permettant d'afficher les reservations actuelles
   * 
   **/
  function showCurrentResa() {
    global $DB, $CFG_GLPI;
    $showentity = Session::isMultiEntitiesMode();



    $begin = $_SESSION['reserve']["begin"];
    $end   = $_SESSION['reserve']["end"];
    $left = "";
    $where = "";

    //tableau contenant un tableau des reservations par utilisateur
    // exemple : (salleman => ( 0=> (resaid => 1, debut => '12/12/2054', fin => '12/12/5464', comment => 'tralala', name => 'hobbit16'
    $ResaByUser = array();


    foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
      if (!($item = getItemForItemtype($itemtype))) {
	continue;
      }

      $itemtable = getTableForItemType($itemtype);

      $otherserial = "'' AS otherserial";
      if ($item->isField('otherserial')) {
	$otherserial = "`$itemtable`.`otherserial`";
      }

      if (isset($begin) && isset($end)) {
	$left = "LEFT JOIN `glpi_reservations`
	  ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
	      AND '". $begin."' < `glpi_reservations`.`end`
	      AND '". $end."' > `glpi_reservations`.`begin`)";

	$where = " AND `glpi_reservations`.`id` IS NOT NULL ";
      }

      $query = "SELECT `glpi_reservationitems`.`id`,
	`glpi_reservationitems`.`comment`,
	`$itemtable`.`name` AS name,
	`$itemtable`.`entities_id` AS entities_id,
	$otherserial,
	`glpi_reservations`.`id` AS resaid,
	`glpi_reservations`.`comment`,
	`glpi_reservations`.`begin`,
	`glpi_reservations`.`end`,
	`glpi_users`.`name` AS username,
	`glpi_reservationitems`.`items_id` AS items_id
	  FROM `glpi_reservationitems`
	  $left
	  INNER JOIN `$itemtable`
	  ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
	      AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
	  LEFT JOIN `glpi_users` 
	  ON (`glpi_reservations`.`users_id` = `glpi_users`.`id`)
	  WHERE `glpi_reservationitems`.`is_active` = '1'
	  AND `glpi_reservationitems`.`is_deleted` = '0'
	  AND `$itemtable`.`is_deleted` = '0'
	  $where ".
	  getEntitiesRestrictRequest(" AND", $itemtable, '',
	      $_SESSION['glpiactiveentities'],
	      $item->maybeRecursive())."
	  ORDER BY username,
	`$itemtable`.`entities_id`,
	`$itemtable`.`name`";

      if ($result = $DB->query($query)) {
	// on regroupe toutes les reservations d'un meme user dans un tableau.
	while ($row = $DB->fetch_assoc($result)) {
	  if(!array_key_exists($row["username"],$ResaByUser)) {
	    $ResaByUser[$row["username"]] = array();
	  }
	  $tmp = array ("resaid" => $row["resaid"],
	      "name" => $row['name'],
	      "debut" => $row["begin"],
	      "fin" => $row["end"],
	      "comment" => nl2br($row["comment"]));
	  $ResaByUser[$row["username"]][] = $tmp;
	  //on trie par date 
	  usort($ResaByUser[$row["username"]], 'compare_date_by_user');
	}
      }
    }
    

    echo "<div class='center'><form name='form' method='GET' action='reservation.form.php'>";
    echo "<table class='tab_cadre'>";
    echo "<thead>";
    echo "<tr><th colspan='".($showentity?"10":"9")."'>"."Matériels empruntés"."</th></tr>\n";
    echo "<tr class='tab_bg_2'>";
    
#echo "<td>Utilisateur</td><td>materiel</td><td>Debut</td><td>Fin</td><td>Commentaire</td><td>Mouvement</td><td><center>Marquer comme rendu</center></td><td>Editer la reservation</td></tr>";
    



    echo "<th><a href=\"#\" onclick=\"SortTable(0);\">Utilisateur</a></th>";
    echo "<th><a href=\"#\" onclick=\"SortTable(1);\">Materiel</a></th>";
    echo "<th><a href=\"#\" onclick=\"SortTable(2);\">Debut</a></th>";
    echo "<th><a href=\"#\" onclick=\"SortTable(3);\">Fin</a></th>";
    echo "<th><a href=\"#\" onclick=\"SortTable(4);\">Commentaires</a></th>";
    echo "<th><a href=\"#\" onclick=\"SortTable(5);\">Mouvement</a></th>";
    echo "<th>Marquer comme rendu</th>";
    echo "<th>Editer la reservation</th>";

    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    //on parcourt le tableau pour construire la table à afficher
    foreach($ResaByUser as $User => $arrayResa) {
      echo "<tr class='tab_bg_2'>";
      echo "<td rowspan=".count($arrayResa).">".$User."</td>";
      foreach($arrayResa as $Num => $resa) {
	$colorRed = "";
	// on regarde si la reservation actuelle a été prolongée par le plugin
	$query = "SELECT `date_return`, `date_theorique` FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = ".$resa["resaid"];
	if ($result = $DB->query($query)) {
	  $dates = $DB->fetch_row($result);
	}

	if($DB->numrows($result)) 
	  if($dates[1] < date("Y-m-d H:i:s",time()) && $dates[0] == NULL) // on colore  en rouge seulement si la date de retour theorique est depassée et si le materiel n'est pas marqué comme rendu (avec une date de retour effectif)
	    $colorRed = "bgcolor=\"red\"";
	
	// le nom du materiel
	echo "<td $colorRed>".$resa['name']."</td>";

	//date de debut de la resa
	echo "<td $colorRed>".date("\L\e d-m-Y \à H:i:s",strtotime($resa["debut"]))."</td>";

	// si c'est une reservation prolongée, on affiche la date theorique plutot que la date reelle (qui est prolongée jusqu'au retour du materiel)
	if($DB->numrows($result) && $dates[0] == NULL) 
	  echo "<td $colorRed>".date("\L\e d-m-Y \à H:i:s",strtotime($dates[1]))."</td>";
	else 
	  echo "<td $colorRed>".date("\L\e d-m-Y \à H:i:s",strtotime($resa["fin"]))."</td>";
	
	//le commentaire
	echo "<td $colorRed>".$resa["comment"]."</td>";

	// les fleches de mouvements	
	echo "<td ><center>";
	if(date("Y-m-d",strtotime($resa["debut"])) == date("Y-m-d",strtotime($begin)))
	  echo "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
	if(date("Y-m-d",strtotime($resa["fin"])) == date("Y-m-d",strtotime($end)))
	  echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
	echo "</center></td>";

	// si la reservation est rendue, on affiche la date du retour, sinon le bouton pour acquitter le retour
	if($dates[0] != NULL) 
	  echo "<td>".date("\L\e d-m-Y \à H:i:s",strtotime($dates[0]))."</td>";
	else
	  echo "<td><center><a title=\"Marquer comme rendu\" href=\"reservation.php?resareturn=".$resa['resaid']."\"><img title=\"\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";

	// bouton pour editer la reservation (renvoi vers l'interface glpi standard)
	echo "<td><center><a title=\"Editer la reservation\" href='../../../front/reservation.form.php?id=".$resa['resaid']."'>".
	  "<img title=\"\" alt=\"\" src=\"/glpi/pics/reservation-3.png\"></img></a></center></td>";

	echo "</tr>";
	echo "<tr class='tab_bg_2'>";
      }
      echo "</tr>\n";
    }
    echo "</tbody>";
    echo "</table>\n";
    echo "</div>\n";

  }

}


function compare_date_by_user($a, $b) { return strnatcmp($a['debut'], $b['debut']); }
function compare_date_by_alluser($a, $b) { return strnatcmp($a[0]['debut'], $b[0]['debut']); }


?>
