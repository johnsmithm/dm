from __future__ import absolute_import, division, print_function

import cv2
import argparse
import json
import os
import pickle
import math
import numpy as np
from pyimagesearch.transform import four_point_transform
from pyimagesearch import imutils
from sklearn.cluster import KMeans
import tensorflow as tf
import operator

def preprocessCell(cell, morph=False):
    gray = cv2.cvtColor(cell, cv2.COLOR_BGR2GRAY)
    #print ('gray:',np.histogram(gray))
    blur = cv2.GaussianBlur(gray,(3,3),0)
    #ret3,img = cv2.threshold(gray,10,255,cv2.THRESH_BINARY+cv2.THRESH_OTSU)
    img = cv2.adaptiveThreshold(gray,255,cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY,11,2)
    #print ('thresh:',np.histogram(img))
    newI = []
    maxx = max(gray.reshape((-1)))
    #print('max:',maxx)
    for i in range(len(cell)):
        rr = []
        for j in range(len(cell[0])):
            if img[i,j] == 255.:
                rr.append(0.0)
            else:
                rr.append(round((gray[i,j]*1.0)/maxx,1))
        newI.append(rr)
    #img = cv2.adaptiveThreshold(gray,255,cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY,11,2)#noice gausian
    if not morph:
        return np.asarray(newI)
    img = np.asarray(newI)
    margin = 1
    kernelV = np.ones((img.shape[0]-margin,1),np.uint8)
    kernelH = np.ones((1,img.shape[1]-margin),np.uint8)
    img1 = cv2.morphologyEx(img, cv2.MORPH_CLOSE, kernelV)
    img2 = cv2.morphologyEx(img, cv2.MORPH_CLOSE, kernelH)
    #for i,k in enumerate(img1):
    #    for j,l in enumerate(k):
    #        img[i,j] = img[i,j] if img1[i,j]==0.0 and img2[i,j]==0.0 else 0.0

    kernel = np.ones((2,2),np.uint8)
    img = cv2.morphologyEx(img, cv2.MORPH_OPEN, kernel)
    #img = cv2.morphologyEx(img, cv2.MORPH_CLOSE, kernel)
    return img

def crop_pad(img, h=28, w=28):
    #img = cv2.resize(img, (h, w)) 
    h1, w1= img.shape
    if h1>h*1.5 :
        img = imutils.resize(img, height = h)
    
    if w1>w*1.5:
        img = imutils.resize(img, width = w)
        
    
        
    if h1<h and w1<w:
        scale = 1.0
        if h1>w1:
            img = imutils.resize(img, height = h)
        else:
            img = imutils.resize(img, width = w)
            
    if h1>h or w1>w:
        img = cv2.resize(img, (h, w)) 
        if False:
            bh = int((h1-h)/2) if int((h1-h)/2)>0 else 0
            bw = int((w1-w1)/2) if int((w1-w)/2)>0 else 0
            img = img[bh:bh+h,bw:bw+w]
            
    
    if img.shape[0]!=h and int((h-img.shape[0])/2)>0:
            img = np.insert(img, [0]*int((h-img.shape[0])/2), 0, axis=0)
    if img.shape[0]!=h:
            img = np.insert(img, [img.shape[0]]*(h-img.shape[0]), 0, axis=0)
    if img.shape[1]!=w  and int((w-img.shape[1])/2)>0:
            img = np.insert(img, [0]*int((w-img.shape[1])/2), 0, axis=1)  
    if img.shape[1]!=w:
            img = np.insert(img, [img.shape[1]]*(w-img.shape[1]), 0, axis=1)  
    maxx = max(img.reshape((-1)))
    return (img/maxx)



def getLines(page,margin=5,maxLineGap=30,minLineLength=60,debug=False):
    """Get lines from the image
    @param page: image
    @param margin: the number of pixels from the margins not counted as lines
    @param maxLineGap: param for HoughLinesP
    @param minLineLength: param for HoughLinesP
    return a list of 2 lists of dots
    """
    ratio = 1.
    if page.shape[0]>1000:
        ratio = float(1.*page.shape[0]/1000.0)
        
        page = imutils.resize(page, height = 1000)
    gray = cv2.cvtColor(page,cv2.COLOR_BGR2GRAY)

    edges = cv2.Canny(gray,10,200,apertureSize = 3)   

    lines = cv2.HoughLinesP(edges,1,np.pi/180,15,minLineLength,maxLineGap)
    
    dotsR = []
    dotsC = []
    #assert len(lines)>0, "no lines detected"
    if lines is not None:
        for x in range(0, len(lines)):
            for x1,y1,x2,y2 in lines[x]:
                if debug:
                    cv2.line(page,(x1,y1),(x2,y2),(0,255,0),2)
                if x1<margin or x2<margin or y2<margin or y1<margin or x1>len(page[0])-margin or\
                x2>len(page[0])-margin or y1>len(page)-margin or y2>len(page)-margin:
                    continue
                if abs(x1-x2)<margin:
                    dotsC.append([x1*float(ratio)])
                elif abs(y1-y2)<margin:
                    dotsR.append([y2*float(ratio)])
    if debug:
        print('cosls:',len(dotsC),' rows:',len(dotsR))
    #print('r',ratio)
    return dotsC, dotsR

def getKMean(dots, k):
    """Calculate k means
    @param dots: 1d array 
    @param k: int
    @retruns [k] 1d array
    """
    assert len(dots)>k, "not enough lines detected"
    kmeans = KMeans(n_clusters=k, random_state=0).fit(np.asarray(dots))
    Dots  = sorted(np.asarray(kmeans.cluster_centers_,dtype=np.int32)[:,0])
    return Dots

def scannedImg(img,square,ratio):
    # apply the four point transform to obtain a top-down
    # view of the original image
    warped = four_point_transform(img, square * ratio)
    
    return warped

def getSquarePage(img):
    """return four point that indicates the paper corners
    @param img: the image color, 3 colors
    @returns [4,2] shape numpy array
    """    
    image = img.copy()
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    #gray = cv2.GaussianBlur(gray, (3, 3), 0)
    edged = cv2.Canny(gray, 10, 200)
    # find the contours in the edged image, keeping only the
    # largest ones, and initialize the screen contour
    cnts = cv2.findContours(edged.copy(), cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)[1]
    
    #for i in cnts:
    #    print(cnts);
    cnts = sorted(cnts, key = cv2.contourArea, reverse = True)[:10]
    for c in cnts:
        
        # approximate the contour
        peri = cv2.arcLength(c, True)
        approx = cv2.approxPolyDP(c, 0.02 * peri, True)
        #cv2.contourArea(c)<0.5*AreaImage: not good

        # if our approximated contour has four points, then we
        # can assume that we have found our screen
        if len(approx) == 4 and cv2.contourArea(c)>(img.shape[0]*img.shape[1])/4.: 
            #print(cv2.contourArea(c))
            #print(img.shape)           
            return approx.reshape(-1, 2)
    return None

def f1(img):
    H = 700
    page = None
    orig = img.copy()
    while H>100:
            img = orig.copy()
            ratio = img.shape[0] / H         
            
            img = imutils.resize(img, height = H)

            square = getSquarePage(img)    
            if square is not None:
                return square*ratio
            H-=100
    return None 
def f2(image,out):
        sq = f1(image)
        if sq is None:
            out['status'] = 'ok'
            out['po'] = [[10,10],[10,image.shape[0]-10],
            [image.shape[1]-10,image.shape[0]-10],[image.shape[1]-10,10]]
        else:
            out['po'] = [[int(g) for g in c] for c in sq]
            l = out['po']
            mlat = sum(x[0] for x in l) / len(l)
            mlng = sum(x[1] for x in l) / len(l)
            def algo(x):
                return (math.atan2(x[0] - mlat, x[1] - mlng) + 2 * math.pi) % (2*math.pi)

            l.sort(key=algo)
            hR = 1.#out['height'] / image.shape[0]
            wR = 1.#out['width'] / image.shape[1]
            out['po'] = [[c[0]*wR,c[1]*hR] for c in l]
        return out
def cr(image,out,args,scale=1):
    hR = 1.# image.shape[0]/out['height']
    wR = 1.#image.shape[1]/ out['width']
    points = [int(c) for c in args.points.split('-')]
    #print(points)
    points = np.array([[points[i*2]*wR,points[i*2+1]*hR] for i,k in enumerate(points[::2])])
    #print(points)
    #print(wR,hR)
    crop  = scannedImg(image,points,1)
    return crop

def rand_model(imgs):
    probs = np.zeros((len(imgs),2))
    for i in range(len(imgs)):
        probs[i,0] = np.random.randint(13)
        probs[i,1] = 1/13.
    return probs

def null_model(imgs):
    probs = np.ones((len(imgs),2))*12
    probs[:,1] = 0.01
    return probs

def preprocess_trivial(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    resized_image = cv2.resize(gray, (28, 28)) 
    maxx, minn = 255., 0.
    #map to 0-1
    resized_image = (resized_image - minn) / (maxx - minn)
    return resized_image.reshape(-1)

def preprocess(img):
    return crop_pad(preprocessCell(img,True)).reshape(-1)

def tf_cnn_slim_model13(imgs,batchSize=100,
    save_dir='tensorflow/models/model-slim-cnn13/checkpoint-2000',
    pathGraph='tt',
    pathVars='rr'):
    # preprocess to 28x28
    testData = []
    for img in imgs:
        testData.append(preprocess(img))
    testData = np.array(testData)
    
    loaded_Graph = tf.Graph()
    session_config = tf.ConfigProto(log_device_placement=False, 
        allow_soft_placement=True)    
    sess = tf.Session(graph=loaded_Graph,config=session_config)
    with tf.Session(graph=loaded_Graph,config=session_config) as sess:
    #with tf.Graph().as_default(),sess:
        loader = tf.train.import_meta_graph(pathGraph)
        loader.restore(sess, pathVars.split('.index')[0])    
        # get tensors
        loaded_x = loaded_Graph.get_tensor_by_name('inputT:0')
        loaded_y = loaded_Graph.get_tensor_by_name('labelT:0')
        keep_pr = loaded_Graph.get_tensor_by_name('keep_prob:0')
        
        loaded_prob = loaded_Graph.get_tensor_by_name('probability:0')
        is_training = loaded_Graph.get_tensor_by_name('train_batch:0')

        probs = np.zeros((len(testData),2))
        for i in range(0,len(testData),batchSize):
            prob = sess.run(loaded_prob, 
                feed_dict = {loaded_x: testData[i:i+batchSize],is_training:False,
                keep_pr:1.0})
            probs[i:i+batchSize,0] = np.argmax(prob,1)
            probs[i:i+batchSize,1] = np.amax(prob,1)
            del prob     
        sess.close()
        del sess
    #tf.reset_default_graph()
    del testData
    del loaded_Graph   
    return probs

def getModel(pathGraph, pathVars):
    #loaded_Graph = tf.Graph()
    sess = tf.Session()
    loader = tf.train.import_meta_graph(pathGraph)
    loader.restore(sess, pathVars.split('.index')[0])  
    loaded_Graph = tf.get_default_graph() 
    # get tensors
    loaded_x = loaded_Graph.get_tensor_by_name('inputT:0')
    loaded_y = loaded_Graph.get_tensor_by_name('labelT:0')
    keep_pr = loaded_Graph.get_tensor_by_name('keep_prob:0')
        
    loaded_prob = loaded_Graph.get_tensor_by_name('probability:0')
    is_training = loaded_Graph.get_tensor_by_name('train_batch:0')  

    def predict(imgs, batchSize=100):
        testData = []
        for img in imgs:
            testData.append(preprocess(img))
        testData = np.array(testData)
        probs = np.zeros((len(testData),2))
        for i in range(0,len(testData),batchSize):
            prob = sess.run(loaded_prob, 
                feed_dict = {loaded_x: testData[i:i+batchSize],is_training:False,
                keep_pr:1.0})
            probs[i:i+batchSize,0] = np.argmax(prob,1)
            probs[i:i+batchSize,1] = np.amax(prob,1) 
        return probs
    return predict

def tf_cnn_slim_model(imgs,batchSize=100,
    save_dir='tensorflow/models/model-slim-cnn/save',
    pathGraph='tt',
    pathVars='rr'):
    # preprocess to 28x28
    testData = []
    for img in imgs:
        testData.append(preprocess_trivial(img))
    testData = np.array(testData)
    
    loaded_Graph = tf.Graph()
    with tf.Session(graph=loaded_Graph) as sess:
        loader = tf.train.import_meta_graph(save_dir +'.meta')
        loader.restore(sess, save_dir)    
        # get tensors
        loaded_x = loaded_Graph.get_tensor_by_name('input:0')
        loaded_y = loaded_Graph.get_tensor_by_name('label:0')
        loaded_prob = loaded_Graph.get_tensor_by_name('probability:0')
        is_training = loaded_Graph.get_tensor_by_name('is_training:0')
        probs = np.zeros((len(testData),2))
        for i in range(0,len(testData),batchSize):
            prob = sess.run(loaded_prob, 
                feed_dict = {loaded_x: testData[i:i+batchSize],is_training:True})
            probs[i:i+batchSize,0] = np.argmax(prob,1)
            probs[i:i+batchSize,1] = np.amax(prob,1)
        for i in range(len(testData)):
            if(probs[i,1]<0.3):
                probs[i,0] = 12
    return probs

def load_graph(frozen_graph_filename):
    # We load the protobuf file from the disk and parse it to retrieve the 
    # unserialized graph_def
    with tf.gfile.GFile(frozen_graph_filename, "rb") as f:
        graph_def = tf.GraphDef()
        graph_def.ParseFromString(f.read())

    # Then, we can use again a convenient built-in function to import a graph_def into the 
    # current default Graph
    with tf.Graph().as_default() as graph:
        tf.import_graph_def(
            graph_def, 
            input_map=None, 
            return_elements=None, 
            name="prefix", 
            op_dict=None, 
            producer_op_list=None
        )
    return graph

def chatbot(text, path):

    output_graph = path + '/chatbot-context.pb'
    tf.reset_default_graph()
    loaded_Graph = load_graph(output_graph)
    #laod vocab, classes and answers

    obj = pickle.load( open( path+'/chatbot-data.p', "rb" ) )
    classes = obj['classes']
    vocab = obj['vocab']
    answers = obj['answers']
       
    messages = loaded_Graph.get_tensor_by_name('prefix/messages:0')
    lengths = loaded_Graph.get_tensor_by_name('prefix/lengths:0')
    bs = loaded_Graph.get_tensor_by_name('prefix/batch_size:0')    
            
    probs = loaded_Graph.get_tensor_by_name('prefix/probabilities:0')
    preds = loaded_Graph.get_tensor_by_name('prefix/prediction:0')

    sess =  tf.Session(graph=loaded_Graph)   
    q = text# 'what kind of modep do you have?'
    m = [[vocab[c]] for c in q[:30].lower() if c in vocab]
    tePRO, tePRE = sess.run([probs,preds], 
                    feed_dict = {messages:[m], lengths:[len(m)],bs:1})
    sess.close()
    return classes[tePRE[0]], tePRO[0][tePRE[0]],\
     np.random.choice(answers[classes[tePRE[0]]])




def getCells(page,rowDots,collDots, out, debug=False):    
    """get cell from table image
    @param page: the image
    @param rowDots: the array with rows points
    @param collDots: the array with cols points
    @returns: a list of lists with cell images
    """
    out['predictions'] = []

    hR = 1.# out['height']/page.shape[0]
    wR = 1.# out['width'] /page.shape[1]

    cells = []
    cellsCN = []
    maxH = 0
    for i in range(len(rowDots)-1): 
        maxH = max(maxH,rowDots[i+1]-rowDots[i])
    for i in range(len(rowDots)-1):   
        #if i!=args.colmnN: continue     
        for j in range(len(collDots)-1):
            cellI = page[rowDots[i]:rowDots[i+1],collDots[j]:collDots[j+1]]
            cells.append(cellI)
            if j == out['colmnN']:
                gray = cv2.cvtColor(cellI, cv2.COLOR_BGR2GRAY)
                gray = np.amax(gray) - gray
                print('gray shape', np.amax(gray),np.amin(gray))
                gray = np.insert(gray, [gray.shape[0]]*(maxH-gray.shape[0]), 0., axis=0)
                if i==2:
                    cv2.imwrite('modules/dm_simple/imgs/test.png',gray)
                print(gray.shape)                
                cellsCN.append(gray)
    #rand_model, null_model, tf_cnn_slim_model
    #print(len(cells))
    
    #predictFast = getModel('sites/default/files/metagraph-2017-05/checkpoint-750.meta', 
    #'sites/default/files/2017-05/checkpoint-750.index')

    #probs = predictFast(cells)# 
    #probs = tf_cnn_slim_model13(cells,pathGraph=out['pathGraph'],pathVars=out['pathVars'])
    
    probs = tf_cnn_slim_model13(cells,
        pathGraph='sites/default/files/metagraph-2017-05/checkpoint-750.meta',
        pathVars='sites/default/files/2017-05/checkpoint-750.index')
    del cells
    #probs = np.ones((900,2))
    print(probs.shape)
    #print(len(probs))

    for i in range(len(rowDots)-1):
        cellsR = []
        for j in range(len(collDots)-1):
            
            prediction  = probs[i*(len(collDots)-1)+j,0]
            probability = probs[i*(len(collDots)-1)+j,1]
            cell = {'col':j,'x':collDots[j]*wR,'width':(collDots[j+1]-collDots[j])*wR,
            'prediction':prediction,'probability':probability}
            cellsR.append(cell)
            del cell
        row = {'y':rowDots[i]*hR, 'height':(rowDots[i+1]-rowDots[i])*hR,
        'row':i,'cells':cellsR}
        out['predictions'].append(row)
        del row
    del probs
    out['cellsCN'] = cellsCN
    return out

def order(d, nr=50, path='save-movie-full.p'):
    d2 =  pickle.load( open( path, "rb" ) )
    movies = d2[0]##year, actor, directo, countr, rating, plot, cats
    p = []
    w = [2, 1, 3, 1, 1, 0.5, 2]
    for id in movies:
        sc = 0
        for m in d:
            if str(m[0]) == id:
                sc = 0
                break
            for i, info in enumerate(movies[str(m[0])]):
                if isinstance(info, list):
                    for it in info:
                        if it in movies[str(id)][i]:
                            sc += w[i]*float(m[1])
                else:
                    if info == movies[str(id)][i]:
                        sc += w[i]*float(m[1])
        p.append([id,sc])
    
    p = sorted(p, key=lambda x: x[1], reverse=True)
        
    return p[:nr]
def make_movie_data(likedMovies, maxCateggoriesMovie=10, maxM=15, path='save-movie-full.p'):
    movie_ids = []
    
    movie_l = []
    cats = []
    year = []
    actor = []
    director = []
    country = []
    rating = []
    plot = []
    u_rating = []
        
    d2 =  pickle.load( open( path, "rb" ) )
    movies = d2[0]##year, actor, directo, countr, rating, plot
    backRatI = {str(i[1][0]):i[0] for i in d2[5].items()}
    
    yearBu = {c:int((c//10)-190) for c in range(1900, 2017)}
    mark = d2[7]
    #print(mark)
    
    #maxCateggoriesMovie , maxCharsTitle, nClases , rr
    for i in range(1):
        v = [a[0] for a in likedMovies]
        r = [a[1] for a in likedMovies]
        
        mmax = min(len(v),maxM)
        cat1 = []
        year1 = []
        actor1 = []
        director1 = []
        country1 = []
        rating1 = []
        u_rating1 = []
        plot1 = []
        movie_ids1 = []
        j = 0
        while j<len(v) and len(cat1)<= maxM:
            movieId = v[j]
            #movieIbmId = df[df['movieId']==movieId]['imdbId'].values[0]
            if str(movieId) not in movies:
                j+=1
                #print(movieId)
                continue
            
            info = movies[str(movieId)]
            #year, actor, director, country, rating, plot
            g1 = info[6]+[20]*(maxCateggoriesMovie-len(info[6])) if len(info[6])<maxCateggoriesMovie else info[6]
            
            cat1.append(g1)
            year1.append(yearBu[info[0]])
            actor1.append(info[1]+[len(d2[2])+1]*(10-len(info[1])) if len(info[1])<10 else info[1])
            director1.append(info[2]+[len(d2[1])+1]*(2-len(info[2])) if len(info[2])<2 else info[2])
            country1.append(info[3]+[len(d2[4])+1]*(3-len(info[3])) if len(info[3])<3 else info[3])
            plot1.append(info[5]+[len(d2[3])+1]*(10-len(info[5])) if len(info[5])<10 else info[5])
            rating1.append(round(backRatI[str(info[4])]))
            u_rating1.append(mark[str(r[j])])#change!!!
            movie_ids1.append(movieId)
            #movie, directorId,actorsId,wordId,countryId,imdbRatingsId
            j+=1
        
        if len(cat1) < 3:
            print ('less than 3')
            continue
        
        movie_l.append(len(cat1)-1)
        movie_ids.append(movie_ids1)    
        
        
        if  len(cat1) < maxM:
            #print(len(v))
            year1.extend([201-190+1]*(maxM-len(cat1)))#ok
            rating1.extend([11]*(maxM-len(cat1)))#ok
            actor1.extend([[len(d2[2])+1]*10]*(maxM-len(cat1)))
            director1.extend([[len(d2[1])+1]*2]*(maxM-len(cat1)))
            country1.extend([[len(d2[4])+1]*3]*(maxM-len(cat1)))
            plot1.extend([[len(d2[3])+1]*10]*(maxM-len(cat1)))
            u_rating1.extend([10]*(maxM-len(cat1)))
            cat1.extend([[20]*maxCateggoriesMovie]*(maxM-len(cat1)))
        
        #print('',len(director1))
        #for c in director1:
        #    print(len(c))
        #break
        cats .append(cat1)
        year .append(year1)
        actor .append(actor1)
        director.append(director1)
        country.append(country1)
        rating .append(rating1)
        plot .append(plot1)
        u_rating .append(u_rating1)#??
        
        
        
        #print(movie_ids)
        
        #break
        
        
    # ids, len, corect, cats
    return np.array(cats), np.array(year),np.array(actor),np.array(director),np.array(country),\
    np.array(rating),np.array(plot),np.array(u_rating),\
    np.array(movie_l)

def order_dl(d, output_graph, nr=50, path='save-movie-full.p'):
    li = order(d,nr, path=path)
    li = [[l[0],1.0] for l in li]
    liked = make_movie_data(d, path=path)
    toOrder = make_movie_data(li,maxM=len(li), path=path)
    toOrder = [np.squeeze(t) for t in toOrder]
    
    #for i,ss in enumerate(toOrder[:8]):
    #    print(i,nn[i],ss.shape)
    
    ids_order = []
    
    #load graph
    tf.reset_default_graph()
    loaded_Graph = load_graph(output_graph)
    
    #nrMovies = loaded_Graph.get_tensor_by_name('prefix/numberofmovies:0')
    batch_size =  loaded_Graph.get_tensor_by_name('prefix/batchsize:0')
    cats = loaded_Graph.get_tensor_by_name('prefix/cats:0')
    actors = loaded_Graph.get_tensor_by_name('prefix/actors:0')
    country = loaded_Graph.get_tensor_by_name('prefix/country:0')
    directors = loaded_Graph.get_tensor_by_name('prefix/directors:0')
    plot = loaded_Graph.get_tensor_by_name('prefix/plot:0')
    year = loaded_Graph.get_tensor_by_name('prefix/year:0')
    ratings = loaded_Graph.get_tensor_by_name('prefix/ratigns:0')
    ratingsI = loaded_Graph.get_tensor_by_name('prefix/ratignsi:0')
    movies_l = loaded_Graph.get_tensor_by_name('prefix/moviessequencelength:0') 
    #ratings_p = loaded_Graph.get_tensor_by_name('prefix/ratingsCorect:0')  
    ratingsI_p = loaded_Graph.get_tensor_by_name('prefix/ratingsCorectI:0')  
    cats_p = loaded_Graph.get_tensor_by_name('prefix/predcitcategories:0')
    actors_p = loaded_Graph.get_tensor_by_name('prefix/actors_p:0')
    country_p = loaded_Graph.get_tensor_by_name('prefix/country_p:0')
    directors_p = loaded_Graph.get_tensor_by_name('prefix/directors_p:0')
    plot_p = loaded_Graph.get_tensor_by_name('prefix/plot_p:0')
    year_p = loaded_Graph.get_tensor_by_name('prefix/year_p:0')
    
    llSt = loaded_Graph.get_tensor_by_name('prefix/laststate:0')
    score = loaded_Graph.get_tensor_by_name('prefix/score:0')
    bs = 1
    nm = 15
    feed = {#nrMovies : nm,
                    batch_size :1,
                    
                    cats : liked[0],#np.ones((bs, nm,maxCateggoriesMovie),dtype=np.int32),
                    actors : liked[2],#np.ones((bs, nm,maxActors),dtype=np.int32),
                    country : liked[4],#np.ones((bs, nm,maxCountry),dtype=np.int32),
                    directors  : liked[3],# np.ones((bs,nm, maxDirectors),dtype=np.int32),
                    plot : liked[6],#np.ones((bs,nm,maxPlot),dtype=np.int32),
                    year : liked[1],#tt,# np.ones((bs,nm),dtype=np.int32)        ,            
                    ratings : liked[7],#np.ones((bs,nm),dtype=np.int32),
                    ratingsI :liked[5],#np.ones((bs,nm),dtype=np.int32),                    
                    movies_l : liked[8]
    }#np.ones(bs,dtype=np.int32)*nm,
    '''
    (0, 'categ', (15, 10))
    (1, 'year', (15,))
    (2, 'acto', (15, 10))
    (3, 'direc', (15, 2))
    (4, 'countr', (15, 3))
    (5, 'ratin', (15,))
    (6, 'plot', (15, 10))
    '''
    feed1 = {
                    batch_size :len(toOrder[5]),
                    ratingsI_p : toOrder[5],#np.ones((bs),dtype=np.int32),                    
                    cats_p : toOrder[0],#np.ones((bs, maxCateggoriesMovie),dtype=np.int32),
                    actors_p : toOrder[2],#np.ones((bs, maxActors),dtype=np.int32),
                    country_p : toOrder[4],#np.ones((bs, maxCountry),dtype=np.int32),
                    directors_p  : toOrder[3],#np.ones((bs, maxDirectors),dtype=np.int32),
                    plot_p : toOrder[6],#np.ones((bs,maxPlot),dtype=np.int32),
                    year_p : toOrder[1]
    }
    
    
    sess =  tf.Session(graph=loaded_Graph)   
    
    
    
    ls = llSt.eval(session=sess, 
                    feed_dict = feed)
    #print(ls.shape)
    
    feed1[llSt] = np.tile(ls, (len(toOrder[5]), 1))
    #print(np.tile(ls, (len(toOrder[5]), 1)).shape)
    sc = score.eval(session=sess, 
                    feed_dict = feed1)
    #print(sc)
    sess.close()
    #print(li)
    sc = [[li[i][0],round(ss[0])] for i,ss in enumerate(sc)]
    
    #sort by the prediciton
    p = sorted(sc, key=lambda x: x[1], reverse=True)
        
    return p[:nr]

def showMovies1(ids, path='save-movie-full.p'):
    '''
        0('categ', (50, 15, 10))
        1('year', (50, 15))
        2('acto', (50, 15, 10))
        3('direc', (50, 15, 2))
        4('countr', (50, 15, 3))
        5('ratin', (50, 15)) - 78
        6('plot', (50, 15, 10))
        7('u-ratin', (50, 15)) - 0-10
        8('len', (50,))
        9('categ_p', (50, 10))
        10('year_p', (50,))
        11('acto_p', (50, 10))
        12('direc_p', (50, 2))
        13('countr_p', (50, 3))
        14('ratin_p', (50,)) - 78
        15('plot_p', (50, 10))
        16('u-ratin_p', (50,)) -10
        17 movies ids'''
    d2 =  pickle.load( open( path, "rb" ) )
    #movie, directorId,actorsId,wordId,countryId,imdbRatingsId, cats, marks
    movies = d2[0]##year, actor, directo, countr, rating, plot, cats, title, url
    backDir = {str(i[1][0]):i[0] for i in d2[1].items()}
    backAct = {str(i[1][0]):i[0] for i in d2[2].items()}
    backPlt = {str(i[1][0]):i[0] for i in d2[3].items()}
    backCou = {str(i[1][0]):i[0] for i in d2[4].items()}
    backRatI = {str(i[1][0]):i[0] for i in d2[5].items()}
    #print(d2[1].items()[:3],len(d2[1]))
    #backRatI = {str(i[1][0]):i[0] for i in d2[5].items()}
    id = 0
    bb = 20
    backCat = {c:d for (d,c) in d2[6].items()}
    
    jss = []
    for i,c in enumerate(ids):
        dd = movies[str(c[0])]
         
        jss . append({'title':dd[7], 'year': dd[0], 'cats':[backCat[d]   for d in dd[6] if d!=20],
                     'actors':[backAct[str(d)]   for d in dd[1] if d!=11480],
                     'director':[backDir[str(d)]   for d in dd[2] if d!=1252],
                     'country':[backCou[str(d)]   for d in dd[3] if d!=64],
                     'url':dd[8], 'rating':round(backRatI[str(dd[4])]),
                     'id':str(c[0])})
        
    return jss

def reorderPoints(points,h,md=5):
    l = len(points)
    points = sorted(points)
    print(points)
    oks = [0]*l
    dif = {}
    for i in range(1,l):
        index = abs(points[i]-points[i-1])
        ok = 1
        for j in range(-4,4):#depends on h            
            if index+j in dif:
                dif[index+j] += 1
                ok = 2
                break
        if ok == 1:
            dif[index] = 1
    sorted_x = sorted(dif.items(), key=operator.itemgetter(1))
    print(sorted_x[-1])
    d = sorted_x[-1][0]
    i = -1
    while d<md and l>-i:
        d = sorted_x[i][0]
        i-=1
    if l<-i:
        print('no good distance founded')
        return points
    print(sorted_x)
    print(d)
    ##?? - aList.insert( 3, 2009), del l[9]
    ee = 0
    while True:
        if ee == 30:
            break
        ee+=1
        toD = -1
        for i in range(1,l):
            if abs(points[i]-points[i-1]) < md:
                toD = i
                break
        toA = -1
        for i in range(1,l):
            if abs(points[i]-points[i-1])/1.9 >= d:
                toA = i-1
                break
        print('d',toD,'a',toA,points[toD],points[toA])
        if toD != -1 and toA != -1:
            x = points[toA]
            del points[toD]
            points.insert(toA,x+d)
        elif toD != -1 and points[-1] + d < h:    
            del points[toD]
            points.append(points[-1]+d)
        elif toD != -1 and points[0] - d > 0:      
            del points[toD]
            points.insert(0,points[0]-d)
        else:
            break
    return points




parser = argparse.ArgumentParser()
parser.add_argument("--path", help="the path to the image",
                    type=str,default='tensorflow/test7.jpg')
parser.add_argument("--path_s", help="the path to save croped image",
                    type=str,default='sites/default/files/tensorflow/test.jpg')
parser.add_argument("--stage", help="the path to the image",
                    type=str,default='square')
parser.add_argument("--points", help="the path to the image",
                    type=str,default='50-50-50-900-550-900-550-50')
parser.add_argument("--hLines", help="the path to the image",
                    type=str,default='50-50-50-900-550-900-550-50')
parser.add_argument("--vLines", help="the path to the image",
                    type=str,default='50-50-50-900-550-900-550-50')
parser.add_argument("--path_dataset", help="the path to save the dataset",
                    type=str,default='sites/default/files/tensorflow/test.p')
parser.add_argument("--preds", help="the predictions of the images",
                    type=str,default='1-5-5-9_5-9-0-0')
parser.add_argument("--pathGraph", help="the predictions of the images",
                    type=str,default='tensorflow/models/model-slim-cnn13/checkpoint-2000.meta')
parser.add_argument("--pathVars", help="the predictions of the images",
                    type=str,default='tensorflow/models/model-slim-cnn13/checkpoint-2000.index')
parser.add_argument("--cols", help="the path to the image",
                    type=int,default=10)
parser.add_argument("--rows", help="the path to the image",
                    type=int,default=10)
parser.add_argument("--height", help="the path to the image",
                    type=int,default=1000)
parser.add_argument("--width", help="the path to the image",
                    type=int,default=600)
args = parser.parse_args()

def logic(args):
    out = {'status':'ok','stage':'square','height':args.height,'width':args.width}
    if args.stage == 'square':
        if os.path.isfile(args.path):
            out = {'status':'ok','stage':'square','height':args.height,'width':args.width}
            image = cv2.imread(args.path)
            #get rect
            out = f2(image,out)        
            out['heightT'] = image.shape[0];
            out['widthT'] = image.shape[1];
            print (json.dumps(out))
            return out
        else:
            print("{'status':'error'}")
    elif args.stage == 'lines':#args-cols,rows
        #crop
        image = cv2.imread(args.path)
        crop = cr(image,out,args)
        out['heightT'] = crop.shape[0];
        out['widthT'] = crop.shape[1];
        cv2.imwrite(args.path_s,crop)

        #print(reorderPoints([58,60,61,112,111,160,261,310,359],470))
        #get line
        if True:
            #todo play to edge detection like with height
            dotsC, dotsR = getLines(crop,margin=1,debug=False)
            print(crop.shape)
            print(len(dotsR),len(dotsC))
            args.cols = int(args.cols)
            args.rows = int(args.rows)
            if len(dotsC) > args.cols  and len(dotsR) > args.rows:
                dotsCs = getKMean(dotsC,args.cols)
                
                dotsRs = getKMean(dotsR,args.rows)

                dotsCs = reorderPoints(dotsCs,crop.shape[1],md=15)
                dotsRs = reorderPoints(dotsRs,crop.shape[0],md=15)
                

                hR =  1.# out['height']/crop.shape[0]
                wR =   1.#out['width'] /crop.shape[1]
                out['vLines'] = [int(c*wR) for c in dotsCs]
                out['hLines'] = [int(c*hR) for c in dotsRs]
                out['path_s'] = args.path_s
                print (json.dumps(out))
                return out
            else:
                hR =  1.# out['height']/crop.shape[0]
                wR =   1.#out['width'] /crop.shape[1]
                hh = int((crop.shape[0]-10.)/(int(args.rows)-1))
                ww = int((crop.shape[1]-10.)/(int(args.cols)-1))
                bb = 5
                out['hLines'] = [int(((c*ww)+bb)*hR) for c in range(args.rows)]
                out['vLines'] = [int(((c*hh)+bb)*wR) for c in range(args.cols)]
                out['path_s'] = args.path_s
                print (json.dumps(out)) 
                return out
        
    elif args.stage == 'crop':
        #crop
        image = cv2.imread(args.path)
        crop = cr(image,out,args)
        out['heightT'] = crop.shape[0];
        out['widthT'] = crop.shape[1];
        out['path_s'] = args.path_s
        cv2.imwrite(args.path_s,crop)
        kernel = np.ones((3,3),np.uint8)*255
        crop = cv2.dilate(crop,kernel,iterations = 1)
        #get rect
        out = f2(crop,out)
        print (json.dumps(out))
        return out
    elif args.stage == 'dataset_check':
        try:
            obj = pickle.load( open( args.path, "rb" ) ) 
            #todo:not saving as had to
            print(obj['x'][10])
            ids = np.random.randint(len(obj['x'])-1)
            cv2.imwrite(args.path_s,obj['x'][ids].reshape((28,28))*255)
            out['path_s'] = args.path_s
            out['image_label'] = np.argmax(obj['y'][ids])
        except Exception, e:
            print (e)
            out['status'] = 'error-load'
        for c in args.points.split('_'):
            s = c.split('-')
            if s[0] not in obj or int(s[1])!=len(obj[s[0]][0]):
                print(c,len(obj[s[0]][0]))
                out['status'] = 'error-'+c
        print (json.dumps(out))
        return out
    elif args.stage == 'dataset':
        #crop
        image = cv2.imread(args.path)
        p = args.points.split('_')
        args.points = p[0]
        crop = cr(image,out,args)
        #kernel = np.ones((3,3),np.uint8)*255
        #crop = cv2.dilate(crop,kernel,iterations = 1)

        args.points = p[1]
        crop = cr(crop,out,args)
        out['heightT'] = crop.shape[0];
        out['widthT'] = crop.shape[1];
        out['path_s'] = args.path_s

        cv2.imwrite(args.path_s,crop)

        hR = crop.shape[0]/out['height']
        wR = crop.shape[1]/ out['width']
        vLines = [int(int(c)*wR) for c in args.vLines.split('-')]
        hLines = [int(int(c)*hR)  for c in args.hLines.split('-')]
        collDots = sorted(vLines)
        rowDots = sorted(hLines)
        cells = []
        for i in range(len(rowDots)-1):        
            for j in range(len(collDots)-1):
                cellI = crop[rowDots[i]:rowDots[i+1],collDots[j]:collDots[j+1]]
                cells.append(preprocess(cellI).reshape(-1))

        preds = []
        for c in args.preds.split('_'):
            for s in c.split('-'):
                v = [0.0]*13
                v[int(s)]=1.0
                preds.append(v)
        #print(preds[:2]);
        obj = {'x':np.array(cells),'y':np.array(preds)}
        pickle.dump( obj , open( args.path_dataset, "wb" ) )
        out['path_dataset'] = args.path_dataset
        out['stage'] = 'dataset'
        print (json.dumps(out))
        return out
    elif args.stage == 'edit':
        #crop
        image = cv2.imread(args.path)
        p = args.points.split('_')
        args.points = p[0]
        crop = cr(image,out,args)
        #kernel = np.ones((3,3),np.uint8)*255
        #crop = cv2.dilate(crop,kernel,iterations = 1)

        args.points = p[1]
        crop = cr(crop,out,args)
        out['heightT'] = crop.shape[0];
        out['widthT'] = crop.shape[1];
        out['path_s'] = args.path_s

        cv2.imwrite(args.path_s,crop)

        print (json.dumps(out))
        return out
    elif args.stage == 'predict':#vlines, hlines, hb, he, vb, ve
        #get cell - parse lines string
        image = cv2.imread(args.path)
        hR =  1.#image.shape[0]/out['height']
        wR = 1.#image.shape[1]/ out['width']
        print(tf.__version__)
        args.cols = int(args.cols)
        args.rows = int(args.rows)
        vLines = [int(int(c)*wR) for c in args.vLines.split('-')]
        hLines = [int(int(c)*hR)  for c in args.hLines.split('-')]
        vLines = sorted(vLines)
        hLines = sorted(hLines)
        #print(hLines)
        #print(vLines)
        #print(args)

        

        #predict - preprocess
        out['pathGraph'] = args.pathGraph
        out['pathVars'] = args.pathVars
        out['colmnN'] = int(args.colmnN)
        out = getCells(image,hLines,vLines,out)#each cell from each row
        del image
        del hLines
        del vLines
        out['ans'] = 1        
        print(len(out['cellsCN']),'len cellsCN', out['colmnN'])
        #print (json.dumps(out))
        return out
    return "{'status':'error'}"