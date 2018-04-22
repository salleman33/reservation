function afficher_cacher (id) {
  if (document.getElementById(id).style.display == 'none') {
    document.getElementById(id).style.display = 'inline'
    document.getElementById('bouton_' + id).style.display = 'none'
  } else {
    document.getElementById(id).style.display = 'none'
    document.getElementById('bouton_' + id).style.display = 'inline'
  }
  return true
}

