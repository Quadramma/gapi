<?php
use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;


define('UPLOAD','uploads/');

Flight::map('getFileNames',function($path){
    $rta = array();
    //if (!file_exists($path)) {return $rta;}
    $handle = opendir($path);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $rta[] = $entry;
        }
    }
    closedir($handle);
    return $rta;
});

Flight::route('GET /upload/getImages(/@category)',function($category){
    $res = Flight::getDefaultRes();
    try{
        $res['rta']=Flight::getFileNames(UPLOAD.$category);
    }catch(Exception $e){
        $res['ok'] = false;
        $res['message'] = '[' . $e->getCode() . ']' . $e->getMessage();
        if($e->getCode()==2){
            $res['errorcode'] = QJERRORCODES::$API_INVALID_PATH;
        }
    }
    Flight::res($res);
});

Flight::route('POST /upload/image', function(){
    Flight::trycatch(function(){
        $post = json_decode(file_get_contents('php://input'),TRUE);
        $post['title'] = isset($post['title'])?$post['title']:$_POST['title'];
        if(!isset($post['category'])){
            $post['category'] = $_POST['category'];
            if(!isset($post['category'])){
                $post['category'] = 'sin_categoria';    
            }
        }
        $res = Flight::getDefaultRes();
        $res = Flight::uploadImage('file',$post['category'],$res);
        $res['message'] = ($res['ok'])?'Image Upload OK':'Upload Fail';

        if($res['ok']){
            //We save a image representation in db collection image
            Flight::crud_saveValidated('image',array(
                'title'=> $post['title'],
                'url'=>$res['rta'],
                'category'=>$post['category']
            ));
        }

        Flight::res($res);
    });
});

Flight::map("uploadImage",function($fileInputName,$uploadFolder,$res){
    $post = json_decode(file_get_contents('php://input'),TRUE);
    //
    $extraMessage = "";
    $width = 0;
    $height = 0;
    if (isset($_FILES[$fileInputName])) {
        $filename = $_FILES[$fileInputName]['tmp_name'];
        list($width, $height) = getimagesize($filename);
    }
    //
    $settings = array(
        "imageFileInputName"=>$fileInputName,
        "imageTempFileNamePrefix"=>"image_",
        "imageFileNameGeneration"=> function(){
            return md5(time().rand()) . "_gen";
        },
        "imageCanvasW" => $width,
        "imageCanvasY" => $height,
        "imageTempFolder" => 'uploads/' . $uploadFolder,
        "imageMaxSize"=> 2048,
        "imageQuality"=> 90,
        "imageX" => 0,
        "imageY" => 0,
        "imageW" => $width,
        "imageH" => $height
    );
    $r = ImageUploader::upload($settings);
    //Flight::res($r);
    $url = 'http://' . API_URL . '/' . $settings['imageTempFolder'] . '/' . $r['filename'];
    $res['errorcode'] = $r['errorcode'];
    if($res['errorcode'] != 0){
        $res['message'] = $r['message'];
    }
    $res['maxSize']= $settings['imageMaxSize'];
    $res['rta'] = $url;
    $res['ok']  = $r['ok'];
    $res['trace'] = $r['trace'];
    return $res;
});


class ImageUploader {
    public static function moveTempFileToTempDirectory($tempFileName,$settings,$response){
        $inputName = $settings["imageFileInputName"];
        move_uploaded_file($_FILES[$inputName]['tmp_name'], $tempFileName);
        $response["trace"][] = "file moved to " . $tempFileName;
        @chmod($tempFileName, 0777); //0644
        return $response;
    }
    public static function configureTempDirectory($tempDir,$response){
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777);
            $response["trace"][] = "directory " . $tempDir ." was successfully created.";
        } else {
            $response["trace"][] = "directory " . $tempDir ." founded.";
        }
        return $response;
    }
    public static function generateTempFileName($settings){
        return $settings["imageTempFileNamePrefix"] . $settings["imageFileNameGeneration"]();
    }
    public static function isFileAvailable($inputName) { 
        return (isset($_FILES[$inputName])==true);
    }
    public static function fileExistsAndHasSize($tempFileName){
        return file_exists($tempFileName) && filesize($tempFileName) > 0;
    }
    public static function upload($settings) {
        $response = array(
                "ok" => true,
                "filename"=>"",
                "message"=>"Everything work just fine",
                "errorcode"=>0,
                "trace" => array(),
                "path" => ""
            );
        //$response["file"] = $_FILES["fileUpload"];
        //VAL #1: existe en FILE
        if(!ImageUploader::isFileAvailable($settings["imageFileInputName"])){ 
            $response["message"] = "File " . $settings["imageFileInputName"] . " cannot be found in FILES";
            $response["ok"] = false;
            return $response;
        }
        $inputName = $settings["imageFileInputName"];

        //VAL #2: contiene error?
        if ($_FILES['file']['error']) {
            $response["message"] = "File " . $settings["imageFileInputName"] . " has errors : " . $_FILES['file']['error'];
            $response["ok"] = false;
            return $response;
        }
        //VAL #3: supera tamanio ?
        if($_FILES['file']['size'] > $settings["imageMaxSize"] * 1024){
            $response["message"] = "File " . $settings["imageFileInputName"] . " size exeded (" . $settings["imageMaxSize"] .") Kbs";
            $response["trace"][] = "File size: " . $_FILES['file']['size'] / 1024 . " Kbs";
            $response['errorcode'] = QJERRORCODES::$API_FILE_UPLOAD_EXEDED_SIZE_LIMIT;
            $response["ok"] = false;
            return $response;
        }
        //
        $tempDir = $settings["imageTempFolder"];
        $response['trace'][] = 'tempDir is '.$tempDir;
        //VAL #4: verifica si la carpeta temporal existe sino la crea
        $response = ImageUploader::configureTempDirectory($tempDir,$response);
        //
        //-Genera un filename 
        $tempFileNameOnly = ImageUploader::generateTempFileName($settings);
        //-Genera un nombre para el tempfile
        $tempFileName = $tempDir . "/" . $tempFileNameOnly;
        //
        $response = ImageUploader::moveTempFileToTempDirectory($tempFileName,$settings,$response);
        //
        //VAL #5: verifica si se movio correctamente el temp file
        if(!ImageUploader::fileExistsAndHasSize($tempFileName)){ 
            $response["message"] = "File " . $tempFileName . " cannot be found in temp directory or there was and error during moving operation";
            $response["ok"] = false;
            return $response;
        }
        //

        $aSize = getimagesize($tempFileName); // try to obtain image info
        if (!$aSize) {
            @unlink($tempFileName);
            $response["message"] = "File " . $tempFileName . " imposible to extract image info.";
            $response["ok"] = false;
            return $response;
        }

                //$response['trace'][] = $aSize; = ''

        // check for image type
        switch($aSize[2]) {
            case IMAGETYPE_JPEG:
                $sExt = '.jpg';
                // create a new image from file 
                $vImg = @imagecreatefromjpeg($tempFileName);
                break;
            case IMAGETYPE_PNG:
                $sExt = '.png';
                // create a new image from file 
                $vImg = @imagecreatefrompng($tempFileName);
                $response["trace"][] = 'Created virtual image (png)';
                break;
            default:
                @unlink($tempFileName);
                $response["message"] = "File " . $tempFileName . " extensions allowed (jpg,png)";
                $response["ok"] = false;
                return $response;
        }


        //
        $iWidth =  $settings["imageCanvasW"];
        $iHeight = $settings["imageCanvasY"];; // desired image result dimensions
        $iJpgQuality = $settings["imageQuality"];
        $response["trace"][] = "creating image canvas...";
        // create a new true color image
        $vDstImg = @imagecreatetruecolor( $iWidth, $iHeight );
        // copy and resize part of an image with resampling
        $response["trace"][] = "resampling image...";
        imagecopyresampled($vDstImg, $vImg, 0, 0
            , (int)$settings["imageX"], (int)$settings["imageY"], $iWidth, $iHeight
            , (int)$settings["imageW"], (int)$settings["imageH"]);



        // define a result image filename
        $sResultFileName = $tempFileName . $sExt;
        $response["trace"][] = "moving image...";
        // output image to file
        switch($aSize[2]) {
            case IMAGETYPE_JPEG:
                imagejpeg($vDstImg, $sResultFileName, $iJpgQuality);
                break;
            case IMAGETYPE_PNG:
                imagepng($vDstImg, $sResultFileName);
                break;
        }

        
        @unlink($tempFileName);
        //
        $response["filename"] = $tempFileNameOnly . $sExt;
        $response["trace"][] = "Proccess end :)";
        return $response;
    }
}


?>