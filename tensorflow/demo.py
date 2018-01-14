from flask import Flask, request, jsonify, session
from flask_cors import CORS
import tensorflow as tf
import numpy as np
import datetime
import time
import run
import scipy.io
import cv2
import json
import eval_util
import os

app = Flask(__name__)
app.secret_key = 'any random string1'
CORS(app)


@app.route('/server', methods = ['POST','OPTIONS','GET'])
def getServerInfo():
    if str(request.form['action']) == 'runC':
		process = os.popen(request.form['command'].replace('%20','\ '))
		#print(request.form['command'].replace('%20','\ '))
		f = process.read()
		process.close()
		print(f)
		return jsonify(f)

if __name__ == '__main__':
	#print (run.args)
	app.run(host='0.0.0.0', port=8000)