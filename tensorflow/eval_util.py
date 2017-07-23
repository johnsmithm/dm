"""Provides functions to help with evaluating models."""
import datetime
import numpy as np
import operator

def levenshtein(source, target, mat = None):
    #todos:\/
    #add different score for changing a letter into another
    #add different score for adding a letter before and after another letter ?
    if len(source) < len(target):
        return levenshtein(target, source)

    # So now we have len(source) >= len(target).
    if len(target) == 0:
        return len(source)

    # We call tuple() to force strings to be used as sequences
    # ('c', 'a', 't', 's') - numpy uses them as values by default.
    source = np.array(tuple(source))
    target = np.array(tuple(target))
    
    mm = np.ones((len(source),len(target)))
    #mat = getMat()
    if mat is not None:
        for i,p1 in enumerate(source):
            for j,p2 in enumerate(target):
                mm[i,j] = mat[p1,p2]

    # We use a dynamic programming algorithm, but with the
    # added optimization that we only need the last two rows
    # of the matrix.
    previous_row = np.arange(target.size + 1).astype("float32")
    for i,s in enumerate(source):
        # Insertion (target grows longer than source):
        current_row = previous_row + 1

        # Substitution or matching:
        # Target and source items are aligned, and either
        # are different (cost of 1), or are the same (cost of 0).
        current_row[1:] = np.minimum(
                current_row[1:],
                np.add(previous_row[:-1], mm[i,:]*(target != s)))

        # Deletion (target grows shorter than source):
        current_row[1:] = np.minimum(
                current_row[1:],
                current_row[0:-1] + 2)

        previous_row = current_row

    return previous_row[-1]

def getIndex(c,voc):
    for name, age in voc.iteritems():
        if age == c:
            return name
    print("-"*30,"error-",c)
    return None


def get_trie(vocabulary, sp=56):
    Trie = {}
    def add_trie(trie, w, n = 0):

        if n == len(w):
            trie[0] = 1
            return
        if w[n] == sp:
            add_trie(Trie, w[n+1:] )
            return
        if w[n] not in trie:
            trie[w[n]] = {}
        add_trie(trie[w[n]], w , n + 1)
    for w in vocabulary:
        add_trie(Trie, w)
    return Trie
def trie_exist(trie, w, n = 0):
    
    if n == len(w):
        
        if 0 in trie:
            return True
        return False
    if w[n] not in trie:
        return False
    return trie_exist(trie[w[n]], w, n + 1)

def bi_gram_model(w, tr, bi, on):
    #print(w)
    if len(w)>2:
        return tr[w[-3],w[-2],w[-1]]
    if len(w)>1:
        return bi[w[-2],w[-1]]
    if len(w)==1:
        return on[w[0]]
    return 0.
def softmax(x):
    """Compute softmax values for each sets of scores in x."""
    return x/np.max(x)
    e_x = np.exp(x - np.max(x))
    return e_x / e_x.sum()
def get_n_gram(vocab, vocab_size):    
    tri_gram = np.zeros([vocab_size]*3)
    bi_gram = np.zeros([vocab_size]*2)
    one_gram = np.zeros([vocab_size])
    mB = 0
    mT = 0
    nrL = 0
    for w in vocab:
        nrL+=1
        if w[0]>vocab_size:
            print w[0]
        one_gram[w[0]]+=1
        for j in range(1,len(w)):
            nrL+=1
            one_gram[w[j]]+=1
            bi_gram[w[j-1],w[j]] += 1.
            mB = max(mB,bi_gram[w[j-1],w[j]])
            if j>1:
                tri_gram[w[j-2],w[j-1],w[j]] += 1
                mT = max(mT, tri_gram[w[j-2],w[j-1],w[j]])
            
    return softmax(one_gram), softmax(bi_gram), softmax(tri_gram)

def sort_prediction(preds, vocL, traz, dis, ch, maxD=1, k=10):
    """
        pr: (batch,number prediction,[prob, prob blank, list characters] ) from beam search
        voc: list of words as numbers
        traz: function one argument, returns false or true if word are in vocab
        ch: map from ids to letters
        dis: fucntion, two arguments, edit distance between two words
        maxD: maximum edit distance allow to take into account the word as posible prediction
        k: number of prediction to return
    """
    lmd_pred = [[] for _ in range(len(preds[0][0][0]))]
    for i in range(len(preds[0][0][0])):
        for j in range(len(preds[0])):
            lmd_pred[i].append([0,0,[c for c in preds[0][j][0][i] if c]])
    preds = lmd_pred
    pr = [{} for _ in range(len(preds))]
    nu = [{} for _ in range(len(preds))]
    def f(p,a):
        if traz(p):
            p1 = ''.join([getIndex(j,ch) for j in p if j])
            if p1 in a:
                a[p1]+=1
            else:
                a[p1]=1
        else:
            m = maxD + 1
            l = []
            for v in vocL:
                if abs(len(v)-len(p))<=maxD:
                    d = dis(v,p)
                    if d<m:
                        m=d
                        l = [v]
                    if d==m:
                        l.append(v)
            for v in l:
                v1 = ''.join([getIndex(j,ch) for j in v if j])
                if v1 in a:
                    a[v1]+=0.01*(1./len(l))
                else:
                    a[v1]=0.01*(1./len(l))
            if len(l)==0:
                p1 = ''.join([getIndex(j,ch) for j in p if j])
                if p1 in a:
                    a[p1]+=0.01
                else:
                    a[p1]=0.01
    sp = ch[' ']
    for i,b in enumerate(preds):
        for j,p in enumerate(b):
            ar = []
            k = 0
            a = []
            while k<len(p[2]):
                if p[2][k] == sp and len(a)>0:
                    ar.append(a[:])
                    del a[:]
                    a = []
                else:
                    a.append(p[2][k])
                k+=1
            if len(a)>0:
                    ar.append(a[:])
            #print(ar,p[2])
            if len(ar) < 1:
                continue
            f(ar[0],nu[i])
            if len(ar) < 2:
                continue
            f(ar[1],pr[i])            
            if len(ar) < 3:
                continue                
            f(ar[2],pr[i])
        #print(nu[i],pr[i])
            #maybe sort here
    #sort the dict
    #print(nu[0].items())
    predsN = []
    for i in range(len(preds)):
        #print(nu[i],pr[i],'==')
        if len(nu[i])>0:
            #print(nu[i].items(),'00')
            #print(sorted(nu[i].items(), key=operator.itemgetter(1),reverse=True)[:k])
            nu[i] = sorted(nu[i].items(), key=operator.itemgetter(1),reverse=True)[:k]
            #nu[i].items().sort(key=lambda x: x[1], reverse=True)[:k]
            #print(nu[i],'nu[i]')
        else:
            nu[i] = [['',0]]
        if len(pr[i])>0:
            pr[i] = sorted(pr[i].items(), key=operator.itemgetter(1),reverse=True)[:k]
            #pr[i].items().sort(key=lambda x: x[1], reverse=True)[:k]
        else:
            pr[i] = [['',0]]
        #print(nu[i],pr[i],'--')
        k1 = 0;j=0
        predsN.append([])
        #print(i)
        #print(predsN[i])
        #print(nu[i],pr[i])
        while 1:
            predsN[i].append(nu[i][k1][0]+' '+pr[i][j][0])
            k1+=1
            j+=1
            if k1==len(nu[i]):
                k1=0
            if j==len(pr[i]):
                j=0
                if j==k1:
                    if len(pr[i])>1:
                        j=1
                    elif len(nu[i])>1:
                        k1=1
            if len(predsN[i])==k or len(predsN[i])==len(pr[i])*len(nu[i]):
                break
    #print(pr,nu)
    return predsN#maybe join them