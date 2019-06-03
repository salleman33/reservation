# PLUGIN RESERVATION REST API:  Documentation

## Summary

* [checkin Reservation](#checkin-reservation)
* [checkout Reservation](#checkout-reservation)
* [search](#search)
* [current Reservation](#current-reservation)
* [next Reservation](#next-reservation)
* [current or next Reservation](#current-or-next-reservation)

## Checkin Reservation

* **URL**: ../plugin/reservation/apirest.php/reservationItem/Checkin
* **Description**: Checkin a reservation existing in GLPI. You can find **reservation_item id** in the URL when you look the glpi reservation page, example : https://localhost/front/reservation.php?reservationitems_id=41
* **Method**: PUT
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (JSON Payload)
  * *id*: the unique identifier of the **reservation item id** passed in URL. You **could skip** this parameter by passing it in the input payload.
  * *input*: Object with id of reservation to be checked out.
               Mandatory.
* **Returns**:
  * 200 (OK) with update status of item.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"id": "41"}}' \
'http://path/to/glpi/../plugins/reservation/apirest.php/reservationItem/checkin'

< 200 OK
[{"41" => true, "message" => ""}]

```

## Checkout Reservation

* **URL**: ../plugin/reservation/apirest.php/reservationItem/checkout
* **Description**: Checkout a reservation existing in GLPI. You can find **reservation_item id** in the URL when you look the glpi reservation page, example : https://localhost/front/reservation.php?reservationitems_id=41
* **Method**: PUT
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (JSON Payload)
  * *id*: the unique identifier of the **reservation item id** passed in URL. You **could skip** this parameter by passing it in the input payload.
  * *input*: Object with id of reservation to be checked out.
               Mandatory.
* **Returns**:
  * 200 (OK) with update status of item.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"id": "41"}}' \
'http://path/to/glpi/../plugins/reservation/apirest.php/reservationItem/checkout'

< 200 OK
[{"41" => true, "message" => ""}]

```

## Search

* **URL**: plugin/reservation/apirest.php/reservationItem/search/
* **Description**: Search reservation items
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (query string)
  * *begin*: begin date. Mandatory.
  * *end*: end date. Mandatory.
  * *availabe*: true or false to search available or reserved items. Default to false.
* **Returns**:
  * 200 (OK) with item data.
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/plugins/reservation/apirest.php/reservationItem/search/?begin=2019-05-26 14:04:14&end=2019-05-26 19:59:14&user=2'

< 200 OK
[
  {
    "id": 6,
    "itemtype": "Computer",
    "entities_id": 0,
    "is_recursive": 0,
    "items_id": 5,
    "comment": null,
    "is_active": 1,
    "is_deleted": 0,
    "links": [
      {
        "rel": "Entity",
        "href": "http://localhost/glpi/apirest.php/Entity/0"
      },
      {
        "rel": "Computer",
        "href": "http://localhost/glpi/apirest.php/Computer/5"
      }
    ]
  },
  {
    "id": 7,
    "itemtype": "Computer",
    "entities_id": 0,
    "is_recursive": 0,
    "items_id": 6,
    "comment": null,
    "is_active": 1,
    "is_deleted": 0,
    "links": [
      {
        "rel": "Entity",
        "href": "http://localhost/glpi/apirest.php/Entity/0"
      },
      {
        "rel": "Computer",
        "href": "http://localhost/glpi/apirest.php/Computer/6"
      }
    ]
  }
]

```

## Current Reservation

* **URL**: plugin/reservation/apirest.php/reservationItem/currentReservation/:id
* **Description**: Get the next reservation informations for an item.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (query string)
  * *id*: unique identifier of the reservation item. Mandatory.
* **Returns**:
  * 200 (OK) with item data.
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/plugins/reservation/apirest.php/reservationItem/currentReservation/7'

< 200 OK
{
  "id": 11,
  "reservationitems_id": 7,
  "begin": "2019-05-26 14:04:14",
  "end": "2019-05-26 19:59:14",
  "users_id": 2,
  "comment": "",
  "group": 1510921615,
  "links": [
    {
      "rel": "ReservationItem",
      "href": "http://localhost/glpi/apirest.php/ReservationItem/7"
    },
    {
      "rel": "User",
      "href": "http://localhost/glpi/apirest.php/User/2"
    }
  ]
}

```

## Next Reservation

* **URL**: plugin/reservation/apirest.php/reservationItem/nextReservation/:id
* **Description**: Get the next reservation informations for an item.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (query string)
  * *id*: unique identifier of the reservation item. Mandatory.
* **Returns**:
  * 200 (OK) with item data.
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/plugins/reservation/apirest.php/reservationItem/nextReservation/7'

< 200 OK
{
  "id": 11,
  "reservationitems_id": 7,
  "begin": "2019-05-26 14:04:14",
  "end": "2019-05-26 19:59:14",
  "users_id": 2,
  "comment": "",
  "group": 1510921615,
  "links": [
    {
      "rel": "ReservationItem",
      "href": "http://localhost/glpi/apirest.php/ReservationItem/7"
    },
    {
      "rel": "User",
      "href": "http://localhost/glpi/apirest.php/User/2"
    }
  ]
}

```

## Current or Next Reservation

* **URL**: plugin/reservation/apirest.php/reservationItem/currentOrNextReservation/:id
* **Description**: Get the current or next reservation informations for an item.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Parameters**: (query string)
  * *id*: unique identifier of the reservation item. Mandatory.
* **Returns**:
  * 200 (OK) with item data.
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/plugins/reservation/apirest.php/reservationItem/currentOrNextReservation/7'

< 200 OK
{
  "id": 11,
  "reservationitems_id": 7,
  "begin": "2019-05-26 14:04:14",
  "end": "2019-05-26 19:59:14",
  "users_id": 2,
  "comment": "",
  "group": 1510921615,
  "links": [
    {
      "rel": "ReservationItem",
      "href": "http://localhost/glpi/apirest.php/ReservationItem/7"
    },
    {
      "rel": "User",
      "href": "http://localhost/glpi/apirest.php/User/2"
    }
  ]
}

```
