# Reservation

Ce plugin propose une gestion différente des réservations de matériel :

- acquittement des retours de matériel
- prolongation automatique des réservations de matériels non rendus
- envoi de mail aux emprunteurs si la réservation est arrivée à expiration

Il apporte également quelques actions simplifiées sur les réservations comme :

- ajouter un matériel à une réservation existante
- remplacer un matériel par un autre

## Description du plugin Réservation 

Le plugin Réservation offre une nouvelle fonctionnalité pour mieux gérer les réservations : L'acquittement des retours.
Ce plugin s'adresse aux responsables des réservations/administrateurs de GLPI. Il leur propose une vision synthétique des réservations en cours et à venir.
Il permet d'acquitter le retour d'un matériel. Si jamais la réservation d'un matériel touche à sa fin mais que le matériel n'a pas été marqué comme étant rendu, la réservation est prolongée automatiquement. Les réservations dont la date de retour théorique de retour est dépassée apparaissent en rouge !

La colonne "Mouvement" permet d'identifier rapidement les flux des matériels réservés. Une flèche rouge indique que le matériel sera emprunté aujourd'hui. une flèche verte indique qu'un matériel sera retourné aujourd'hui.

## Comment ça marche ? 

Il y a deux taches automatiques :
la première permet de surveiller les réservations : Elle se lance à interval regulier et regarde si une réservation va se terminer prochainement. S'il y en a une, elle la prolonge automatiquement. Tant qu'un matériel n'est pas marqué comme rendu, la réservation se prolonge indéfiniment. Une réservation prolongée est marquée en rouge dans l'interface du plugin !
Si le prolongement automatique d'une réservation rentre en conflit avec une autre, cette dernière est supprimée afin de permettre le prolongement automatique. Un mail est envoyé aux responsables pour signaler ce problème.

Si vous choisissez le mode automatique, vous pouvez utiliser la tache automatique pour envoyer un mail aux utilisateurs dont la réservation est expirée.
Pour activer le mode automatique, il faut aller dans la configuration du plugin (menu Configuration > Plugins > Reservation) puis choisir le mode auto.
La tache se lance par défaut le soir à 23H.
ATTENTION ! Il vous faut créer vous même les modèles de notification ET les notifications (choisir le type Réservations. Deux événements sont disponibles : "Conflit pour la prolongation d'une réservation" et "expiration d'une réservation d'un utilisateur") !

## Modification rapide d'une réservation 

Le plugin permet aussi d'ajouter ou remplacer un matériel dans une réservation existante.

**Pour les taches automatiques, pensez à augmenter le nombre limite de tache pouvant etre executées en meme temps (par defaut à 2).**
