<?php
namespace Core\Helpers;
class File {

    /**
     * File post array
     *
     * @var array
     */
    private $files_post = array();

    /**
     * Allowed mime types
     *
     * @var array
     */
    private $allowed_types = array();

    /**
     * Max size in megabytes
     *
     * @var float
     */
    private $max_size = 1.0;

    /**
     * File size in bytes
     *
     * @var float
     */
    private $size_in_bytes;

    /**
     * File size in MB
     *
     * @var float
     */
    private $size_in_mb;

    /**
     * Destination directory
     *
     * @var string
     */
    private $destination = DOCROOT;

    /**
     * Upload status
     *
     * @var bool
     */
    private $status = false;

    /**
     * Force create dir in case of it doesn't exists
     *
     * @var bool
     */
    private $force_create_dir = false;

    /**
     * Personalized filename
     *
     * @var string
     */
    private $filename = '';

    /**
     * Mime types
     *
     * @var array
     */
    private $mimes = array();

    /**
     * Image width
     *
     * @var string
     */
    private $width = 'auto';

    /**
     * Image height
     *
     * @var string
     */
    private $height = 'auto';

    /**
     * Resize image
     *
     * @var bool
     */
    private $resize = false;

    /**
     * Errors thrown
     *
     * @var array
     */
    private $errors = array();

    public function __construct($files_post) {
        if (is_array($files_post)) {
            $this->files_post = $files_post;

            $this->size_in_bytes = $this->get_file_size();
            $this->size_in_mb = $this->bytes_to_mb($this->size_in_bytes);
            $this->filename = $this->files_post['name'];

            global $CONFIG;
            $this->mimes = $CONFIG['mimes'];
        }
    }

    public function get_filename() {
        return $this->filename;
    }

    protected function array_rsearch($needle, $haystack) {
        foreach ($haystack as $key => $value) {
            $current_key = $key;
            if ($needle === $value OR ( is_array($value) && array_rsearch($needle, $value) !== false)) {
                return $current_key;
            }
        }
        return false;
    }

    protected function get_file_size() {
        return filesize($this->files_post['tmp_name']);
    }

    protected function bytes_to_mb($bytes) {
        return round(($bytes / 1048576), 2);
    }

    protected function check_mime_type() {
        if (!count($this->allowed_types)) {
            return true;
        }
        if ($ext = $this->array_rsearch($this->files_post['type'], $this->mimes)) {
            if (in_array($ext, $this->allowed_types)) {
                return true;
            } else {
                $this->errors[] = 'Format not allowed';
            }
        } else {
            $this->errors[] = 'Format not allowed.';
        }
        return false;
    }

    protected function get_format() {
        return $this->array_rsearch($this->files_post['type'], $this->mimes);
    }

    public function resize($width, $height) {
        $this->width = $width;
        $this->height = $height;
        $this->resize = true;
    }

    public function set_force_create_dir($op) {
        $this->force_create_dir = $op;
    }

    public function set_max_size($size) {
        $this->max_size = $size;
    }

    public function set_allowed_types($type_list) {
        $this->allowed_types = $type_list;
    }

    public function set_destination($dir) {
        $this->destination .= DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($this->destination)) {
            if (!$this->force_create_dir) {
                $this->errors[] = 'Destination dir not found.';
                return false;
            } else {
                if (!mkdir($this->destination, 0777)) {
                    $this->errors[] = 'Could not create destination folder.';
                    return false;
                }
            }
        }
        return true;
    }

    public function set_filename($filename) {
        $ext = $this->get_format();
        if (!strstr($filename, '.'.$ext)) {
            $this->filename = $filename . '.' . $ext;
        }
        else {
            $this->filename = $filename;
        }
    }

    public function get_errors() {
        return $this->errors;
    }

    public function dump() {
        $this->upload();
        var_dump($this->errors);
    }

    public function upload() {
        if (!$this->check_mime_type()) {
            // _dump('mime error');
            return false;
        }

        if (!$this->resize) {
            if (move_uploaded_file($this->files_post['tmp_name'], $this->destination . DIRECTORY_SEPARATOR . $this->filename)) {
                $filename = $this->destination . DIRECTORY_SEPARATOR . $this->filename;
                $filename = str_replace(DOCROOT, '', $filename);
                return $filename;
            }
            else {
                return false;
            }
        }
        // resize image
        else {
            // resize and save picture
            $ext = $this->get_format();

            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $src = imagecreatefromjpeg($this->files_post['tmp_name']);
                    break;
                case 'png':
                    if (!function_exists('imagecreatefrompng')) {
                        $this->errors[] = 'Could not convert png file.';
                        return false;
                    }
                    $src = imagecreatefrompng($this->files_post['tmp_name']);
                    break;
                case 'gif':
                    $src = imagecreatefromgif($this->files_post['tmp_name']);
                    break;
                default:
                    return false;
                    break;
            }

            list($width, $height) = getimagesize($this->files_post['tmp_name']);

            if ($this->width != 'auto') {
                $new_width = $this->width;
                if ($this->height != 'auto') {
                    $new_height = $this->height;
                } else {
                    // proportional image
                    $new_height = ($height / $width) * $new_width;
                    // square image
                    //$new_height = $this->width;
                }
            } else {
                $new_width = $width;
                $new_height = $height;
            }

            // calculating the part of the image to use for thumbnail
            if ($width > $height) {
                $y = 0;
                $x = ($width - $height) / 2;
                $smallestSide = $height;
            } else {
                $x = 0;
                $y = ($height - $width) / 2;
                $smallestSide = $width;
            }

            $tmp = imagecreatetruecolor($new_width, $new_height);

            // fix transparency for png/gif images
            switch ($ext) {
                case 'gif':
                case 'png':
                    $background = imagecolorallocate($tmp , 255, 255, 255);
                    imagefill($tmp,0,0,$background);
                    break;
                default:
                    break;
            }

            $this->filename = $this->destination . DIRECTORY_SEPARATOR . $this->filename;

            // proportional image
            imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            // squared
            if (imagejpeg($tmp, $this->filename, 100)) {
                return $this->filename;
            }
            else {
                $this->errors[] = 'Could not save file';
                _dump($this->filename);
                _dump('Could not save file');
                return false;
            }
        }
    }

}

?>