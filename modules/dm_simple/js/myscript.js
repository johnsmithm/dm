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
      var argsP = drupalSettings.dm_simple.dm_simple;
      argsP.width = window.innerWidth;
      console.log(argsP);
      var imgId = '';
      var stage = {};
      function Stage (args) {
		    this.type = args.type;
		    this.stage = new Konva.Stage({
	          container: args.container,
	          width: args.width,
	          height: args.height
	        });
	        this.layerI = new Konva.Layer();
	        this.layerB = new Konva.Layer();
	        this.layerL = new Konva.Layer();
	        this.initX = 20;
	        this.initY = 20;
	        this.keyboardMv = "";
	        this.po = args.po;
	        this.scale = args.scale;
	        this.ratio = (1.0*args.width/args.imgW)*this.scale;
	        this.imgW = args.imgW;
	        this.imgH = args.imgH;
	        this.hLines = args.hLines;
	        this.offsety = args.offsety;
	        this.offsetx = args.offsetx;
	        this.vLines = args.vLines;
	        this.predictions  = args.predictions;
	        this.addSquare = function(){

		        var boxs = [];
		        var po = [];
		        var poly = {};
		        var layer = this.layerL;
		        var layer1 = this.layerB;
		        var keyboardMv = this.keyboardMv;
	        	function updateSquare(ratio,xx,yy){
		          for (var i=0;i<4;i++){		            
		            po[i*2] = boxs[i].getX();
		            po[i*2+1] = boxs[i].getY();
		            argsP.po[i][0] = [boxs[i].getX()-xx]*(1.0/ratio);
		            argsP.po[i][1] = [boxs[i].getY()-yy]*(1.0/ratio);
		            console.log(ratio);
		            console.log(argsP.po);
		          }
		          poly = new Konva.Line({
		                points: po,
		                stroke: 'black',
		                strokeWidth: 5,
		                closed : true,
		                id:'poly'
		              });
		          layer.draw();
		        }
		        for (var i=0;i<4;i++){
		            po.push(this.initX+this.po[i][0]*this.ratio);
		            po.push(this.initY+this.po[i][1]*this.ratio);
		            var box = new Konva.Rect({
		                  x: this.initX+this.po[i][0]*this.ratio,
		                  y: this.initY+this.po[i][1]*this.ratio,
		                  width: 20,
		                  height: 20,
		                  fill: '#00D2FF',
		                  stroke: 'black',
		                  strokeWidth: 4,
		                  draggable: true,
		                  id: 'myRect_'+i,
		              });
		            var ratio = this.ratio;
		            var xx = this.initX;
		            var yy = this.initY;
		            box.on('dragend yChange xChange', function() {
		              //document.body.style.cursor = 'default';
		              updateSquare(ratio,xx,yy);
		              keyboardMv = this.getId();
		            });
		            box.on('mouseover', function() {
			              document.body.style.cursor = 'pointer';
			          });
			        box.on('mouseout', function() {
			              document.body.style.cursor = 'default';
			          });
		            boxs.push(box);
		            this.layerB.add(box);
		        }
		        poly = new Konva.Line({
		              points: po,
		              stroke: 'black',
		              strokeWidth: 5,
		              closed : true,
		              id:'poly'
		            });
		        this.po = po;
		        this.layerL.add(poly);

		        document.addEventListener("keydown", keyDownTextField, false);

			      function keyDownTextField(e) {
			      	var keyCode = e.keyCode;
			        //console.log(keyCode);
			        if(keyboardMv=='' || keyboardMv=="" ||
			         layer1.find('#'+keyboardMv).length==0)return;
			        var b=layer1.find('#'+keyboardMv)[0];
			        if(keyCode==87) {//up w
			          b.y(b.y()-1);          
			        }else if(keyCode==83) {//down s
			          b.y(b.y()+1);
			        }else if(keyCode==65) {//right a
			          b.x(b.x()-1);
			        }else if(keyCode==68) {//left d
			          b.x(b.x()+1);
			        }
			        layer1.draw();
			      }
	        }
	        this.addImage = function(layer, path, width, height){
	        	var imgK = new Konva.Image({
		            x: this.initX,
		            y: this.initY,
		            width: width,
		            height: height,
		            stroke: 'red',
		            strokeWidth: 1
		        });

		        layer.add(imgK);

		        var image = new Image();
		        image.onload = function() {
		            imgK.image(image);
		            layer.draw();
		        };
		        image.src = path;
		        return image;
	        }
	        this.lineControl = function(layerC,obj,Lines,n){
	        	var mt = ['x','y']; var d = ['V','H'];
	        	var nr = parseInt(obj.getId().split('_')[1]);
	        	if(nr >= Lines.length-2)
	        		return;
	        	var yy1 = parseFloat( layerC.find('#line'+d[n]+'_'+(nr+1))[0][mt[n]]());
                var yy0 = parseFloat(obj[mt[n]]());
                var p0 = obj.getPoints()[n];
                var p1 = layerC.find('#line'+d[n]+'_'+(nr+1))[0].getPoints()[n];
                console.log(yy0,yy1);
                var rule = parseFloat(prompt("length", ""+( Math.abs(p1+yy1-p0 - yy0))));
                console.log(rule);
                if(rule === null || rule<=0){
                	return;
                }
                if(rule.isNann)
                for(var j=nr+1;j<Lines.length;j++){
                  var pp = layerC.find('#line'+d[n]+'_'+j)[0].getPoints()[n];//p[1]
                  layerC.find('#line'+d[n]+'_'+j)[0][mt[n]](rule*(j-(nr))+yy0+(p0-pp));
                }
                layerC.draw();

                $('#dialog').html('<div id="slider"></div>').dialog({width:500,buttons:{}});
                $( "#slider" ).slider({range: "max",
                  min: parseInt((rule-10)*10),
                  max: parseInt((rule+10)*10),
                  value: parseInt((rule)*10),slide: function( event, ui ) {
                  //alert( ui.value );
                  
                  if($('#selectDMS').val()=='2'){
                          var yy1 = parseFloat( layerC.find('#line'+d[n]+'_'+(nr+1))[0][mt[n]]());
                          var yy0 = parseFloat(layerC.find('#line'+d[n]+'_'+(nr))[0][mt[n]]());
                          var p0 = layerC.find('#line'+d[n]+'_'+(nr))[0].getPoints()[n];
                          var p1 = layerC.find('#line'+d[n]+'_'+(nr+1))[0].getPoints()[n];
                          console.log(yy0,yy1);
                          var rule = parseFloat(ui.value/10.);
                          for(var j=nr+1;j<Lines.length;j++){
                            var pp = layerC.find('#line'+d[n]+'_'+j)[0].getPoints()[n];//p[1]
                            layerC.find('#line'+d[n]+'_'+j)[0][mt[n]](rule*(j-nr)+yy0+(p0-pp));
                          }
                          layerC.draw();
                        
                  }
                }});//end slider
	        }
	        this.addLines = function(){
	        	var keyboardMv = '';
	        	var f = this.lineControl;
		      	var layer1 = this.layerL;
		      	var lines = this.vLines;
	        	for(var i in this.vLines){
		          var line = new Konva.Line({
		            points: [this.initX+this.vLines[i]*this.ratio, this.initY,
		            this.initX+ this.vLines[i]*this.ratio, 
		            this.initY+this.imgH*this.ratio],
		            stroke: 'red',
		            strokeWidth: 5,
		            lineCap: 'round',
		            lineJoin: 'round',
		            draggable: true,
		            id:'lineV_'+i
		          });

		          line.on(' yChange xChange', function() {
		              //document.body.style.cursor = 'default';
		              keyboardMv = this.getId();
		            });
		          line.on('dragend  ', function() {
		          	if($('#selectDMS').val()=='2')
		              f(layer1,this,lines,0);
		            });
		      		line.on('mouseover', function() {
			              document.body.style.cursor = 'pointer';
			          });
			        line.on('mouseout', function() {
			              document.body.style.cursor = 'default';
			          });
		      		this.layerL.add(line);
		      	}
		      	var lines1 = this.hLines;
		      	for(var i in this.hLines){
		          var line = new Konva.Line({
		            points: [this.initX,this.initY+ this.hLines[i]*this.ratio, 
		            this.initX+this.imgW*this.ratio,
		            this.initY+this.hLines[i]*this.ratio],
		            stroke: 'red',
		            strokeWidth: 5,
		            lineCap: 'round',
		            lineJoin: 'round',
		            draggable: true,
		            id:'lineH_'+i
		          });  
		          line.on(' yChange xChange', function() {
		              //document.body.style.cursor = 'default';
		              keyboardMv = this.getId();
		            });
		          line.on('dragend  ', function() {
		          	if($('#selectDMS').val()=='2')
		              f(layer1,this,lines1,1);
		            });
		          //console.log(i);
		      		line.on('mouseover', function() {
			              document.body.style.cursor = 'pointer';
			          });
			        line.on('mouseout', function() {
			              document.body.style.cursor = 'default';
			          });
		      		this.layerL.add(line);
		      	}
		      	document.addEventListener("keydown", keyDownTextField, false);

			    function keyDownTextField(e) {
			      	var keyCode = e.keyCode;
			        //console.log(keyCode);
			        if(keyboardMv=='' || keyboardMv=="" ||
			         layer1.find('#'+keyboardMv).length==0)return;
			        var b=layer1.find('#'+keyboardMv)[0];
			        if(keyCode==87) {//up w
			          b.y(b.y()-1);          
			        }else if(keyCode==83) {//down s
			          b.y(b.y()+1);
			        }else if(keyCode==65) {//right a
			          b.x(b.x()-1);
			        }else if(keyCode==68) {//left d
			          b.x(b.x()+1);
			        }
			        layer1.draw();
			      }
	        }	
	        this.cloneIs = [];
	        this.add_i = function(layer,cell,row,k){
	          var ox = 0, oy = 0;
	          var ll = {'11':'a','12':'-','13':'x'};
	          if(this.offsety.length>0){
	            ox = this.predictions[0].cells[this.offsetx[0]].x;
	            oy = this.predictions[this.offsety[0]].y;
	          }
	            var cloneI = new Konva.Image({
	                id:'img-0-'+cell.col+'-'+row.row,
	                draggable: true,
	                stroke: 'red',
	                strokeWidth: 0
	            });

	            layer.add(cloneI);            
	            
	            //clones.push(clone);            
	            
	            cloneI.crop({
	                  x: parseInt(cell.x),
	                  y: parseInt(row.y),
	                  width: cell.width,
	                  height: row.height
	                });
	                cloneI.width(cell.width);
	                cloneI.height(row.height);
	                cloneI.scale({  x: this.ratio , y: this.ratio });
	                cloneI.y((row.y-oy)*this.ratio);
	                cloneI.x((cell.x-ox)*this.ratio);

	            var layer2 = this.layerI;
	            var layer1 = this.layerL;
	            var customCorrector = function(){
	            	if($( "#selectDMS" ).val()=='30'){
	            		$('#dialog').html('First colum:<input id="mds_col" name="col"><br />'+
						'Number colums:<input id="mds_cols" name="cols"><br />'+
						'First row:<input id="mds_row" name="row"><br />'+
						'Number rows:<input id="mds_rows" name="rows"><br />'+
						'Model:').append(modelS)
						.dialog({buttons: {
					        Ok: function() {
					          if($('#mds_cols').val()=='' || $('#mds_rows').val()=='' ||
					          	$('#mds_col').val()=='' || $('#mds_row').val()==''){
					          	alert('Set the number of colums and rows!');
					          }else{	

					          }
					      }}})
	            	}
	            }
	            cloneI.on('click', function() {
	                  imgId = this.getId();
	                  if($( "#selectDMS" ).val()=='1' ||
	                  	$( "#selectDMS" ).val()=='2'){
		                  this.stroke('green');
		                  this.strokeWidth(2);
		                  console.log(this.getId());
		                  
		                  var groupMI = layer2.find('#groupM')[0];
		                  var xx = parseInt(this.x());
		                  var yy = this.y();
		                  if(xx>350)
		                    xx = xx - 280;
		                  console.log(xx,yy);
		                  groupMI.x(xx);
		                  groupMI.y(yy);
		                  layer2.show();
		                  layer2.draw();
		                  layer1.draw();
		               }else
		               		customCorrector();
	              });


	            var simpleText = new Konva.Text({
	              x: (cell.x-ox)*this.ratio,//+(cell.width*this.ratio)/3,
	              y: (row.y-oy)*this.ratio,// +(row.height*this.ratio)/3,
	              text: cell.prediction>10?ll[cell.prediction]:cell.prediction,
	              fontSize: 12,
	              fontFamily: 'Calibri',
	              fill: 'green',
	              id:'Timg-0-'+cell.col+'-'+row.row
	            });

	            layer.add(simpleText);     

	            this.cloneIs.push(cloneI);
	            //tooltipLayer.batchDraw();tooltipLayer.batchDraw();
	        }
	        this.addPreditions = function(){
	        	  //layerP[0].add(cloneI);
		          var clone = new Image();      
		          var  k =0;

		          for (var i in this.predictions){
		            if(this.offsety.length>0 &&(
		             this.offsety[0]>i || this.offsety[0]+this.offsety[1]<=i))
		              continue;
		            var row = this.predictions[i];
		            for (var j in row.cells){
		              if(this.offsetx.length>0 &&(
		                this.offsetx[0]>j || this.offsetx[0]+this.offsetx[1]<=j))
		                continue;
		              var cell = row.cells[j];
		              //todo: calculate scale factor!!!!
		              this.add_i(this.layerL,cell,row,k);              
		              
		              k+=1;
		              //layerP.add(clone);
		            }
		            if(i==4 && false)
		              break;
		          }
		          var layer = this.layerL;
		          var cloneIs1 = this.cloneIs;
		          clone.onload = function() {
		                //var t = this.target();
		                //var id = parseInt(t.getId().split('_')[1]);
		                for(var i in cloneIs1)
		                  cloneIs1[i].image(clone);               

		                //console.log('-',cloneI.width(),cloneI.height());
		                layer.draw();
		                //layer.batchDraw();
		          };
		          clone.src = args.path_i;
		          //stage.add(layerP[0]);
	        }
	        
	        this.addCorrector = function(){
	        	var ll = {'11':'a','12':'-','13':'x'};
	        	var groupM = new Konva.Group({
		              draggable: true,
		              id:'groupM'
		          });

		        var colors = ['red', 'orange', 'yellow', 'green', 'blue', 'purple'];
		        var box = new Konva.Rect({
		                  x: 30,
		                  y: 40,
		                  width: 300,
		                  height: 25,
		                  name: 'red',
		                  fill: 'red',
		                  stroke: 'black',
		                  strokeWidth: 2
		              });
		        groupM.add(box);

		        for(var i = 0; i <= 13; i++) {
	              var simpleLabel = new Konva.Label({
	                  x: 35 + i*20,
	                  y: 44,
	                  opacity: 0.75,
	                  id: i
	              });
	              simpleLabel.add(new Konva.Tag({
	                  fill: 'yellow'
	              }));
	              simpleLabel.add(new Konva.Text({
	                  text: i>10?ll[''+i]:''+i,
	                  fontFamily: 'Calibri',
	                  fontSize: 15,
	                  padding: 3,
	                  fill: 'black'
	              }));
	              var layer = this.layerL;
	              var layer1 = this.layerI;
	              function setNewPrediction(predictions,colId,rowId,pred){
		            console.log(colId,rowId,pred);
		            for(var i in predictions){
		              if(predictions[i].row==rowId){
		                for(var j in predictions[i].cells){
		                  if(predictions[i].cells[j].col==colId){
		                    predictions[i].cells[j].prediction = pred;
		                    console.log('founded!');
		                    return;
		                  }
		                }
		              }
		            }
		          }
		          var predictions = this.predictions;
		          var offsety = this.offsety;
	              simpleLabel.on('click', function() {

	                  console.log(this.getId());
	                  if (this.getId()!='13'){             
	                    var textI = layer.find('#T'+imgId)[0];
	                    textI.text(this.getId()>10?ll[this.getId()]:this.getId());
	                    var colId = parseInt(imgId.split('-')[2]); 
	                    var rowId = parseInt(imgId.split('-')[3]);  
	                    setNewPrediction(predictions,colId,rowId,parseInt(this.getId()));
	                    if($( "#selectDMS" ).val()=='2'){
	                    	console.log(imgId);
		                  	for (var ii in predictions){
		                  		if(offsety.length>0 &&(
					             offsety[0]>ii || offsety[0]+offsety[1]<=ii))
					              continue;
		                  		console.log('#Timg-0-'+
		                        	colId+'-'+predictions[ii].row);
		                        var textI = layer.find('#Timg-0-'+
		                        	colId+'-'+predictions[ii].row)[0];
		                        textI.text(this.getId()>10?ll[this.getId()]:this.getId());
		                        setNewPrediction(predictions,colId,predictions[ii].row,
		                        	parseInt(this.getId()));
		                    }
		                  }         
	                  }        
	                  var imgM = layer.find('#'+imgId)[0];
	                  imgM.stroke('red');
	                  imgM.strokeWidth(0);
	                  layer.draw();
	                  layer1.hide();	     
	                               
	              });
	              simpleLabel.on('mouseover', function() {
	              	this.getTag().fill('green');
	              	layer1.draw();
		          });
		          simpleLabel.on('mouseout', function() {
		            this.getTag().fill('yellow');
		            layer1.draw();
		          });
	              groupM.add(simpleLabel);
	          }
	          groupM.on('mouseover', function() {
	              document.body.style.cursor = 'pointer';
	          });
	          groupM.on('mouseout', function() {
	              document.body.style.cursor = 'default';
	          });
	          this.layerI.add(groupM);
	        }
	        this.addChoiseImages = function(){

	        	for(var i=0;i<4;++i)
	        		for(var j=0;j<5;++j){
	        			if(args.path_i.length <= i*3+j)
	        				continue;
	        			function addI(xx,yy,path_i,layer){
	        				var imgK = new Konva.Image({
					            x: xx,
					            y: yy,
					            width: 200,
					            height: 310,
					            stroke: 'red',
					            strokeWidth: 1
					        });
					        layer.add(imgK);

					        var image = new Image();
					        image.onload = function() {
					            imgK.image(image);
					            layer.draw();
					        };
					        console.log(path_i);
					        image.src = path_i[0];
					        imgK.on('click', function() {
					        	argsP.path_i  = path_i[0];
					        	argsP.imgH = path_i[2];
					        	argsP.imgW = path_i[1];
					        	argsP.fid = path_i[3];
					        	$.post(urlS, {'action':'square',
									'height':argsP.imgH,
									'width':argsP.imgW,
									'path':argsP.path_i.split('http://localhost:8006/')[1]},
										function(result){
										console.log(result);
										argsP.type = 'square';
										argsP['po'] = result['po'];
										//args = argsP;
										//var history = {};
										stage = new Stage(argsP);
										stage.logic();
										$('#buttons').show();
										if(localStorage['helpDMS']=='1')
											helpVideos();
										//logicAlias();
									});
					        })
	        			}
			        	addI(this.initX+210*j,
			        		this.initY+410*i,
			        		args.path_i[i*3+j],
			        		this.layerI, this);
				    }
	        	
	        }
		    this.logic = function() {
		    	$('#downloadExcel').hide();
		    	if(this.type != 'pred' && this.type != 'init')
		    		this.addImage(this.layerI, args.path_i, 
		    			args.imgW*this.ratio,args.imgH*this.ratio);
		    	if(this.type == 'square'){
		    		this.addSquare();
		        	this.stage.add(this.layerI);
		        	this.stage.add(this.layerL);
		        	this.stage.add(this.layerB);
		    	}else if(this.type == 'lines'){
		    		this.addLines();
		        	this.stage.add(this.layerI);
		        	this.stage.add(this.layerL);
		    	}else if(this.type == 'pred'){
		        	this.addPreditions();
		        	this.addCorrector();
		        	this.stage.add(this.layerL);
		        	this.layerI.hide();
		        	this.stage.add(this.layerI);
		        }else if(this.type == 'init'){
		        	this.addChoiseImages();
		        	this.stage.add(this.layerI);
		        }
		    };
		}
		var select = $('<select id="selectDMS">');
		select.append($('<option value="1">').text('Find table'));
		select.append($('<option value="2">').text('Find lines'));
		$('#buttons').append(select);
      	$('#buttons').append('<input type="button" id="resetDMS" value="Reset">'); 
      	$('#buttons').append('<input type="button" id="backStage" value="Back">');
      	$('#buttons').append('<input type="button" id="nextStage" value="Next">'); 
      	$('#buttons').append('<input type="button" id="downloadExcel" value="Excel">'); 
      	$('#buttons').append('Help<input type="checkbox" id="helpDMS">');
      	$('#buttons').append('<div style="width:200px;" id="zoomslider"></div>Zoom');

      	$( "#zoomslider" ).slider({range: "max",
                  min: 1,
                  max: 10,
                  value: 5,slide: function( event, ui ) {
                  //alert( ui.value );
                  argsP.scale = ui.value/10.;
                  if(argsP.type == 'lines'){
                  	  var vL = [], hL = [];
			          for(var i in stage.hLines){
			            var box = stage.layerL.find('#lineH_'+i)[0];
			            var p = box.getPoints();
			            var vl = (box.y()+p[1]-stage.initY)*(1./stage.ratio);
			            if (vl<0)vl=0;
			            hL.push(vl);
			          }
			          for(var i in stage.vLines){
			            var box = stage.layerL.find('#lineV_'+i)[0];
			            var p = box.getPoints();
			            var vl = (p[0]+box.x()-stage.initX)*(1./stage.ratio);
			            if( vl<0)vl=0;
			            vL.push(vl);
			          }
			          console.log(vL,hL);
			          argsP.vLines = vL;
			          argsP.hLines = hL;
                  }
                  stage = new Stage(argsP);
                  stage.logic();
        }}).css('display','inline-block');

      	if (localStorage.getItem("helpDMS") === null){
      		localStorage['helpDMS'] = '1';
      	}
      	$('#buttons').hide()
		$('#backStage').attr("disabled", true);
      	//
      	var urlS = "http://localhost:8000/server";
		
		stage = new Stage(argsP);
		var history = {};
		stage.logic();
		function helpVideos(){
			var id = 'GsmKcBH2rJY';
			if(argsP.type=='square')
				id = 'tBDqxf7lrbg';

			if(argsP.type=='lines' ){
							id = '8jSGCwDffQk';
							if( $('#selectDMS').val()=='2')
								id = 'FruSRtPaniA';
			}
			if(argsP.type=='pred')
				id = 'oYMt5bZVR2k';
			//todo: add text with images also
			$('#dialog').html('<iframe id="playerID" width="420" height="315"'+
			'src="https://www.youtube.com/embed/'+id+'?autoplay=1">'+
			'</iframe>').dialog({buttons:{},width:500,close: function( event, ui ) {
				$("#dialog #playerID").attr("src","");
			}});
		}
		console.log(localStorage['helpDMS']);
		$('#helpDMS').change(function(){
			console.log(localStorage['helpDMS']);
			localStorage['helpDMS'] = '2';
			if(!$(this).prop('checked'))
				return;
			localStorage['helpDMS'] = '1';
			helpVideos();
		})		
		
      	$('#helpDMS').prop('checked', (localStorage['helpDMS']=='1'));
		$('#resetDMS').click(function(){
			localStorage.removeItem('dm_simple_offsety1');
			localStorage.removeItem('dm_simple_offsety0');
			localStorage.removeItem('dm_simple_offsetx1');
			localStorage.removeItem('dm_simple_offsetx0');
			localStorage.removeItem('dm_simple_offsetx_cols');
			localStorage.removeItem('dm_simple_offsetx_rows');
			localStorage.removeItem('dm_simple_model');
			//localStorage.removeItem('helpDMS');
			$.post("/dm_simple/ajax", {'action':'reset'}, 
		            	function(result){
			                location.reload();
			            });
		})

		

		function flaskRequest(command,f){
			command = command.replace(/ /g, '+');
			console.log(command);
			$.post(urlS, {'action':'da','command':command},
				function(result){
				f(result);
			});
		}

		$('#backStage').click(function(){
			if(argsP.type == "square"){
				$( "#selectDMS" ).prepend($('<option value="1">').text('Find table'));
				$( "#selectDMS" ).val('1');
				history.stage1.po = history.stage1.po1;
				argsP = $.extend({}, history.stage1);
				console.log(history.stage1);
				stage = new Stage(history.stage1);
				stage.logic();
				$('#backStage').attr("disabled", true);
			}else if(argsP.type == "lines"){
				$( "#selectDMS" ).empty()
				.prepend($('<option value="2">').text('Find lines'));
				history.stage2.po = history.stage2.po2;
				argsP =  $.extend({}, history.stage2);
				console.log(history.stage2);
				stage = new Stage(history.stage2);
				stage.logic();
				//$('#backStage').attr("disabled", true);
			}else{
				$( "#selectDMS" ).empty()
				.prepend($('<option value="1">').text('Normal'))
				.prepend($('<option value="2">').text('Slider'));
				//history.stage2.po = history.stage2.po2;
				//argsP = history.stage2;
				argsP = $.extend({}, history.pred);
				console.log(history.pred);
				stage = new Stage(history.pred);
				stage.logic();
			}
		})
		$('#selectDMS').change(function(){
			if(argsP.type =='lines')
				if($('#helpDMS').prop('checked'))
					helpVideos();
		})
		$('#nextStage').click(function(){			
			
			if(argsP.type == "square"){
				var po1 = [];var po2 = [];
				var st = true;//$( "#selectDMS" ).val() == '1';
		        for(var i =0;i<4;++i){
		            var box = stage.layerB.find('#myRect_'+i)[0];
		            po1.push([(box.getX()-(st?stage.initX:0))*(1./stage.ratio),
		            	(box.getY()-(st?stage.initY:0))*(1./stage.ratio)
		            	]);
		           	po2.push([(box.getX()-(stage.initX))*(1./stage.ratio),
		            	(box.getY()-(stage.initY))*(1./stage.ratio)
		            	]);
		        }
				if($( "#selectDMS" ).val() == '1'){
					$(this).attr("disabled", true);
					argsP.po1 = po1;
		            history.stage1 = $.extend({}, argsP);
		            $.post('/dm_simple/ajax', {'data':argsP,'action':'crop'}, 
		            	function(result){
			                console.log(result);
			                flaskRequest(result['command'],function(result1){
			                	console.log(result1);
				                argsP.path_i = '/'+result1.path_s;
				                argsP.imgH = result1.heightT;
				                argsP.imgW = result1.widthT;
				                argsP.po = result1.po;
				                stage = new Stage(argsP);
				                stage.logic();
				                $('#selectDMS option[value=1]').remove();
				                $("#nextStage").attr("disabled", false);
				                $('#backStage').attr("disabled", false);	
			                })
			                
			            });
				}else{
					$('#dialog').html('Colums:<input id="mds_cols" name="cols"><br />'+
						'Rows:<input id="mds_rows" name="rows">')
					.dialog({buttons: {
				        Ok: function() {
				          if($('#mds_cols').val()=='' || $('#mds_rows').val()==''){
				          	alert('Set the number of colums and rows!');
				          }else{			          	
				          	argsP.po1 = po1;  
				          	argsP.po2 = po2;          
				            history.stage2 = $.extend({}, argsP);
				            argsP.cols = parseInt($('#mds_cols').val());
				            argsP.rows = parseInt($('#mds_rows').val());
				            localStorage['dm_simple_offsetx_cols'] = argsP.cols;
				            localStorage['dm_simple_offsetx_rows'] = argsP.rows;
				            $.post("/dm_simple/ajax", {'data':argsP,'action':'lines'},
					            function(result){
					                console.log(result);
					                flaskRequest(result['command'],function(result1){

						                argsP.type = 'lines';
						                argsP.path_i = '/'+result1.path_s;
						                argsP.imgH = result1.heightT;
						                argsP.imgW = result1.widthT;
						                argsP.hLines = result1.hLines;
						                argsP.vLines = result1.vLines;
						                stage = new Stage(argsP);
				                		stage.logic();
						                $("#nextStage").attr("disabled", false);
						                if($('#helpDMS').prop('checked'))
											helpVideos();
						                //show option to change the lines
						            })
					            });
				          	$( this ).dialog( "close" );
				          	$( "#selectDMS" ).empty()
							.prepend($('<option value="1">').text('Normal'))
							.prepend($('<option value="2">').text('Slider'));
				          }
				        }
				      }});
					$('#mds_cols').val(localStorage['dm_simple_offsetx_cols']||'');
					$('#mds_rows').val(localStorage['dm_simple_offsetx_rows']||'');
				}
			}else if(argsP.type == "lines"){
				var vL = [], hL = [];
		          for(var i in stage.hLines){
		            var box = stage.layerL.find('#lineH_'+i)[0];
		            var p = box.getPoints();
		            var vl = (box.y()+p[1]-stage.initY)*(1./stage.ratio);
		            if (vl<0)vl=0;
		            hL.push(vl);
		          }
		          for(var i in stage.vLines){
		            var box = stage.layerL.find('#lineV_'+i)[0];
		            var p = box.getPoints();
		            var vl = (p[0]+box.x()-stage.initX)*(1./stage.ratio);
		            if( vl<0)vl=0;
		            vL.push(vl);
		          }
		          console.log(vL,hL);
		          argsP.vLines = vL;
		          argsP.hLines = hL;
		          history.lines = $.extend({}, argsP);
		        var modelS = $('<select id="modelSelect">');
		        for(var i in argsP.models){
		        	modelS.append($("<option value='"+argsP.models[i][0]+"'>").
		        		text(argsP.models[i][1]));
		        }
				$('#dialog').html('First colum:<input id="mds_col" name="col"><br />'+
						'Number colums:<input id="mds_cols" name="cols"><br />'+
						'First row:<input id="mds_row" name="row"><br />'+
						'Number rows:<input id="mds_rows" name="rows"><br />'+
						'Model:').append(modelS)
					.dialog({buttons: {
				        Ok: function() {
				          if($('#mds_cols').val()=='' || $('#mds_rows').val()=='' ||
				          	$('#mds_col').val()=='' || $('#mds_row').val()==''){
				          	alert('Set the number of colums and rows!');
				          }else{			          	
				          	argsP.po1 = po1;  
				          	argsP.po2 = po2;          
				            history.pred = $.extend({}, argsP);
				            argsP.offsetx = []; argsP.offsety = [];
				            argsP.offsetx[1] = parseInt($('#mds_cols').val());
				            argsP.offsety[1] = parseInt($('#mds_rows').val());
				            argsP.offsetx[0] = parseInt($('#mds_col').val());
				            argsP.offsety[0] = parseInt($('#mds_row').val());
				            localStorage['dm_simple_offsetx1'] = argsP.offsetx[1];
				            localStorage['dm_simple_offsety1'] = argsP.offsety[1];
				            localStorage['dm_simple_offsetx0'] = argsP.offsetx[0];
				            localStorage['dm_simple_offsety0'] = argsP.offsety[0];
				            argsP.model = $('#modelSelect').val();
				            localStorage['dm_simple_model'] = argsP.model;
				            var models = argsP.models;
				            argsP.models = [];
				            console.log(argsP);
				            //todo: veryfy with nrCols/rows
				            $.post("/dm_simple/ajax", {'data':argsP,'action':'predict'},
					            function(result){
					                console.log(result);
					                flaskRequest(result['command'],function(result1){
						                argsP.type = 'pred';
						                //argsP.imgH = result.heightT;
						                //argsP.imgW = result.widthT;
						                argsP.predictions = result1.predictions;
						                
						                console.log(argsP);
						                stage = new Stage(argsP);
				                		stage.logic();
				                		$('#downloadExcel').show();
						                $("#nextStage").attr("disabled", false);
						                if($('#helpDMS').prop('checked'))
											helpVideos();
						                //show option to corect the table
						            })
					            });

				          	$( "#selectDMS" ).empty()
							.prepend($('<option value="1">').text('Normal'))
							.prepend($('<option value="2">').text('All column'))
							.prepend($('<option value="3">').text('Range lines'))
							.prepend($('<option value="4">').text('Range lines/marks'));
				          	$( this ).dialog( "close" );
				          }
				        }
				      }});
				$('#modelSelect').val(localStorage['dm_simple_model']||'0');
				$('#mds_col').val(localStorage['dm_simple_offsetx0']||'0');
				$('#mds_cols').val(localStorage['dm_simple_offsetx1']||argsP.vLines.length-1);
				$('#mds_row').val(localStorage['dm_simple_offsety0']||'0');
				$('#mds_rows').val(localStorage['dm_simple_offsety1']||argsP.hLines.length-1);
			}
		})
		$("#downloadExcel").click(function(){        
        var st = '';
        for(var i in argsP.predictions){
            if(argsP.offsety.length>0 &&(
             argsP.offsety[0]>i || argsP.offsety[0]+argsP.offsety[1]<=i))
              continue;
            if(i!=argsP.offsety[0])
              st+="\n";
            for(var j in argsP.predictions[i]['cells']){
              if(argsP.offsety.length>0 &&(
             argsP.offsetx[0]>j || argsP.offsetx[0]+argsP.offsetx[1]<=j))
              continue;
              if(j != argsP.offsetx[0])
                st+=',';
              var tt = argsP.predictions[i]['cells'][j]['prediction'];
              st+=(tt==12?"":tt);
            }
        }
        console.log(st);
        window.URL = window.URL || window.webkiURL;
        var blob = new Blob([st]);
        var blobURL = window.URL.createObjectURL(blob);
        $('#dialog').empty();
        $("#downloadLink").html("");
        $("<a></a>").
        attr("href", blobURL).
        attr("download", "data.csv").
        text("Download Data").
        appendTo('#dialog');
        $('#dialog').dialog({width:500,buttons:{}});
      })
 	}
  };
})(jQuery, Drupal, drupalSettings);