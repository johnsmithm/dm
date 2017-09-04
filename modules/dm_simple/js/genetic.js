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
      var argsP = drupalSettings.dm_genetic.dm_genetic;
      console.log(argsP);
  		$(document).on({
  		    ajaxStart: function() { $("body").addClass("loading");    },
  		    ajaxStop: function() { $("body").removeClass("loading"); }    
  		});
      function rand(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
        //return Math.round(Math.random() * (max - min) + min);
      }
      function randomTeachers(info){
        var onv  = {}
        for(var i  in info['pr'])
          onv[info['pr'][i][0]] = []
        for(var i  in info['cl']){//cl
          for(var j in info['ob']){//obiect
            var nr = 0
            do{
              nr+=1
              var idpr = rand(0,info['pr'].length-1)
              //lc(idpr)
            }while(onv[info['pr'][idpr][0]].length > 0 && nr<100 &&
             onv[info['pr'][idpr][0]][0]!=j)
            //if(onv[info['pr'][idpr][0]].length == 0)
            onv[info['pr'][idpr][0]].push(j)
            //lc([idpr,info['ob'][j]])
            if(!info['pr'][idpr][1].hasOwnProperty(info['ob'][j])){
              var cdd = info['ob'][j]
              var rr = info['cl'][i]
              info['pr'][idpr][1][cdd] = {}
              info['pr'][idpr][1][cdd][rr] = 1
             // info['ob'][j] :
             //  {info['cl'][i]: 1}}
            }else{
              info['pr'][idpr][1][info['ob'][j]][info['cl'][i]] = 1
            }
          }
        }
        lc(onv)
      }
      function findTeachers(info){
        var tet = []
        for(var j in info['ob']){
          var ff = []
          for(var j in info['cl'])
            ff.push(-1)
          tet.push(ff)
        }
        for(var i  in info['pr']){//prof
          for(var j in info['pr'][i][1]){//obiect
            var obs= info['pr'][i][1][j]
            for(var k in obs){
              var ido = inA(info['ob'],j)
              var idc = inA(info['cl'],k)
              //lc([ido,idc,k,j])
              tet[ido][idc] = i
            }
          }
        }
        return tet
      }
      var ob = ['Limba romana','Limba franceza','Matematica','Fizica',
      'Istoria','Educatia Fizica','Informatica','Geografia','Biologia']
      var h = [4,2,4,2,2,2,2,2,2]
      var pr = []
      for(var i=0;i<20;i++)pr.push(['profesor'+i,{},{}])
      var days = ['Luni','Marti','Miercuri','Joi','Vineri']
      var cl = []
      for(var i=0;i<9;i++)cl.push('clasa'+i)
      var info = {'ob':ob, 'h':h, 'pr':pr, 'days':days, 'cl':cl, 'const':{} }
      function lc(x){console.log(x)}
      if(argsP['ok']==0){
        randomTeachers(info)
      }else{
        info = argsP['json']
      }
      if(argsP['nid']==-1){
        info['ob'] = []
        info['cl'] = []
        info['pr'] = []
      }
      
      lc(info)
      $('#buttonsx').append('<div id="backgroundofkont"><ul  id="messages"></ul></div>'); 
      $( "#tabs" ).tabs();
      $( "#tabs-1" ).tabs();
      function showPr(pr,id,title,f=function(x,i){return x}){
        var l = $('<ul>')
        for(var i in pr){
          var x = f(pr[i],i)
          l.append($('<li>').html(x))
        }
        id.html($('<h2>').html(title)).append(l)
      }
      function inA(a, v){
        for(var i in a)
          if(a[i]==v)
            return i
        return -1
      }
      function addCl(x,i){
        var s = $('<a href="#">').html(x[0])
        var tu = $('<div style="">')
        function addOb(x,i){
          var s = $('<a href="#">').html(i)
          var ta = $('<div style="">')
          var a = $('<input class="addCl" type="text">')
          a.autocomplete({
                              source: info['cl'],
                              select: function( event, ui ) { 
                                $(this).val('')
                                //console.log(ui.item.value);                              
                              }
                            });
          var b = $('<button>').html('Adauga Clase')
          
          b.click(function(){
            var v = $(this).parent().find('.addCl').val()
            lc(v)
            if (v.length>0){
              var n = inA(info['cl'],v)
              if (n==-1)
                return
              lc(n)
              //var i1 = $(this).parent().parent().attr('id').split('pr-')[1]
              //ls(i1)
              x[v] = 1
              lc(info)
            }
            $(this).parent().find('.addCl').val('')
            showPr(x ,ta,'clase',function(x,i){return i})
          })

          showPr(x, ta,'clase',function(x,i){return i})
          var d = $('<div id="pr-'+i+'">')
          var te = $('<div>')
          d.append(i).append(' ').append(a).append(b)
          te.append(ta)
          d.append(te)
          return d
        }//end function for adding classes to object


        //Obiecte
        var a1 = $('<input class="addOB" type="text">')
        a1.autocomplete({
                            source: info['ob'],
                            select: function( event, ui ) { 
                              $(this).val('')
                              //console.log(ui.item.value);                              
                            }
                          });
        var b1 = $('<button>').html('Adauga Obiecte')
        
        b1.click(function(){
          var v = $(this).parent().find('.addOB').val()
          lc(v)
          if (v.length>0){
            var n = inA(info['ob'],v)
            if (n==-1)
              return
            lc(n)
            info['pr'][i][1][v] = {}
            $(this).parent().find('div').show()
          }
          $(this).parent().find('.addOB').val('')
          showPr(info['pr'][i][1],tu,'obiecte',addOb)
        })
        
        showPr(info['pr'][i][1],tu,'obiecte',addOb)
        var d = $('<div id="pr-'+i+'">')
        var te = $('<div style="display:none;">')
        var sh = $('<span style="cursor:pointer;color:blue;">').text('+')
        sh.click(function(){
          if($(this).text()=='+'){
            $(this).parent().find('div').show()
            $(this).text('-')
          }else{
            $(this).parent().find('div').hide()
            $(this).text('+')
          }
        })
        d.append(sh)
        d.append(x[0]).append(' ').append(a1).append(b1)
        te.append(tu).append('<div style="clear: both;"></div>')
        d.append(te)
        return d
      }
      //call render functionsss
      function renderpross(){
        showPr(info['pr'],$('#tabs-1 #tabs-31'),'',addCl)
        var addpr = $('<input type="text">')
        addpr.focusout(function(){
          var v = $(this).val()
          if(v.length>3){  
            info['pr'].push([v,{},{}])      
            renderpross()
          
          }else{
            //alert('need more than 3 characters/trebuie mai mult de 3 caracter')
          }
          $(this).val("")
        })
        var delpr = $('<a href="#">').text('Delete All')
        delpr.click(function(){
                    info['pr']=[]
                    $('#tabs-1 #tabs-31 > ul').empty()
                    return false;
                })
        $('#tabs-1 #tabs-31').append('Add').append(addpr).append(delpr)
      }
      renderpross()
      //clase ## objs
      function clasWrapper(x,i){
        var nrcl = '<input type="text" id="myclassHoursid'+i+'">'
        //abstractChange(nrcl,info['h'],i,0,10,2)
        return x+'---Ore saptamana(hour/week)'+nrcl
      }
      function renderClases(aad=false){
        showPr(info['cl'],$('#tabs-1 #tabs-11'),'',obWrapper)
        if(!info.hasOwnProperty('coh') || aad){
          info['coh'] = []
          for(var i in info['cl']){
            var ff = []
            for(var j in info['ob'])
              ff.push(info['h'][j])
            info['coh'].push(ff)
          }
        }
        for(var i in info['cl']){
          $('#plusobject'+i).click(function(){
            if($(this).text()=='+ '){
              $(this).parent().find('ul').show()
              $(this).text('- ')
            }else{
              $(this).parent().find('ul').hide()
              $(this).text('+ ')
            }
          })

          for(var j in info['ob']){
            //lc([info['coh'],i])
            abstractChange($('#ffmyclassHoursid'+i+'-'+j),info['coh'][i],j,0,10,info['coh'][i][j])
          }
        }
        var addcls = $('<input type="text">')
        addcls.focusout(function(){
          var v = $(this).val()
          if(v.length>3){        
            
            info['cl'].push(v)
            renderClases(true)
            
          }else{
            //alert('need more than 3 characters/trebuie mai mult de 3 caracter')
          }
          $(this).val("")
        })
        var delcls = $('<a href="#">').text('Delete All')
        delcls.click(function(){
            info['cl']=[]
            info['coh'] = []
            renderClases(true)
            refrePr()
            return false;
        })
        $('#tabs-1 #tabs-11').append("add").append(addcls).append(delcls)
      }
      renderClases()
      
      function obWrapper(x,i){
        var tt = '<ul style="display:none;">'
        for(var j in info['ob'])
          tt += '<li>'+info['ob'][j]+
        '---Ore saptamana(hour/week)<input type="text" id="ffmyclassHoursid'+i+'-'+j+'"></li>'
        tt+= '</ul>'
        //abstractChange(nrcl,info['h'],i,0,10,2)
        return '<span style="cursor:pointer;color:blue;" id="plusobject'+i+'">+ </span>'+
        x+tt
      }
      showPr(info['ob'],$('#tabs-1 #tabs-21'),'',clasWrapper)
      for(var i in info['ob'])
        abstractChange($('#myclassHoursid'+i),info['h'],i,0,10,info['h'][i],
          function(){
          renderClases(true)
          lc('again')
        })

      var addObj = $('<input type="text">')
      addObj.focusout(function(){
        var v = $(this).val()
        if(v.length>3){          
          $('#tabs-1 #tabs-21 > ul').append('<li>'+v+'---Ore saptamana(hour/week)'
            +'<input type="text" id="myclassHoursid'+info['ob'].length
            +'"></li>')
          info['h'].push(2)
          abstractChange($('#myclassHoursid'+info['ob'].length),
            info['h'],info['ob'].length,
            0,10,info['h'][info['ob'].length],function(){
          renderClases(true)
          lc('again')
        })
          info['ob'].push(v)
          renderClases(true)
        }else{
          //alert('need more than 3 characters/trebuie mai mult de 3 caracter')
        }
        $(this).val("")
      })
      function refrePr(){
        for(var i in info['pr']){
          info['pr'][i][1] = {}
        }
        renderpross()
        //$('#tabs-1 #tabs-31 > ul').empty()
      }
      var delcls = $('<a href="#">').text('Delete All')
        delcls.click(function(){
            refrePr()
            info['ob']=[]
            info['ob']=[]
            info['coh'] = []
            renderClases(true)
            $('#tabs-1 #tabs-21 > ul').empty()
            return false;
        })
      $('#tabs-1 #tabs-21').append("add").append(addObj).append(delcls)




      //constrains
      var cp = $('<select id="prCt">')
      cp.append('<option value="0">Toti Profesorii</option>')
      for(var i in info['pr'])        
        cp.append('<option value="'+(parseInt(i)+1)+'">'+info['pr'][i][0]+'</option>')
      cp.change(function(){

        var v = parseInt($(this).val())

        lc(v)
        var oo = $('#obCt')
        oo.html('<option value="0">Toate Obiecte</option>')
        var cc = $('#clCt')
        cc.html('<option value="0">Toate Clasele</option>')
        if(v==0){
          for(var i in info['ob'])        
            oo.append('<option value="'+(parseInt(i)+1)+'">'+info['ob'][i]+'</option>')
           for(var i in info['cl'])        
            cc.append('<option value="'+(parseInt(i)+1)+'">'+info['cl'][i]+'</option>')
     
          return          
        }
        var ww = []
        for(var i in info['pr'][v-1][1])  {
          oo.append('<option value="'+(parseInt(inA(info['ob'],i))+1)+'">'+i+'</option>')
          for(var j in info['pr'][v-1][1][i])
            if(inA(ww,j)==-1){
              ww.push(j)
              cc.append('<option value="'+(parseInt(inA(info['cl'],j))+1)+'">'+j+'</option>')
            }
        }
      })
      //day      
      var dd = $('<select id="daysCt">')
      dd.append('<option value="0">Toate Zilele(all)</option>')
      for(var i in info['days'])        
        dd.append('<option value="'+(parseInt(i)+1)+'">'+info['days'][i]+'</option>')

      //object
      var oo = $('<select id="obCt">')
      oo.append('<option value="0">Toate Obiecte(all)</option>')
      for(var i in info['ob'])        
        oo.append('<option value="'+(parseInt(i)+1)+'">'+info['ob'][i]+'</option>')
     oo.change(function(){
        var v = parseInt($(this).val())        
        var v1 = parseInt($('#prCt').val())
        if(v==0 || v1==0)
          return
        lc(v)
        lc(v1)
        var cc = $('#clCt')
        cc.html('<option value="0">Toate Clasele(All)</option>')
        var ww = []
          for(var j in info['pr'][v1-1][1][info['ob'][v-1]])
            
              cc.append('<option value="'+(parseInt(inA(info['cl'],j))+1)+'">'+j+'</option>')
            
        
     })
     //clases
      var cc = $('<select id="clCt">')
      cc.append('<option value="0">Toate clasele(All)</option>')
      for(var i in info['cl'])        
        cc.append('<option value="'+(parseInt(i)+1)+'">'+info['cl'][i]+'</option>')
     

      var oo1 = $('<select id="dela">')
      var oo2 = $('<select id="panala">')
      for(var i=0;i<8;++i){        
        oo1.append('<option value="'+(i)+'">Lectia(lecture) '+(i+1)+'</option>')
        oo2.append('<option value="'+(i)+'">Lectia(lecture) '+(i+1)+'</option>')
      }
      oo2.val('7')

      var b = $('<button>').text('Add')
      b.click(function(){
        var pe = $('#pedeapsa').val()
        if(pe.length == 0 )
        {
          alert('add pedeapsa(score)')
          return
        }
        pe = Math.min(100,parseInt(pe))
        var t = []
        t.push(parseInt($('#prCt').val())-1)//prof
        t.push(parseInt($('#obCt').val())-1)//obiect
        t.push(parseInt($('#clCt').val())-1)//clasa
        t.push(parseInt($('#daysCt').val())-1)//zi
        t.push(parseInt($('#dela').val()))//dela
        t.push(parseInt($('#panala').val()))//pana la
        t.push(parseInt(pe))//depeapsa
        //prof, ob, cl, zi, de, pana, pe
          lc(t)
        addCons(t,getL(info['const']))
        info['const'][getL(info['const'])]=t
        lc(info)
      })
      function getL(x){
        var n = 0
        for(var i in x)
          n  = Math.max(parseInt(i),n)
        return n+1
      }
      function addCons(t,lll){
        if(t[0]!=-1 && t[1] != -1 && t[2]!=-1 ){
          if(!info['pr'][t[0]][1].hasOwnProperty(info['ob'][t[1]]) ||
            !info['pr'][t[0]][1][info['ob'][t[1]]].hasOwnProperty(info['cl'][t[2]]) ){
            lc('error')
          }
        }
        var a = $('<a href="#" id="dsd-'+lll+'">')
        

        var l = $('<li>')
        a.text('Sterge(delete)')
        var te = ''
        if(t[0]!=-1)
          te+=info['pr'][t[0]][0]+', '
        else
          te+='Toti profesorii(all teachers), '
        if(t[1]!=-1)
          te+=info['ob'][t[1]]+', '
        else
          te+='Toate obiecte(all lectures), '

        if(t[2]!=-1)
          te+=info['cl'][t[2]]+', '
        else
          te+='Toate clasele(all groups), '        
        if(t[3]!=-1)
          te+=info['days'][t[3]]+', '
        else
          te+='toate zilele(all days), '    
        te+='De la lectia(from)'+t[4]
        te+=', pana la lectia(to) '+t[5]
        te+=', scor(score):'+t[6]+ ' --'
        //prof, ob, cl, zi, de, pana, pe
        l.append(te)
        a.click(function(){
          var ii = $(this).attr('id').split('dsd-')[1]
          lc(['ddl',ii])
          delete info['const'][ii]
          $(this).parent().remove()
          return false;
        })
        l.append(a)
        $('#constrains').prepend(l)
      }
      function abstractChange(id, ob,prop, mmin, mmax,vv,fu=function(){}){
        
        ob[prop] = vv
        id.val(vv)
        id.change(function(){
          var v = id.val()
          if(v.length>0)
            v = parseInt(v)
          else v = mmin
          if(isNaN(v))
            v = mmin
          v = Math.max(mmin,v)
          v = Math.min(mmax,v)
          ob[prop] = v
          lc([prop,v])
          fu()
        })
        id.focusout(function(){
          id.val(ob[prop])
        })
        
      }
      var bigC = $('<div id="bifcons">')
      var dayTeacherPenalty = $('<input type="text" id="dayTeacherPenalty">')
      abstractChange(dayTeacherPenalty,info,'dayTeacherPenalty',0,100,5)
      var twoConsecutive = $('<input type="text" id="twoConsecutive">')
      abstractChange(twoConsecutive,info,'twoConsecutive',-100,0,-10)
      var threeConsecutive = $('<input type="text" id="threeConsecutive">')
      abstractChange(threeConsecutive,info,'threeConsecutive',0,100,5)
      var spaceB = $('<input type="text" id="spaceB">')
      abstractChange(spaceB,info,'spaceB',0,100,1)

      bigC.append('Day Teacher penalty').append(dayTeacherPenalty)
      bigC.append('twoConsecutive Lectures').append(twoConsecutive)
      bigC.append('<br/>threeConsecutive Lectures').append(threeConsecutive)
      bigC.append('space between lectures').append(spaceB)
      


      $('#tabs-2 #Mbody').append(bigC).append(cp)
      $('#tabs-2 #Mbody').append(oo).append(cc).append(dd)
      $('#tabs-2 #Mbody').append('De la(from)').append(oo1).append('pana(to)').append(oo2)
      $('#tabs-2 #Mbody').append(' Pedeapsa(score):<input type="number" min="0" max="100" id="pedeapsa">')
      $('#tabs-2 #Mbody').append(b).append('<ul id="constrains"></ul>')

      
      for(var i in info['const'])
        addCons(info['const'][i],i)
      //the genetic alg

      function checkAll(){
        //todo: take into account the costum number of lectures for each group
        var cls = {}
        var nCl = []
        for(var i  in info['cl']){
          cls[info['cl'][i]] = []
          var nr = 0
          for(var j in info['ob'])
            if(info['coh'][i][j]>0)
              nr+=1
            nCl.push(nr)
        }
        info['nCl'] = nCl
        for(var i  in info['pr']){//prof
          var obs = info['pr'][i][1]
          for(var j in obs){//obiect
            var ccl = obs[j]
            for(var k in ccl){//clasa
              if(inA(cls[k],j)!=-1){//[j,i]
                alert('2 profesori pentru obiectul:'
                  +j+' pentru clasa :'+ k+
                  '/two teachers for one object:'
                  +j+' for group :'+ k)
                return false
              }
              cls[k].push(j)
            }
          }
        }
        for(var i  in info['cl']){
          //lc([i,cls[info['cl'][i]],info['cl'][i]])
          if(cls[info['cl'][i]].length != nCl[i]){
            alert('sunt '+cls[info['cl'][i]].length+' obiecte pentru clasa '+
              info['cl'][i]+', ar trebui sa fie '+nCl[i]+
              '/there are '+cls[info['cl'][i]].length+' lectures for group '+
              info['cl'][i]+', must be '+nCl[i])
            return false
          }
        }
        return true
      }
      function download(text, name, type) {
          var a = document.createElement("a");
          var file = new Blob([text], {type: type});
          a.href = URL.createObjectURL(file);
          a.download = name;
          a.click();
      }
      // config
      var maxL = 8
      var maxLD = 2
      var save = 10
      var stargen = 20
      var maxItt = 200
      var best = []

      function showT(id, cls){
        var tt  = $('<table border="1">')
        var tr = $('<tr>')
        var colors=['black','red','blue','green']
        tr.append('<td></td>')
        tr.append('<td></td>')
        for(var j=0;j<5;j++)
          tr.append('<td>'+info['days'][j]+'</td>')
        tt.append(tr)
        for(var i in cls){//cl
          for(var k=0;k<8;++k){//hours
            var tr = $('<tr>')
            if(k==0)
              tr.append($('<td rowspan="8">').text(i))
            tr.append($('<td>').text('L.'+k))
            for(var j=0;j<5;j++){//days
              //lc([i,j,k])
              var x = cls[i][j][k][0]
              if (x==-1)
                x='-'
              else{
                var oo = cls[i][j][k][0]
                var cc = inA(info['cl'],i)  
                x = info['ob'][x]+':<br/>'+info['pr'][info['t'][oo][cc]][0]
              } 
              var cid = 0 
              if(cls[i][j][k][1]>0)
                cid = 1
              if(cls[i][j][k][1]<0)
                cid = 3
              tr.append($('<td>').html(x).css('color',colors[cid]))
            }
            tt.append(tr)
          }
        }
        id.html(tt)

        //show profesori
        for (var ji in info['pr']){
          id.append($('<h3>').text(info['pr'][ji][0]))
          var tt  = $('<table border="1">')
          var tr = $('<tr>')
          var colors=['black','red','yellow','green']
          tr.append('<td></td>')
          for(var j=0;j<5;j++)
            tr.append('<td>'+info['days'][j]+'</td>')
          tt.append(tr)
          
            for(var k=0;k<8;++k){//hours
              var tr = $('<tr>')
              tr.append($('<td>').text('L.'+k))
              for(var j=0;j<5;j++){//days
                //lc([i,j,k])
                var x1="-"
                for(var i in cls){//cl??
                  var x = cls[i][j][k][0]
                  if (x==-1 )
                    x='-'
                  else{
                    var oo = cls[i][j][k][0]
                    var cc = inA(info['cl'],i)  
                    if(info['pr'][ji][0] == info['pr'][info['t'][oo][cc]][0]){
                      x1 += info['ob'][x]+'<br/>'+i
                      var cid = 0 
                      if(cls[i][j][k][1]>0)
                        cid = 1
                      if(cls[i][j][k][1]<0)
                        cid = 3
                      if(x1.split('<br/>').length>2)
                        cid = 2
                      //break
                    }
                    else x = '-'
                  } 
                  /*var cid = 0 
                  if(cls[i][j][k][1]>0)
                    cid = 1
                  if(cls[i][j][k][1]<0)
                    cid = 3*/
                  
              }              
              tr.append($('<td>').html(x1).css('color',colors[cid]))
            }
            tt.append(tr)
          }
          id.append(tt)
        }

      }
      
      if(info.hasOwnProperty('best')){
        showT($('#tabs-3 #Mbody'),info['best'])
        //delete info['best']
      }

      var b1 = $('<button id="startBu">').text('start')
      var b3 = $('<button id="stopbuto">').text('stop')
      var b2 = $('<button>').text('download settings')
      var worker = {}
      b3.hide()
      b3.click(function(){
        worker.terminate()//postMessage({'stop':1});
        b1.show()
        b3.hide()
      })
      b1.click(function(){
        lc(1)
        if(!checkAll())return
        info['t'] = findTeachers(info)//can be done inside worker
        lc(info['t'])
        info['nCls'] = []
        for (var i in info['cl'])
        {
          var rrr = 0
          for(var j in info['ob'])
            rrr+= info['coh'][i][j]
          info['nCls'].push(rrr)
        }         
        lc(['nCls',info['nCls']])
        //info['stargen'] = stargen
        info['save'] = save
        info['workers'] = 1
        info['stop'] = 0
        b1.hide()
        b3.show()
        var startDate = new Date();
        //var kd  = evolution(maxItt)
         worker = new Worker('/modules/dm_simple/js/core.js');
         worker.postMessage(info);
         worker.onmessage = function (event) {
           //document.getElementById('buttons').textContent = event.data;
            lc(event.data)
            if(event.data[0]=='data'){
              showT($('#tabs-3 #Mbody'),event.data[1])
              $('#ddisplay span').text('Finished')
              $('#progress').progressbar({
                value: 100
              });
              b1.show()
              b3.hide()
              info['best'] = event.data[1]
            }
            else if(event.data[0]=='data1'){
              $('#progress').progressbar({
                value: (event.data[2]/info['maxItt'])*100
              });
              var endDate = new Date();
              var seconds = (endDate.getTime() - startDate.getTime()) / 1000;
              startDate = new Date();
              $('#ddisplay span').text(''+event.data[2]+' iterations of '
                +info['maxItt']+". Time:"+seconds+'s. Score:'+event.data[3])
              lc(['display',i])
              showT($('#tabs-3 #Mbody'),event.data[1])
              info['best'] = event.data[1]
            }
         };
        //var kd = makeGeneration()
        
      })
      b2.click(function(){
        lc(1)
        if(!checkAll())return
        download(JSON.stringify(info), 'test.txt', 'text/plain');
      })
      var itt = $('<input type="number" min="0" max="10000" id="itteration">')
      abstractChange(itt,info,'maxItt',1,10000000,200)
      var mLecturesDay = $('<input type="number" min="0" max="4" id="mLecturesDay">')
      abstractChange(mLecturesDay,info,'maxLD',1,10,2)
      var stargen1 = $('<input type="number" min="0" max="100" id="stargen">')
      abstractChange(stargen1,info,'stargen',1,100,10)
      
      var display = $('<input type="number" min="0" max="10000" id="display">')
      abstractChange(display,info,'ddis',1,100000,100)

      var htab3 = $('<div></div>')
      htab3.append('Generations:').append(itt)
      htab3.append('Items:').append(stargen1)
      htab3.append('Max LEctures Day:').append(mLecturesDay)
      htab3.append('Display:').append(display)

      $('#tabs-3 #Mheader').append(htab3)
      $('#tabs-3 #Mheader').append(b1).append(b3).append(b2)
      
      var add = $('<button>').text('add File setting')
      add.click(function(){
        $.post('/dm_simple/ajax', {'action':'addGenetic'},
                        function(result){
                          lc(result)
                          location.reload();
                        })
      })
      var defaul = $('<button>').text('tryDefault')
      defaul.click(function(){
        $.post('/dm_simple/ajax', {'action':'tryGenetic'},
                        function(result){
                          lc(result)
                          location.reload();
                        })
      })
      var reset = $('<button>').text('reset')
      reset.click(function(){
        $.post('/dm_simple/ajax', {'action':'resetGenetic'},
                        function(result){
                          lc(result)
                          location.reload();
                        })
      })
      var ddiv = $('<div id="ddisplay">')
      ddiv.append('<div id="progress"></div>').append('<span>')
      $('#buttons').append(ddiv).append('</div>').append(add)//for live
      $('#buttons').append(reset).append(defaul)

      //https://www.w3.org/TR/workers/ #Multicore computation
       //https://github.com/dmihal/Subworkers
      $('#infohelp').append($('<a href="/how_to_use" target="_blank">').text('Help'))

    }
  }
})(jQuery, Drupal, drupalSettings);//Vicamamica12
