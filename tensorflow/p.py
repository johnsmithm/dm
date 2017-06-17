from flask import Flask, request, jsonify, session
from flask_cors import CORS
import datetime
import run
app = Flask(__name__)
app.secret_key = 'any random string'
CORS(app)


@app.route('/')
def hello_world():
    return 'Hello, World!'



@app.route('/server', methods = ['POST','OPTIONS'])
def getServerInfo():
	if str(request.form['action']) == 'square':
	    run.args.path = str(request.form['path'])
	    run.args.height = str(request.form['height'])
	    run.args.width = str(request.form['width'])
	    run.args.stage = str(request.form['action'])
	else:
		print( str(request.form['command']))
		command = str(request.form['command']).split('tensorflow/run.py')[1]
		args = command.split('+--')
		for arg in args:			
			if len(arg.split('+'))<2:
				continue
			key, value = arg.split('+')[:2]
			setattr(run.args, key.strip(), value.strip())

	print(run.args)
	
	if run.args.stage == 'predict':
		file = open("tensorflow/s.txt","r") 
		v = file.read() 
		print(v,'-'*100)
		if v == '1':
			return jsonify({'ans':None})
		file = open("tensorflow/s.txt","w") 
		file.write("1") 		 
		file.close() 
	out = jsonify(run.logic(run.args))
	if run.args.stage == 'predict':
		file = open("tensorflow/s.txt","w") 
		file.write("0") 		 
		file.close()
	return out

if __name__ == '__main__':
	print (run.args)
	app.run(host='0.0.0.0', port=8000)