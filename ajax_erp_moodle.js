


// MUDANÃƒâ€¡A DA SETA
   $("#page-header .page-header-headings").hide();
$(".larrow").html('<spam class="awe5"> <i class="fas fa-angle-double-left"></i></spam>');
$(".rarrow").html('<spam class="awe5"> <i class="fas fa-angle-double-right"></i></spam>');


function consultamatricula(){
 matricula = $('#matricula').val();
 
 $.ajax({
    url: 'blocks/fametroapresentacao/consulta_status.php',
    type: 'GET',
    data: {matricula: matricula},
    success: function (response) {
       //$("#aceitemodal").empty();
       $("#retorno_consulta_matricula").html('');
        $("#retorno_consulta_matricula").html(response);
        //console.log(response);
        
        //$('#formulario_consulta').hide();

    }, error: function () {
        alert('Digitar apenas os nÃºmeros da matrÃ­cula!');
    }
 });
}

// PÃGINA DE LOGIN

$(document).ready(function(){


    
var pathname = window.location.pathname;

//console.log(pathname);

if(pathname == '/'){

/*// insere no header da frontpage
     $.ajax({
    url: 'https://erpead.fametro.edu.br/custom/frontheader.php',
    type: 'POST',
    crossDomain : true,
    success: function (response) {

        $("#page-header > div > div > div").html(response);
     
        console.log(response);
    }, error: function () {
    }
 });
 



// insere no header da frontpage
     $.ajax({
     url: 'https://erpead.fametro.edu.br/custom/conteudo.php',
    type: 'POST',
    crossDomain : true,
    success: function (response) {
       $("#page-site-index #region-main ").empty();
        $("#page-site-index #region-main ").html(response);

        $("#page-site-index #region-main ").show();
        //console.log(response);
    }, error: function () {
    }
 });
 */




}

    $('#region-main > div > div.row.justify-content-center > div > div > div > div > div > div:nth-child(1)').removeClass("col-md-5");
    
    $('#region-main > div > div.row.justify-content-center > div > div > div > div > div > div').addClass("col-md-12");
    $('#region-main > div > div.row.justify-content-center > div > div > div>h2').removeClass("text-center");
    $('#region-main > div > div.row').removeClass("justify-content-center");
    $('#region-main > div > div.row').addClass("flex-row-reverse");
    $('#region-main > div > div.row > div > div > div>h2').html('ACESSO AO AMBIENTE VIRTUAL DE APRENDIZAGEM - AVA');
    
    



    


 
// VERIFICA QUAL AVA ESTÃ OPRANDO
var url = window.location.href;

var surl = url.split('.');

var ambiente  = surl[0].slice(8);

//console.log(ambiente);


// ESCONDE LINK PARA BAIXAR O APLICATIVO
$("#page-footer > div > a").hide();

// ESCONDE A DATA DE MODIFICAÃ‡ÃƒO
$(".modified").hide();

// ACRECENTAS ANIMAÃ‡Ã•ES
// HEADER DA PAGE
$("#page-header").addClass("animated fadeIn");
//$("#region-main").addClass("animated fadeIn");


//ACRESCENTA IMAGEM NA FRENTE DO HEADER
/*$(".page-header-headings h1").prepend('<img src="https://erpead.fametro.edu.br/custom/svg/icone001.svg" class="svg-header" />');


if ($('h2:first-of-type').text().indexOf('Videoaula') != -1 || $('h2:first-of-type').text().indexOf('VIDEOAULA') != -1){
$("h2:first-of-type").prepend('<img src="https://erpead.fametro.edu.br/custom/svg/2332749-online-education/2332749-online-education/svg/105-video.svg" class="svg-header-h2" />');
}*/



// ##############################################################2019 rotinas de carregamento

caminho ='https://erpead.fametro.edu.br/custom/';

// CARREGAR AVISOS
$( ".Avisos" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var  modalidade= $(this).data("modalidade"); 

 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao=avisos&id="+moodlecode+"&nome="+modalidade+"&ambiente="+ambiente);
  //console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});

// CARREGAR PAGE
$( ".Ajax" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var PageTipo = $(this).data("pagetipo"); 
   var PageNome = $(this).data("pagenome");
   var Aluno = $(this).data("aluno");
     //console.log(moodlecode);
     //console.log(PageTipo);
    // console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao="+PageTipo+"&id="+moodlecode+"&nome="+PageNome+"&ambiente="+ambiente+"&aluno="+Aluno);
  //console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});
// CARREGAR PAGE
$( ".Roteiro" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var PageTipo = $(this).data("pagetipo"); 
   var PageNome = $(this).data("pagenome");
   var Aluno = $(this).data("aluno");
   var Modalidade = $(this).data("modalidade");
     //console.log(moodlecode);
     //console.log(PageTipo);
    // console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao="+PageTipo+"&id="+moodlecode+"&nome="+PageNome+"&ambiente="+ambiente+"&aluno="+Aluno+"&modalidade="+Modalidade);
  //console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});
// CARREGAR VIDEO EM BLOCO
$( ".AjaxVideo" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var VideoNome = $(this).data("videonome"); 
   //var PageNome = $(this).data("pagenome");
     //console.log(moodlecode);
     //console.log(VideoNome);
     //console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao=videobloco&id="+moodlecode+"&nome="+VideoNome+"&ambiente="+ambiente);
  console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});
// CARREGAR COMPLEMENTOS EM BLOCOS
$( ".AjaxComplemento" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var Complemento = $(this).data("complemento"); 
   //var PageNome = $(this).data("pagenome");
     //console.log(moodlecode);
     //console.log(VideoNome);
     //console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao=complementobloco&id="+moodlecode+"&nome="+Complemento+"&ambiente="+ambiente);
  //console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});

// CARREGA OS TEXTOS DO SISTEMA DE INICIO
$( ".TextoIniciando" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var PageTipo = $(this).data("pagetipo"); 
   var PageNome = $(this).data("pagenome");
   var Nome = $( this ).data("iniciando")   
     //console.log(moodlecode);
     //console.log(PageTipo);
    // console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao=iniciando&id="+moodlecode+"&nome="+Nome+"&ambiente="+ambiente);
  console.log(caminhocompleto);
    $(this).load(caminhocompleto); 
});


// CARREGA O SUPORTE RM  NO MODAL PAINEL
$( ".SuporteRmModal" ).each(function( index ) {
   var moodlecode = $(this).data("moodlecode"); 
   var PageTipo = $(this).data("pagetipo"); 
   var PageNome = $(this).data("pagenome");
   var Aluno = $( this ).data("aluno");   
     //console.log(moodlecode);
     //console.log(PageTipo);
     //console.log(PageNome);
 var caminhocompleto = caminho+$.trim("ajax_moodle_2020.php?&acao=statusrm&id="+moodlecode+"&nome="+PageNome+"&ambiente="+ambiente+"&aluno="+Aluno);
  console.log(caminhocompleto);
    $("#block-region-content").load(caminhocompleto); 
});






});


    
// MUDANÃƒâ€¡A DA SETA$
$(".larrow").html('<spam class="awe5"> <i class="fas fa-angle-double-left"></i></spam>');
$(".rarrow").html('<spam class="awe5"> <i class="fas fa-angle-double-right"></i></spam>');


function consultamatricula(){
 matricula = $('#matricula').val();
 
 $.ajax({
    url: 'blocks/fametroapresentacao/consulta_status.php',
    type: 'GET',
    data: {matricula: matricula},
    success: function (response) {
       //$("#aceitemodal").empty();
       $("#retorno_consulta_matricula").html('');
        $("#retorno_consulta_matricula").html(response);
        //console.log(response);
        
        //$('#formulario_consulta').hide();

    }, error: function () {
        alert('Digitar apenas os nÃºmeros da matrÃ­cula!');
    }
 
 });


}


 
