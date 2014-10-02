
function sortTable (tb, n) {

var iter = 0;
while (!tb.tagName || tb.tagName.toLowerCase()
!= "table") {
if (!tb.parentNode) return;
tb = tb.parentNode;
}
if (tb.tBodies && tb.tBodies[0]) tb = tb.tBodies[0];

// Tri par sélection
 
var reg = /^\d+(\.\d+)?$/g;
var index = 0, value = null, minvalue = null;
console.log(tb.rows.length);
for (var i= tb.rows.length -2; i >= 0; i -= 1) {
    minvalue = value = null;
    index = -1;
    for (var j=i; j >= 0; j -= 1) {
        value = tb.rows[j].cells[n].firstChild.nodeValue;
        if (!isNaN(value)) value = parseFloat(value);
        if (minvalue == null || value < minvalue) { index = j; minvalue = value; }
    }

    if (index != -1) {
    var row = tb.rows[index];
    if (row) {
    tb.removeChild(row);
    tb.appendChild(row);
    }}

}
}
