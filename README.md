# Reservation

This plugin offers a different management of the material reservations :

- acknowledgment of items returns
- acknowledgment of gone items
- automatic extension of non-returned items
- sending mail to borrowers if the reservation has expired (manual or automatic)
- create your own categories of items

It also brings some simplified actions on bookings like :

- add a material to an existing reservation
- replace one material with another.

You can use the API to manage your items ! (look apirest.md for help)

## Description

The Reservation plugin offers a new feature to better manage reservations: Acknowledgment of returns.
This plugin is for managers of GLPI reservations / administrators. It offers them a synthetic vision of current and future reservation.
It allows to acquit the return of a material. If the reservation of a material is ended but the material has not been marked as returned, the reservation is extended automatically. Reservation whose return date has been exceeded will appear in red !

## How it works

There are 2 automatic actions :

- checkReservations : it watchs reservations to automatically extends reservations, delay or cancel conflicted reservations, merge it if it's the same user. If check in is enabled, cancel the reservation if user did'nt checked in.
- sendMailLateReservations : it automatically sends mail to user with ended reservations (enable auto mode in the plugin configuration). **You have to create and use the notification "User Reservation Expired (plugin)" and a new notification template for it.**

Use the notification events :

- Reservation Conflict When Extended, new user (plugin) : when there is a conflict to extends a reservation. ##reservation.user## is the next user, ##reservation.otheruser## is the current user 
- Reservation Conflict When Extended, previous user (plugin) : when there is a conflict to extends a reservation. ##reservation.user## is the current user, ##reservation.otheruser## is the next user
- User Reservation Expired (plugin) : for automatic mode
- User Reservation Not Checkin (plugin) : when a reservation has not been check in
- User Reservation Checkin (plugin) : when a reservation is check in

**For automatic actions, consider increasing the limit number of actions that can be run at the same time (default is 2).**

 [![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
