line=$(head -n 1 tensorflow/flask.txt)
 
kill $line

python tensorflow/p.py &
FOO_PID=$!
echo "$FOO_PID" > tensorflow/flask.txt
