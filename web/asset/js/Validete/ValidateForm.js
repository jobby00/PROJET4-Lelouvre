var validation =  document.getElementById('#btnSuivant');
var nom = document.getElementById('jd_lelouvrebundle_reservation_nom');
var nomVide = document.getElementById('#nomVide');
var nomValide = /^[a-zA-ZéèîïÎÏÈ][a-zéèàêçîï]+([-'\s][a-zA-ZéèîïÎÏÈ][a-zéèàêçîï])?/;
validation.addEventListener('click', valid);

function valid(e) {
    if(nom.validity.valueMissing){
        e.preventDefault();
        nomVide.textContent = 'Vous devrez remplire ce champs.';
        nomVide.style.color = "red";
    }else if (nomValide.test(nom.value) == false ){
        event.preventDefault();
        nomVide.textContent = 'format incorrect';
        nomValide.style.color = 'orange';
    }else {

    }
    console.log(e);
}
