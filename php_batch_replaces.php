#!/usr/bin/php
<?php
class BatchEditing {
    private $__args;
    private $__ = array();
    private $__mappings = array(
        '-d' => 'directory', '-s' => 'search', '-r' => 'replace',
        '--exclude' => 'exclude', '--ext' => 'ext', '-v' => 'verbose'
    );
    private $__requiredArgs = array('-d', '-s', '-r');
    private $__errorMessages = array();
    
    public function __construct() {
        $argv = $_SERVER['argv'];
        unset($argv[0]);
        $this->__argValues = $argv;
        $this->__argKeys = array_flip($argv);
        
        foreach ( $this->__argValues as $arg ) {
            if ( substr($arg, 0, 1) == '-'  ) {
                $this->__extractArgs($arg);
            }
        }
        
        try {
            $this->__verifyArgs();
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
    
    private function __extractArgs($arg) {
        if ( array_key_exists($arg, $this->__argKeys) && 
             isset($this->__argValues[$this->__argKeys[$arg]+1]) ) {
            $this->__[$this->__mappings[$arg]] = $this->__argValues[$this->__argKeys[$arg]+1];
        } else {
            // args without values
            if ( $arg == '-v' ) $this->__[$this->__mappings[$arg]] = $this->__argValues[$this->__argKeys[$arg]];
        }
    }
    
    private function __verifyArgs() {
        $verify = FALSE;
        
        if ( !empty($this->__argValues) && !empty($this->__) ) $verify = TRUE;
        
        $required_exists = array_intersect($this->__requiredArgs, $this->__argValues);
        if ( $required_exists == $this->__requiredArgs ) {
            $verify = ($verify && TRUE);
        } else {
            $verify = FALSE;
        }
        
        if ( $verify ) {
            return TRUE;
        } else {
            throw new Exception(
                "Usage: -d [directory] -s [search keyword] -r [replacement]\n" .
                "       --ext [file extenstion] --exclude [file to exclude] -v\n"
            );
        }
    }
    
    private function __fileCriteria($filename) {
        $file_match = TRUE;
        if ( $filename == '.' || $filename == '..' ) {
            $file_match = FALSE;
        }
        
        if ( $file_match && isset($this->__['ext']) && 
             substr($filename,strlen($this->__['ext'])*-1) != $this->__['ext'] ) {
            $file_match = FALSE;
        }
        
        if ( $file_match && isset($this->__['exclude']) ) {
            $excludes = explode(',', $this->__['exclude']);
            foreach ($excludes as $exclude) {
                if ( $filename == $exclude) {
                    $file_match = FALSE;
                    break;
                }
            }
        }
        
        return $file_match;
    }
    
    public function replace() {
        $total_files = 0;
        if ( isset($this->__['directory']) && $dir = opendir($this->__['directory']) ) {
            while ( FALSE !== ($file = readdir($dir)) ) {
                if ( $this->__fileCriteria($file) ) {
                    $filepath = $this->__['directory'] . DIRECTORY_SEPARATOR . $file;
                    $current_file = file($filepath);
                    
                    if ( isset($this->__['verbose']) ) {
                        echo 'Replace ' . $this->__['search'] . ' with ' . $this->__['replace'] .
                             ' in ' . $filepath . "\n";
                    }
                    
                    // replace the file
                    foreach ($current_file as $line_num => $line) {
                        $current_file[$line_num] = str_replace($this->__['search'], $this->__['replace'], $line);
                    }
                    
                    // write the new overridden file
                    if ( !is_writable($filepath) ) {
                        $this->__errorMessages[$filepath] = 'Unable to write';
                        
                        if ( isset($this->__['verbose']) ) {
                            echo 'Unable replace ' . $this->__['search'] . ' with ' . $this->__['replace'] .
                                 ' in ' . $filepath . "\n";
                        }
                    } else {
                        $handle = fopen($filepath, 'w');
                        foreach ( $current_file as $line ) {
                            fwrite($handle, $line);
                        }
                        fclose($handle);
                        
                        if ( isset($this->__['verbose']) ) {
                            echo 'Successfully replace ' . $this->__['search'] . ' with ' . $this->__['replace'] .
                                 ' in ' . $filepath . "\n";
                        }
                    }
                    
                    $total_files++;
                }
            }
            
            closedir($dir);
        }
        
        if ( isset($this->__['verbose']) ) {
            echo "\n";
            echo 'Total files replaced: ' . ($total_files - count($this->__errorMessages)) . "\n";
            if ( !empty($this->__errorMessages) ) {
                echo 'Total files failed to replace: ' . count($this->__errorMessages) . "\n";
            }
            echo "\n";
        }
    }
}
$b = new BatchEditing;
$b->replace();
?>
