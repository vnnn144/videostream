<?php

    Class VideoStream
    {
        private $path, $buffer, $modify, $size, $type, $start, $end;
        private static $instance;        
        
        /**
         * Constructor
         */
        public function __construct() 
        {
            if(is_object(self::$instance))
            {
                return self::$instance;
            }

            $this->buffer = 102400;
            $this->start  = -1;
            $this->end    = -1;
            $this->size   = 0;

            @session_start();
            self::$instance = $this;
        }
        
        /**
         * Create video Session
         */
        public function create($video, $time = 500)
        {
            $_SESSION['_video_path'] = $video;
            
            if(!isset($_SESSION['_video_token']) || $_SESSION['_video_time'] + $time < time())
            {
                $_SESSION['_video_token'] = ip2long($_SERVER['REMOTE_ADDR']);
                $_SESSION['_video_time'] = time();
            }
            
            $name = substr(md5(basename($_SESSION['_video_path'])), 0, 8);
            $type = explode('.', $_SESSION['_video_path'])[1];

            echo 'videos/' . $this->token() . '/' . strtoupper($name) . '.'. $type;
        }  

        /**
         * Get video token
         */
        public function token()
        {
            if(!isset($_SESSION['_video_token']))
            {
                return null;
            }

            return md5($_SESSION['_video_token'] + $_SESSION['_video_time']);
        }

        /**
         * Check video token
         */
        public function checkToken()
        {
            if(!isset($_GET['video_key']) || $_GET['video_key'] !== $this->token())
            {
                return false;
            }

            return true;
        }

        /**
         * Open stream
         */
        private function open()
        {
            if(!($this->stream = fopen($this->path, 'rb')))
            {
                die('Could not open stream for reading');
            }

            $this->type = filetype($this->path);
            $this->size = filesize($this->path);
            $this->modify = filemtime($this->path);
        }
         
        /**
         * Set proper header to serve the video content
         */
        private function setHeader()
        {
            ob_get_clean();
            header("Content-Type: " . $this->type);
            header("Cache-Control: max-age=2592000, public");
            header("Expires: " . gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
            header("Last-Modified: " . gmdate('D, d M Y H:i:s', $this->modify) . ' GMT' );
            $this->start = 0;
            $this->end   = $this->size - 1;
            header("Accept-Ranges: 0-" . $this->end);
             
            if(isset($_SERVER['HTTP_RANGE']))
            {
                $c_start = $this->start;
                $c_end = $this->end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                
                if(strpos($range, ',') !== false)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $this->start-$this->end/$this->size");
                    return;
                }

                if($range == '-')
                {
                    $c_start = $this->size - substr($range, 1);
                }
                else
                {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
                }

                $c_end = ($c_end > $this->end) ? $this->end : $c_end;

                if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $this->start-$this->end/$this->size");
                    return;
                }

                $this->start = $c_start;
                $this->end = $c_end;
                $length = $this->end - $this->start + 1;
                fseek($this->stream, $this->start);
                header('HTTP/1.1 206 Partial Content');
                header("Content-Length: ".$length);
                header("Content-Range: bytes $this->start-$this->end/".$this->size);
            }
            else
            {
                header("Content-Length: ".$this->size);
            }
        }
        
        /**
         * close curretly opened stream
         */
        private function close()
        {
            fclose($this->stream);
        }
         
        /**
         * perform the streaming of calculated range
         */
        private function stream()
        {
            $i = $this->start;
            set_time_limit(0);
            while(!feof($this->stream) && $i <= $this->end) 
            {
                $bytesToRead = $this->buffer;
                if(($i + $bytesToRead) > $this->end)
                {
                    $bytesToRead = $this->end - $i + 1;
                }
                echo fread($this->stream, $bytesToRead);
                flush();
                $i += $bytesToRead;
            }
        }
    	
        /**
         * Start streaming video content
         */
        public function start($path)
        {
            $this->path = $path;
            $this->open();
            $this->setHeader();
            $this->stream();
            $this->close();
        }
    }

    $stream = new VideoStream;

    if($stream->checkToken())
    {
        $stream->start($_SESSION['_video_path']);
    }

?>