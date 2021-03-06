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
app = Flask(__name__)
app.secret_key = 'any random string'
CORS(app)


@app.route('/')
def hello_world():
    return 'Hello, World!'


def getModel1(path):
    #loaded_Graph = tf.Graph()
    sess1 = tf.Session()
    loader1 = tf.train.import_meta_graph(path+'.meta')
    loader1.restore(sess1, path)  
    loaded_Graph1 = tf.get_default_graph()
    beam_size = 2
    # get tensors 
    if True:               
        images_tensor = loaded_Graph1.get_tensor_by_name('images:0')
        hei_tensor    = loaded_Graph1.get_tensor_by_name('hey:0')
        lens_tensor   = loaded_Graph1.get_tensor_by_name('lens:0')
        bb_tensor     = loaded_Graph1.get_tensor_by_name('bacth:0')
        #targets_tensor = loaded_Graph.get_tensor_by_name('targets:0')
        is_training_tensor = loaded_Graph1.get_tensor_by_name('is_training:0')
        
        decoded_tensor=[]
        for i in range(beam_size):
            decoded_tensor.append(tf.get_collection("decodedPrediction{}".format(i)))
        logits_tensor = tf.get_collection('logits_softmax')[0]
        cost_tensor = tf.get_collection('cost')[0]
        ler_tensor = tf.get_collection('ler')[0]  

    def predict(imgs):
        start = time.time()
        feed_t = {images_tensor: np.expand_dims(imgs,-1),
            lens_tensor:imgs[0].shape[1],
            hei_tensor:imgs[0].shape[0],
            is_training_tensor:False}
        softmax, d = sess1.run([logits_tensor, decoded_tensor[0]], feed_t)
        end=time.time()
        return softmax, d, (end-start)
    return predict

#predict1 = 1#getModel1('modules/dm_simple/imgs/mblstm-width-free-2.ckpt')
pathData = 'modules/dm_simple/imgs/'
file = open(pathData+"nume.txt","r") 
n = file.read().split('\n')
file = open(pathData+"prenume.txt","r") 
p = file.read().split('\n')
lines = []
vocab_info = json.loads(scipy.io.loadmat(pathData+'info.mat')['vocab'][0])
for c in n:
    lines.append([vocab_info[v] for v in c if v in vocab_info])
for c in p:
    lines.append([vocab_info[v] for v in c if v in vocab_info])
#print(lines[0])
trie = eval_util.get_trie(lines)

def load_predict(imgs, path='modules/dm_simple/imgs/mblstm-width-free-3.ckpt',
    batch_size=1):
	#start = time.time()
    loaded_Graph = tf.Graph()
    start = time.time()
    with tf.Session(graph=loaded_Graph) as sess:
        loader = tf.train.import_meta_graph(path +'.meta')
        loader.restore(sess, path)   
        # get tensors                
        images_tensor = loaded_Graph.get_tensor_by_name('images:0')
        hei_tensor    = loaded_Graph.get_tensor_by_name('hey:0')
        lens_tensor   = loaded_Graph.get_tensor_by_name('lens:0')
        #bb_tensor     = loaded_Graph.get_tensor_by_name('bacth:0')
        #targets_tensor = loaded_Graph.get_tensor_by_name('targets:0')
        is_training_tensor = loaded_Graph.get_tensor_by_name('is_training:0')
        
        decoded_tensor=[]
        for i in range(10):
            decoded_tensor.append(tf.get_collection("decodedPrediction{}".format(i)))
        logits_tensor = tf.get_collection('logits_softmax')[0]
        #cost_tensor = tf.get_collection('cost')[0]
        #ler_tensor = tf.get_collection('ler')[0]  
        if batch_size==1:
            feed_t = {images_tensor: np.expand_dims(imgs,-1),
                lens_tensor:imgs[0].shape[1],
                hei_tensor:imgs[0].shape[0],
                is_training_tensor:False}
            softmax, d = sess.run([logits_tensor, decoded_tensor[0]], feed_t)
            end=time.time()
            sess.close()
            del sess
            return softmax, d, (end-start)
        probs = []
        for i in range(0,len(imgs),batch_size):
            if len(imgs[i:i+batch_size])==0:
                continue
            print(imgs[i:i+batch_size].shape)
            feed_t = {images_tensor: np.expand_dims(imgs[i:i+batch_size],-1),
                lens_tensor:imgs[0].shape[1],
                hei_tensor:imgs[0].shape[0],
                is_training_tensor:False}
            d = sess.run([decoded_tensor], feed_t)
            
            probs.append(d)
        end=time.time()
        sess.close()
        del sess
        #tf.reset_default_graph()
        return probs, (end-start)

@app.route('/server', methods = ['POST','OPTIONS'])
def getServerInfo():
    if str(request.form['action']) == 'movies':
        path = 'modules/dm_simple/imgs/save-movie-full.p'
        liked = [[110,4.5], [ 527,4.5], [1580,5.0], [5349,4.0], [2959,5.0], [58559,4.0], [1210,5.0], [589,4.5]]
        mm = run.showMovies1(liked[:9],path = 'modules/dm_simple/imgs/save-movie-full.p')
        import pickle
        d2 =  pickle.load( open( path, "rb" ) )
        #movie, directorId,actorsId,wordId,countryId,imdbRatingsId, cats, marks
        movies = d2[0]
        return jsonify({'ans':mm,'mo':movies,'cat':d2[6]})
    if str(request.form['action']) == 'movies_s':
        path = 'modules/dm_simple/imgs/save-movie-full.p'
        liked =  [[int(h),1.0] for h in request.form['id'].split(',')]
        mm = run.showMovies1(liked[:9],path = 'modules/dm_simple/imgs/save-movie-full.p')
        
        return jsonify({'ans':mm})
    if str(request.form['action']) == 'movies_q':
        liked = [ [int(v.split('-')[0]),float(v.split('-')[1])] \
         for v in request.form['liked'].split(',')]
        print(liked)
        #liked = [[110,4.5], [ 527,4.5], [1580,5.0], [5349,4.0], [2959,5.0], [58559,4.0], [1210,5.0], [589,4.5]]
        
        l = run.order_dl(liked[:5], 'modules/dm_simple/imgs/save-movies-model.pb', 
                        nr=20,
                        path = 'modules/dm_simple/imgs/save-movie-full.p')
        
        mm = run.showMovies1(l[:6],path = 'modules/dm_simple/imgs/save-movie-full.p')
        
        return jsonify({'ans':mm})

    if str(request.form['action']) == 'chatbot':
        out = run.chatbot(request.form['message'], path="modules/dm_simple/imgs")
        print(out)
        return jsonify({'ans':out[-1],'prob':float(out[1])})
    if str(request.form['action']) == 'json':
        for data in request.form:
            print(data,request.form[data])
        image = cv2.imread(request.form['path'][1:],cv2.IMREAD_GRAYSCALE)
        print(image.shape)
        scipy.io.savemat("tensorflow/json/json.mat",#.format(request.form['fid']), 
            mdict={'data':json.dumps(request.form),
                    'img':image.tolist()})
        return jsonify({'ans':None})
    if str(request.form['action']) == 'sequence':
		image = cv2.imread(request.form['path'][1:],cv2.IMREAD_GRAYSCALE)
		print(image.shape)
		x = int(request.form['x'])
		y = int(request.form['y'])
		w = int(request.form['width'])
		h = int(request.form['height'])
		print(x,y,w,h)
		image = image[y:y+h,x:x+w]
		cv2.imwrite('modules/dm_simple/imgs/test.png',image)
		print(image.shape);nnModel = request.form['nnModel']
		soft, d, t = load_predict((255.-np.array([image]))/255.,
            path="modules/dm_simple/imgs/mblstm-width-free-{}.ckpt".format(nnModel))
		print(d[0][0])
		pathData = 'modules/dm_simple/imgs/'
		vocab_info = json.loads(scipy.io.loadmat(pathData+'info.mat')['vocab'][0])
		back = {c:s for s,c in vocab_info.items()}
		print(back[6])
		text = ''.join([str(back[c]) for c in d[0][0]])
		return jsonify({'soft':soft.tolist(),'time':t,'d':text,'v':back})
	
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
	
    if False and run.args.stage == 'predict':
        file = open("tensorflow/s.txt","r") 
        v = file.read() 
        print(v,'-'*100)
        if v == '1':
            return jsonify({'ans':None})
        file = open("tensorflow/s.txt","w") 
        file.write("1") 		 
        file.close() 
    out = run.logic(run.args)
    if 'cellsCN' in out and len(out['cellsCN'])>0:
        pathData = 'modules/dm_simple/imgs/'
        vocab_info = json.loads(scipy.io.loadmat(pathData+'info.mat')['vocab'][0])
        back = {c:s for s,c in vocab_info.items()}
        iim = np.array(out['cellsCN'])
        d, t = load_predict( iim/(0.0+np.amax(iim)-np.amin(iim)),
            path="modules/dm_simple/imgs/mblstm-width-free-5.ckpt",batch_size=5)
        prs = []
        for d1 in d:  
            pr = eval_util.sort_prediction(d1,lines,
             lambda x: eval_util.trie_exist(trie,x), 
             eval_util.levenshtein, 
             ch=vocab_info)      
            prs.extend(pr)
        out['time'] = t
        for i,p in enumerate(prs):
            print(p)
            #text = ''.join([str(back[c]) for c in p])
            out['predictions'][i]['cells'][out['colmnN']]['prediction'] = p[0]
            out['predictions'][i]['cells'][out['colmnN']]['options'] = p
            out['predictions'][i]['cells'][out['colmnN']]['probability'] = 0.7
        del out['cellsCN']
        out['cellsCN'] = []
    if run.args.stage == 'predict':
        file = open("tensorflow/s.txt","w") 
        file.write("0") 		 
        file.close()
    return  jsonify(out)
	

if __name__ == '__main__':
	#print (run.args)
	app.run(host='0.0.0.0', port=8000)