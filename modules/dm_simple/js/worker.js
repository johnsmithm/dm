var info;
onmessage = getEnd;
function getEnd(event) {
	//postMessage('0');
  info = event.data;
  console.log(999)
  onmessage = getEnd1;
  //work();
}
var id ;
function getEnd1(event) {
  id = 1*event.data;
  onmessage = null;
  work();
}
//postMessage('0');

function work() {
  var result = id//info['pr'][0]
  postMessage(result);
  //close();
}