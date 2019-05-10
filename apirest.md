# API REST

## Checkout reservation(s)

* **URL**: ../plugin/reservation/apirest.php/checkoutItem/:id
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
  * 200 (OK) Checkout done ! Returns JSON object {"item_id": "item_name", "success" : true, "message" : "OK"}
  * 202 (Accepted) item is not checkable. Returns JSON object {"item_id": "item_name", "success" : false, "message" : "reason"}
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"id": "41"}}' \
'http://path/to/glpi/../plugins/reservation/apirest.php/checkoutItem'

< 200 OK
[{"41":"Computer_1", "success": true, "message": "OK"}]

```

## get list of categories

* **URL**: ../plugin/reservation/apirest.php/categories
* **Description**: Get the list of categories defined by user in the plugin configuration
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPI API configuration. Optional.
* **Returns**:
  * 200 (OK) Returns JSON Object with list of categories
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/../plugins/reservation/apirest.php/categories'

< 200 OK
["cat1", "cat2", "cat3"]

```
