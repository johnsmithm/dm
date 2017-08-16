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
  		$(document).on({
  		    ajaxStart: function() { $("body").addClass("loading");    },
  		    ajaxStop: function() { $("body").removeClass("loading"); }    
  		});

      $('#buttons').append('<div style="width:300px;" id="messages"></div>'); 
      $('#buttons').append('<textarea name="message"></textarea>'); 
      $('#buttons').append('<input type="button" id="sentmessage" value="Sent">');

      var urlS = "http://"+window.location.hostname+":8000/server";

      function sentMessage(){
        var text = $('textarea').val()
        $('#messages').append('<p style="background-color:blue;text-align:right;">Me:'+text+'</p>');
        $.post(urlS, {'action':'chatbot',
                  'message':text},
                    function(result){
                    console.log(result);
                    
                    $('#messages').append('<p style="background-color:green">Bot:'+result['ans']+'</p>');
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
