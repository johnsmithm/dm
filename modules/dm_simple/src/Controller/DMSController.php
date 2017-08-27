<?php

namespace Drupal\dm_simple\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\examples\Utility\DescriptionTemplateTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Drupal\node\Entity\Node;


/**
 * Controller routines for page example routes.
 */
class DMSController extends ControllerBase {

  #use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'dm_simple';
  }

  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'dm_simple/simple' to this method.
   *
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  public function simple() {
    $current_user = \Drupal::currentUser();//getName
    $language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
    $textL = ['photo'=>['ro'=>'Pentru a incarca propriile fotografii inregistrativa!',
    'en'=>'In order to upload your own photos you need to log in!']
    ,'limits'=>['en'=>'3 requests limits for un non-authenticated users!',
    'ro'=>'Sunt permise 3 incercari pentru o zi pentru utilizatorii neinregistrati!']];
    #dsm($current_user);
    $notAut = !isset($_REQUEST['imgs']) && ($current_user->id()!=0);
    #dsm($language);
  	if ($notAut && (!isset($_SESSION['dm_simple_FID']) || $_SESSION['dm_simple_FID']==0 ||
      !file_load($_SESSION['dm_simple_FID']))){
  		return \Drupal::formBuilder()->getForm('Drupal\dm_simple\Form\DMSimpleForm');
  	}
    if(!isset($_SESSION['dm_simple_TIMES']))
      $_SESSION['dm_simple_TIMES'] = 0;
    if($_SESSION['dm_simple_TIMES']>3 && $current_user->id()==0)
    return ['content'=>['#markup'=>
    "<p>".$textL['limits'][$language]."</p>"]];
    #$result = self::make_post_request(['action'=>'433']);
    #dsm($result);
    if(!$notAut and False){
    	$fid = $_SESSION['dm_simple_FID'];
    	$file = file_load($fid);
      $path_i = file_create_url($file->getFileUri());
      $image_factory = \Drupal::service('image.factory');
      $image = $image_factory->get($file->getFileUri());
    }else{
      $query = \Drupal::entityQuery('node')
      ->condition('type', 'tensorflow_wizard');
      $nids = $query->execute();
      //dsm(node_load_multiple($nids));
      $path_i = [];
      if($notAut && isset($_SESSION['dm_simple_FID']) && 
        file_load($_SESSION['dm_simple_FID']) &&
        $_SESSION['dm_simple_FID'] != 0){
        $fid = $_SESSION['dm_simple_FID'];
        $file = file_load($fid);
        #dsm($file);
        $image_factory = \Drupal::service('image.factory');
        $image = $image_factory->get($file->getFileUri());
        $path_i[] = [file_create_url($file->getFileUri()),
                     $image->getWidth(), $image->getHeight(),$fid];
      }
      $fid = 0;
      foreach (node_load_multiple($nids) as $key => $value) {
        $fid = $value->get('field_input_image')->getValue()[0]['target_id'];
        $file = file_load($fid);     
        $image_factory = \Drupal::service('image.factory');
        $image = $image_factory->get($file->getFileUri());
        if (!in_array($fid,[34,38]))
        $path_i[] = [file_create_url($file->getFileUri()),
                     $image->getWidth(), $image->getHeight(),$fid];
        //dsm($value->toArray()['title'][0]['value']);
        //break;
      }
    }
    //$image = image_load($file->getFileUri());
    
  	//dsm($file->toArray());
  	//dsm($path_i);
  	$build = array();
    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '
       <h5>'.$textL['photo'][$language].'</h5>
      <div id="buttons">
       
      </div>
      <div id="textboxes"></div>
      <div  id="container">        
      </div>
      <div id="dialog" style="height:300px;overflow: scroll;" title="Basic dialog"></div>
      
      <div class="modal"><!-- Place at bottom of page --></div>
      ',
    );

    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'dm_simple/conva.min';
    $build['#attached']['library'][] = 'dm_simple/dm_simple.test';

    $result = ['type'=>'init','path_i'=>$path_i,'width'=>700,'height'=>2000,
    'container'=>'container','fid'=>$fid,'scale'=>0.5];
    if ($notAut && $image->isValid()){
      $result['imgW']=$image->getWidth();
      $result['imgH']=$image->getHeight();
    }
    if (False and $image->isValid()){
      $command = 'python tensorflow/run.py --path '.'sites'
      .explode('/sites', $path_i)[1].' --stage square --height '.$result['imgH'].
      ' --width '.$result['imgW'];
      dsm($command);
      $outC = exec($command);
      //dsm($outC);
      $outC = json_decode($outC, true);
      if($outC['status']=='ok'){        
        $result['po'] = $outC['po'];        
      }else{
        dsm('error');
      }
    }
    //models
    $query = \Drupal::entityQuery('node')
    ->condition('type', 'training_job');
    $nids = $query->execute();
    //dsm(node_load_multiple($nids));
    $result['models'] = [];
    foreach (node_load_multiple($nids) as $key => $value) {
      $result['models'][] = [$value->toArray()['nid'][0]['value'],
      $value->toArray()['title'][0]['value']];
      //dsm($value->toArray()['title'][0]['value']);
      //break;
    }
    $result['lang'] = $language;
    $build['#attached']['drupalSettings']['dm_simple']['dm_simple'] = $result;
    
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Constructs a simple request handler.
   *
   *
   *
   * @param string $data
   *
   */
  public function make_post_request($data){
    $url = 'http://localhost:8000/';
    #$data = array('action' => '2e', 'key2' => 'value2');
    dsm(http_build_query($data));
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    dsm($response);
    curl_close($curl);
    return $response;

    //return $result;
  }

  /**
   * Constructs a simple ajax page.
   *
   * The router _controller callback, maps the path
   * 'dm_simple/ajax' to this method.
   *
   *
   * @param string $nid
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  public function ajax() {
  	$data = $_REQUEST['data'];
    $result = array();
    if ($_REQUEST['action'] == 'crop'){
      $file = file_load($data['fid']);
      $path_i = file_create_url($file->getFileUri());
      $image_factory = \Drupal::service('image.factory');
      $image = $image_factory->get($file->getFileUri());

      $paths = "sites/default/files/tensorflow/page-".$data['fid'].
      date("-Y-m-d-h:i:sa").".jpg";

    	$path_i = $data['path_i'];
      $points = '';
      foreach ($data['po1'] as $key => $value) {
        if($key != 0)
          $points .= '-';
        $points .= floor($value[0])."-".floor($value[1]);
      }
      //exec("rm sites/default/files/tensorflow/page-".$data['fid']."*.jpg");
      $command = 'python tensorflow/run.py --path '.'sites'.explode('/sites',
      $path_i)[1].' --stage crop --points '.$points.' --height '.$image->getHeight().
      ' --width '.$image->getWidth().' --path_s '.$paths ;
      #dsm($command);
      
      $outC = ['status'=>'false'];#exec($command);
      #dsm($outC);
      $outC = json_decode($outC, true);
      if($outC['status']=='ok'){
        $result = $outC;
        $result['command'] = $command;
      }else{
        //dsm('error');        
        $result['command'] = $command;
      }
      $result['poi'] = $command;      
    }else if($_REQUEST['action'] == 'lines'){
      $file = file_load($data['fid']);
      $path_i = file_create_url($file->getFileUri());
      $image_factory = \Drupal::service('image.factory');
      $image = $image_factory->get($file->getFileUri());

      $path_i = $data['path_i'];
      $points = '';
      foreach ($data['po1'] as $key => $value) {
        if($key != 0)
          $points .= '-';
        $points .= floor($value[0])."-".floor($value[1]);

      }
      
      $cols = $data['cols'];
      $rows = $data['rows'];
      //exec('rm sites/default/files/tensorflow/table-'.$data['fid'].'*.jpg');
      $command = 'python tensorflow/run.py --path '.'sites'.explode('/sites',
      $path_i)[1].' --stage lines --points '.$points.
      ' --cols '.$cols.' --rows '.$rows.
      ' --path_s sites/default/files/tensorflow/table-'.$data['fid']
      .date("-Y-m-d-h:i:sa").'.jpg'.
      ' --height '.$image->getHeight().
      ' --width '.$image->getWidth();
      #dsm($command);
      
      $outC = [];#exec($command);
      #dsm($outC);
      $outC = json_decode($outC, true);
      if($outC['status']=='ok'){ 
        $result = $outC;
        $result['command'] = $command;
      }else{
        //dsm('error');        
        $result['command'] = $command;
      }
      $result['poi'] = $command;      
    }else if($_REQUEST['action'] == 'predict'){
      $_SESSION['dm_simple_TIMES'] += 1;
      $path_i = $data['path_i'];
      $vLines = '';
      foreach ($data['vLines'] as $key => $value) {
        if($key != 0)
          $vLines .= '-';
        $vLines .= floor($value);
      }
      $hLines = '';
      foreach ($data['hLines'] as $key => $value) {
        if($key != 0)
          $hLines .= '-';
        $hLines .= floor($value);
      }
      
      $cols = $data['cols'];
      $rows = $data['rows'];

      $modelPath = '';
      if(False and $data['model']!=''){
        $modelNode = node_load($data['model']*1);
        if(count($modelNode->get('field_metagraph')->getValue())>0 &&
          count($modelNode->get('field_graph_variables')->getValue())>0){
          $fid = $modelNode->get('field_metagraph')->getValue()[0]['target_id'];
          $file = file_load($fid);
          $path_i1 = file_create_url($file->getFileUri());
          $modelPath = ' --pathGraph '.'sites'.explode('/sites', $path_i1)[1];

          $fid = $modelNode->get('field_graph_variables')->getValue()[0]['target_id'];
          $file = file_load($fid);
          $path_i1 = file_create_url($file->getFileUri());
          $modelPath .= ' --pathVars '.'sites'.explode('/sites', $path_i1)[1];          
        }
        $result['modelspp0']=$modelPath;
      }
      
      $command = 'python tensorflow/run.py --path '.'sites'.explode('/sites',
      $path_i)[1].' --stage predict'.
      ' --cols '.$cols.' --rows '.$rows.
      ' --path_s sites/default/files/tensorflow/table-'.$data['fid'].'.jpg'.
      ' --vLines '.$vLines.' --hLines '.$hLines.$modelPath;
      if($data['colmnN']!='')
        $command .= ' --colmnN '.$data['colmnN'];
      else
        $command .= ' --colmnN -1';
      #dsm($command);
      
      $outC = [];#exec($command);
      #dsm($outC);
      $outC = json_decode($outC, true);
      if($outC['status']=='ok'){  
        $result = $outC;
        $result['command'] = $command;
      }else{
        //dsm('error');        
        $result['command'] = $command;
      }
      $result['poi'] = $command;
    }else if($_REQUEST['action'] == 'reset'){
      $_SESSION['dm_simple_FID'] = 0;
      $_SESSION['dm_simple_nid'] = 0;
    }else if($_REQUEST['action'] == 'count'){
      $_SESSION['dm_simple_TIMES'] += 1;
      $current_user = \Drupal::currentUser();
      $result['count'] = 0;
      if($_SESSION['dm_simple_TIMES']>3 && $current_user->id()==0)
        $result['count'] = 1;
    }else if($_REQUEST['action'] == 'save' && isset($_SESSION['dm_simple_nid'])
      && $_SESSION['dm_simple_nid'] != 0){
      #get the last prediction
      $rows = explode('"',$_REQUEST['st']);
      foreach ($rows as $key => $value) {
         $values = explode(',', $value);
         foreach ($values as $key1 => $value1) {
           if($value1 != 12){
            //save the node, check for the names, make users if needed
            
            $result[] = $value1;
           }
         }
       }

      $_SESSION['dm_simple_nid'] = 0;
    }else if($_REQUEST['action']=='getFile'){
      $_SESSION['dm_chatbot_file'] = 1;
    }else if($_REQUEST['action']=='openfile'){
      $file = file_load($_REQUEST['file']);
      $path_i1 = file_create_url($file->getFileUri());
      $result[] = $path_i1;
      $f = explode('/sites/', $path_i1);
      $result[] = 'sites/'.$f[1];
      $file = file('sites/'.$f[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      $result[] = $file;
    }else if($_REQUEST['action']=='savefile'){      
      $file = file_load($_REQUEST['file']);
      $path_i1 = file_create_url($file->getFileUri());
      $f = explode('/sites/', $path_i1);
      $result[] = $path_i1;

      #$result[] = $_REQUEST['fileText']['rental'];
      //write to file!!
      $text = '';
      $myfile = fopen('sites/'.$f[1], "w");
      foreach ($_REQUEST['fileText'] as $key => $value) {
        fwrite($myfile, $key."\n");
        fwrite($myfile, "###beginQ\n");
        $text .= $key."\n###beginQ\n";
        foreach ($value['ques'] as $key1 => $value1) {
          fwrite($myfile, $value1."\n");
          $text .= $value1."\n";
        }        
        $text .= "###endQ\n###beginA\n";
        fwrite($myfile, "###endQ\n");
        fwrite($myfile, "###beginA\n");
        foreach ($value['ques'] as $key1 => $value1) {
          fwrite($myfile, $value1."\n");
          $text .= $value1."\n";
        }
        fwrite($myfile, "###endA\n");
        $text .= "###endA\n";
      }
      fclose($myfile);
      $result['text'] = $text;
    }
    return new JsonResponse($result);
  }

  /**
   * Constructs a sequence page.
   *
   * The router _controller callback, maps the path
   * 'sequence' to this method.
   *
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  function dm_sequence(){
    $language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
     $textL = ['photo'=>['ro'=>'Pentru a incarca propriile fotografii inregistrativa!',
    'en'=>'In order to upload your own photos you need to log in!'],
    'credits'=>['en'=>'IAM dataset was used!',
    'ro'=>'Pentru antrenarea modelului a fost folosit setul de imagini IAM'],
    'limits'=>['en'=>'3 requests limits for un non-authenticated users!',
    'ro'=>'Sunt permise 3 incercari pentru o zi pentru utilizatorii neinregistrati!']];
   
    $current_user = \Drupal::currentUser();
    if(!isset($_SESSION['dm_simple_TIMES']))
      $_SESSION['dm_simple_TIMES'] = 0;
    if($_SESSION['dm_simple_TIMES']>3 && $current_user->id()==0)
      return ['content'=>['#markup'=>
    '<p>'.$textL['limits'][$language].'</p>']];
   
    $build = array();

    #dsm($current_user);
    $notAut = !isset($_REQUEST['imgs']) && ($current_user->id()!=0);
    #dsm($notAut);
    if ($notAut && (!isset($_SESSION['dm_simple_FID']) || $_SESSION['dm_simple_FID']==0 ||
      !file_load($_SESSION['dm_simple_FID']))){
      return \Drupal::formBuilder()->getForm('Drupal\dm_simple\Form\DMSimpleForm');
    }

    $build['#attached']['library'][] = 'dm_simple/conva.min';
    $build['#attached']['library'][] = 'dm_simple/sequence.test';

    $build['content'] = array(
      '#markup' => '
      <h5>'.$textL['photo'][$language].'</h5>
      <div id="buttons">
      </div>
      <div  id="container"></div>
      
      <div id="dialog" style="height:300px;overflow: scroll;" title="Basic dialog"></div>
      <p>'.$textL['credits'][$language].'</p>
      <div class="modal"><!-- Place at bottom of page --></div>
      ',
    );
    $build['#cache']['max-age'] = 0;
    $result = ['path'=>'/modules/dm_simple/imgs/a01-011u.png','stage'=>'img'];
    if($notAut && isset($_SESSION['dm_simple_FID']) && 
        file_load($_SESSION['dm_simple_FID']) &&
        $_SESSION['dm_simple_FID'] != 0){
      $fid = $_SESSION['dm_simple_FID'];
      $file = file_load($fid);
      #dsm($file);
      $image_factory = \Drupal::service('image.factory');
      //dsm($file->getFileUri());
      $image = $image_factory->get($file->getFileUri());
      $parts = explode('/', file_create_url($file->getFileUri()));
      //dsm($parts);
      for($i=0;$i<3;$i++)
        unset($parts[$i]);
      $url = implode('/', $parts);
      //dsm($url);
      $result['path'] = '/'.$url;//file_create_url($file->getFileUri());
    }
    $result['lang'] = $language;
    $build['#attached']['drupalSettings']['dm_sequence']['dm_sequence'] = $result;
    return $build;
  }

  /**
   * Constructs a chatbot page.
   *
   * The router _controller callback, maps the path
   * 'sequence' to this method.
   *
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  function dm_chatbot(){
    $current_user = \Drupal::currentUser();//getName

    if (0 && isset($_SESSION['dm_chatbot_file']) && $_SESSION['dm_chatbot_file']==1 &&
      $current_user->id()!=0){
      $_SESSION['dm_chatbot_file'] = 0;
      return \Drupal::formBuilder()->getForm('Drupal\dm_simple\Form\DMSimpleForm');
    }
    $language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
     $textL = ['info'=>['ro'=>'Acest robot poate raspunde la intrebari referiotoare la orele de deschidere a unui magazin de inchiriere a masinilor, metode de plata, salutare, multumiri(just english)',
    'en'=>'The bot can answer questions about a rental shop, the open hours, the payments methods and car information!'],
    'credits'=>['en'=>'Tensorflow was used to train the model!',
    'ro'=>'Pentru antrenarea modelului a fost libraria Tensorflow']];
    
    $build = array();
    $build['#attached']['library'][] = 'dm_simple/chatbot.test';

    $tr = '
<div id="kont">
  
  <div id="name"></div>
  <div class="img"></div>
  <div class="bar">
  </div>
  

  
  
  <div class="footer">
  </div>
  <div id="backgroundofkont">
   
  <ul id="messages"> 

  </ul>
   
 
  </div>
  
</div>
  ';

    $build['content'] = array(
      '#markup' => '
      <h5>'.$textL['info'][$language].'</h5>
      <div id="buttons1"></div>
      <div id="buttonsx">
      </div>
      <div  id="container"></div>
      
      <div id="dialog" style="max-height:1000px;overflow: scroll;" title="Basic dialog"></div>
      <p>'.$textL['credits'][$language].'</p>
      <div class="modal"><!-- Place at bottom of page --></div>
      ',
    );
    $result = ['auth'=>$current_user->id()!=0];
    if (0 && isset( $_SESSION['dm_simple_FID']) && isset($_SESSION['dm_chatbot_file'])
       && $_SESSION['dm_simple_FID']>0){
      $result['file'] = $_SESSION['dm_simple_FID'];
      $file = file_load($result['file']);
      $path_i1 = file_create_url($file->getFileUri());
      $result[] = $path_i1;
      $f = explode('/sites/', $path_i1);
      $result['filepath'] = 'sites/'.$f[1];
    }
    $build['#cache']['max-age'] = 0;
    $build['#attached']['drupalSettings']['dm_chatbot']['dm_chatbot'] = $result;
    return $build;
  }

  /**
   * Constructs a movies page.
   *
   * The router _controller callback, maps the path
   * 'sequence' to this method.
   *
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  function dm_movies(){
    $current_user = \Drupal::currentUser();//getName

    
    $language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
     $textL = ['info'=>['ro'=>'Apreciaza cateva filme apoi vezi recomandarile!',
    'en'=>'Just rank some movies then go to Recommendations Tab!'],
    'credits'=>['en'=>'Tensorflow was used to train the model!',
    'ro'=>'Pentru antrenarea modelului a fost libraria Tensorflow']];
    
    $build = array();
    $build['#attached']['library'][] = 'dm_simple/movies.test';

    $build['content'] = array(
      '#markup' => '
      <h5>'.$textL['info'][$language].'</h5>
      
      <div id="buttons">
      </div>
      
      <div id="tabs">
      <ul>
        <li><a href="#tabs-1">Movies</a></li>
        <li><a id="likedM" href="#tabs-2">Liked</a></li>
        <li><a id="recodm" href="#tabs-3">Recommendations</a></li>
      </ul>
      <div id="tabs-1">
        <div id="Mheader"></div>
        <div id="Mbody"></div>
      </div>
      <div id="tabs-2">
        <div id="Mheader"></div>
        <div id="Mbody"></div>
       </div>
      <div id="tabs-3">
        <div id="Mheader"></div>
        <div id="Mbody"></div>
      
      </div>
    </div>
      
      <div id="dialog" style="max-height:1000px;overflow: scroll;" title="Basic dialog"></div>
      <p>'.$textL['credits'][$language].'</p>
      <div class="modal"><!-- Place at bottom of page --></div>
      ',
    );
    $result = ['auth'=>$current_user->id()!=0];
    
    $build['#cache']['max-age'] = 0;
    $build['#attached']['drupalSettings']['dm_movies']['dm_movies'] = $result;
    return $build;
  }
}