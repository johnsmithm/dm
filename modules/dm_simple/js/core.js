
importScripts("/modules/dm_simple/js/subworkers.js");

// settings
var num_workers = 3;
var result = '';
var info;

onmessage = getInfo;

function getInfo(event) {
  info = event.data;
  onmessage = refreshInfo;
  info['middle'] = 50

  info['save'] = Math.min(info['save'],info['stargen'])
  lc(info['save'])
  info['maxLD-p'] = 100 //not used

  kd  = evolution(info['maxItt'])
  postMessage(['data',kd[0]]);
}

function refreshInfo(event) {
  var d = event.data;
  lc(['refrshhh',d])
  for(var i in d)
    info[i] = d[i]
}

//genetic
      function firstGeneration(nr){
        var g = []
        for(var i=0;i<nr;++i){
          g.push(makeGeneration())
        }
        if(info.hasOwnProperty('best'))
          g.push(info['best'])
        return g
      }
      function getConstrans(cls){
        var n = 0
        var moO = {}
        var tODH = {}
        var tD ={}
        var mD ={}
        for(var i in info['pr'])
          tD[i]={}
        for(var i in cls){//cl
          var nrO= 0
          for(var j=0;j<5;j++){//days
            for(var k=0;k<8;++k){//hours
              if(cls[i][j][k].length==1) 
                  cls[i][j][k].push(0)   
              else cls[i][j][k][1]=0   
              if(k>0 && k<7 ){
                //two lecture the same award, space bad
                if(cls[i][j][k][0]!=-1){
                  if(cls[i][j][k][0] == cls[i][j][k-1][0]){
                    n+=info['twoConsecutive'];
                    cls[i][j][k][1] +=info['twoConsecutive'];
                    cls[i][j][k-1][1] += info['twoConsecutive'];
                  }  
                  if(cls[i][j][k][0] == cls[i][j][k-1][0] && 
                    cls[i][j][k][0] == cls[i][j][k+1][0]){
                    n+=info['threeConsecutive'];
                    cls[i][j][k][1] +=info['twoConsecutive'];
                    cls[i][j][k-1][1] += info['twoConsecutive'];
                    cls[i][j][k+1][1] +=info['twoConsecutive'];
                  }
                }else{
                  if(cls[i][j][k][0] == -1 && cls[i][j][k-1][0] != -1 && 
                     cls[i][j][k+1][0] != -1){
                    n+=info['spaceB'];
                    cls[i][j][k][1] += info['spaceB'];
                  }
                }
              }  
              if(cls[i][j][k][0]!=-1){
                //check the total number
                nrO+=1
                //check maximum lectures                
                var oo = cls[i][j][k][0]
                var cc = inA(info['cl'],i) 
                var prop = i+'--'+cls[i][j][k][0]
                if (!moO.hasOwnProperty(prop))
                  moO[prop] = 1
                else moO[prop] += 1
                if(moO[prop]>info['coh'][cc][cls[i][j][k][0]]){
                  // look at coh array instead
                  n+=1000//return 1000                
                  cls[i][j][k][1] +=1000
                }//info['maxLD-p']

                // check two lecture same time teachers!!!
                var prop = info['t'][oo][cc]+'-'+j+'-'+k
                if(tODH.hasOwnProperty(prop)){
                  //lc(info['t'][oo][cc]+'-'+j+'-'+k)
                  //return 1000
                  n+=1000
                  cls[i][j][k][1] +=1000
                }
                //check max lectures in one day
                var mmprop = info['t'][oo][cc]+'-'+j+"-"+i
                if(mD.hasOwnProperty(mmprop))
                  mD[mmprop] += 1
                else mD[mmprop] = 1
                if(mD[mmprop]>info['maxLD']){
                  n += 1000
                  cls[i][j][k][1] += 1000
                }

                if(tD[info['t'][oo][cc]].hasOwnProperty(j)){
                  tD[info['t'][oo][cc]][j] += 1
                }else{
                  tD[info['t'][oo][cc]][j] = 1
                }
                tODH[prop]=0

                //check the teacher lecture are cluster in days

                //check the single space between lectures 

                //check the single space between lectures for teachers??
              }



              for(var q in info['const']){
                var t = info['const'][q]
                var oo = cls[i][j][k][0]
                var cc = inA(info['cl'],i)         
                if(oo == -1)
                  continue
                //#if(i=='clasa0')
                //
                //prof, ob, cl, zi, de, pana, pe
                if(t[0] != -1 && info['t'][oo][cc]!=t[0])
                  continue//prof
                if(t[1] != -1 && oo!=t[1])
                  continue//ob
                if(t[2] != -1 && cc!=t[2])
                  continue//clasa
                if(t[3] != -1 && j!=t[3])
                  continue//zi
                if( t[4] > k  )//??intre
                  continue//ora                
                if(t[5] < k  )//??intre
                  continue//ora
                //lc([info['t'][oo][cc],cc,oo,t,i])
                //lc(t)
                n+= t[6] - info['middle']
                cls[i][j][k][1] += t[6] - info['middle']
              }
            }
          }

          if(nrO!=info['nCls'][cc])
            return 100000
        }
        //how many lectures par day for teachers
        for(var i in info['pr'])
          for(var j in info['days']){
            if(tD[i].hasOwnProperty(j))
              n+= (8-tD[i][j])*info['dayTeacherPenalty']            
          }
        //error when it should be a lecture but it is not
        for(var i in info['cl']){          
          for(var j in info['days']){
            for(var k=0;k<8;++k){
              if(!moO.hasOwnProperty(info['cl'][i]+'--'+
                cls[info['cl'][i]][j][k][0]) && 
                info['coh'][i][cls[info['cl'][i]][j][k][0]]>0)
                n+=10000
            }
          }
        }
        //todo: find/show/edit in the UI best weigths ??
        return n
      }
      function fitness(g){
        var s = []
        for(var i in g){
          var n = 0
          //maybe use threads here
          n += getConstrans(g[i])
          //showT($('#tabs-3 #Mbody'),g[i])
          //lc(['score',n])
          //return 0
          //...
          s.push([n,i])
        }
        return s
      }
      function mutateGenezation(g, mu=10){
        for (var i=0;i<mu;++i){
          var t1 = []
          var nr = 0
          var st = rand(2,20) 
          var ff = rand(0,2) 

          var idc = rand(0,info['cl'].length-1) 
          do{
            nr+=1
            var idd = rand(0,4) 
            var idh = rand(0, 7) 
            //if(g[info['cl'][idc]][idd][idh][0] != -1 || ff != 0 )
              t1.push([g[info['cl'][idc]][idd][idh][1],idc,idd,idh,
                g[info['cl'][idc]][idd][idh][0]])
          }while(nr<st)
          if(t1.length>1){
            t1.sort(function(a,b){if (a[0] === b[0]) {
                                      return 0;
                                  }
                                  else {
                                      return (a[0] > b[0]) ? -1 : 1;
                                  }})
            if(true || ff!=2 && t1[0][1] == t1[1][1]){
              var x = g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]]//[0] = t1[1][4]
              var y = g[info['cl'][t1[1][1]]][t1[1][2]][t1[1][3]]
              g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]]= clone( y)
              //[0] = t1[0][4]
              g[info['cl'][t1[1][1]]][t1[1][2]][t1[1][3]] = clone( x)
              //g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]][1] = 0
              //g[info['cl'][t1[1][1]]][t1[1][2]][t1[1][3]][1] = 0
            }{
              var l = t1.length -1              
              //g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]][0] = t1[l][4]
              //g[info['cl'][t1[l][1]]][t1[l][2]][t1[l][3]][0] = t1[0][4]
             /* var x = g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]]//[0] = t1[1][4]
              var y = g[info['cl'][t1[l][1]]][t1[l][2]][t1[l][3]]
              g[info['cl'][t1[0][1]]][t1[0][2]][t1[0][3]]= clone( y)
              //[0] = t1[0][4]
              g[info['cl'][t1[l][1]]][t1[l][2]][t1[l][3]] = clone( x)
             */
            }

          }

        }
      }
      function mutate(g){
        var nn = []
        for (var i in g){
          //maybe use threads here
          var o1 = clone( g[i])
          mutateGenezation(o1)
          nn.push(o1)
          //mutateGenezation(g[i])
          //nn.push(g[i])
        }
        return nn
      }
       function evolution(nr){
        // fist generation
        var g = firstGeneration(info['stargen'])
        var ps = []
        for(var i=0;i<nr;++i){
          //mutate
          if(i>0)
          {
            if(info['workers']>1){
              for(var k =0; k< info['workers'];++k){//todo
                var g1 = mutate(g)
              }
            }else{
              var g1 = mutate(g)
            }
            //g1.push(g[0])
            //g=g1
          }
          //fitness
           //wait for multithreding too

          //lc(s)
          if(i>0){
            var s = fitness(g1)
            var gg = g1.length
            for(var j=0;j<info['save'];++j){
              ps[j][1] = j+gg
              s.push(clone(ps[j]))
              g1.push(clone(g[j]))
            }
            g=g1
            //g = g1.concat(g)
          }else{
            var s = fitness(g)
          }
          //kill
          s.sort(function(a,b){if (a[0] === b[0]) {
              return 0;
          }
          else {
              return (a[0] < b[0]) ? -1 : 1;
          }})
          ps = []// clone(s)
          if(info['stargen']>1)
            lc([s[0][0],s[1][0]])
          var g1 = []
          for(var j=0;j<info['save'];++j){
            g1.push(clone(g[s[j][1]])       )  
            ps.push(s[j])
            } 
          g = g1
          if(i%info['ddis'] == 0 || info['stop']==1){
            postMessage(['data1',g[0],i,s[0][0]]); 
            if(info['stop']==1)
             return g           
          }
          //$.extend( g, g1 );
        }
        return g
      }


      function makeGeneration(){
        var cls = {}
        var podh = {}
                
        for(var i  in info['cl']){//cl
          cls[info['cl'][i]] = []
          for(var ii  in info['days'])
            cls[info['cl'][i]] .push( [[-1],[-1],[-1],[-1],[-1],[-1],[-1],[-1],0])
          for(var j in info['ob']){//obiect
            for(var k=0;k<info['coh'][i][j];++k){
              // look at coh instead
              var n = 0
              var idD = 0
              var idL = 0  
              var prop = ''
              do{
                n+=1
                idD = 0
                do{
                  idD = rand(0,4)
                  //lc([idpr,info['ob'][j]])
                }while(cls[info['cl'][i]][idD][8] == 8)  
                idL = 0          
                do{
                   idL = rand(0,7)
                  //lc([idpr,info['ob'][j]])
                }while(cls[info['cl'][i]][idD][idL][0] != -1)
                prop = info['t'][j][i]+'-'+idD+'-'+idL
              }while(n<100 && podh.hasOwnProperty(prop))
              podh[prop] = 1
              cls[info['cl'][i]][idD][idL][0]=j
              cls[info['cl'][i]][idD][8] += 1
              if(info['cl'][i]==0 && j==9)
                lc(['searchit',idD,idL])
            }
          }
        }
        return cls
      }
      function rand(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
        //return Math.round(Math.random() * (max - min) + min);
      }
      function inA(a, v){
        for(var i in a)
          if(a[i]==v)
            return i
        return -1
      }
      function lc(a){console.log(a)}

function clone(obj) {
    var copy;

    // Handle the 3 simple types, and null or undefined
    if (null == obj || "object" != typeof obj) return obj;

    // Handle Date
    if (obj instanceof Date) {
        copy = new Date();
        copy.setTime(obj.getTime());
        return copy;
    }

    // Handle Array
    if (obj instanceof Array) {
        copy = [];
        for (var i = 0, len = obj.length; i < len; i++) {
            copy[i] = clone(obj[i]);
        }
        return copy;
    }

    // Handle Object
    if (obj instanceof Object) {
        copy = {};
        for (var attr in obj) {
            if (obj.hasOwnProperty(attr)) copy[attr] = clone(obj[attr]);
        }
        return copy;
    }

    throw new Error("Unable to copy obj! Its type isn't supported.");
}


function getEnd(event) {
  info = event.data;
  onmessage = null;
  // start the workers
  //for (var i = 0; i < num_workers; i += 1) {
    //var worker = new Worker('worker.js');
    //worker.postMessage(info);
    //worker.onmessage = storeResult;
  //}
}
// handle the results
function storeResult(event) {
  //event.data;
  postMessage(result); // finished!
}
