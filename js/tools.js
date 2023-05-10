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

function afficher_cacher_simple(id) {
   if (document.getElementById(id).style.display == 'none') {
      document.getElementById(id).style.display = 'inline';
   } else {
      document.getElementById(id).style.display = 'none';
   }
   return true;
}


function createCategoryEnter() {
   if (event.key === 'Enter') {
      createCategory();
   }
}

function addItemToCategory() {
   var left = document.getElementById("select_availableItems");
   var right = document.getElementById("select_selectedItems");

   var collection = left.options;
   for (let i = 0; i < collection.length; i++) {
      var item = collection[i];
      if (item.selected) {
         const option = document.createElement('option');
         option.setAttribute('value', item.value);
         option.appendChild(document.createTextNode(item.innerText));
         right.appendChild(option);
      }      
   }
   $('#select_availableItems option:selected').remove();
}

function removeItemFromCategory() {
   const left = document.getElementById("select_availableItems");
   const right = document.getElementById("select_selectedItems");

   const collection = right.selectedOptions;
   for (let i = 0; i < collection.length; i++) {
      var item = collection[i];
      if (item.selected) {
         const option = document.createElement('option');
         option.setAttribute('value', collection[i].value);
         option.appendChild(document.createTextNode(collection[i].innerText));
         left.appendChild(option);
      }
   }
   $('#select_selectedItems option:selected').remove();
}

function upItemInCategory() {
   var opt = $('#select_selectedItems option:selected');
  
   if(opt.is(':first-child')) {
      opt.insertAfter($('#select_selectedItems option:last-child'));
   }
   else {
      opt.insertBefore(opt.prev());
   }
}

function downItemInCategory() {
   var opt = $('#select_selectedItems option:selected');
  
   if(opt.is(':last-child')) {
      opt.insertBefore($('#select_selectedItems option:first-child'));
   }
   else {
      opt.insertAfter(opt.next());
   }
}


function configCategory(category) {
   const form = document.getElementById("formPluginReservationConfigs");
   const hiddenField = document.createElement('input');
   hiddenField.type = 'hidden';
   hiddenField.name = 'configCategorySubmit';
   hiddenField.value = category;
   form.appendChild(hiddenField);
   form.submit();
}

function deleteCategory(category) {
   var element = document.getElementById("trConfigCategory_" + category);
   // var tbody_source = element.getElementsByTagName('tbody')[0];
   // element.removeChild(tbody_source);
   element.parentNode.removeChild(element);
}

function createCategory() {
   // TODO : verifier si la categorie n'existe pas deja ! 


   titleField = document.getElementById('newCategoryTitle');
   titleValue = titleField.value;
   if (!/^([a-zA-Z0-9]+)$/.test(titleValue) || titleValue === 'zzpluginnotcategorized') {
      titleField.style.backgroundColor = "red";
      return;
   }
   titleField.style.backgroundColor = "initial";
   titleField.value = "";
   
   var tr = document.createElement('tr');
   tr.setAttribute('class', 'listCustomCategories');
   tr.setAttribute('id', 'trConfigCategory_' + titleValue);

   var td1 = document.createElement("td");
   td1.appendChild(document.createTextNode(titleValue));

   var tdconfig = document.createElement("td");
   tdconfig.appendChild(document.createTextNode("config"));
   tdconfig.setAttribute('class', 'categoryConfig');
   tdconfig.setAttribute('onclick', 'configCategory(\'' + titleValue + '\')');

   var del = document.createElement("td");
   del.appendChild(document.createTextNode("X"));
   del.setAttribute('class', 'categoryClose');
   del.setAttribute('onclick', 'deleteCategory(\'' + titleValue + '\')');

   var input = document.createElement("input");
   input.setAttribute('type', 'hidden');
   input.setAttribute('name', 'category_' + titleValue);
   input.setAttribute('value', titleValue);

   tr.appendChild(td1);
   tr.appendChild(tdconfig);
   tr.appendChild(del);
   tr.appendChild(input);

   var table = document.getElementById('categoriesContainer');
   var tbody_source = table.getElementsByTagName('tbody')[0];
   tbody_source.appendChild(tr);
}

var fixHelper = function (e, ui) {
   ui.children().each(function() {
      $(this).width($(this).width());
   });
   return ui;
};

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
})();


// Handle click event on the Allow Multiple Edit checkbox on the menu.class.php
function onClickAllowMultipleEditCheckbox(checkbox) {
   var showIfMultiEditEnabledElements = document.getElementsByClassName('showIfMultiEditEnabled');
   var hideIfMultiEditEnabledElements = document.getElementsByClassName('hideIfMultiEditEnabled');
   var allowMultipleEditCheckboxElements = document.getElementsByClassName('allowMultipleEditCheckbox');

   for (var i = 0; i < showIfMultiEditEnabledElements.length; i++)
      showIfMultiEditEnabledElements[i].style.display = (checkbox.checked ? "table-cell" : "none");
   for (var i = 0; i < hideIfMultiEditEnabledElements.length; i++)
      hideIfMultiEditEnabledElements[i].style.display = (checkbox.checked ? "none" : "table-cell");

   // Sync checkboxes through tabs
   for (var i = 0; i < allowMultipleEditCheckboxElements.length; i++)
      allowMultipleEditCheckboxElements[i].checked = checkbox.checked;
}


function checkin(resa_id) {
   $.ajax({
      type: "GET",
      url: window.location.origin + window.location.pathname+'/../query.php',
      data: "checkin="+resa_id,
      success: function() {
         document.getElementById('checkin'+resa_id).innerHTML = "checked in !";
      },
      error: function() {
         document.getElementById('checkin'+resa_id).innerHTML = "error...";
      }
   });
}

function checkout(resa_id) {
   $.ajax({
      type: "GET",
      url: window.location.origin + window.location.pathname+'/../query.php',
      data: "checkout="+resa_id,
      success: function() {
         document.getElementById('checkout'+resa_id).innerHTML = "checked out !";
      },
      error: function() {
         document.getElementById('checkout'+resa_id).innerHTML = "error...";
      }
   });
}

function mailuser(resa_id) {
   $.ajax({
      type: "GET",
      url: window.location.origin + window.location.pathname+'/../query.php',
      data: "mailuser="+resa_id,
      success: function() {
         document.getElementById('mailed'+resa_id).innerHTML = "mail sent";
      },
      error: function() {
         document.getElementById('mailed'+resa_id).innerHTML = "error...";
      }
   });
}

function makeAChange(redirect) {
   $.ajax({
      type: "GET",
      url: window.location.origin + window.location.pathname+'/../query.php',
      data: "change_in_progress",
      success: function() {
         location.href = redirect;
      },
      error: function() {
         console.log("error");
      }
   });
}