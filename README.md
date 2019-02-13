# Reservation

This plugin offers a different management of the material reservations :

- acknowledgment of material returns
- automatic extension of non-returned material reservations
- sending mail to borrowers if the reservation has expired

It also brings some simplified actions on bookings like :

- add a material to an existing reservation
- replace one material with another.

## Description

The Reservation plugin offers a new feature to better manage reservations: Acknowledgment of returns.
This plugin is for managers of GLPI reservations / administrators. It offers them a synthetic vision of current and future reservation.
It allows to acquit the return of a material. If the reservation of a material is ended but the material has not been marked as returned, the reservation is extended automatically. Reservation whose return date has been exceeded will appear in red !

## How it works ?

There are 2 automatic actions : 
- checkReservations : it automatically extends reservations 
- sendMailLateReservations : it automatically sends mail to user with ended reservations (enable auto mode in the plugin configuration)

Use the notification events :
- Reservation Conflict When Extended, new user (plugin) : when there is a conflict to extends a reservation. ##reservation.user## is the next user, ##reservation.otheruser## is the current user 
- Reservation Conflict When Extended, previous user (plugin) : when there is a conflict to extends a reservation. ##reservation.user## is the current user, ##reservation.otheruser## is the next user
- User Reservation Expired (plugin) : for automatic mode

** For automatic actions, consider increasing the limit number of actions that can be run at the same time (default is 2). **