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
    $notAut = False;
  	if ($notAut && (!isset($_SESSION['dm_simple_FID']) || $_SESSION['dm_simple_FID']==0 ||
      !file_load($_SESSION['dm_simple_FID']))){
  		return \Drupal::formBuilder()->getForm('Drupal\dm_simple\Form\DMSimpleForm');
  	}
    if(!isset($_SESSION['dm_simple_TIMES']))
      $_SESSION['dm_simple_TIMES'] = 0;
    if($_SESSION['dm_simple_TIMES']>10)
    return ['content'=>['#markup'=>
    '<p>Sunt permise 10 incercari pentru o zi pentru utilizatorii neinregistrati!</p>']];
    #$result = self::make_post_request(['action'=>'433']);
    #dsm($result);
    if($notAut){
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
      <div id="buttons">
       
      </div>
      <div  id="container"></div>
      <div id="dialog" style="height:300px;overflow: scroll;" title="Basic dialog"></div>
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
    }
    return new JsonResponse($result);
  }
}