/**
 * @file
 * Contains the definition of the behaviour jsTestRedWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestRedWeight = {
    attach: function (context, settings) {
      var argsP = drupalSettings.dm_chatbot.dm_chatbot;
      console.log(argsP);
  		$(document).on({
  		    ajaxStart: function() { $("body").addClass("loading");    },
  		    ajaxStop: function() { $("body").removeClass("loading"); }    
  		});

      $('#buttonsx').append('<div id="backgroundofkont"><ul  id="messages"></ul></div>'); 
      $('#buttonsx').append('<div class="text"><textarea placeholder="Type here..." style="font-size:15px;font-family:verdana" name="message"></textarea></div>'); 
      $('#buttonsx').append('<input type="button" id="sentmessage" value="Sent">');



      if(argsP['auth']){
        $('#buttons1').append('<input type="button" id="getFile" value="Get dataset">');
        if('file' in argsP){
          $('#buttons1').append('<input type="button" id="editFile" value="Edit dataset">');
          $('#buttons1').append('<input type="button" id="downloadfile" value="Download dataset">');
        }
        $('#getFile').click(function(){
          $.post('/dm_simple/ajax', {'action':'getFile'},
            function(result){
              console.log(result);
              location.reload();     
            });
        })
        $('#downloadfile').click(function(){
          downloadURI(argsP[0],'chatbot.txt');
        })
        function downloadURI(uri, name) 
        {
            var link = document.createElement("a");
            link.download = name;
            link.href = uri;
            link.click();
        }
        $('#editFile').click(function(){

          var contextFile = {};
          $.post('/dm_simple/ajax', {'action':'openfile','file':argsP['file']},
            function(result){
              console.log(result);              
              var thehtmlContext = '<div id="accordion">';
              var st = 1;
              var cnt = '';
              for(var i in result[2]){
                var l = result[2][i];
                if(l=='###beginQ'){
                  thehtmlContext += '<div id=context-'+cnt+'>'
                  st = 2; continue;
                }
                if(l=='###endQ'){
                  //thehtmlContext += '</div>'
                  st = 3; continue;
                }
                if(l=='###beginA'){
                  //thehtmlContext += '<div>'
                  st = 4; continue;
                }
                if(l=='###endA'){
                  thehtmlContext += '</div>'
                  st = 1; continue;
                }
                if(st == 1){
                  cnt = l;
                  contextFile[l] = {'ans':[],'ques':[]}
                  thehtmlContext += '<h3 id=context-t'+cnt+'>'+l+'</h3>'
                }
                if(st == 2){
                  contextFile[cnt]['ques'].push(l)
                  thehtmlContext += '<p class="questionDM">'+l+'</p>'
                }
                if(st == 4){
                  contextFile[cnt]['ans'].push(l)
                  thehtmlContext += '<p class="answersDM">'+l+'</p>'
                }
              }
              thehtmlContext += '</div>'
              console.log(contextFile)
              $('#dialog').html('<div id="thefile">'+thehtmlContext+'</div>'+
                '<textarea  class="textareainfop" name="addContext"></textarea>'+
                '<button id="contextbutton">Exec</button>'+
                "<p class='infop'>/add [context] : [ques-1] \\n [ques-n]  : [ans-1] \\n [ans-n] </p>"+
                '<p class="infop">/del [context]</p>'+
                '<p class="infop">/del [context] [nr] ||||->'+
                '<a href="'+argsP[0]+'" target="_blank">view file</a></p>').dialog({title:argsP['filepath'],
                buttons: {
                    Cancel:function(){
                      $( this ).dialog( "close" );
                    },
                    Save: function() {
                      $.post('/dm_simple/ajax', {'action':'savefile',
                        'file':argsP['file'],
                        'fileText':contextFile},
                        function(result){
                          console.log(result);
                          
                        });
                      $( this ).dialog( "close" );
                    }
                  },width:700, height:500});//end dialog  
              $('#dialog #accordion').accordion();
              $('#contextbutton').click(function(){
                var l = $('.textareainfop').val()
                console.log(l);
                var f = l.trim().split('/add')
                if(f.length > 1){
                  var g = f[1].split(':')
                  if(!(g[0].trim()  in contextFile)){
                    contextFile[g[0].trim()] = {'ans':[],'ques':[]}
                    $('#dialog #accordion').append('<h3 id=context-t'+g[0].trim()+'>'+g[0].trim()+'</h3>')
                    $('#dialog #accordion').append('<div id=context-'+g[0].trim()+'></div>')
                    
                  }
                  //ques
                  f = g[1].split('\n')
                  console.log(f)
                  for(var i in f){
                    if(f[i].trim().length>1){
                      contextFile[g[0].trim()]['ques'].push(f[i].trim())
                      $('#dialog #accordion #context-'+g[0].trim()).append('<p class="questionDM">'+f[i].trim()+'</p>')
                    }
                  }
                  //ans
                  f = g[2].trim().split('\n')
                  console.log(f)
                  for(var i in f){
                    if(f[i].trim().length>1){
                      contextFile[g[0].trim()]['ans'].push(f[i].trim())
                      $('#dialog #accordion #context-'+g[0].trim()).append('<p class="answersDM">'+f[i].trim()+'</p>')
                    }
                  }
                  console.log(contextFile)
                }
                var f = l.split('/del')
                if(f.length > 1){
                  var g = f[1].split(' ')
                  if(g.length>2 && !isNaN(parseInt(g[2].trim())) 
                    && g[1].trim() in contextFile){
                    $('#context'+g[1].trim()+
                      ' p:nth-child('+g[2].trim()+')').remove()
                    var ff = parseInt(g[2].trim())
                    if(contextFile[g[1]]['ques'].length < ff)
                      delete contextFile[g[1]]['ques'][ff];
                    else
                      delete contextFile[g[1]]['ans'][ff-contextFile[g[1]]['ques'].length];
                  }else{
                    if(g[1] in contextFile){
                      delete contextFile[g[1]]
                      $('#context-t'+g[1].trim()).remove()
                      $('#context'+g[1].trim()).remove()
                    }
                  }
                  console.log(contextFile)
                }
                $('.textareainfop').val("")
                $('#dialog #accordion').accordion( "refresh" );
              })//end push button
          })//end open file
          
        })//end click editfile
      }

      var urlS = "http://"+window.location.hostname+":8000/server";

      function sentMessage(){
        var text = $('textarea').val()
        if(text.length<=1)
          return ;
        $('#messages').append('<li class="konta" >Me:'+text+'</li>');
        $.post(urlS, {'action':'chatbot',
                  'message':text},
                    function(result){
                    console.log(result);
                    
                    $('#messages').append('<li class="kontb" >Bot:'+result['ans']+'</li>');
                    $('#backgroundofkont').scrollTop($('#backgroundofkont')[0].scrollHeight);
          });
        $('textarea').val("");
      }
      $('textarea').keyup(function(e){
          if(e.keyCode == 13){
            sentMessage()
          }
      })
      $('#sentmessage').click(sentMessage)
    }
  }
})(jQuery, Drupal, drupalSettings);
