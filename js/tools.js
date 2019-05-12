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


var dndHandler = {

   draggedElement: null, // Propriété pointant vers l'élément en cours de déplacement

   applyDragEvents: function (element) {

      element.draggable = true;

      var dndHandler = this; // Cette variable est nécessaire pour que l'événement « dragstart » ci-dessous accède facilement au namespace « dndHandler »

      element.addEventListener('dragstart', function (e) {
         dndHandler.draggedElement = e.target; // On sauvegarde l'élément en cours de déplacement
         e.dataTransfer.setData('text/plain', ''); // Nécessaire pour Firefox
      });

   },

   applyDropEvents: function (dropper) {

      dropper.addEventListener('dragover', function (e) {
         e.preventDefault(); // On autorise le drop d'éléments
         this.className = 'dropper drop_hover'; // Et on applique le style adéquat à notre zone de drop quand un élément la survole
      });

      dropper.addEventListener('dragleave', function () {
         this.className = 'dropper'; // On revient au style de base lorsque l'élément quitte la zone de drop
      });

      var dndHandler = this; // Cette variable est nécessaire pour que l'événement « drop » ci-dessous accède facilement au namespace « dndHandler »
      dropper.addEventListener('drop', function (e) {

         var target = e.target,
            draggedElement = dndHandler.draggedElement, // Récupération de l'élément concerné
            clonedElement = draggedElement.cloneNode(true); // On créé immédiatement le clone de cet élément

         while (target.className.indexOf('dropper') == -1) { // Cette boucle permet de remonter jusqu'à la zone de drop parente
            target = target.parentNode;
         }

         

         target.className = 'dropper'; // Application du style par défaut
         clonedElement = target.appendChild(clonedElement); // Ajout de l'élément cloné à la zone de drop actuelle
         dndHandler.applyDragEvents(clonedElement); // Nouvelle application des événements qui ont été perdus lors du cloneNode()

         draggedElement.parentNode.removeChild(draggedElement); // Suppression de l'élément d'origine

         // positionnement du nom de la categorie dans la valeur de l'input hidden
         categoryName = /^[a-zA-Z]+\_([a-zA-Z0-9]+)$/.exec(target.id)[1];
         var input = clonedElement.getElementsByTagName('input')[0];
         input.value=categoryName;

      });

   }

};

function createCategoryEnter() {
   if(event.key === 'Enter') {
      createCategory();
   } 
}

function deleteCategory(category) {
   var categorieOther = document.getElementById("itemsCategory_other");
   var element = document.getElementById("itemsCategory_"+category);
   while (element.firstChild) {
      if (/^item_[0-9]+$/.test(element.firstChild.id)) {
         clonedElement = element.firstChild.cloneNode(true);
         var input = clonedElement.getElementsByTagName('input')[0];
         input.value="other";

         clonedElement = categorieOther.appendChild(clonedElement);         
      }         
      element.removeChild(element.firstChild);
   }
   element.parentNode.removeChild(element);
}

function createCategory() {
     
   titleField = document.getElementById('newCategoryTitle');
   titleValue = titleField.value;
   if(!/^([a-zA-Z0-9]+)$/.test(titleValue)) {
      titleField.style.backgroundColor = "red";
      return;
   }   
   titleField.style.backgroundColor = "initial";
   titleField.value = "";
   
   var p = document.createElement("p");
   p.appendChild(document.createTextNode(titleValue));
   p.setAttribute('class', 'categoryTitle');
   p.style.cursor = 'default';
   var del = document.createElement("div");
   del.appendChild(document.createTextNode("X"));
   del.style.cursor = 'pointer';
   del.setAttribute('class', 'categoryClose');
   del.setAttribute('onclick', 'deleteCategory(\''+titleValue+'\')');

   var input = document.createElement("input");
   input.setAttribute('type', 'hidden');
   input.setAttribute('name', 'category_'+titleValue);

   var div = document.createElement("div");
   div.setAttribute('class', 'dropper');
   div.setAttribute("id", "itemsCategory_"+titleValue);
   div.appendChild(input);
   div.appendChild(p);
   div.appendChild(del);   
   dndHandler.applyDropEvents(div);
   document.getElementById("categoriesContainer").appendChild(div);   
}


(function () {   

   $('.noEnterSubmit').keypress(function(e){
      // if ( e.which == 13 ) return false;
      // //or...
      if ( e.which == 13 ) e.preventDefault();
   });

   var elements = document.querySelectorAll('.draggable'),
      elementsLen = elements.length;

   for (var i = 0; i < elementsLen; i++) {
      dndHandler.applyDragEvents(elements[i]); // Application des paramètres nécessaires aux éléments déplaçables
   }

   var droppers = document.querySelectorAll('.dropper'),
      droppersLen = droppers.length;

   for (var i = 0; i < droppersLen; i++) {
      dndHandler.applyDropEvents(droppers[i]); // Application des événements nécessaires aux zones de drop
   }

})();

