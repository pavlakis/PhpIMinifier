<?php
/**
 * @copyright Copyright (C) 2011 Antonios Pavlakis
 * @license MIT
 * @category PHP
 * @package Deployment
 * @subpackage PhpIMinifier
 */
/**
 *
 * Iterate through all directories, add a version and minify JS or CSS files.
 * 
 * 
 * The PHP Iterator Minifier takes the following 5 steps:
 * 
 *      1. Iterate through all directories of chosen type
 *      2. Store each directory in a directory array
 *      3. Store each chosen type file in an array
 *      4. Create all chosen type directories in the new location
 *      5. Minify each chosen file type and copy it across to its new location
 * 
 * @category PHP
 * @package Deployment
 * @subpackage PhpIMinifier
 * @author Antonios Pavlakis <antonis@pavlakis.info>
 * @version 0.01 alpha 
 * 
 */
class PhpIMinifier {

    private $_dir;
    
    // This is just for testing!! Change to read-only for deployment
    private $_folderPermissions = 0777;
    

    // the default location on ubuntu installation
    private $_yuiCompressorExecutable   = '/usr/share/yui-compressor/yui-compressor.jar';
    private $_yuiJavaCommand            = 'java -jar ';
    
    private $_charset = 'utf-8';
    
    private $_fromRootLocation;
    private $_toRootLocation;
    private $_version = '';

    private $_dirArray = array();
    private $_OriginalFileArray = array();
    private $_NewFileArray = array();

    // js or css
    private $_typeOfFile;

    private $_renameFromCss = '/public/css/';
    private $_renameFromJs = '/public/js/custom/';

    private $_errorLog = array();
    private $_showOutput = false;
    private $_outputActivityString = '';

    public function __construct($directory, $version, $fileType){
        // pass NULL for testing
        $this->setDirectory($directory);
        $this->setVersion($version);
        $this->setFileType($fileType);
    }
    
    public function run(){
        // expected that all the setup has been done through the constructor
        
        // map files and directories
            // _mapFilesAndDirectories()
            $this->_mapFilesAndDirectories();
        // rename directories
            // _renameDirectories
            $this->_renameDirectories();
            
        // rename folder names with version where new files will be created
            $this->_renameFilesFolderVersion();
            
        // create new directories
            // createNewDirectories
            $this->_createNewDirectories();            
            
            
        // minify files and write them to their new location
            $this->_minify();
        
        
        
    }
    
    
    /**
     * Set output activity.
     * If true, it will echo all activity when
     * creating/copying/minifying folders and files.
     * 
     * @param Boolean $show 
     */
    public function setOutputActivity($show = false){
        $this->_showOutput = $show;
    }
    
    /**
     * Log all errors in an array
     * 
     * @param type $err
     * @param type $func 
     */
    public function logError($err, $func = null){
        if($func !== NULL){
            $this->_errorLog[$func] = $err;
        }else{
            $this->_errorLog[] = $err;
        }
        
    }
    
    /**
     * Format errors in a readable format to be shown 
     * through CLI
     * 
     * @return String
     */
    private function _formatErrors(){
        // add some stars and new lines
        $errDisplay = '*** ERRORS ***' . PHP_EOL;
        
        if(empty($this->_errorLog)){
            return '** No errors were logged **';
        }
        
        foreach($this->_errorLog as $func => $value){
            if(is_string($func) && strlen($func) > 5 ){
                $errDisplay .= 'Method: ' . $func . PHP_EOL;
            }
            
            $errDisplay .= $value . PHP_EOL;
        }
        
        return $errDisplay;
        
    }
    
    /**
     * Get a var_dump of the errors array.
     */
    public function getErrorDump(){
        var_dump($this->_errorLog);
    }
    
    
    /**
     * Output string if showOutput flag is set.
     * Otherwise pass it to a string.
     * 
     * @param String $output 
     */
    private function _showOutput($output){
        if($this->_showOutput){
            echo $output . PHP_EOL;
        }else{
            $this->_outputActivityString .= $output . PHP_EOL;
        }
    }
    
    /**
     * Get all output activity, if showOutput flag was not set
     * 
     * @return String
     */
    public function getAllOutputActivity(){
        return $this->_outputActivityString;
    }
    
    /**
     * Get all errors formatted in a string.
     * 
     * @return String 
     */
    public function getErrors(){
        return $this->_formatErrors();
    }
    
    /**
     *
     * @param type $permissions 
     */
    public function setFolderPermissions($permissions){
        $this->_folderPermissions = $permissions;
    }
    
    
    /**
     * Set the file location of the YUI compressor.
     * This is usually a -jar file.
     * 
     * @param String $fileLocation 
     */
    public function setYuiCompressorExec($fileLocation){
        if($fileLocation){
            $this->_yuiCompressorExecutable = $fileLocation;
        }        
    }
    
    
    public function setCharset($charset){
        $this->_charset = $charset;
    }
    
    /**
     * Return a charset option if charset has been set.
     * Else return an empty string.
     * 
     * @return String 
     */
    public function getCharsetOption(){
        $option = '';
        if($this->_charset !== ''){
            $option = '--charset ' . $this->_charset;
        }
        
        return $option;
    }

    /**
     *
     * @param type $directory 
     */
    public function setDirectory($directory = NULL ){
        // just for testing
        if($directory === NULL){
            $this->_dir = __DIR__;
        }else{
            $this->_dir = $directory;
        }
    }
    
    /**
     *
     * @param type $fileType 
     */
    public function setFileType($fileType){
        $this->_typeOfFile = trim(strtolower($fileType));    
    }


    /**
     *
     * @param type $version 
     */
    public function setVersion($version){
        $this->_version = $version;
    }
    
    
    
/**
     * Change the java command if not java -jar to 
     * which ever is required.
     * 
     * @param String $commandStr 
     */
    public function setYuiJavaCommand($javaCommand){
        if($javaCommand){
            $this->_yuiJavaCommand = $javaCommand;
        }
    }
    
    
    /**
     * Get the first part of the YUI command
     * Default: java -jar
     * 
     * @return String 
     */
    public function getYuiJavaCommand(){
        return $this->_yuiJavaCommand;
    }
    
    
    /**
     * Get the location of the YUI Executable file.
     * 
     * @return String 
     */
    public function getYuiExecutable(){
        return $this->_yuiCompressorExecutable;
    }
        
    
    /**
     * Create the directory structure under
     * the new version
     */
    private function _createNewDirectories(){
        
        // need to first create the **version** directory
        $topLevel = $this->_dir .  $this->_version . '/';
        

        try{
            if(!is_dir($topLevel)){
                mkdir($topLevel);  
                $this->_showOutput('Creating directory: ' . $topLevel);
            }
        }catch(Exception $e){
         $this->logError($e, '_createNewDirectories');   
        }
        

        // iterate through the directories array and create all folders
        if(!empty($this->_dirArray)){
            foreach($this->_dirArray as $folder){
                // create folder
                try{
                    if(!is_dir($folder)){
                        mkdir($folder, $this->_folderPermissions);
                        $this->_showOutput('Creating directory: ' . $folder);
                    }
                    
                }catch(Exception $e){
                 $this->logError($e, '_createNewDirectories');   
                }
                
            }
        }
            
    }
    
    
    /**
     * Rename all directories with their new 'version'
     * name.
     * 
     */
    private function _renameDirectories(){

        $search = $this->_getPathToChangeByFileType();
        $replace = $search . $this->_version . '/';
        
        $newArr = array();
        // save in the original array
        foreach($this->_dirArray as $dir){
            // rename directories to the new version 
            $newArr[] = str_replace($search, $replace, $dir);
        }
        
        $this->_dirArray = $newArr;
        
    }
    
    /**
     * Rename all paths for each file - to the new version.
     * 
     */
    private function _renameFilesFolderVersion(){
        
        $search = $this->_getPathToChangeByFileType();
        
        $replace = $search . $this->_version . '/';
        
        // save in the original array
        foreach($this->_OriginalFileArray as $dir){
            // rename directories to the new version 
            $this->_NewFileArray[] = str_replace($search, $replace, $dir);
        }        
    }
    
    
    /**
     * Determine the path that needs to be
     * 'versioned' based on the file type.
     * 
     * @return string $path 
     */
    private function _getPathToChangeByFileType(){
        if($this->_typeOfFile == 'js'){
            $path = $this->_renameFromJs;
        }else{            
            $path = $this->_renameFromCss;
        }
        
        
        return $path;
    }
    
    
    /**
     * Iterate through all directories store files and directories
     * with their full path to two arrays
     * 
     * This has been adapted from a script found on php.net:
     * @link http://php.net/manual/en/class.recursivedirectoryiterator.php
     * By: joelhy 23-Feb-2011 03:39
     * 
     */
    private function _mapFilesAndDirectories() {
        
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_dir),
                                              RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $path) {
      if ($path->isDir()) {
          // add to Directory array
          
          // don't pass any svn folders
          if(!strstr($path->__toString(), '.svn')){
               $this->_dirArray[] = $path->__toString();
          }
         
      } else {
          // add to File array
          $this->_OriginalFileArray[] = $path->__toString();
      }
    }
    
    
    // this is needed because of the order by which the directories
    // are mapped, children are higher than parents, which means that
    // children are never created
    $this->_dirArray = array_reverse($this->_dirArray);
    

    }    
    
    
    /**
     * Minify all files.
     * This is invoking java and yui. Need to make sure
     * that it has been configured properly.
     */
    private function _minify(){
        // call the minify script, passing the right parameters
        /*
        * java -jar yuicompressor-x.y.z.jar myfile.js -o myfile-min.js --charset utf-8
        */
        
        $json = 'json';
        $counter = 0;
        foreach($this->_OriginalFileArray as $key => $originalFile){
            
            
            // make sure that only css or js files are converted
            $fileOfInterest = substr($originalFile, -strlen($this->_typeOfFile));

            if($fileOfInterest == $this->_typeOfFile ){

                // format command
                $command = "{$this->getYuiJavaCommand()} {$this->getYuiExecutable()} {$originalFile} -o {$this->_NewFileArray[$key]} {$this->getCharsetOption()}";
                // execute command
                // log errors or just any output from the execution
                $output = '';

                // use 2>&1 to make sure we get STDIN, STDOUT and STDERR
                exec($command . ' 2>&1', $output);
                $this->_showOutput('Minified file: ' . $this->_NewFileArray[$key]);
                
                if(is_array($output) && !empty($output)){
                   $this->logError(implode(' ', $output) . ' - For file: ' . $this->_NewFileArray[$key], '_minify'); 
                }
                
                $counter++;       
            
                
            }else if( 'json' === substr($originalFile, -strlen($json) ) ){
                // if a json file, just copy it accross
                copy($originalFile, $this->_NewFileArray[$key]);
                $this->_showOutput('Copied file: ' . $this->_NewFileArray[$key]);
            }
          
         

        }
    
        $this->_showOutput('Have minified a total of: ' . $counter . ' ' . $this->_typeOfFile . ' files.');
    }
    
    
    
    /**
     * Get all output and all errors.
     * 
     * @return String
     */
    public function __toString() {
        return $this->getAllOutputActivity() . $this->getErrors();
    }
    
    
        
    
    

}



