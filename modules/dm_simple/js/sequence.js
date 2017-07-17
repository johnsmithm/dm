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
		$(document).on({
		    ajaxStart: function() { $("body").addClass("loading");    },
		    ajaxStop: function() { $("body").removeClass("loading"); }    
		});
        var argsP = drupalSettings.dm_sequence.dm_sequence;
        console.log(argsP);
        var path = argsP.path;//a01-011u/a01-003
        var urlS = "http://"+window.location.hostname+":8000/server";
        var realh=0, realw=0;
        var myh = 900, myw = 500;
        var stage = new Konva.Stage({
	          container: 'container',
	          width: 1200,
	          height: 1200
	        });
	    var layerI = new Konva.Layer();
	    var layerL = new Konva.Layer();
	    var layerB = new Konva.Layer();
	    var layerT = new Konva.Layer();
	    var layerS = new Konva.Layer();
	    var tooltipLayer = new Konva.Layer();
	    var imgK = new Konva.Image({
		            x: 20,
		            y: 20,
		            width: myw,
		            height: myh,
		            stroke: 'red',
		            strokeWidth: 1
		        });

		layerI.add(imgK);

		var image = new Image();
		image.onload = function() {
		            imgK.image(image);
		            realh = this.height;
		            realw = this.width;
		            console.log(this.width + 'x' + this.height);
		            layerI.draw();
		        };
		image.src = path;
		var boxs = [];
		var po = [20,20,20,100,100,100,100,20];
		var keyboardMv = '';

		function updateSquare(ratio,xx,yy){
		          for (var i=0;i<4;i++){		            
		            po[i*2] = boxs[i].getX();
		            po[i*2+1] = boxs[i].getY();
		            //console.log(ratio);
		            
		          }
		          poly = new Konva.Line({
		                points: po,
		                stroke: 'black',
		                strokeWidth: 5,
		                closed : true,
		                id:'poly'
		              });
		          layerL.draw();
		        }
		var mph = {'myRect_0':1,'myRect_1':0,
					'myRect_2':3,'myRect_3':2};
		var mpv = {'myRect_0':3,'myRect_1':2,
					'myRect_2':1,'myRect_3':0};
		var mphb = {'myRect_0':1,'myRect_1':-1,
					'myRect_2':-1,'myRect_3':1};
		var mpvb = {'myRect_0':1,'myRect_1':1,
					'myRect_2':-1,'myRect_3':-1};
		for (var i=0;i<4;i++){
		            var box = new Konva.Rect({
		                  x: po[i*2],
		                  y: po[i*2+1],
		                  width: 20,
		                  height: 20,
		                  fill: '#00D2FF',
		                  stroke: 'black',
		                  strokeWidth: 4,
		                  draggable: true,
		                  id: 'myRect_'+i,
		              });
		            //'dragend yChange xChange'
		            box.on('xChange', function() {
		              //document.body.style.cursor = 'default';
		              keyboardMv = this.getId();
		              if((-1*mpvb[keyboardMv])*this.getX()+
		              	mpvb[keyboardMv]*boxs[mpv[keyboardMv]].getX()>20
		              	&& 20<=this.getX() && this.getX()<=20+myw)
		              	boxs[mph[keyboardMv]].setX(this.getX());
		              else
		              	this.setX(boxs[mph[keyboardMv]].getX());
		              updateSquare(1,20,20);
		            });
		            box.on('yChange', function() {
		              //document.body.style.cursor = 'default';
		              keyboardMv = this.getId();
		              if((-1*mphb[keyboardMv])*this.getY()+
		              	mphb[keyboardMv]*boxs[mph[keyboardMv]].getY()>20
		              	&& 20<=this.getY() && this.getY()<20+myh)
		              	boxs[mpv[keyboardMv]].setY(this.getY());
		              else
		              	this.setY(boxs[mpv[keyboardMv]].getY());
		              updateSquare(1,20,20);
		            });
		            box.on('mouseover', function() {
			              document.body.style.cursor = 'pointer';
			          });
			        box.on('mouseout', function() {
			              document.body.style.cursor = 'default';
			          });
		            boxs.push(box);
		        	layerB.add(box);
		        }
		var poly = new Konva.Line({
		              points: po,
		              stroke: 'black',
		              strokeWidth: 5,
		              closed : true,
		              id:'poly'
		            });
		layerL.add(poly);


		stage.add(layerI);
		stage.add(layerL);
		stage.add(layerB);

		$('#buttons').append('<input type="button" id="resetDMS" value="Reset">'); 
      	$('#buttons').append('<input type="button" id="next" value="Next">'); 
      	//$('#resetDMS').hide()

      	var colors = ['red',"orange", "yellow",'green'];
      	var ints = [0.05,0.1,0.5,2.0];

      	$('#next').click(function(){
      		console.log(po);
      		var maxy=0,maxx=0,minx=10000,miny=10000;
      		for(var i=0;i<4;++i){
      			maxx = Math.max(maxx,po[i*2])
      			minx = Math.min(minx,po[i*2])
      			maxy = Math.max(maxy,po[i*2+1])
      			miny = Math.min(miny,po[i*2+1])
      		}
      		if((maxx-minx-0)*(realw/myw) > 900 ||
      			(maxy-miny+0)*(realh/myh) > 200){
      			alert('Selectati doar un rand si nu mai mult de 3 cuvinte(200x900)!');
      			return;
      		}
      		console.log(maxx,maxy,minx,miny);
      		imgK.crop({
	                  x: (minx-20)*(realw/myw),
	                  y: (miny-20)*(realh/myh),
	                  width: (maxx-minx-0)*(realw/myw),
	                  height: (maxy-miny+0)*(realh/myh)
	                });
	        imgK.width((maxx-minx));
	        imgK.height((maxy-miny));
	        layerB.hide()
	        layerL.hide()
      		layerI.draw()
      		$(this).hide()
      		$('#resetDMS').show()
      		console.log(parseInt((maxx-minx-0)*(realw/myw)));
      		$.post(urlS, {'action':'sequence',
      			'x':parseInt((minx-20)*(realw/myw)),
      			'y':parseInt((miny-20)*(realh/myh)),
				'height':parseInt((maxy-miny-0)*(realh/myh)),
				'width':parseInt((maxx-minx-0)*(realw/myw)),
				'path':path},
				function(result){
									console.log(result);	
									var simpleText = new Konva.Text({
								      x: 20,
								      y: (maxy-miny)+20,
								      text: 'Prediction: '+result['d'],
								      fontSize: 15,
								      fontFamily: 'Calibri',
								      fill: 'green'
								    });
								    layerT = new Konva.Layer();
								    layerS = new Konva.Layer();
								    layerT.clearBeforeDraw(true);
								    layerS.clearBeforeDraw(true);
								    layerT.show();
								    layerS.show();
								    layerT.add(simpleText);
								    layerT.draw();

					for(var i in result['soft']){
						if(i>50)
							break;
						for(var j in result['soft'][i][0]){
							var v = result['soft'][i][0][j];
							var idc = 0;
							while(ints[idc]<v && idc<ints.length-1)
								idc++
							var circle_text = new Konva.Text({
					            x: 20 + i*12,
					            y: (maxy-miny)+ 40 + j*12,
					            fontSize: 10,
					            text:result['v'][j],
					            fontFamily: 'Calibri',
					            fill: 'black',
					            name : (i*57+j).toString()
					        });
					        var circle = new Konva.Circle({
					            x: 2 + 20 + i*12,
					            y: 4 +(maxy-miny)+ 40 + j*12,
					            radius: 5,
					            fill: colors[idc],
					            name : (i*57+j).toString()
					        });
					        layerS.add(circle);
					        layerS.add(circle_text);
						}
					}
					layerS.draw();					
					stage.add(layerS);
					stage.add(layerT);
					argsP.stage = 'pred';
					$.post('/dm_simple/ajax', {'action':'count'}, 
		            	function(re1){
		            		if(re1['count']==1){
		            			location.reload();
		            		}
		            	});
			});
	        /*imgK.scale({  x: this.ratio , y: this.ratio });
	        imgK.y((row.y-oy)*this.ratio);
	        imgK.x((cell.x-ox)*this.ratio);
	        */
      	})
      	$('#resetDMS').click(function(){
      		if (argsP.stage == 'img'){
      			$.post("/dm_simple/ajax", {'action':'reset'}, 
		            	function(result){
			                location.reload();
			            });
      			return;
      		}
      		argsP.stage = 'img';
			imgK.crop({
	                  x: 0,
	                  y: 0,
	                  width: realw,
	                  height: realh
	        });
	        imgK.width(myw);
	        imgK.height(myh);
		    layerI.draw();
      		layerB.show()
	        layerL.show()
	        layerS.hide()
	        layerT.hide()

      		//$(this).hide()
      		$('#next').show()
      	})
      	
	}
  };
})(jQuery, Drupal, drupalSettings);
