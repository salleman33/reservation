<?php


// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// Récupération du fichier includes de GLPI, permet l'accès au cœur
include (GLPI_ROOT."inc/includes.php");



/**
 * Récupère le chemin absolu de l'instance GLPI
 * @return String : le chemin absolu (racine principale)
 */
function getAbsolutePath()
    {return str_replace("plugins/reservation/front/reservation.php", "", $_SERVER['SCRIPT_FILENAME']);}


$PluginReservationReservation = new PluginReservationReservation();


Session::checkSeveralRightsOr(array("reservation_central"  => "r",
                                    "reservation_helpdesk" => "1"));

if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpHeader(__('Simplified interface'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
} else {
  // Html::header(Reservation::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "dreservation");
   Html::header(PluginReservationReservation::getTypeName(2),  $_SERVER['PHP_SELF'], "plugins", "Reservation");
}


$PluginReservationReservation->showFormDate();
$_SESSION['reserve']=$_POST['reserve'];
if(isset($_GET['resareturn']))
  $PluginReservationReservation->resaReturn($_GET['resareturn']);
$PluginReservationReservation->show();


if (!Session::haveRight("reservation_central","r")) {
   echo "vue simple"; //ReservationItem::showListSimple();
} else {

//$myfield = [ "field" => "name" ];
$myparam = array('completename');
$myfield = array( "field" => $myparam );
//   Search::show('ReservationItem');
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}



