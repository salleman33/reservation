function afficher_cacher(id) {
   if (document.getElementById(id).style.display == 'none') {
      document.getElementById(id).style.display = 'inline';
      document.getElementById('bouton_' + id).style.display = 'none';
   } else {
      document.getElementById(id).style.display = 'none';
      document.getElementById('bouton_' + id).style.display = 'inline';
   }
   return true;
}


function createCategoryEnter() {
   if (event.key === 'Enter') {
      createCategory();
   }
}

function deleteCategory(category) {
   var categorieOther = document.getElementById("itemsCategory_notcategorized");
   var tbody_dst = categorieOther.getElementsByTagName('tbody')[0];

   var element = document.getElementById("itemsCategory_" + category);
   var tbody_source = element.getElementsByTagName('tbody')[0];
   while (tbody_source.firstChild) {
      if (/^item_[0-9]+$/.test(tbody_source.firstChild.id)) {

         clonedElement = tbody_source.firstChild.cloneNode(true);
         var input = clonedElement.getElementsByTagName('input')[0];
         input.value = "notcategorized";

         clonedElement = tbody_dst.appendChild(clonedElement);
      }
      tbody_source.removeChild(tbody_source.firstChild);
   }
   element.removeChild(tbody_source);
   element.parentNode.removeChild(element);

   // maj des index destination
   for (var i = 0; i < tbody_dst.childNodes.length; i++) {
      tbody_dst.childNodes[i].getElementsByClassName('index')[0].innerHTML = i + 1;
   }
}

function createCategory() {
   // TODO : verifier si la categorie n'existe pas deja ! 


   titleField = document.getElementById('newCategoryTitle');
   titleValue = titleField.value;
   if (!/^([a-zA-Z0-9]+)$/.test(titleValue) || titleValue === 'notcategorized') {
      titleField.style.backgroundColor = "red";
      return;
   }
   titleField.style.backgroundColor = "initial";
   titleField.value = "";
   
   var th = document.createElement("th");
   th.appendChild(document.createTextNode(titleValue));
   th.setAttribute('class', 'categoryTitle');
   th.setAttribute('colspan', 3);

   var del = document.createElement("td");
   del.appendChild(document.createTextNode("X"));
   del.setAttribute('class', 'categoryClose');
   del.setAttribute('onclick', 'deleteCategory(\'' + titleValue + '\')');

   var thead = document.createElement("thead");
   thead.appendChild(th);
   thead.appendChild(del);   

   var input = document.createElement("input");
   input.setAttribute('type', 'hidden');
   input.setAttribute('name', 'category_' + titleValue);
   input.setAttribute('value', titleValue);

   var table = document.createElement("table");
   table.appendChild(thead);
   table.appendChild(input);
   table.appendChild(document.createElement("tbody"));
   table.setAttribute('class', 'dropper');
   table.setAttribute("id", "itemsCategory_" + titleValue);

    	   
   document.getElementById("categoriesContainer").appendChild(table);
   $(table).sortable(
      {
         connectWith: '.dropper',
         items: 'tbody tr',
         stop: updateHiddenConfig,
         receive: function (e, ui) { $(this).find("tbody").append(ui.item); }
      }).disableSelection();
}


var updateHiddenConfig = function (e, ui) {
   var target = ui.item.offsetParent()[0],
      tr = ui.item[0];

   categoryName = /^[a-zA-Z]+\_([a-zA-Z0-9]+)$/.exec(target.id)[1];
   var input = tr.getElementsByTagName('input')[0];
   input.value = categoryName;

   // maj des index sources
   tbody_source = e.target.getElementsByTagName('tbody')[0];
   for (var i = 0; i < tbody_source.childNodes.length; i++) {
      tbody_source.childNodes[i].getElementsByClassName('index')[0].innerHTML = i + 1;
   }
   // maj des index destination
   tbody_dst = target.getElementsByTagName('tbody')[0];
   for (var i = 0; i < tbody_dst.childNodes.length; i++) {
      tbody_dst.childNodes[i].getElementsByClassName('index')[0].innerHTML = i + 1;
   }
};



   (function () {

      $('.noEnterSubmit').keypress(function (e) {
         // if ( e.which == 13 ) return false;
         // //or...
         if (e.which == 13) e.preventDefault();
      });

      $('.dropper').sortable(
         {
            connectWith: '.dropper',
            items: 'tbody tr',
            stop: updateHiddenConfig,
            receive: function (e, ui) { $(this).find("tbody").append(ui.item); }
         }).disableSelection();

   })();

