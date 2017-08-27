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
      var argsP = drupalSettings.dm_movies.dm_movies;
      console.log(argsP);
  		$(document).on({
  		    ajaxStart: function() { $("body").addClass("loading");    },
  		    ajaxStop: function() { $("body").removeClass("loading"); }    
  		});

      function cl(a){
        console.log(a)
      }

      var liked = {}
      //liked['110'] = '3'
      var lm = {'0':'1.0','1':'2.0','2':'3.0','3':'4.0','4':'5.0'}

      function showInfo(mid){
        $.post(urlS, {'action':'movies_s',
                        'id':mid},
          function(result){
            console.log(result);
            var mov = result['ans']
            var actor = $('<div>')
            actor.html('<p><b>actors</b></p>')
            for(var k in mov[0]['actors'])
              actor.append(mov[0]['actors'][k]+'|')
            var cats = $('<div>')
            cats.html('<p><b>Categories</b></p>')
            for(var k in mov[0]['cats'])
              cats.append(mov[0]['cats'][k]+'|')
            var country = $('<div>')
            country.html('<p><b>country</b></p>')
            for(var k in mov[0]['country'])
              country.append(mov[0]['country'][k]+'|')
            $('#dialog').html(cats).append(actor).append(country).dialog()
          });
            
      }

      function showM(id, mov){
        $(id).empty()
        var dd = $('<div class="movies-row">')
        for (var i in mov){
          if (i>10)
            break
          var m = $('<div class="mmm" id="mov-'+mov[i]['id']+'">')
          var img = $('<img id="img-'+mov[i]['id']+'" src="'+mov[i]['url']+'" />')
          m.append(img)
          img.click(function(){//todo
            var mid = $(this).attr('id').split('-')[1]
            showInfo(mid)
          })
          m.append('<p>'+mov[i]['title']+'</p>')
          var mark = $('<div>')
          for(var j=0;j<5;j++){
            var bb = $('<button class="cl-'+mov[i]['id']+'" id="'+j+'+but-'+mov[i]['id']+'">')
            mark.append(bb.html(j+1))
            bb.click(function(){
              var mid = $(this).attr('id').split('-')[1]
              var vl = $(this).attr('id').split('+')[0]
              console.log([mid,vl])
              $(id+' .cl-'+mid).css('color','black')
              $(this).css('color','red')
              liked[mid] = vl
            })
            if(mov[i]['id'] in liked && liked[mov[i]['id']] == j)
              bb.css('color','red')
          }
          m.append(mark)
          dd.append(m)
          if((i % 3 == 2) || (mov.length == (parseInt(i)+1))){
            //cl([mov.length, i+1])
            $(id).append(dd)
            $(id).append('<div style="float:clear;"></div>')
            var dd = $('<div class="movies-row">')
          }
        }
        //$(id).append(dd)
      }
      
      $('#tabs-1 #Mheader').append('Search:<input type="text" id="searchM">');
      $('#tabs-1 #Mheader').append(' Category:<select  id="searchCat"></select>');
      var urlS = "http://"+window.location.hostname+":8000/server";
      $( "#tabs" ).tabs();



      $('#recodm').click(function(){
        
        if(Object.keys(liked).length < 3)
          $('#tabs-3 #Mbody').html('We need more than 3 movies')
        else{

          $('#tabs-3 #Mbody').html('ok')
          var ll = ''
          for(var k in liked){
            if(ll.length > 0)
              ll += ','
            ll += k +'-'+lm[''+liked[k]]
          }
          console.log(ll)
          console.log(lm)
          cl(liked)
          $.post(urlS, {'action':'movies_q',
                        'liked':ll},
                        function(result){
                          console.log(result);
                          showM('#tabs-3 #Mbody', result['ans'])
                          //$('#tabs-1 #Mbody').append('ok')
                        });
        }
      })

      function getM(pp,ids){
        $.post(urlS, {'action':'movies_s',
                        'id':ids},
                        function(result){
                          console.log(result);
                          showM(pp, result['ans'])
                          //$('#tabs-1 #Mbody').append('ok')
                        });
      }

      $('#likedM').click(function(){
        var op = ''
        for(var k in liked)
          {
            if(op.length > 0)
              op+=','
            op += k
          }
        if (op.length > 0)
          getM('#tabs-2 #Mbody',op)
      })

      var movies = {}

      $.post(urlS, {'action':'movies'},
                        function(result){
                          console.log(result);
                          showM('#tabs-1 #Mbody', result['ans'])
                          //$('#tabs-1 #Mbody').append('ok')
                          var op = []
                          movies  = result['mo']
                          for(var k in result['mo'])
                            op.push( { label: result['mo'][k][7], 
                              value:k})
                          $('#searchM').autocomplete({
                            source: op,
                            select: function( event, ui ) { 
                              $('#searchM').val('')
                              console.log(ui.item.value);
                              getM('#tabs-1 #Mbody', ui.item.value)
                            }
                          });
                          //$('#tabs-1 #Mheader #searchCat')
                          //.append('<option value="-1">Choose</option>')

                          for(var k in result['cat'])
                            $('#tabs-1 #Mheader #searchCat').append('<option value="'+
                              result['cat'][k]+'">'+k+'</option>')

                        });
      $('#tabs-1 #Mheader #searchCat').change(function(){
        var v = $('#tabs-1 #Mheader #searchCat').val()
        cl(v)
        var op = '', n = 0
        for(var k in movies){
          if($.inArray(parseInt(v) ,movies[k][6]))
            continue
          if(op.length >0)
            op += ','
          op+=k
          n+=1
          if(n>10)
            break
        }
        cl(op)
        if(op.length >0)
          getM('#tabs-1 #Mbody', op)
      })
      $('#searchM').change(function(){
        var v = $(this).val()
        cl(v)
      })
      
      }
  }
})(jQuery, Drupal, drupalSettings);
